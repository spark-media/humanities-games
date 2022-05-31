var score;
var drawnCards;
var deck1;
var deck2;
var deck3;
var decks;
var maxDrawn;
var cardsActive = false;
var gameAssets = {};

$(document).ready(function () {
	/* jQuery is ready to go. */
	hgSetupValues(function(){



		/* Game title */
		var gameTitle = hgGet('text.title');
		$('#header').children('p').text(gameTitle);

		score = 0;
		drawnCards = 0;
		maxDrawn = hgGet('options.maxDrawn'); //get our card limit, offset due to index at 0
		$('#turn').children('p').text("Remaining Cards: " + (maxDrawn - drawnCards));//set the text for the card limit

		var gameDecks = hgGet('options.decks');

		//create the dom elements for the decks
		var deckWrapper = document.getElementById('deck-wrapper');
		var totalDecks = gameDecks.length;
		deckWrapper.classList.add( 'deck-total-' + totalDecks );
		var styleData = ''
		for(var i = 0; i<gameDecks.length; i++){
			var singleDeckWrap = document.createElement('div');
			singleDeckWrap.className = 'deck-wrap';
			var newDeck = document.createElement('div');
			newDeck.className += 'deck';
			var data = new Deck();
			var nameEl = document.createElement('p');
			nameEl.textContent = gameDecks[i].name;
			
			if( gameDecks[i].image ) {
				styleData += "#gamespace .deck-wrap:nth-of-type("+(i+1)+") .deck:before{background-image: url('"+gameDecks[i].image.file+"');}";
			}
			

	
			for(var c=0; c < gameDecks[i].cards.length; c++){
				var cardData = gameDecks[i].cards[c];
				data.addCard( cardData );
			}

			data.shuffle();
			newDeck.myDeck = data;
			singleDeckWrap.appendChild(newDeck);
			singleDeckWrap.appendChild(nameEl);
			deckWrapper.appendChild(singleDeckWrap);
		}

		/* Background */
		var background = hgGet('images.background');
		var styleElem = document.head.appendChild(document.createElement("style"));
		styleElem.innerHTML = "#gamespace:after {background-image: url('"+background.file+"');}" + styleData;

		$('.deck').click(function(){	
			if(drawnCards < maxDrawn){
				if( cardsActive ) {
					readCard(this.myDeck, this);
				}		
			}else{
				console.log("Game Over");		
			}
		})
		$(document).on( 'click', '#continue-game', function(e){
			/* True forces the endgame */
			cardsActive = true;
			e.stopPropagation();
		});
		$(document).on( 'click', '#end-game', function(e){
			/* True forces the endgame */
			endGame(true);
			e.stopPropagation();
		});	
		preloadImages(function(){
			console.log('loaded');
			hgSendMessage({ event: 'prepare' });
			/* animate the background. */
			window.setTimeout(function(){
				console.log('launch-1')
				$('#gamespace').addClass('launch-1');
				window.setTimeout(function(){
					console.log('launch-2');
					$('#gamespace').addClass('launch-2');
					window.setTimeout(function(){
						hgStart();
						window.setTimeout(function(){
							cardsActive = true;
						},500)
					}, gameDecks.length * 600 );
			 	}, 4000 );
			}, 1000 );
		})
	});
});




function readCard(_deck, _dom){	
	if(_deck.topCard < _deck.library.length){		
		let card = _deck.library[_deck.topCard];
		var infoText = '<div class="hg-card-title-wrap" style="display: flex;align-items: center;justify-content: space-around;">'
		if( card.title.length > 0 ) {
			infoText += '<h2 style="max-width: 50%;padding: 0 20px;">' + card.title + '</h2>'
		}

		if( card.image ) {
			infoText += '<img src="' + card.image.file + '" style="max-width: 50%; display: block; margin: 5px;border-radius: 10px;overflow: hidden;border: solid 3px #111;" />';
		}
		infoText += '</div>';
		infoText += card.content;
		/* Points */
		infoText += '<p class="points">';
		if( card.points > 0 ) {
			infoText += '+'
		}
		infoText += card.points + '</p>';
		/* Update screen */
		if(drawnCards < maxDrawn-1){
			score += _deck.library[_deck.topCard].points;
			$('#score').children('p').text("Score: " + score);	
		}
		_deck.topCard++;
		drawnCards++;
		cardsActive = false;
		$('#turn').children('p').text("Remaining Cards: " + (maxDrawn - drawnCards));		
		if(_deck.topCard == _deck.library.length){
			console.log("hit the limit " , _dom);
			$(_dom).css("opacity", '.3'); //gray out
		}
		hgSendMessage({ event: 'info', text: infoText }, function( response ){
			cardsActive = true;
			console.log('from parent');
			endGame();
		})
	} else {
		console.log("no more cards in deck");
	}
}


function endGame(force = false){
	console.log(drawnCards, maxDrawn, 'end-test')
	if( drawnCards >= maxDrawn || force ){		
		$('.deck').each(function(){ //gray out the buttons to signify they are disabled
			$(this).css("opacity", '.3');			
		})
		setTimeout(function(){
			var winScore = hgGet('options.winScore');
			if(score >= winScore ){	
				hgSendMessage({ event: 'win', score: score });
			}else{	
				hgSendMessage({ event: 'lose', score: score });		
			}	
		}, 500);
	}
}

function preloadImages( fn ){
	var loaded = 0;
	console.log('loading assets')             
    for( var imageID in gameData.images ) {		
		var asset = hgGet( 'images.' + imageID );                       
        if( asset != null ) {	
            gameAssets[ 'images.' + imageID ] = asset.file
            var img = new Image();
			img.onload = function () {
				console.log('loaded', loaded, Object.keys(gameData.images).length );
			   	loaded++
			   	if( loaded >= Object.keys(gameData.images).length ) {
			   		console.log('done')
			   		fn();
			   	}
			}
			img.src = asset.file;
		}
    }
}
