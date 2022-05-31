/*
Game Type: Memory
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
    /* Plugins */ 
    this.load.plugin('rexshakepositionplugin', '../render/libs/rexshakepositionplugin.min.js', true);
    /* Required for all games. */
    this.load.on('complete', hgStart )
	}

	create() {
		var self = this;
		this.cameras.main.setViewport(0, 0, 1920, 1080)
		/* Background */
    this.background = this.add.image(this.cameras.main.width / 2, this.cameras.main.height / 2, 'background')
    let scaleX = this.cameras.main.width / this.background.width
		let scaleY = this.cameras.main.height / this.background.height
		let scale = Math.max(scaleX, scaleY)
		this.background.setScale(scale).setScrollFactor(0)

		/* Gate */
		this.gate = this.add.image( 960, 420, 'gate').setOrigin(0.5,0.5)
		this.gate.shake = this.plugins.get('rexshakepositionplugin').add( this.gate, {
      duration: 1000,
      mode: 'effect'
    }).on('complete', function () {
     	/* Great job. */
    })

		/* Avatar */
		this.avatar = this.add.image(25, this.cameras.main.height - 300 , 'avatar');
	  this.avatar.setOrigin(0,0.5)
	  this.avatar.setDisplaySize(256,256);
	  this.avatar.setDepth(1);

	  /* Questions */
	  this.questions = hgGet('options.questions');
	  this.questionActive = false;
	  this.questionsLeft = this.questions.length;
	  this.questionIndex = 0;

	  /* Durability of the gate */
	  this.durability = hgGet('options.winScore');
	  this.durabilityText = this.add.text(this.game.canvas.width - 10, 5, 'Durability: ' + this.durability, {fontFamily: 'Arial', fontSize: 52, stroke: '#000', strokeThickness: 3, align: 'right' });
    this.durabilityText.setOrigin(1,0);
    this.questionsText = this.add.text(this.game.canvas.width - 10, this.durabilityText.height+5, 'Questions: ' + this.questionsLeft, {fontFamily: 'Arial', fontSize: 52, stroke: '#000', strokeThickness: 3, align: 'right' });
    this.questionsText.setOrigin(1,0);


	  

    this.gate.setInteractive().on('pointerdown', function (pointer) {
    	this.questionActive = true;
    	hgSendMessage({
	  		event : 'quiz',
	  		question : this.questions[ this.questionIndex ]
	  	}, function( response ){
	  		if( typeof response.correct !== 'undefined' ) {
	  			self.questionIndex++;
	  			self.questionsLeft --;
	  			self.questionsText.text = "Questions: " + self.questionsLeft;
	  			if( response.correct ) {
      			self.durability --;
      			self.durabilityText.text = "Durability: " + self.durability;
      			if( self.durability <= 0 ) {
      				self.winGame();
      			} else {
      				self.gate.shake.shake();
      			} 
		  		} else {		  		
		  			self.wrongAnimation();
		  		}
	  		}
	  	})
    }, this);
		this.scene.pause("GameJS");
	}

	winGame() {
		/* Destroy the gate, create prompt */
		this.gate.shake.shake();
		this.add.tween({
		  targets: this.gate,
		  ease: 'Sine.easeInOut',
		  duration: 1000,
		  delay: 0,
		  x: {
		    getStart: () => 960,
		    getEnd: () => 960
		  },
		  y: {
		    getStart: () => 420,
		    getEnd: () => 460
		  },
		  alpha: {
		    getStart: () => 1,
		    getEnd: () => 0
		  },
		  onComplete: () => {
		    // Handle completion
		    window.setTimeout(function(){
					hgSendMessage({ event: 'win' });
				},2000);
		  }
		});
	}

	wrongAnimation() {
		var self = this;
		this.add.tween({
		  targets: this.gate,
		  ease: 'Sine.easeInOut',
		  duration: 1000,
		  delay: 0,
		  scale: {
		    getStart: () => 1,
		    getEnd: () => 1.6
		  },
		  angle: {
		    getStart: () => 0,
		    getEnd: () => 10
		  },
		  onComplete: () => {
  			/* No way to win, so fail it. */
  			if( self.questionsLeft < self.durability ) {
  				self.loseGame();
  			}
		  },
		  yoyo : true
		});
	}

	loseGame() {
		hgSendMessage({ event: 'lose' });
	}

    addVictorySlides() {
    	this.victoryImageGroup.removeAll(true);
    	this.victoryImages = [];
       	var self = this;
   			/* Determine the duration of the sound. */
   			var victory = self.game.cache.getSound(MaestroGameBuilder.Options.playData['SimonSays'].sequences[this.round].complete);
   			var durationMarkers = Math.floor(victory.data.duration*1000)/MaestroGameBuilder.Options.playData['SimonSays'].sequences[this.round].images.length;
   			var counter = 0;
   			MaestroGameBuilder.Options.playData['SimonSays'].sequences[this.round].images.forEach(function(image){
   				var randX = Math.random() * (self.maxX - self.minX) + self.minX;
   				var randY = Math.random() * (self.maxY - self.minY) + self.minY;
   				self.victoryImages[counter] = self.game.make.image(self.game.width*.55,self.game.height*.4,image);
   				self.victoryImages[counter].anchor.setTo(0.5);
   				//self.victoryImages[i][counter].scale.setTo(1.3);
   				self.victoryImages[counter].finalX = randX;
   				self.victoryImages[counter].finalY = randY;
   				self.victoryImages[counter].trigger = (durationMarkers*counter)+10;
   				self.victoryImages[counter].alpha = 0;
   				self.victoryImages[counter].markerLength = durationMarkers;
   				self.victoryImageGroup.add(self.victoryImages[counter]);
   				counter++;
   			});
    }
    addButtons() {
    	this.buttons.removeAll(true);
    	this.buttons.count = 0;
    	this.buttonObjects = {};
    	var self = this;
    	var multiplier = 1;
    	var increment = this.width/4;
    	/* For each button provided, add metadata and set position. This is immediately overruled by resize.*/
    	// MaestroGameBuilder.Options.playData['SimonSays'].sequences[this.round].buttons.forEach(function(button){
    	// 	self.buttonObjects[self.buttons.count] = self.game.make.button((increment*multiplier)-(increment/2),self.game.height*.95,button.image, self.buttonPress, self);
    	// 	self.buttonObjects[self.buttons.count].anchor.setTo(.5,1);
    	// 	/* Store Metadata with the button. */
    	// 	self.buttonObjects[self.buttons.count].data = button;
    	// 	self.buttons.add(self.buttonObjects[self.buttons.count]);
    	// 	multiplier++;
    	// 	self.buttons.count++;
    	// });
    }
    buttonPress(button) {
    	if(this.isPlaying){
    		return false;
    	}
    	if(this.expectsPattern){
    		if(button.data.id==MaestroGameBuilder.Options.playData['SimonSays'].sequences[this.round].sequence[this.progressCounter].key){
    			/* Right */
    			this.game.sound.play(button.data.sound);
    			/* Send up the ghost. */
    			this.createButtonGhost(button);
    			if(this.progressCounter==MaestroGameBuilder.Options.playData['SimonSays'].sequences[this.round].sequence.length-1){
    				/* Round victory! */
    				this.isPlaying = true;
    				var self = this;
    				var victorySound = this.game.sound.play(MaestroGameBuilder.Options.playData['SimonSays'].sequences[this.round].complete);
    				this.victoryImages.forEach(function(image){
    					var tweenA = self.game.add.tween(image).to({alpha:1},image.markerLength*.25,"Linear",true,image.trigger);
    					tweenA.onComplete.add(function(){
    						self.game.add.tween(image).to({alpha:0},image.markerLength*.1,"Linear",true,image.markerLength*.75);
    					},this);
    					//var tweenB = 
    					//tweenA.chain(tweenB);
    					//tweenA.start();
    				});
    				this.expectsPattern = false;
    				this.progressCounter = 0;
    				if(MaestroGameBuilder.Options.playData['SimonSays'].sequences.length-1<=this.round){
    					/* Level Complete */
              if(MaestroGameBuilder.Options.progress.stillPlaying==false&&MaestroGameBuilder.Options.progress.levels[MaestroGameBuilder.Options.progress.nextLevel.index].complete!=true){
                MaestroGameBuilder.Options.progress.levels[MaestroGameBuilder.Options.progress.nextLevel.index].complete = true;
                MaestroGameBuilder.Options.progress.nextLevel.index++;
              }
				    	
				    	victorySound.onStop.addOnce(function(){
					    	MGBUtils.launchModal(self.game.textData[self.game.state.current].success.title,self.game.textData[self.game.state.current].success.text,self,function(){
						    	/* TODO victory text then back to home*/
					    		MaestroGameBuilder.Options.container.className = 'fade';
					            /* Time parameter matches the CSS transition length so fade completes */
					            window.setTimeout(function(){
					            	/* Controlled by Plugin */
					            	self.game.paused = false;
									self.state.start("LevelMenu");
									//MGBUtils.resumeGame(self);
					            },300);
					    	});
				    	});
    				}else{
    					var self = this;
    					victorySound.onStop.addOnce(function(){
	    					self.round++;
	    					/* Load Next Round */
	    					setTimeout(function(){
		    					self.isPlaying = false;
		    					self.addVictorySlides();
		    					self.addButtons();
		    					self.playButtonSequence();
	    					},500);
    					});
    				}
    			}else{
    				this.progressCounter++;
    			}
    		}else{
    			/* Wrong */
    			this.game.sound.play('SimonSaysWrong');
    			var self = this;
    			this.progressCounter = 0;
    			window.setTimeout(function(){
    				self.playButtonSequence();
    			},1100);
    			return false;
    		}
    		return false;
    	}
    	this.game.sound.play(button.data.sound);
    }

	playButtonSequence(){
		if(this.isPlaying){
			return false;
		}    
		this.isPlaying = true;
	    this.game.time.events.repeat(Phaser.Timer.SECOND, MaestroGameBuilder.Options.playData['SimonSays'].sequences[this.round].sequence.length, this.playSequenceElement, this);
	}
	playSequenceElement() {
		var soundLocation = MaestroGameBuilder.Options.playData['SimonSays'].sequences[this.round].sequence[this.sequenceCounter].key;
		this.game.sound.play(MaestroGameBuilder.Options.playData['SimonSays'].sequences[this.round].buttons[soundLocation].sound);
		this.createButtonGhost(this.buttonObjects[soundLocation]);
		if(this.sequenceCounter>=MaestroGameBuilder.Options.playData['SimonSays'].sequences[this.round].sequence.length-1){
			this.sequenceCounter = 0;
			this.isPlaying = false;
			/* Player's turn. */
			this.expectsPattern = true;
		}else{
			this.sequenceCounter++;
		}
	}
	createButtonGhost(button) {
		/* Create a duplicate to float up */
		var cueImage = this.game.add.image(button.x,button.y,button.data.image);
		var scale = .40;
    	if(this.game.width<955){
    		scale = .31;
    	}
    	if(this.game.width<800){
    		scale = .28;
    	}
    	if(this.game.width<660){
    		scale = .23;
    	}
		cueImage.scale.setTo(scale);
		cueImage.anchor.setTo(.5,1);
		this.game.add.tween(cueImage).to({y: this.game.height/2,alpha: 0},1000, "Linear", true);
		window.setTimeout(function(){
			cueImage.destroy();
		},1000);
	}
}