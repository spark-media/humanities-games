/*!
 * Deep merge two or more objects together.
 * (c) 2019 Chris Ferdinandi, MIT License, https://gomakethings.com
 * @param   {Object}   objects  The objects to merge together
 * @returns {Object}            Merged values of defaults and options
 */
const deepMerge = function () {
	// Setup merged object
	var newObj = {};
	// Merge the object into the newObj object
	var merge = function (obj) {
		for (var prop in obj) {
			if (obj.hasOwnProperty(prop)) {
				// If property is an object, merge properties
				if (Object.prototype.toString.call(obj[prop]) === '[object Object]') {
					newObj[prop] = deepMerge(newObj[prop], obj[prop]);
				} else {
					newObj[prop] = obj[prop];
				}
			}
		}
	};
	// Loop through each object and conduct a merge
	for (var i = 0; i < arguments.length; i++) {
		merge(arguments[i]);
	}
	return newObj;
};

/* Global structure */
var gameData = {
	text: {},
	images: {},
	sounds: {},
	count: {},
	options: {}
}

/* Async helper function */
function hgAjax(url, params, fn) {
	var method = (typeof params !== 'undefined' && typeof params.method !== 'undefined') ? params.method : 'GET';
	var data = (typeof params !== 'undefined' && typeof params.data !== 'undefined') ? params.data : {};
	var request = new XMLHttpRequest();
	request.open(method, url, true);
	request.setRequestHeader('Content-Type', 'application/json; charset=UTF-8');
	request.onload = function() {
	  if (this.status >= 200 && this.status < 400) {
	    var response = JSON.parse(this.response);
	    fn({ success: true, data: response });
	  } else {
		var resp = this.response;
		console.log('failure',resp);
		throw 'Load Error';
	  }
	};
	request.onerror = function(error) {
	  throw 'Load Error';
	};
	if(method == 'POST') {
		var sendData = JSON.stringify(data);
		request.send(sendData);
	} else {
		request.send();
	}
}

/* Helper that retrieves from the gameData obj */
function hgFetchValue( props, obj ) {
	/* Defaults to gameData for first level */
	var obj = (typeof obj !== 'undefined') ? obj : gameData;
	/* Nested property can retrive */
  	const prop = props.shift();
  	if (!obj[prop] || !props.length) {
    	return obj[prop]
  	}
  	return hgFetchValue(props, obj[prop])
}

function hgGet( key ) {
	var props = key.split('.');
	if( props.length > 0 ) {
		var prop = hgFetchValue( props );
		if( typeof prop !== 'undefined' ) {
			if( typeof prop.custom !== 'undefined' 
				&& typeof prop.use_custom !== 'undefined'
				&& prop.use_custom == true ) {
				/* http/s bugfix */
				if( typeof prop.custom.file !== 'undefined' 
					&& prop.custom.file 
					&& prop.custom.file.indexOf( '//' + window.location.host ) > -1 ) {
					var file = prop.custom.file.split( window.location.host );
					if( file.length == 2 ) {
						/* Root relative, protocol agnostic. */
						prop.custom.file = file[ 1 ]
					}
				}
				return prop.custom
			} else if( typeof prop.default !== 'undefined') {
				return prop.default;
			} else {
				/* Not found */
				return null;
			}
		} else {
			return undefined
		}
	} else {
		/* Check with if(runner != null) {} */
		return null;
	}
}

/**
 * hgSetupValues wraps all "game start" logic
 * It fetches defaults.json and merges
 * with what is sent by the parent iframe.
 */
const hgReady = new Event('hgReady');
var dataSource;
function hgSetupValues(fn) {
    /* Fetch the json files */
    try {
    	/* Create the request */
        window.addEventListener('hgReady', function (e) {
            hgAjax( 'defaults.json', 
                { method: 'GET'}, function(defaults){
                /* Merges the two objects */
                gameData = deepMerge( gameData, defaults.data, dataSource );
                /* Let the ball roll. */
                fn();
            });
        }, false);
        /* Trigger the parent */
        hgSendMessage({ event: 'load' });
    } catch(e) {
        console.log('Error',e);
    }
}

/**
 * The parent iframe sends data, parsed here.
 */
var hgMessageResponse = null;
window.addEventListener('message', hgHandleMessage, false);
function hgHandleMessage( event ) {
    try {
        var message = JSON.parse( event.data );
        if( message.source == 'hg-embed' ) {
        	switch( message.action ) {
        		case 'load' :
        			dataSource = message.data;
            		window.dispatchEvent( hgReady );
        		break;
        		case 'pause' :
        			window.dispatchEvent( hgPause );
        		break;
        		case 'resume' :
        			window.dispatchEvent( hgResume );
        		break;
        		case 'response' :
        			if( hgMessageResponse ) {
        				hgMessageResponse( message );
        				hgMessageResponse = null;
        			}
        		break;
        		default :
        			console.log('Uncaught iframe event', message );
        		break;
        	}
        }
    } catch( e ) {
        // fail silent for other events.
    }
}

/**
 * To send to the parent iframe
 */
function hgSendMessage( data, fn = null ) {
	parent.postMessage(
        JSON.stringify( data )
    );
	if( fn ) {
		hgMessageResponse = fn;
	}
}

/**
 * Game Listeners
 */
const hgPause = new Event('hgPause');
const hgResume = new Event('hgResume');
const hgResponse = new Event('hgResponse');
function hgListen( key, fn ) {
	window.addEventListener( key, function (e) {
		fn( e );
    }, false);
}

/* Required for all games */
var hgGameText = {
    controls : '',
    title : '',
    winTitle : '',
    winText : '',
    loseTitle : '',
    loseText : '',
    intro : ''
}
function hgStart() {
	for( var prop in hgGameText ) {
		var result = hgGet('text.' + prop)
		if( typeof result !== 'undefined' ) {
			hgGameText[ prop ] = result;
		}
	}
    hgSendMessage({ 
        event: 'ready', 
        text : hgGameText
    });
}

/**
 * Helpers
 */

/**
 * For randomizing art
 */
var hgArtCycler = {};
function hgArtCycle( key, source = null ) {
	/* Get total count of options */
	if( source ) {
		var start = 0;
		var offset = 1;
		var total = source.length;
	} else {
		var start = 1;
		var offset = 0;
		var total = hgGet( 'count.' + key );
	}
	if( typeof hgArtCycler[ key ] === 'undefined' ) {
		/* Start */
		hgArtCycler[ key ] = start;
	} else {
		/* Total is stored as length, index starts at 1 unless array */
		if( hgArtCycler[ key ] >= (total - offset) ) {
			hgArtCycler[ key ] = start;
		} else {
			/* Increment and return. */
			hgArtCycler[ key ] ++;
		}
	}
	return hgArtCycler[ key ];
}

