var fanfare;
var bkgMusic;

var openMarks = [];
var closedMarks = [];
var arrows;
var player;
var markReady;

var playerAnim;

var dodgeTimer;
var lineTimer; 
var throwTimer;
var infoTimer;

var movementEnabled;
var combo; 
var comboSuccess;

var score;
var scoreText;

var infoText;

var projectiles_L = [];
var projectileSpeed = 300;
var splat = [];
var activeSplats;
var paused; 
var script;
// var info;
var info;
var quizPanel;

var rawCombo = [];

var firstStart;

var timer;
var timeStatus;
var timeText;
var textBoxTop;
var textTop;
var tutorialText = [];

var me;




class GameJS extends Phaser.Scene {
  
	constructor(){
		super({key:'GameJS'});
	}
  
    preload(){                
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
        this.projectiles = hgGet( 'options.projectiles' );
        for( var i = 0; i < this.projectiles.length; i++ ) {
            if( this.projectiles[i].image ) {
                this.load.image( 'projectile-' + i, this.projectiles[i].image.file );
            }
        }
        /* Required for all games. */
        this.load.on('complete', hgStart )
    }


    create(){
      
        var direction = hgGet('options.projectileDirection');
        
        switch(direction){
            case "down":
                console.log("projectiles down");
                break;
            case "up": 
                console.log("projectiles up");
                break;
            case "left":
                console.log("projectiles left");
                break;
            case "right":
                console.log("projectile right");
                break;
        }
      
        me = this;      


        let image = this.add.image(this.game.canvas.width / 2, this.game.canvas.height / 2, 'background')
        let scaleX = this.game.canvas.width / image.width
        let scaleY = this.game.canvas.height / image.height
        let scale = Math.max(scaleX, scaleY)
        image.setScale(scale).setScrollFactor(0)

        score = 0;
        scoreText = this.add.text((this.game.canvas.width/5)*4, 5, 'Score: ' + score, {fontFamily: 'Arial', fontSize: 64, stroke: '#000', strokeThickness: 3});
        timeText = this.add.text((this.game.canvas.width/5)*4, scoreText.height+5, 'Time: 120', {fontFamily: 'Arial', fontSize: 64, stroke: '#000', strokeThickness: 3});
        //click bool so we can control when we start the game
        firstStart = true;    


        //MARKS    
        const collectibles = this.add.group();
        var cCounter = 0;
        for(var i =0; i < 5; i++){
            if( cCounter >= this.collectibles.length ) {
                cCounter = 0;
            }
            collectibles.create(90+(90*i), 90, 'collectible-'+ cCounter ).setScale(0.75, 0.75);
            openMarks.push(collectibles.children.entries[i])
            this.physics.add.existing(collectibles.children.entries[i]);
            collectibles.children.entries[i].body.setAllowGravity(false);  
            cCounter++;                                 
        }   

   
         //math this fool, calculate the position based on canvas size  (chelsea's right)
        collectibles.children.entries[0].setPosition((this.game.canvas.width/3)-300,this.game.canvas.height/3);
        collectibles.children.entries[1].setPosition(((this.game.canvas.width/3)*2)-300, this.game.canvas.height/3);
        collectibles.children.entries[2].setPosition((this.game.canvas.width)-300, this.game.canvas.height/3);
        collectibles.children.entries[3].setPosition((this.game.canvas.width/6)*2, (this.game.canvas.height/3)*2);
        collectibles.children.entries[4].setPosition((this.game.canvas.width/6)*4, (this.game.canvas.height/3)*2);

        for(var i =0; i < 5; i++){     
            collectibles.children.entries[i].setVisible(false);
        }
        let markInit = this.time.delayedCall(500, this.startDelay);

    //PLAYER
        player = this.physics.add.sprite(300, 200, 'idle');
        player.body.setAllowGravity(false);
        player.setSize(44,64);
        player.setCollideWorldBounds(true);
        //just one animation
        if(this.textures.exists('run-1') && this.textures.exists('run-2')){
           console.log("Both side move animation files found")
           this.anims.create({
                key: 'move',
                frames:[
                    {key: 'run-1'},
                    {key: 'run-2'}
                ],
                frameRate: 4,
                repeat: -1
		    });	
            // this.anims.exists('move') check if this animation exists 
          
        }else if(this.textures.exists('run-1')){
            console.log("Only one side move frame")
            this.anims.create({
                key: 'move',
                frames:[
                    {key: 'run-1'}                  
                ],
                frameRate: 4,
                repeat: -1
            });
        }else{
            console.log("no side move frames")
        }


    //PROJECTILES
        const projectiles = this.add.group();
        var pCounter = 0;
        for(let y = 0; y<5; y++){
            if( pCounter >= this.projectiles.length ) {
                pCounter = 0;
            }
            //this needs to be changed depend
            projectiles.create((100+(this.game.canvas.width/4)*y), -100, 'projectile-' + pCounter ).setScale(0.5, 0.5);
            this.physics.add.existing(projectiles.children.entries[y]);
            projectiles.children.entries[y].body.setAllowGravity(false);
            projectiles.children.entries[y].body.onWorldBounds = true;
            projectiles_L.push(projectiles.children.entries[y]);  
            pCounter++;      
        }



    //Movement
        arrows = this.input.keyboard.createCursorKeys();
        movementEnabled = false;

        
    //Physics    
        this.physics.add.overlap(player, collectibles, this.markOverlap);
        this.physics.add.overlap(player, projectiles, this.projectileHit);
    //TIMERS
        dodgeTimer = this.time.addEvent({
            delay:1000, 
            callback: this.showMark, 
            loop: true
        });
        dodgeTimer.paused = false;

        throwTimer = this.time.addEvent({
            delay: 500, 
            callback: ()=>{
                this.throwProjectile();
                this.checkProjectile();
            }, 
            loop: true
        });
        throwTimer.paused = false;
        
        firstStart = false;
        paused = false;
        this.input.keyboard.on('keydown_SPACE', this.pause, this);
        
        var self = this;
        timer = this.time.delayedCall(121000, function(){
            var winScore = hgGet('options.winScore');
            if( score >= winScore ) {
                var message = {
                    event: 'win',
                    score: score
                }
                var text = self.parseCollectibleHTML();
                if( text.length > 0 ) {
                    message.text = text;
                }
                hgSendMessage( message );
            } else {
                var message = {
                    event: 'lose',
                    score: score
                }
                var text = self.parseCollectibleHTML();
                if( text.length > 0 ) {
                    message.text = text;
                }
                hgSendMessage( message );
            }
        }); 

        timeStatus = this.time.addEvent({
            delay: 1000,                // ms
            callback: function(){
                timeText.text = "Time: " + Math.ceil(120 - timer.getElapsedSeconds()); 
            },
            loop: true
        });
        /* Pause to start */
        //this.pause();
        this.scene.pause("GameJS");
    }   

    update(){        
        this.playerMove();                  
    }

    parseCollectibleHTML() {
        var items = '';
        var collectedItems = '';
        for( var i = 0; i < this.collectibles.length; i++ ) {
            if(typeof this.collectibles[i].title !== 'undefined' 
            && this.collectibles[i].title.length > 0) {
                collectedItems += '<h4>'+this.collectibles[i].title+'</h4>';
            }
            if( typeof this.collectibles[i].description !== 'undefined' 
            && this.collectibles[i].description.length > 0) {
                collectedItems += this.collectibles[i].description
            }
        }
        if( collectedItems.length > 0 ) {
            collectedItems = '<br /><p>Here are details about the items you were trying to collect:</p>' + collectedItems
        }
        var avoidedItems = '';
        for( var i = 0; i < this.projectiles.length; i++ ) {
            if(typeof this.projectiles[i].title !== 'undefined' 
            && this.projectiles[i].title.length > 0) {
                avoidedItems += '<h4>'+this.projectiles[i].title+'</h4>';
            }
            if( typeof this.projectiles[i].description !== 'undefined' 
            && this.projectiles[i].description.length > 0) {
                avoidedItems += this.projectiles[i].description
            }
        }
        if( avoidedItems.length > 0 ) {
            avoidedItems = '<br /><p>Here are details about the items you were trying to avoid:</p>' + avoidedItems
        }
        items = collectedItems + avoidedItems;
        return items;
    }

    pause(){       
        if(!paused){
            timer.paused = true;
            throwTimer.paused = true;           
            movementEnabled = false;   
            player.body.velocity.x = 0;
            player.body.velocity.y = 0;
            paused = true;
        }else{
            timer.paused = false;
            throwTimer.paused = false;            
            movementEnabled = true;
            paused = false;
        }
        this.freezeProjectile();
    }

    throwProjectile(){   
        let randNum = Phaser.Math.Between(0, projectiles_L.length-1); 
        if(projectiles_L[randNum].y < 0){
            var textureInt = hgArtCycle( 'projectile', this.projectiles );
            projectiles_L[randNum].setTexture("projectile-"+ textureInt );
            projectiles_L[randNum].body.setVelocityY(300);           
        }
    }

    checkProjectile(){ //checks for all thrown objects that are past the screen 
        //currently 
        for(let i=0; i<projectiles_L.length; i++){
            if(projectiles_L[i].y > me.game.canvas.height){                
                projectiles_L[i].body.setVelocityY(0);
                projectiles_L[i].y = -100;            
            }
            projectiles_L[i].body.enable = true;
            projectiles_L[i].setVisible(true);
        }
    }

    freezeProjectile(){
        for(let i=0; i<projectiles_L.length; i++){
            if(projectiles_L[i].body.enable){
                if(paused){
                    projectiles_L[i].body.setVelocity(0,0);
                }else{
                    projectiles_L[i].body.setVelocity(0, 300); //come back and add customization to this for direction thrown
                }               
            }            
        }
    }

    projectileHit(_p, _t){
        _t.body.setVelocityY(0);
        _t.y = -300;
        _t.body.enable = false;      
        _t.setVisible(false);
        score -= 10;
        scoreText.text = "Score: " + score;
        var textureInt = hgArtCycle( 'projectile', this.projectiles );
        _t.setTexture( "projectile-" + textureInt );  
    }

    pauseGame(status){
        dodgeTimer.paused = status;
        throwTimer.paused = status;
        movementEnabled = status;
    }


    playerMove(){
        if(movementEnabled){
            if(arrows.left.isDown){
                player.setVelocityX(-260);
                player.flipX = true;
            }else if(arrows.right.isDown){
                player.setVelocityX(260);
                player.flipX = false;
            }else{
                player.setVelocityX(0);               
            }
        
            if(arrows.up.isDown){
                player.setVelocityY(-200);  
            }else if(arrows.down.isDown){
                player.setVelocityY(200);                
            }else{
                player.setVelocityY(0);                
            }

            if(player.body.velocity.x != 0 || player.body.velocity.y != 0){
                player.anims.play('move', true);
            }else{
                player.setTexture('idle');
            }

        }
    }

    clickStart(){ //should be placed in the create, should probs be a button really
        if(me.game.input.mousePointer.isDown){          
            if(!firstStart){            
                throwTimer.paused = false;
                dodgeTimer.paused = false;
                firstStart = true; 
            }
        }
    }

    startDelay(){
        for(let i=0; i< openMarks.length; i++){
            if(openMarks[i] == undefined){
                console.log("open marks " , openMarks);
            }else{
                openMarks[i].body.enable = false;
            }            
        }
        movementEnabled = true;    
    }

    reOpenMarks(){
        for(let i=0; i < 5; i++){
            openMarks.push(closedMarks[i]);
        }
        closedMarks = [];
    }

    markOverlap(_p, _m){//did you touch the mark
        openMarks.splice(openMarks.indexOf(_m), 1);     
        closedMarks.push(_m);
        _m.body.enable = false;
        dodgeTimer.paused = false;    
        score += 25;
        scoreText.text = "Score: " + score;
        setTimeout(()=>{
            _m.setVisible(false);
        }, 100);
    }


    showMark(){  
        dodgeTimer.paused = true;
        if(openMarks.length > 1){    
                let randNum = Phaser.Math.Between(0, openMarks.length-1);
                let obj = openMarks[randNum];      
                obj.body.enable = true;
                obj.setVisible(true);
        }else if(openMarks.length == 1){      
            openMarks[0].body.enable = true;
            openMarks[0].setVisible(true);
        }else{           
            me.reOpenMarks();
            me.checkProjectile();
            dodgeTimer.paused = false;
        }
    }
}

