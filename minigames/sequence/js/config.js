const config = {
    type: Phaser.CANVAS,
    scale: {
        mode: Phaser.Scale.FIT,
        parent: 'gamespace',
        autoCenter: Phaser.Scale.CENTER_BOTH,    
        width: 1920,
        height: 1080
    },      
    physics: {
        default: 'arcade', 
        arcade: {
            debug: true
        }
    },
    scene: [GameJS],
    backgroundColor: "#000",
};

var game;
hgSetupValues(function(){
    game = new Phaser.Game(config);
});
