/*
Game Type: Bridge Runner
Description: Collect Objects with a constant moving sprite. Mind the gaps.
*/
class GameJS extends Phaser.Scene {
  
	constructor(){
		super({key:'GameJS'});
	}

	preload() {
		for( var imageID in gameData.images ) {		
			var asset = hgGet( 'images.' + imageID );                       
            if( asset != null ) {							
				this.load.image( imageID, asset.file );		
                // console.log("added " , imageID , " at " , asset);
			} else {
				// Shouldn't happen but we should display a whoops. 
				console.log("image preload whoops " + imageID );
			}
        }
        this.collectibles = hgGet( 'options.collectibles' );
        for( var i = 0; i < this.collectibles.length; i++ ) {
	    	if( this.collectibles[i].image ) {
	    		this.load.image( 'collectible-' + i, this.collectibles[i].image.file );
	    	}
	    }
        /* Required for all games. */
        this.load.on('complete', hgStart )
	}

	create() {
		this.physics.world.fixedStep = false;
		/* Data from our json file */
		this.levelDesign = hgGet('options.levelDesign');
		/** Sprite **/
		/*
		- Defaults are set here.
		*/
		this.player;
		// How fast the player runs
		this.player_speed = 700;
		// How fast the player goes up when they jump
		this.initial_jump_velocity = 650;
		// How fast the player falls down when they are done jumping
		this.initial_fall_velocity = 250;
		/** End Sprite **/
		this.playerStartX = hgGet('options.levels.' + this.levelDesign + '.player.startX');   
		this.playerStartY = hgGet('options.levels.' + this.levelDesign + '.player.startY');

		this.arrows;
		this.jumpButton;
		this.gameOver = false;
		/**
		 * Gap between floor pieces
		 */
		this.gap = 350; 

		/* TODO see if needed */
		this.collected = 0;
		this.wrapping = true;
		this.stopped = false;

		/**
		 * Camera settings.
		 */
		this.cameras.main.setViewport(0, 0, 1920, 1080);
		this.cameras.main.setBounds(0,0, 11000, 1080);
		this.physics.world.setBounds(0,0,11000, 1080);

     
		/** Background **/
		/* 
		The image is tagged as 'background' and this loads it in.
		*/
		this.background = this.add.tileSprite(0, 0, 11000, 1500, "background")
		.setOrigin(0)
		/** End Base Layer **/

		/** Collectibles **/
		/*
		Each collectible shows up in two spots.
		First, they are added to collectibleList, which is what displays in the top right.
		Later they will be individually placed in the level for the player to collect.
		*/
		this.collectibleList = this.add.group(); 
		/* 
		This code runs whenever custom images are used.
		It detects how many there are (up to 4) and adds them to the display.
		*/
		for(var i = 0; i < this.collectibles.length; i++){
			this.collectibles[i].collected = false;
			var offset = this.collectibles.length - i - 1;
			var listX = this.game.config.width - ( 100 * offset ) - ( 10 * offset );
			this.collectibleList.create( listX, 10, 'collectible-' + i )
			/* So your progress doesn't also scroll */
			.setScrollFactor(0,0)
			.setOrigin(1,0)
			.setDisplaySize(100,100)
			.setAlpha(.2);
		}
		/** End Collectibles **/
		
		/** Sprite **/
		this.player = this.physics.add.sprite(  this.playerStartX, this.playerStartY, 'run1')
		.setDisplaySize(225,225)
		.setOrigin(0.5,1)
		.setDepth(1);
		this.player.inWorld = true;
		this.anims.create({
            key: 'run',
            frames:[
                {key: 'run1'},
                {key: 'run2'}
            ],
            frameRate: 4,
            repeat: -1
	    });
	    this.anims.create({
            key: 'jump',
            frames:[
                {key: 'jump'}
            ],
            frameRate: 1,
            repeat: -1
	    });
	    this.player.anims.play('run', true);
	    this.player.body.setSize().setOffset(0,-5)
	  	/** End Sprite **/
		this.placeGround();
		
		this.arrows = this.input.keyboard.createCursorKeys();

		this.jumpButton = false;
		this.input.keyboard.on('keydown_SPACE', this.jump, this);
		this.input.keyboard.on('keyup_SPACE', this.fall, this);
		this.input.on('pointerdown', this.jump, this );
		this.input.on('pointerup', this.fall, this );
		/** Sprite **/
		/*
		The game's camera is set to the sprite so it can't run off-screen.
		*/
		this.cameras.main.startFollow( this.player, true, 0.05, 0.05, 0, 5 );
		/** End Sprite **/
		
		this.placeBlocks();
		this.placeCollectibles();
		this.scene.pause("GameJS");
	}

	jump() {
		if( this.player.body.onFloor() ) {
			this.player.body.velocity.y = -this.initial_jump_velocity;
		}
		this.jumpButton = true;
	}

	fall(){
		if( ! this.player.body.onFloor() ) {
			this.player.body.velocity.y = this.initial_fall_velocity;
		}
		this.jumpButton = false;
	}
	/** Collectibles **/
	/*
	Using the same images as the progress group,
	each collectible has a specific position based on other elements.
	*/
	placeCollectibles(){
		/* Backwards from the end. Not all will be used if length exceeds collectibles. */
		var collectibleCoordinates = hgGet('options.levels.' + this.levelDesign + '.collectibles');   


	    this.collectibleGroup = this.add.group();
        for( var i = 0; i < this.collectibles.length; i++ ){
            //this needs to be changed depend
            this.collectibleGroup.create( collectibleCoordinates[ i ].x, this.game.canvas.height - collectibleCoordinates[ i ].y, 'collectible-' + i )
            .setScale(.5,.5)
            .setOrigin(.5,.5);
            this.physics.add.existing( this.collectibleGroup.children.entries[ i ] );
            this.collectibleGroup.children.entries[ i ].body.setAllowGravity(false);
            this.collectibleGroup.children.entries[ i ].body.onWorldBounds = true;
            this.collectibleGroup.children.entries[ i ].name = i;
        }
        this.physics.add.overlap(this.player, this.collectibleGroup, this.collect, null, this );
	}
	/** End Collectibles **/
	/** Platforms **/
	placeGround(){
		/* This can be altered with more level designs. */
		/* Use med - 548 wide */
		var groundCoordinates = hgGet('options.levels.' + this.levelDesign + '.ground');
		/* A max of 1500 is usually as wide as the gap can be. */
		this.groundBlocks = this.physics.add.staticGroup();
		for( var i = 0; i < groundCoordinates.length; i++ ) {
			this.groundBlocks.create( groundCoordinates[ i ].x, this.game.canvas.height - groundCoordinates[ i ].y, 'ground')
			.setOrigin(0,1)
			.setScale(1.5)
			.setDepth(2)
			.setDisplaySize(550,210)
			.refreshBody()
		}
		this.physics.add.collider( this.player, this.groundBlocks );
	}
	/** Obstacle **/
	placeBlocks(){
		/* 210 is the ground level, blocks are 150 tall. */
		var obstacleCoordinates = hgGet('options.levels.' + this.levelDesign + '.obstacles');   
		this.obstacleGroup = this.physics.add.staticGroup();  
		for( var i = 0; i < obstacleCoordinates.length; i++ ) {
			this.obstacleGroup.create( obstacleCoordinates[ i ].x, this.game.canvas.height - obstacleCoordinates[ i ].y, 'block')
			.setOrigin(0.5,1)
			.setDisplaySize(150,150)
			.refreshBody()
		}
		this.physics.add.collider( this.player, this.obstacleGroup );	
	}
	/** End Obstacle **/
	/** Collectibles **/
	/*
	When the player touches a collectible, this triggers.
	The score is increased, the collectible disappears from the main level, and the top right version becomes fully visible.
	*/
	collect( player, collectible ){ 
		this.collected++;
		this.collectibleList.children.entries[ collectible.name ].setAlpha(1);
		this.collectibles[ collectible.name ].collected = true;
		collectible.destroy();
		if( this.collected >= this.collectibles.length ) {
			/* End game. */
			this.gameOver = true;
			var message = {
				event: 'win'
			}
			var text = this.parseCollectibleHTML();
			if( text.length > 0 ) {
				message.text = text;
			}
			hgSendMessage( message );
		}
	}

	parseCollectibleHTML() {
		var collectedItems = '';
    	for( var i = 0; i < this.collectibles.length; i++ ) {
    		if( this.collectibles[i].collected ) {
    			if(typeof this.collectibles[i].title !== 'undefined' 
    			&& this.collectibles[i].title.length > 0) {
    				collectedItems += '<h4>'+this.collectibles[i].title+'</h4>';
    			}
    			if( typeof this.collectibles[i].description !== 'undefined' 
    			&& this.collectibles[i].description.length > 0) {
    				collectedItems += this.collectibles[i].description
    			}
        	}
    	}
    	if( collectedItems.length > 0 ) {
    		collectedItems = '<br /><p>Here are details about the items you found:</p>' + collectedItems
    	}
    	return collectedItems;
	}
	/** End Collectibles **/
	/** Sprite **/
	/**
	 * Update runs constantly during gameplay
	 */
	update() {
		if( this.player.anims.getCurrentKey() == 'jump' && this.player.body.onFloor() ) {
			this.player.anims.play('run');
		} else if( this.player.anims.getCurrentKey() == 'run' && ! this.player.body.onFloor() ) {
			this.player.anims.play('jump');
		}
		if( this.jumpButton ) {
			this.jump();
		} 
		/* If player falls outside bounds */
		if( ! Phaser.Geom.Rectangle.Overlaps(this.physics.world.bounds, this.player.getBounds())) {
			if( this.player.body.position.y > this.game.canvas.height - this.player.body.height ) {
				this.playerFail();
			} else if( this.player.body.position.y < 0 ) {
				this.player.body.velocity.y = 0;
			} else if( this.player.body.position.x >= 10000 ) {
				this.playerReset();
			}
		}
		if(this.player.inWorld){  
			/**
			 * The speed that the player runs through the level, 
			 * set previously.
			 */
			this.player.body.velocity.x = this.player_speed;
		}
	}

	playerReset(){
		/*
		- Reset the player's position.
		*/
		this.player.inWorld = false;
		this.player.body.velocity.x = 0;
		this.player.body.velocity.y = 0;
		this.player.body.position.x = this.playerStartX;
		this.player.body.position.y = this.playerStartY;
		this.time.addEvent({
			delay: 1000, 
			callback: function(){
		   		this.player.inWorld = true;
			},
			callbackScope: this
		});
	}

	playerFail() {
		if( this.player.inWorld && !this.gameOver ) {
			this.gameOver = true;
			this.player.inWorld = false;
			var message = { event: 'lose' }
			var text = this.parseCollectibleHTML();
			if( text.length > 0 ) {
				message.text = text;
			}
			hgSendMessage( message );
		}
	}
	/** End Sprite **/
}