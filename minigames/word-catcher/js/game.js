


class GameJS extends Phaser.Scene {
  
	constructor(){
		super({key:'GameJS'});
	}
  
    preload(){  
        this.gameAssets = {};              
        for( var imageID in gameData.images ) {		
			var asset = hgGet( 'images.' + imageID );                       
            if( asset != null ) {			
                this.gameAssets[ 'images.' + imageID ] = asset				
				this.load.image( imageID, asset.file );		
			} else {
				// Shouldn't happen but we should display a whoops. 
				console.log("image preload whoops " + imageID );
			}
        }
        /* Required for all games. */
        this.load.on('complete', hgStart )
    }

    create() {
        this.physics.world.fixedStep = false;
    	this.wordData = hgGet( 'options.keywords' );
    	this.winScore = hgGet( 'options.winScore' );

        /* 1.0 was 4.67 */
    	this.gameSpeed = 5.795;
    	this.rush = false;
    	this.cameras.main.setViewport(0, 0, 1920, 1080);
    	this.background = this.add.tileSprite( 960, 540, 1920, 1080, 'background')
        /* Useful for defining background constraints */
        //this.grid = this.add.grid(960, 540, 1920, 1080, 64, 135, 0x00b9f2).setAltFillStyle(0x016fce).setOutlineStyle();

    	this.tokenLanes = {
            0 : {
                active : false,
                y : 343,
                correct: true,
                keywordIndex: -1
            },
            1 : {
                active : false,
                y : 477,
                correct: true,
                keywordIndex: -1
            },
            2 : {
                active : false,
                y : 611,
                correct: true,
                keywordIndex: -1
            },
            3 : {
                active : false,
                y : 745,
                correct: true,
                keywordIndex: -1
            },
            4 : {
                active : false,
                y : 879,
                correct: true,
                keywordIndex: -1
            }
        }

        this.defaultTokenBounds = {
            x : 8,
            y : 59,
            width: 246,
            height: 164
        }

        this.tokenGroup = this.add.group();
        for( var i = 0; i < 5; i++ ){
            var textureInt = hgArtCycle( 'token' );
            this.tokenGroup.create(this.game.canvas.width + 300, this.tokenLanes[ i ].y, 'token-' + textureInt )
            .setScale(.8,.8)
            .setDisplaySize(205,205)
            .setSize(205, 205)
            .setOrigin(1,.5)
            this.physics.add.existing( this.tokenGroup.children.entries[ i ] );
            this.tokenGroup.children.entries[ i ].body.setAllowGravity(false);
            /* 
            When a page is uploaded via API, 
            setSize is height/width of bounds, offset is x/y 
            This means smaller drawings are easier to avoid
            and harder to collect. 
            */
            this.resetToken( this.tokenGroup.children.entries[ i ] )
            this.tokenGroup.children.entries[ i ].body.onWorldBounds = true;
            this.tokenGroup.children.entries[ i ].name = i;
        }
    	/** Sprite **/
		/*
		- Defaults are set here.
		*/
		this.player = this.physics.add.sprite(100, this.cameras.main.height / 2, 'run-1')
		.setDisplaySize(175,175)
		.setSize(175,200);
		this.anims.create({
            key: 'run',
            frames:[
                {key: 'run-1'},
                {key: 'run-2'}
            ],
            frameRate: 4,
            repeat: -1
	    });
	    this.player.anims.play('run', true);
	    this.player.body.setAllowGravity(false);
	    this.player.body.setSize(125,200);
        this.player.setCollideWorldBounds(true);

        /* Collision */
        this.physics.add.overlap(this.player, this.tokenGroup, this.tokenHit, null, this );

        this.arrows = this.input.keyboard.createCursorKeys();
        this.score = 0;
        this.scoreText = this.add.text(this.game.canvas.width - 10, 5, 'Score: ' + this.score, {fontFamily: 'Arial', fontSize: 52, stroke: '#000', strokeThickness: 3, align: 'right' });
        this.scoreText.setOrigin(1,0);
        this.timeText = this.add.text(this.game.canvas.width - 10, this.scoreText.height+5, 'Time: 120', {fontFamily: 'Arial', fontSize: 52, stroke: '#000', strokeThickness: 3, align: 'right' });
        this.timeText.setOrigin(1,0);

        this.playTimer = this.time.delayedCall(121000, function(){
        	/* Append all terms and descriptions to the text */
        	var caughtTerms = '';
        	var wrongTerms = '';
        	for( var i = 0; i < this.wordData.length; i++ ) {
        		if( typeof this.wordData[i].caught !== 'undefined'
                    && this.wordData[i].caught
        			&& typeof this.wordData[i].description !== 'undefined' 
        			&& this.wordData[i].description.length > 0 ) {
        			if( this.wordData[i].correct ) {
	        			caughtTerms += this.wordData[i].description;
	        		} else {
	        			wrongTerms += this.wordData[i].description;
	        		}
	        	}
        	}
        	if( caughtTerms.length > 0 ) {
        		caughtTerms = '<br /><p>Here are details about correct keywords you found:</p>' + caughtTerms
        	}
        	if( wrongTerms.length > 0 ) {
        		wrongTerms = '<br/><p>Here are details about incorrect keywords you found:</p>' + wrongTerms
        		caughtTerms += wrongTerms;
        	}
            if( this.score >= this.winScore ) {
                hgSendMessage({ event: 'win', text : caughtTerms, score : this.score });
            } else {
                hgSendMessage({ event: 'lose', text : caughtTerms, score : this.score });
            }
        }, null, this );

        this.timeStatus = this.time.addEvent({
            delay: 1000,                // ms
            callback: function(){
                this.timeText.text = "Time: " + Math.ceil(120 - this.playTimer.getElapsedSeconds()); 
            },
            callbackScope: this,
            loop: true
        });


        this.throwTimer = this.time.addEvent({
            delay: 3000, 
            callback: ()=>{
                this.sendToken();
            }, 
            loop: true
        });

        this.missTimer = this.time.addEvent({
            delay: 500, 
            callback: ()=>{
                this.checkTokenPosition();
            }, 
            loop: true
        });


        this.input.keyboard.on('keydown_SPACE', function(){
        	this.rush = true;
        }, this);
        this.input.keyboard.on('keyup_SPACE', function(){
        	this.rush = false;
        }, this);




        this.scene.pause("GameJS");
    }

    update() {
    	this.background.tilePositionX += this.gameSpeed;
    	this.playerMove();
    }

    checkTokenPosition(){
        /* Removes objects that are missed or avoided. */ 
        for(var i = 0; i < this.tokenGroup.children.entries.length; i++){
            if( this.tokenGroup.children.entries[i].x < 0 ){  
                this.resetToken( this.tokenGroup.children.entries[i] ) 
                this.tokenLanes[ i ].lane.destroy();
        		this.tokenLanes[ i ].active = false;          
            }
        }
    }

    tokenHit(player, token){
    	var bonus = 7;
    	if( player.body.position.x > 1350 ) {
    		bonus = 15;
    	} else if( player.body.position.x > 960 ) {
    		bonus = 12;
    	} else if( player.body.position.x > 360 ) {
    		bonus = 10;
    	}
    	/* token.name links us to the lane. */
    	if( this.tokenLanes[ token.name ].correct ) {
    		this.tokenLanes[ token.name ].lane.setBackgroundColor('green');
    		this.score += bonus;
        	this.scoreText.text = "Score: " + this.score;
    	} else {
    		this.tokenLanes[ token.name ].lane.setBackgroundColor('red');
    		this.score -= 10;
        	this.scoreText.text = "Score: " + this.score; 
    	}
    	/* If there is text. */
    	if( this.tokenLanes[ token.name ].keywordIndex > -1 ) {
    		this.wordData[ this.tokenLanes[ token.name ].keywordIndex ].caught = true;
    	}
    	this.tokenLanes[ token.name ].keywordIndex = -1;  
        this.resetToken( token )
       	/* Remove the text */
       	var self = this;
       	window.setTimeout(function(){
       		self.tokenLanes[ token.name ].lane.destroy();
        	self.tokenLanes[ token.name ].active = false;
       	},1000)
    }

    resetToken( token ) {
        var textureInt = hgArtCycle( 'token' );
        token.setTexture( "token-" + textureInt );
        token.x = this.game.canvas.width + 300;
        token.body.setVelocityX(0);
        let bounds = this.defaultTokenBounds;
        // if( typeof this.gameAssets[ 'images.token-' + textureInt ].bounds !== 'undefined' ) {
        //     bounds = this.gameAssets[ 'images.token-' + textureInt ].bounds
        // }
        // token.body.setSize( bounds.width, bounds.height ).setOffset( bounds.x, bounds.y )
    }

    sendToken() {
    	var freeLanes = [];
    	for( var i = 0; i < 5; i++ ) {
    		if( ! this.tokenLanes[ i ].active ) {
    			freeLanes.push(i);
    		}
    	}
    	if( freeLanes.length > 0 ) {
	    	var laneID = freeLanes[Math.floor(Math.random()*freeLanes.length)];
	        var fontSize = 38;
	        /* Get item from array at random. Adjust font if needed. */
	        var keywordIndex = Math.floor(Math.random()*this.wordData.length);
	        var word = this.wordData[ keywordIndex ];
	        this.tokenLanes[ laneID ].keywordIndex = keywordIndex;
	        this.tokenLanes[ laneID ].lane = this.add.text(this.game.canvas.width - 350, this.tokenLanes[ laneID ].y, word.text, 
	        {
	        	fontFamily: 'Arial', 
	        	fontSize: fontSize, 
	        	stroke: '#000', 
	        	strokeThickness: 2,
	        	backgroundColor: '#000',
	        	shadow: {
			        offsetX: 0,
			        offsetY: 0,
			        color: '#000',
			        blur: 3,
			        stroke: false,
			        fill: false
			    },
			    align: 'right',  // 'left'|'center'|'right'|'justify'
			    padding: {
			        left: 15,
			        right: 15,
			        top: 10,
			        bottom: 10,
			    },
	        	wordWrap: {
	        		width: 450,
	        		callback: null,
	        		callbackScope: null,
	        		useAdvancedWrap: false
	    		}
	        });
	        this.tokenLanes[ laneID ].lane.setOrigin(1,.5);
	        this.tokenLanes[ laneID ].active = true;
	        this.tokenLanes[ laneID ].correct = word.correct;
	        this.tokenGroup.children.entries[ laneID ].body.setVelocityX( -350 );
	    } else {
	    	/* All lanes taken. */
	    }
    }

    playerMove() {
    	/* Temp disable rush */
    	var pointer = this.input.activePointer;
    	var velocityX = 300;
    	var velocityY = 230;
    	if( this.rush ) {
    		velocityX += 180;
    		velocityY += 100;
    	}
        if(this.arrows.left.isDown){
            this.player.setVelocityX(-velocityX);
            this.player.flipX = true;
        } else if(this.arrows.right.isDown){
            this.player.setVelocityX(velocityX);
            this.player.flipX = false;
        } else {
            this.player.setVelocityX(0); 
            this.player.flipX = false;              
        }
    
        if(this.arrows.up.isDown){
            if( this.player.body.y <= 170 ) {
                this.player.setVelocityY(0);  
            } else {
                this.player.setVelocityY(-velocityY);  
            }
        } else if(this.arrows.down.isDown){
            if( this.player.body.y >= 780 ) {
                this.player.setVelocityY(0);
            } else {
                this.player.setVelocityY(velocityX);
            }       
        } else {
            this.player.setVelocityY(0);               
        }
    }
}