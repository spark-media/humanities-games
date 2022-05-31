


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
				if( imageID == 'background' ) {
                    this.load.image('background', [ asset.file, 'defaults/bg_normal-map.png' ]);
                }   else {
                    /* Run sprite is square */
                    this.load.image( imageID, [ asset.file, 'defaults/character_normal-map.png' ] );     
                }
                this.load.image( 'wall', [ 'defaults/wall.png', 'defaults/wall_normal-map.png' ] );     		
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
    	this.rush = false;
    	this.cameras.main.setViewport(0, 0, 1920, 1080);
        this.wordData = hgGet( 'options.keywords' );
        this.winScore = hgGet( 'options.winScore' );
        this.penalty = hgGet( 'options.penalty' );
        this.score = 0;
        /* Number of Attempts */
        this.attempts = hgGet( 'options.attempts' );
        this.intensityInterval = 1.3 / this.winScore;
        this.radiusInterval = 1300 / this.winScore;
        this.progressInterval = 96 / this.winScore;
        this.progressLight = 4;
    	/* Background */
        this.background = this.add.sprite(this.cameras.main.width / 2, this.cameras.main.height / 2, 'background')
        let scaleX = this.cameras.main.width / this.background.width
        let scaleY = this.cameras.main.height / this.background.height
        let scale = Math.max(scaleX, scaleY)
        this.background.setScale(scale).setScrollFactor(0)
        .setPipeline('Light2D');
        /* Useful for defining background constraints */
        //this.grid = this.add.grid(960, 540, 1920, 1080, 384, 216, 0x00b9f2).setAltFillStyle(0x016fce).setOutlineStyle();

        /** Maze **/
        this.generateSpawnTiles();
        /* 
            Maze is controlled by activeLines (which walls appear). 
            0, 4, 26, 31, 36 and 39 should always be active due to UI overlap 
        */
        this.mazeLines = {
            0 : [ 384, 106 ], 1 : [ 768, 106 ], 2 : [ 1152, 106 ], 3 : [ 1536, 106 ],
            4 : [ 192, 216 ], 5 : [ 576, 216 ], 6 : [ 960, 216 ], 7 : [ 1344, 216 ], 8 : [ 1728, 216 ],
            9 : [ 384, 324 ], 10 : [ 768, 324 ], 11 : [ 1152, 324 ], 12 : [ 1536, 324 ],
            13 : [ 192, 432 ], 14 : [ 576, 432 ], 15 : [ 960, 432 ], 16 : [ 1344, 432 ], 17 : [ 1728, 432 ],
            18 : [ 384, 540 ], 19 : [ 768, 540 ], 20 : [ 1152, 540 ], 21 : [ 1536, 540 ],
            22 : [ 192, 648 ], 23 : [ 576, 648 ], 24 : [ 960, 648 ], 25 : [ 1344, 648 ], 26 : [ 1728, 648 ],
            27 : [ 384, 756 ], 28 : [ 768, 756 ], 29 : [ 1152, 756 ], 30 : [ 1536, 756 ],
            31 : [ 192, 864 ], 32 : [ 576, 864 ], 33 : [ 960, 864 ], 34 : [ 1344, 864 ], 35 : [ 1728, 864 ],
            36 : [ 384, 972 ], 37 : [ 768, 972 ], 38 : [ 1152, 972 ], 39 : [ 1536, 972 ]
        }
        /* Used for sizing */
        this.verticals = [ 0, 1, 2, 3, 9, 10, 11, 12, 18, 19, 20, 21, 27, 28, 29, 30, 36, 37, 38, 39 ]
        /* Create the maze */
        this.mazeZoneGroup = this.add.group();
        this.generateMaze();

        /** Spawn Points */
        this.keywordGroup = this.add.group();

        /* Set this.tileSpawnPoints and this.distanceArray, and spawns keywords */
        this.generateSpawnPaths( 10, true );        

    	/** Sprite **/
		/*
		- Defaults are set here.
		*/
		this.player = this.physics.add.sprite(100, this.cameras.main.height / 2, 'idle')
		.setDisplaySize(175,175);
		this.anims.create({
            key: 'run',
            frames:[
                {key: 'run-1'},
                {key: 'run-2'}
            ],
            frameRate: 4,
            repeat: -1
	    });
	    this.player.body.setAllowGravity(false);
	    this.player.body.setSize(200,220);
        this.player.setCollideWorldBounds(true)
        .setPipeline('Light2D');
        /* Lights */
        this.lights.enable().setAmbientColor(0x000000); 
        this.playerLight = this.lights.addLight(100, this.cameras.main.height / 2, 200 )
        .setIntensity(1);
        console.log( this.playerLight.intensity )
        /* Collision */
        this.physics.add.overlap(this.player, this.keywordGroup, this.keywordHit, null, this );

        /* Maze Collision */
        this.mazeCollider = this.physics.add.collider( this.player, this.mazeZoneGroup );
        /* Controls */
        this.arrows = this.input.keyboard.createCursorKeys();
        this.input.keyboard.on('keydown_SPACE', function(){
        	this.rush = true;
        }, this);
        this.input.keyboard.on('keyup_SPACE', function(){
        	this.rush = false;
        }, this);

        this.attemptsText = this.add.text(this.game.canvas.width - 15, 5, 'Attempts: ' + this.attempts, {fontFamily: 'Arial', fontSize: 52, stroke: '#000', strokeThickness: 3 }).setOrigin(1, 0);
        this.lightText = this.add.text(this.game.canvas.width - 15, this.attemptsText.height+5, 'Light: 4%', {fontFamily: 'Arial', fontSize: 52, stroke: '#000', strokeThickness: 3}).setOrigin(1, 0);

        this.scene.pause("GameJS");
    }

    update() {
    	this.playerMove();
        this.playerLight.x = this.player.x;
        this.playerLight.y = this.player.y;
    }

    generateSpawnPaths( home = 10, start = false ) {
        /* 
        Tile where player spawns is 10. 
        Recalculate 
        */
        this.distanceArray = [];
        /* Words can never go here. 10 is start point. */
        var neverActive = [ 0, 4, 10, 19, 20, 24 ]
        /* We track tile blocks on the road to home to try and not get stuck. */
        var blocked = {
            top : [],
            bottom : [],
            left : [],
            right : []
        }
        if( start ) {
            /* First run */
            this.tileSpawnPoints = {};
            for( var key in this.spawnPoints ) {
                /* We will blacklist the neveractive tiles at render time but need them for paths. */
                this.tileSpawnPoints[ key ] = { 
                    before: [], 
                    lit: 0, 
                    block : null, 
                    active: false, 
                    pathHome: [], 
                    key: parseInt(key),
                    correct: false, 
                    keywordIndex: -1 
                }
            }
        }
        /* loop this.spawnPoints and find the fastest way home. */
        for( var key in this.spawnPoints ) {
            var currentKey = parseInt(key);
            var tilePath = [ currentKey ];  
            var blockedRoutes = []          
            while( currentKey != home ) {
                var tile = this.spawnPoints[ currentKey ]
                var attemptOrder;
                if( currentKey < home ) {
                    /* Left then down */
                    attemptOrder = [ 'left', 'bottom', 'right', 'top' ]
                } else {
                    attemptOrder = [ 'left', 'top', 'right', 'bottom' ]
                }
                var moved = false;
                for( var i = 0; i < attemptOrder.length; i++ ) {
                    var direction = attemptOrder[ i ];
                    if( tile.walls[ direction ] != 'world' 
                        && this.activeLines.indexOf( tile.walls[ direction ] ) < 0
                        &&  tilePath.indexOf( tile.blockNeighbors[ direction ] ) < 0
                        &&  blockedRoutes.indexOf(tile.blockNeighbors[ direction ]) < 0 ) {
                        /* path is open and we are not retracing. Move and add this tile to that prereq */
                        // if( tileSpawnPoints[ tile.blockNeighbors[ direction ] ].before.indexOf( currentKey ) < 0 ) {
                        //     tileSpawnPoints[ tile.blockNeighbors[ direction ] ].before.push( currentKey );
                        // }
                        //tileSpawnPoints[ tile.blockNeighbors[ direction ] ]
                        tilePath.push( tile.blockNeighbors[ direction ] );
                        currentKey = parseInt(tile.blockNeighbors[ direction ]);
                        moved = true;
                        break;
                    }
                }
                if( !moved ) {
                    /* Start over but avoid that tile. */
                    blockedRoutes.push( currentKey )
                    currentKey = parseInt( key );
                    tilePath = [ currentKey ];
                } else if( currentKey == home ) {
                    //console.log('home found', key, tilePath )
                    this.tileSpawnPoints[ key ].pathHome = tilePath;
                    this.tileSpawnPoints[ key ].pathCount = tilePath.length;
                    /* TODO before so we don't block player. */
                    // for( var p = 0; p < tilePath.length; p++ ) {
                    //     if( this.tileSpawnPoints[ tilePath[ p ] ] )
                    // }
                    /* If words are not blocked, push to distance array */
                    if( neverActive.indexOf( parseInt( key ) ) < 0 ) {
                        this.distanceArray.push( this.tileSpawnPoints[ key ] );
                    }
                    break;
                }
            }
        }
        /* sort spawnArray by pathLength to determine furthest points */
        this.distanceArray.sort((a, b) => ( a.pathCount > b.pathCount ) ? -1 : 1 )
        this.spawnKeywords();     
    }

    spawnKeywords() {
        var maxTiles = 3;
        var placedTiles = 0;
        var fontSize = 38;
        var counter = 0;
        var oneCorrect = false;
        var activeTiles = []
        console.log(this.distanceArray);
        var loops = 0;
        while( placedTiles < maxTiles ) {
            loops++;
            var tile = this.distanceArray[ counter ];
            var key = tile.key;
            var spawnPoint = this.spawnPoints[ key ];
            var conflict = false;
            for( var i = 0; i < activeTiles.length; i++ ) {
                /* If this tile is in the way of another tile on the way to the player, skip */
                if( this.tileSpawnPoints[ activeTiles[ i ] ].pathHome.indexOf( key ) > -1 ) {
                    conflict = true;
                } else if( this.tileSpawnPoints[ key ].pathHome.indexOf( activeTiles[ i ] ) > -1 && loops < 200 ) {
                    /* If a placed tile is blocking this path, skip */
                    console.log('path blocked', key, activeTiles[ i ] )
                    conflict = true;
                }
            }
            if( !tile.lit && !conflict && activeTiles.indexOf( key ) < 0 ) {
                var keywordIndex;
                var word;
                if( ( placedTiles == maxTiles - 1 || counter >= this.distanceArray.length - 1 ) && !oneCorrect ) {
                    /* One must always be correct */
                    while( !oneCorrect ) {
                        keywordIndex = Math.floor(Math.random()*this.wordData.length);
                        word = this.wordData[ keywordIndex ];
                        if( word.correct ) {
                            oneCorrect = true;
                        }
                    }
                } else {
                    keywordIndex = Math.floor(Math.random()*this.wordData.length);
                    word = this.wordData[ keywordIndex ];
                }
                this.tileSpawnPoints[ key ].keywordIndex = keywordIndex;
                this.tileSpawnPoints[ key ].correct = word.correct;
                if( word.correct ) {
                    oneCorrect = true;
                }
                this.tileSpawnPoints[ key ].block = this.add.text( spawnPoint.x, spawnPoint.y, word.text, 
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
                    align: 'center',
                    padding: {
                        left: 15,
                        right: 15,
                        top: 10,
                        bottom: 10,
                    },
                    wordWrap: {
                        width: 360,
                        callback: null,
                        callbackScope: null,
                        useAdvancedWrap: false
                    }
                }).setOrigin(0.5,0.5).setDepth(1);
                this.physics.add.existing( this.tileSpawnPoints[ key ].block );
                this.tileSpawnPoints[ key ].block.body.setSize( this.tileSpawnPoints[ key ].block.body.sourceWidth * .5, this.tileSpawnPoints[ key ].block.body.sourceHeight * .5)
                this.tileSpawnPoints[ key ].block.body.setAllowGravity(false);
                this.tileSpawnPoints[ key ].block.body.onWorldBounds = true;
                this.tileSpawnPoints[ key ].block.name = key;
                this.keywordGroup.add( this.tileSpawnPoints[ key ].block );
                activeTiles.push( key );
                placedTiles++;
            }
            /* Always go up. */
            counter++;
            if( counter >= this.distanceArray.length )  {
                /* Out of tiles. For this game, unlight all and reset */
                for( var i = 0; i < this.distanceArray.length; i++ ) {
                    /* Check how this might be affecting the other render. */
                    this.distanceArray[ i ].lit = false;
                }
                counter = 0;
            }
            /* In case of a weird maze/block position, this aborts. */
            if( loops > 300 ) {
                break;
            }
        }
    }


    generateSpawnTiles() {
        var blocks = {}
        for( var i = 0; i < 25; i++ ) {
            blocks[ i ] = {
                blockNeighbors: {},
                x: ( 1920 / 10 ) + ( ( 1920 / 5 ) * ( i % 5 ) ),
                y: ( 1080 / 10 ) + ( ( 1080 / 5 ) * Math.floor( i / 5 ) ),
                walls: {}
            }
            /* Left */
            if( i - 1 >= 0 && i % 5 != 0 ) {
                blocks[i].blockNeighbors.left = i - 1;
                blocks[i].walls.left = ( ( i % 5 ) + ( ( 9 * Math.floor( i / 5 ) ) -1 ) )
            } else {
                blocks[i].blockNeighbors.left = 'world';
                blocks[i].walls.left = 'world';
            }
            /* Right */
            if( i + 1 <= 24 && (i+1) % 5 != 0 ) {
                blocks[i].blockNeighbors.right = i + 1;
                blocks[i].walls.right = ( ( i % 5 ) + ( 9 * Math.floor( i / 5 ) ) )
            } else {
                blocks[i].blockNeighbors.right = 'world';
                blocks[i].walls.right = 'world';
            }
            /* Top */
            if( i - 5 >= 0 ) {
                blocks[i].blockNeighbors.top = i - 5;
                // 24 block top should be 35
                blocks[i].walls.top = ( ( i % 5 ) + ( ( 9 * Math.floor( i / 5 ) ) -5 ) )
            } else {
                blocks[i].blockNeighbors.top = 'world';
                blocks[i].walls.top = 'world';
            }
            /* Bottom */
            if( i + 5 <= 24 ) {
                blocks[i].blockNeighbors.bottom = i + 5;
                blocks[i].walls.bottom = ( ( i % 5 ) + ( ( 9 * Math.floor( i / 5 ) ) +4 ) )
            } else {
                blocks[i].blockNeighbors.bottom = 'world';
                blocks[i].walls.bottom = 'world';
            }
        }
        this.spawnPoints = blocks
    }

    generateMaze() {
        this.activeLines = []
        /* 
        Based on Eller's algorithm but using columns for future row-running.
        https://www.emanueleferonato.com/2021/01/12/understanding-perfect-maze-generation-row-after-row-with-eller-algorithm-using-a-pure-javascript-class/
        */
        /**
         * Assign right and bottom where relevant to each spawnpoint, skipping world bounds.
         */
        this.maze = [];
        this.columnRegions = []
        this.regionCounter = 0;
        for( var col = 0; col < 5; col++ ) {
            this.generateMazeColumn( col );
        }
        var color = 0x111111 
        for( var i = 0; i < this.activeLines.length; i++ ) {
            var key = this.activeLines[i];
            if( this.mazeLines[key] ) { 
                if( this.verticals.indexOf(key) > -1 ) {
                    /* Vertical, 236 tall *///15, 231, 0x111111
                    var line = this.physics.add.sprite( this.mazeLines[key][0], this.mazeLines[key][1], 'wall' )
                    .setOrigin(0.5,0.5)
                    .setDisplaySize(15,231)
                    .setSize(15,216)
                    .setPipeline('Light2D');
                    line.body.setOffset(193,8)
                    line.body.setImmovable(true);
                    this.mazeZoneGroup.add( line );
                } else {
                    /* Horizontal, 404 wide *///399, 15, 0x111111 
                    var line = this.physics.add.sprite( this.mazeLines[key][0], this.mazeLines[key][1], 'wall' )
                    .setOrigin(0.5,0.5)
                    .setDisplaySize(399,15)
                    .setSize(281,15)
                    .setPipeline('Light2D')
                    /* Fix horizontal offset. TODO */
                    line.body.setOffset(12, 115 )
                    line.body.setImmovable(true); 
                    this.mazeZoneGroup.add( line );
                }
            }
        }
    }

    resetMaze() {
        //this.physics.world.removeCollider(this.mazeCollider);
        this.mazeZoneGroup.clear(true);
        this.generateMaze();
    }

    generateMazeColumn( col = 0, combinedCells = {} ) {
        this.columnRegions[ col ] = []
        /* Rows */
        this.maze[ col ] = []
        var currentCol = []
        var combinedCol = {}
        var combinedCells = {};
        /* Add 5 cells to the column */
        for( var cell = 0; cell < 5; cell++ ) {
            var key = col + ( 5 * cell );
            currentCol[ cell ] = this.spawnPoints[ key ];
            if( col > 0 ) {
                /* Determine if left wall is there */
                if( this.activeLines.indexOf( currentCol[ cell ].walls.left ) > -1 ) {
                    currentCol[ cell ].leftWall = true;
                } else {
                    /* Inherit region from cell with same index */
                    currentCol[ cell ].leftWall = false;
                    combinedCells[ cell ] = this.columnRegions[ col - 1 ][ cell ];
                }                
            }
        }
        /* Combine or wall. */
        for( var c = 0; c < currentCol.length; c++ ) {
            var index;
            if( Object.keys(combinedCells).indexOf( c.toString() ) > -1 ) {
                /* Added to a previous region */
                index = combinedCells[ c ];
                if( typeof combinedCol[ index ] === 'undefined' ) {
                    combinedCol[ index ] = {};
                }
            } else {
                /* New region */
                index = this.regionCounter;
                combinedCol[ index ] = {}; 
                this.regionCounter++;
            }
            /* Track index from previous column for future use. */
            this.columnRegions[ col ][ c ] = index;
            if( c >= currentCol.length - 1 ) {
                /* No wall */
                combinedCol[ index ][ c ] = currentCol[ c ]
                continue;
            }
            var toWall = false;
            if( Math.floor(Math.random() * 10) + 1 > 5 ) {
                toWall = true;
            } else if( col > 0 && c + 1 <= currentCol.length 
                && typeof combinedCells[ c + 1 ] !== 'undefined' && combinedCells[ c + 1 ] == index ) {
                /* If they are of the same region and neither has left, there must be a wall */
                /* TODO if merged region */
                toWall = true;
            }
            // else if( !toWall && )
            if( toWall ) {
                if( currentCol[ c ].walls.bottom != 'world' ) {
                    /* Check if next one shares a region if this is the last column */
                    if( col == 4 && ( typeof combinedCells[ c + 1 ] === 'undefined'
                        || combinedCells[ c + 1 ] != index ) ) {
                        /* Skip to avoid isolation */
                    } else {
                        this.activeLines.push( currentCol[ c ].walls.bottom );  
                    }
                }
                combinedCol[ index ][ c ] = currentCol[ c ]
            } else {
                /* Union */
                /* New region if not then it was already added. */
                combinedCol[ index ][ c ] = currentCol[ c ]
                /* Will add in the next round. */
                combinedCells[ c + 1 ] = index;
            }
        }
        /* Loop regions and add right walls */
        if( col < 4 ) {
            for( var region in combinedCol ) {
                var gapSet = false;
                var counter = 1;
                for( var cell in combinedCol[ region ] ) {
                    var toWall = true;
                    if( Math.floor(Math.random() * 10) + 1 < 6 ) {
                        toWall = false;
                    } else if( counter >= Object.keys(combinedCol[ region ]).length && !gapSet ) {
                        /* Requires a gap */
                        toWall = false;
                    }
                    /* We only need one per. */
                    if( toWall ) {
                        this.activeLines.push( combinedCol[ region ][ cell ].walls.right );
                    } else {
                        gapSet = true;
                    }
                    counter++;
                }
            }
        }
        this.maze[ col ] = combinedCol;
    }

    keywordHit(player, keyword){
        var tile = parseInt( keyword.name );
        this.keywordGroup.remove( keyword );
        var continueGame = true;
    	/* keyword.name links us to the tile. */
    	if( this.tileSpawnPoints[ keyword.name ].correct ) {
            this.score++;
    		this.tileSpawnPoints[ keyword.name ].block.setBackgroundColor('green');
            /* Good effect */
            this.tileSpawnPoints[ keyword.name ].lit = true;
            /* Expand light presence, test endgame */
            if( this.score >= this.winScore ) {
                continueGame = false;
                this.winGame();
            } else {
                this.updatePlayerLight();
            }
    	} else {
            /* Start Over. */
            this.score = Math.max( 0, this.score - this.penalty );
    		this.tileSpawnPoints[ keyword.name ].block.setBackgroundColor('red');
            /* Bad effect */
            /* Remove light boosts */
            for( var key in this.tileSpawnPoints ) {
                this.tileSpawnPoints[ key ].lit = false;
            }
            this.updatePlayerLight();
            this.attempts--;
            this.attemptsText.text = 'Attempts: '+this.attempts;
            if( this.attempts <= 0 ) {
                continueGame = false;
                this.loseGame();
            }
    	}
    	/* If there is text. */
    	if( this.tileSpawnPoints[ keyword.name ].keywordIndex > -1 ) {
    		this.wordData[ this.tileSpawnPoints[ keyword.name ].keywordIndex ].caught = true;
    	}
    	this.tileSpawnPoints[ keyword.name ].keywordIndex = -1;  
        /* Remove the text */
        var self = this;
       	window.setTimeout(function(){
            keyword.destroy();
            console.log('renew')
            /* Player position is the tile they just touched. */
            /* Relaunch answers */
            if( continueGame ) {
                self.keywordGroup.clear( true );
                self.generateSpawnPaths( tile );
            }
       	},1000)
    }

    winGame() {
        var self = this;
        /* Clear the words and the maze. */
        this.keywordGroup.clear( true );
        this.mazeZoneGroup.clear( true );
        /* Animate player light intensity to zero */
        var currentIntensity = this.playerLight.intensity;
        this.tweens.addCounter({
            from: currentIntensity,
            to: 0,
            duration: 750,
            onUpdate: function (tween)
            {
                self.playerLight.setIntensity( tween.getValue() );
            }
        });
        /* Animate board to full */
        this.tweens.addCounter({
            from: 0,
            to: 255,
            duration: 750,
            onUpdate: function (tween)
            {
                var rgb = Math.floor( tween.getValue() );
                var color = new Phaser.Display.Color( rgb, rgb, rgb );
                var hex = Phaser.Display.Color.RGBToString( color.r, color.g, color.b, color.a, '' );
                self.lights.setAmbientColor(hex);
            }
        });
        this.tweens.addCounter({
            from: this.progressLight,
            to: 100,
            duration: 750,
            onUpdate: function (tween)
            {
                self.lightText.text = "Light: " + Math.ceil( tween.getValue() ) + '%'; 
            }
        });
        window.setTimeout(function(){
            var text = self.parseEndgameHTML();
            hgSendMessage({ event: 'win', text : text });
        },4000);
    }

    loseGame() {
        var self = this;
        this.keywordGroup.clear( true );
        window.setTimeout(function(){
            var text = self.parseEndgameHTML();
            hgSendMessage({ event: 'lose', text : text });
        },2000);
    }

    parseEndgameHTML() {
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
        return caughtTerms;        
    }

    updatePlayerLight() {
        var self = this;
        var currentRadius = this.playerLight.radius;
        var newRadius = 200 + ( this.radiusInterval * this.score );
        this.tweens.addCounter({
            from: currentRadius,
            to: newRadius,
            duration: 500,
            onUpdate: function (tween)
            {
                var value = Math.floor(tween.getValue());
                self.playerLight.setRadius( value );
            }
        });
        var currentIntensity = this.playerLight.intensity;
        var newIntensity = 1 + ( this.intensityInterval * this.score );
        this.tweens.addCounter({
            from: currentIntensity,
            to: newIntensity,
            duration: 500,
            onUpdate: function (tween)
            {
                self.playerLight.setIntensity( tween.getValue() );
            }
        });
        /* Light text */
        var currentLight = this.progressLight;
        var newLight = 4 + ( this.progressInterval * this.score );
        this.progressLight = newLight;
        this.tweens.addCounter({
            from: currentLight,
            to: newLight,
            duration: 750,
            onUpdate: function (tween)
            {
                self.lightText.text = "Light: " + Math.ceil( tween.getValue() ) + '%'; 
            }
        });
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
            this.player.setVelocityY(-velocityY);  
        } else if(this.arrows.down.isDown){
            this.player.setVelocityY(velocityX);      
        } else {
            this.player.setVelocityY(0);               
        }
        if(this.player.body.velocity.x != 0 || this.player.body.velocity.y != 0){
            this.player.anims.play('run', true);
        } else{
            this.player.setTexture('idle');
        }
    }
}