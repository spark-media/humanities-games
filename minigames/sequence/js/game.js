/*
Game Type: Sequence
*/
const COLOR_PRIMARY = 0x333333;
const COLOR_LIGHT = 0x666666;
const COLOR_DARK = 0x111111;

class GameJS extends Phaser.Scene {

	constructor(){
		super({key:'GameJS'});
	}

	preload() {
		for( var imageID in gameData.images ) {		
			var asset = hgGet( 'images.' + imageID );                       
            if( asset != null ) {							
				this.load.image( imageID, asset.file );		
			} else {
				// Shouldn't happen but we should display a whoops. 
				console.log("image preload whoops " + imageID );
			}
    }
 
    /* Item images */
    this.items = hgGet('options.items');
    for( var i = 0; i < this.items.length; i++ ) {
    	if( this.items[i].image ) {
    		this.load.image( 'item-' + i, this.items[i].image.file );
    	}
    	if( this.items[i].panel ) {
    		this.load.image( 'panel-' + i, this.items[i].panel.file );
    	}
    }
    /* Plugins */ 
    this.load.scenePlugin({
      key: 'rexuiplugin',
      url: '../render/libs/rexuiplugin.min.js',
      sceneKey: 'rexUI'
    });
    /* Required for all games. */
    this.load.on('complete', hgStart )
	}

	create() {
		var self = this;
		this.input.topOnly = false;
		this.cameras.main.setViewport(0, 0, 1920, 1080)
		/* Background */
    this.background = this.add.image(this.cameras.main.width / 2, this.cameras.main.height / 2, 'background')
    let scaleX = this.cameras.main.width / this.background.width
		let scaleY = this.cameras.main.height / this.background.height
		let scale = Math.max(scaleX, scaleY)
		this.background.setScale(scale).setScrollFactor(0)

		/* Number of Attempts */
	  this.attempts = 3;
	  this.attemptsText = this.add.text(this.game.canvas.width - 185, 18, 'Attempts: ' + this.attempts, {fontFamily: 'Arial', fontSize: 52, stroke: '#000', strokeThickness: 5, align: 'center' });
    this.attemptsText.setOrigin(.5,0);

    this.orderCounter = 0;



		/* Scrollable panel */
		this.itemPanel = this.rexUI.add.scrollablePanel({
      x: 1020,
      y: 100,
      // anchor: {
      // 	right: 'right-15',
      // 	top: 'top+90'
      // },
      width: 330,
      height: 880,
      scrollMode: 0,
      background: this.rexUI.add.roundRectangle(0, 0, 2, 2, 10, COLOR_PRIMARY),
      panel: {
        child: this.createPanel(this),
        mask: {
          padding: 5
        } 
      },

      slider: {
        track: this.rexUI.add.roundRectangle(0, 0, 20, 10, 10, COLOR_DARK),
        thumb: this.rexUI.add.roundRectangle(0, 0, 0, 0, 30, COLOR_LIGHT),
        position: 1
      },

      scroller: {
        pointerOutRelease: true
      },

      mouseWheelScroller: {
        focus: true,
        speed: 0 
      },

      space: {
        left: 10,
        right: 10,
        top: 10,
        bottom: 10,
        panel: 20 
      } 
    })

    this.dropZone = this.createDropZone();

    this.rexUI.add.sizer(
    	{
    		orientation: 'x',
    		space: {
    			item: 20
    		}
  		}
    ).
    add( this.dropZone ).
  	add( this.itemPanel ).
  	addChildrenMap('panels', [ this.dropZone, this.itemPanel ])
  	.setOrigin(1,0).setPosition( this.game.canvas.width - 15, 90 ).layout();

		/* Avatar */
		this.avatar = this.add.image(25, this.cameras.main.height - 300 , 'character');
	  this.avatar.setOrigin(0,0.5)
	  this.avatar.setDisplaySize(256,256);
	  this.avatar.setDepth(1);


		this.scene.pause("GameJS");
	}

	createPanel ( scene ) {
	  var sizer = scene.rexUI.add.sizer({
	    orientation: 'y',
	    space: { item: 10 } }
	  )
	  .add(
	  	this.createTable( scene ), // child
	  	{ expand: true }
	  )

	  // add(
	  // createTable(scene, data, 'items', 2), // child
	  // { expand: true });

	  return sizer;
	}

	nextPanel( index, fn ) {
		var self = this;
		this.add.tween({
		  targets: this.dropZone,
		  ease: 'Sine.easeInOut',
		  duration: 500,
		  delay: 0,
		  alpha: {
		    getStart: () => 1,
		    getEnd: () => 0
		  },
		  onComplete: () => {
	  		if( self.items[ index ].panel ) {
      		self.dropZone.getElement('answer-bg').setTexture('panel-'+ index );
	      	self.dropZone.layout()
      	}
		    // Handle completion
		   	self.add.tween({
				  targets: self.dropZone,
				  ease: 'Sine.easeInOut',
				  duration: 500,
				  delay: 0,
				  alpha: {
				    getStart: () => 0,
				    getEnd: () => 1
				  },
				  onComplete: () => {
				    // Handle completion
				    fn();
				  }
				});
		  }
		});
	}

	loseGame() {
		/* Destroy the zone, create prompt */
		this.dropZone.shake(1000)
		this.add.tween({
		  targets: this.dropZone,
		  ease: 'Sine.easeInOut',
		  duration: 1000,
		  delay: 0,
		  x: {
		    getStart: () => this.dropZone.x,
		    getEnd: () => this.dropZone.x
		  },
		  y: {
		    getStart: () => this.dropZone.y,
		    getEnd: () => this.dropZone.y+40
		  },
		  alpha: {
		    getStart: () => 1,
		    getEnd: () => 0
		  },
		  onComplete: () => {
		    // Handle completion
		    window.setTimeout(function(){
					hgSendMessage({ event: 'lose' });
				},2000);
		  }
		});
	}

	createDropZone() {
		var sizer = this.rexUI.add.fixWidthSizer({
			// anchor : {
			// 	left: 'left+250',
			// 	top: 'top+100'
			// },
			name: 'answer',
	    width: 1300, 
	    height: 731,
	    orientation: 'x',
	    space: {
	      left: 10, 
	      right: 10, 
	      top: 10, 
	      bottom: 10,
	      item: 5, 
	      line: 5 
	    } 
    })
    .addBackground( this.add.image( 0, 0,'start-screen' ), {}, 'answer-bg' )
  	//.layout()
  	.setInteractive({ dropZone: true });
  	return sizer;
	}

	createTable ( scene ) {
    var table = scene.rexUI.add.fixWidthSizer({
        width: 280,
        orientation: 'y',
        name: 'items'  // Search this name to get table back
    }).setInteractive({ dropZone: true });

    var shuffled = this.shuffle(this.items);
    for ( var i = 0; i < shuffled.length; i++ ) {
        var item = shuffled[i];
        var originalIndex = this.items.indexOf(item);
        table.add(
            this.createItem( scene, item, originalIndex ),
            {
            	draggable: true,
            	align: 'top',
            	expand: true
            }
        );
    }

    return scene.rexUI.add.sizer({
        orientation: 'y',
        space: { left: 10, right: 10, top: 10, bottom: 30, item: 30 },
    })
    .add(table, // child
        1, // proportion
        'top', // align
        0, // paddingConfig
        true // expand
    );
	}

	createItem( scene, item, index ) {
		var args = {
    	width: 260,
    	draaggable: true,
      orientation: 'y',
      text: scene.make.text({
      	x: 0, 
      	y: 0, 
      	text: item.name,
      	style: {
      		font: 'bold 35px Arial', 
      		align: 'center',
      		wordWrap: { width: 260 } 
      	} 
      }),
      space: { 
      	left: 10,
      	right: 10,
      	top: 10,
      	bottom: 10
      }
    }
    if( item.image )  {
    	args.icon = this.add.image( 0, 0, 'item-'+index ).setDepth(1);
    }
		var element = scene.rexUI.add.label( args );
    element.setDepth(1);
    this.setDraggable( element, index );
    return element;
	}

	setDraggable ( item, index ) {
		var self = this;
    item.setInteractive({ draggable: true }).
    on('dragstart', function (pointer, dragX, dragY) {
    	/* Freeze panel */
    	self.itemPanel.setScrollerEnable(false);
    	/* Log scroll position of panel. */
      item.setData({ startX: item.x, startY: item.y });
    }).
    on('drag', function (pointer, dragX, dragY) {
      item.setPosition( dragX, dragY );
    }).
    on('dragend', function (pointer, dragX, dragY, dropped) {
    	/* If on a dropzone */
      if ( dropped ) {
        return;
      }
      /* Outside DZ bounds, send home */
      var parent = item.getParentSizer();
      self.arrangeItems( parent );
    }).
    on('drop', function (pointer, target) {
      var parent = item.getParentSizer();
      if( target.name == 'answer' ) {
	      if( self.orderCounter == index ) {
	      	console.log('good', self.orderCounter, index );
	      	parent.remove(item);
	      	self.orderCounter++;
	      	item.destroy();
	      	self.nextPanel( index, function(){
		      	self.arrangeItems( parent );
		      	window.setTimeout(function(){
		      			hgSendMessage({ event: 'info', text: self.items[ index ].description }, function(){
		      				if( self.orderCounter >= self.items.length ) {
			      				window.setTimeout(function(){
					      			hgSendMessage({ event: 'win' });
					      		}, 1000)
			      			}
		      			});
		      	}, 2000)	      		
	      	})
	      } else {
	      	console.log('wrong')
	      	self.dropZone.shake(1000);
	      	self.arrangeItems( parent );
	      	self.attempts--;
	      	self.attemptsText.text = 'Attempts: '+self.attempts;
	      	if( self.attempts <= 0 ) {
	      		self.loseGame();
	      	}
	      }
	    } else {
	    	self.arrangeItems( parent );
	    }
    });
	}

	arrangeItems ( panel ) {
		var self = this;
	  var items = panel.getElement('items');
	  // Save current position
	  items.forEach(function (item) {
	    item.setData({ startX: item.x, startY: item.y });
	  });
	  // Item is placed to new position in fixWidthSizer
	  panel.layout();
	  // Move item from start position to new position
	  items.forEach(function (item) {
	    item.moveFrom({
	      x: item.getData('startX'), y: item.getData('startY'),
	      speed: 3000 });
	    item.setDepth(1);
	  });
	  panel.layout();
    window.setTimeout(function(){
    	//panel.getParentSizer().layout();
    	self.itemPanel.t = 0;
    	self.itemPanel.setScrollerEnable(true);
    }, 1000 )
	}

	shuffle(originalArray) {
	  var array = [].concat(originalArray);
	  var currentIndex = array.length, temporaryValue, randomIndex;

	  // While there remain elements to shuffle...
	  while (0 !== currentIndex) {

	    // Pick a remaining element...
	    randomIndex = Math.floor(Math.random() * currentIndex);
	    currentIndex -= 1;

	    // And swap it with the current element.
	    temporaryValue = array[currentIndex];
	    array[currentIndex] = array[randomIndex];
	    array[randomIndex] = temporaryValue;
	  }

	  return array;
	}
}