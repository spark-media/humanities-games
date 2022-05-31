<?php
/**
 * Displays a game with fixed parameters. Intended for embedding.
 * $site - the multisite where the game lives.
 * $game - the game
 * $minigame - optional - just plays a single minigame 
 * $demo - if a default minigame is being previewed
 */
$demo = false;

/**
 * Overview:
 * This screen is loaded as a middle iframe.
 * It loads other iframes which are the individual games.
 * This allows for multiple minigames to coexist, even if
 * they use different and/or incompatible game engines.
 */
function get_game() {

	$response = array(
		'title' => 'Game'
	);

	$site_id = intval(get_query_var( 'hgsite' ));
	$game_id =  intval(get_query_var( 'hgame' ));

  /**
   * Retrieve the game, then check if either
   * A. User is game owner, or
   * B. Game is published status and set to public in meta
   * User IDs are unique network-wide.
   */
  if( get_current_blog_id() != $site_id ) {
    /* Change to correct blog. */
    switch_to_blog( $site_id );
  }

  $post = get_post( $game_id );
  if ( empty( $post ) ) {
    /* Return */
    return $response;
  }

  /* Check the post type */
  if( $post->post_type == 'hminigame' ) {

  	/* Single minigame */
  	$response = load_minigame( $response, $post );

  } else {

  	/* Game */
  	$response = load_game( $response, $post );

  }
  /* Return */
  return $response;
}

function load_minigame( $response, $post ) {
	/* Get the game data */
	$type = get_post_meta( $post->ID, 'type', true );
	$data = get_post_meta( $post->ID, 'minigame_data', true );
	if( $data ) {
		$game_setup = $data;
	} else {
		$game_setup = array();
	}
	$game_setup[ 'type' ] = $type;
	
	/* Add the title */
	$game_setup = load_title( $game_setup, $post->post_title );
	$response['gameData'] = array( $game_setup );
	
	/* For the loader screen */
	$response[ 'title' ] = $post->post_title;
	$response[ 'minigameIDs' ] = array( $post->ID );
	return $response;
}

function load_title( $game, $title = '' )  {
	if( !isset( $game[ 'text' ] ) ) {
		$game[ 'text' ] = array();
	}
	if( !isset( $game[ 'text' ][ 'title' ] ) ) {
		$game[ 'text' ][ 'title' ] = array();
	}
	/* Always override the title if it exists */
	$game[ 'text' ][ 'title' ][ 'custom' ] = $title;
	$game[ 'text' ][ 'title' ][ 'use_custom' ] = true;
	return $game;
}

function load_game( $response, $post ) {
  /* TODO can this game be played? */
  /* See if owner */
  // $continue = false;
  // if( $post->post_author != $this->user->ID ) {
  //   /* See if visibility is set to public */
  //   if( metadata_exists( 'hgame', $post->ID, 'visibility' ) ) {
  //     if( get_post_meta( $post->ID, 'visibility', true ) == 'public' && $post->post_status == 'publish' ) {
  //       /* It is published and public. */
  //       $continue = true;
  //     }
  //   }
  // } else {
  //   /* Owner */
  //   $continue = true;
  // }

  // if( ! $continue ) {
  //   /* Return */
  //   return $this->return_data( $response );
  // }


  /**
   * Minigames save connected games as an array (so they can appear in multiple)
   */
  $args = array(
    'post_type' => 'hminigame',
    'posts_per_page' => -1,
    'meta_query' => array(
      array(
        'key'     => 'in_hgame',
        'value'   => serialize( strval( $post->ID ) ),
        'compare' => 'LIKE'
      )
    )
  );
  $minigames = array();
  $query = new WP_Query( $args );
  while( $query->have_posts() ) : $query->the_post();
    /* Retrieve all minigames. */
    $mid = get_the_ID();
    $minigames[ $mid ]['title'] = html_entity_decode( get_the_title() );
    $minigames[ $mid ][ 'type' ] = get_post_meta( $mid, 'type', true );
    $minigames[ $mid ][ 'site' ] = $site_id;
    $minigames[ $mid ][ 'id' ] = $mid;
    $counter ++;
  endwhile;

  /* Apply our response */
  foreach ( $response as $key => $prop ) {
    if( isset( $post->{ $key } ) ) {
      $response[ $key ] = $post->{ $key };
    }
  }
  $response[ 'title' ] = $post->post_title;
  $response[ 'minigames' ] = $minigames;
  $response[ 'minigameIDs' ] = array();
  $response['gameData'] = array();
  $minigame_order = get_post_meta( $post->ID, 'minigame_order', true );
  if( $minigame_order  ) {
  	$response[ 'minigameIDs' ] = $minigame_order;
  	foreach( $response[ 'minigameIDs' ] as $mini_id ) {
  		$mid = intval( $mini_id );

  		/* Single minigame */
  		$minigame_post = get_post( $mid );
  		$minigame = load_minigame( $response, $minigame_post );
  		array_push( $response['gameData'], $minigame['gameData'][ 0 ] );
  		//array_push( $response['gameData'], $data );
  		//get_post_meta( $post->ID, 'minigame_order', true );
  		/* For now, empty */
  		// array_push( $response['gameData'], array(
  		// 	'type' => $minigames[ $mid ][ 'type' ]
  		// ));
  	}
  }
  return $response;
}

function get_demo_title( $type = '' ) {
	$response = array();
	$response[ 'title' ] = 'Game Demo';
	$minigame = HG_API::fetch_game_type( $type );
  if( $minigame && isset( $minigame->title ) ) {
    $response[ 'title' ] = $minigame->title;
  }
  return $response;
}

/* An array to hold arrays of custom game data. */
$game_data = array();

if( get_query_var( 'hgsite' ) == 'demo' ) {
	/* Demo for a minigame. */
	$demo = get_query_var( 'hgame' );
	$game = get_demo_title( $demo );
	/* Empty object, no overrides. */
	array_push( $game_data, array(
		'type' => $demo
	) );
} else {
	$game = get_game();
	/* populate $game_data */
	if( isset( $game['gameData'] ) ) {
		$game_data = $game['gameData'];
	}

}

?><!doctype html>
<html lang="en">
<head>
  	<meta charset="utf-8">
  	<meta name="viewport" content="width=device-width, initial-scale=1">
  	<title><?php echo $game['title']; ?></title>
  	<meta name="robots" content="noindex, nofollow">
  	<link href="https://fonts.googleapis.com/css2?family=Architects+Daughter&family=Roboto:ital,wght@0,100;0,400;1,900&display=swap" rel="stylesheet">
  	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  	<style>
  		html, body {
  			margin:  0;
  			background:  black;
  			color:  #fff;
  			font-family: 'Roboto', sans-serif;
  			-webkit-font-smoothing: antialiased;
  		}

  		.hg-error-wrap {
		    width: 100%;
  			display:  flex;
  			align-items: center;
  			justify-content: center;
  		}
  		h2.hg-error {
  			font-family: 'Roboto', sans-serif;
  			text-align:  center;
  			font-weight: normal;
    		letter-spacing: 0.02em;
  		}
  		iframe {
  			border:  0;
  			position: fixed;
  			top:  0;
  			left:  0;
  			width:  100%;
  			height:  100%;
  		}
  		#hg-game-mask {
  			position:  fixed;
  			top:  0;
  			left:  0;
  			width:  100%;
  			height:  100%;
  			background: #000;
  			visibility:  hidden;
  			opacity:  0;
  			transition:  opacity 300ms linear;
  			z-index:  10;
  			display: flex;
   			align-items: center;
    		justify-content: center;
  		}
  		#hg-game-mask.active {
  			opacity:  1;
  			visibility:  visible;
  		}
  		.hg-loading {
  			display:  none;
  		}
	    .hg-loading.active {
	    	display:  inline-block;
            position: relative;
            width: 80px;
            height: 80px;
            margin: 10px auto;
        }
        .hg-loading div {
            position: absolute;
            border: 4px solid #fff;
            opacity: 1;
            border-radius: 50%;
            animation: hg-ripple 1s cubic-bezier(0, 0.2, 0.8, 1) infinite;
        }
        .hg-loading div:nth-child(2) {
          animation-delay: -0.5s;
        }
        @keyframes hg-ripple {
          0% {
            top: 36px;
            left: 36px;
            width: 0;
            height: 0;
            opacity: 1;
          }
          100% {
            top: 0px;
            left: 0px;
            width: 72px;
            height: 72px;
            opacity: 0;
          }
        }
        #hg-game-mask .hg-replay {
        	font-size:  40px;
        	color:  #fff;
        	display:  none;
        }
        #hg-game-mask .hg-replay.active {
        	display: block;
        }
        #hg-embed-game-controls {
        	z-index: 2;
			    position: fixed;
			    top: 5px;
			    left: 5px;
			    background: #575656;
			    display: flex;
			    justify-content: center;
			    align-items: center;
			    cursor:  pointer;
			    font-size: 3vw;
    			padding: 1vw 2vw;
    			border-radius: 3vw;
        }
        #hg-embed-view-controls {
					z-index: 20;
			    position: fixed;
			    bottom: 0;
			    left: 0;
			    width: 100%;
			    height: 30px;
			    
			    display: flex;
			    align-items: center;
			    justify-content: flex-end;
			    padding: 0;
			    box-sizing: border-box;
        }
        #hg-embed-view-controls h5 {
        	justify-self: flex-start;
    			flex: 1;
    			font-family: 'Architects Daughter', cursive;
    			font-size:  13px;
        }
        #hg-embed-view-controls a {
        	text-decoration: none;
			    display: inline-block;
			    background: rgba(0,0,0,.2);
			    padding: 5px 10px;
			    color: #fff;
        }
        #hg-title-screen {
				  background: linear-gradient(135deg, #2d393f, #0f1b20);
				  position: fixed;
				  height: 100%;
				  width:  100%;
				  overflow: hidden;
				  top:  0;
				  left:  0;
				  z-index:  20;
				  align-items: center;
				  justify-content: center;
				  flex-direction: column;
				  display:  none;
				}
				#hg-title-screen.active {
					display: flex;
				}

				#hg-game-title {
				  color: #fff;
				  font-size: 45px;
				  z-index: 1;
				  font-family: 'Architects Daughter', cursive;
				  margin-bottom:  15px;
				  margin-top:  0;
				}

				.hg-start-game {
					z-index: 1;
			    padding: 10px 20px;
			    color: #ddd;
			    background: #111;
			    border: solid 1px #222;
			    border-radius: 3px;
			    letter-spacing: 0.02em;
			    font-size: 15px;
			    text-transform: uppercase;
			    font-weight: bold;
			    cursor:  pointer;
			    transition:  background,border 100ms linear;
				}
				.hg-start-game span {
					padding-left:  10px;
					padding-right:  10px;
					position: relative;
    			top: -2px;
				}
				.hg-start-game i {
					color:  #fff;
					font-size:  20px;
					transition: color 100ms linear;
				}
				.hg-start-game:hover {
					background: #222;
			    border: solid 1px #333;
				}
				.hg-start-game:hover i {
					color:  rgba(255, 193, 7, 1);
				}

				.hg-squares{
			    position: absolute;
			    top: 0;
			    left: 0;
			    width: 100%;
			    height: 100%;
			    overflow: hidden;
			    margin:  0;
			    padding:  0;
				}

				.hg-squares li{
			    position: absolute;
			    display: block;
			    list-style: none;
			    width: 20px;
			    height: 20px;
			    background: rgba(255, 193, 7, 0.2);
			    animation: hg-animate 25s linear infinite;
			    bottom: -150px; 
				}

				.hg-squares li:nth-child(1){
			    left: 25%;
			    width: 80px;
			    height: 80px;
			    animation-delay: 0s;
				}


				.hg-squares li:nth-child(2){
			    left: 10%;
			    width: 20px;
			    height: 20px;
			    animation-delay: 2s;
			    animation-duration: 12s;
				}

				.hg-squares li:nth-child(3){
			    left: 70%;
			    width: 20px;
			    height: 20px;
			    animation-delay: 4s;
				}

				.hg-squares li:nth-child(4){
			    left: 40%;
			    width: 60px;
			    height: 60px;
			    animation-delay: 0s;
			    animation-duration: 18s;
				}

				.hg-squares li:nth-child(5){
			    left: 65%;
			    width: 20px;
			    height: 20px;
			    animation-delay: 0s;
				}

				.hg-squares li:nth-child(6){
			    left: 75%;
			    width: 110px;
			    height: 110px;
			    animation-delay: 3s;
				}

				.hg-squares li:nth-child(7){
			    left: 35%;
			    width: 150px;
			    height: 150px;
			    animation-delay: 7s;
				}

				.hg-squares li:nth-child(8){
			    left: 50%;
			    width: 25px;
			    height: 25px;
			    animation-delay: 15s;
			    animation-duration: 45s;
				}

				.hg-squares li:nth-child(9){
			    left: 20%;
			    width: 15px;
			    height: 15px;
			    animation-delay: 2s;
			    animation-duration: 35s;
				}

				.hg-squares li:nth-child(10){
			    left: 85%;
			    width: 150px;
			    height: 150px;
			    animation-delay: 0s;
			    animation-duration: 11s;
				}

				@keyframes hg-animate {
				    0%{
			        transform: translateY(0) rotate(0deg);
			        opacity: 1;
			        border-radius: 0;
				    }
				    100%{
			        transform: translateY(-1000px) rotate(720deg);
			        opacity: 0;
			        border-radius: 50%;
				    }
				}
				/* Embed responsive */
				#hg-game-title {
			  	font-size: 6vw;
				}
				#hg-info-box-wrap {
					font-size: 3.2vw;
					position:  fixed;
					width:  100%;
					height:  100%;
					z-index:  1;
					align-items: center;
					justify-content: center;
					display:  none;
				}
				#hg-info-box-wrap.active {
					display:  flex;
					backdrop-filter: blur(5px);
					background: rgba(0,0,0,.3);
				}
				#hg-info-box {
					width:  80vw;
					max-height:  70vh;
					background: linear-gradient(135deg, rgba(45, 57, 63,.98), rgba(15, 27, 32,.98));
					text-align: center;
					border-radius: 15px;
					padding:  30px;
					padding-top:  5px;
					overflow-x:  hidden;
					overflow-y:  scroll;
				}
				#hg-info-box::-webkit-scrollbar {
          display:  none;
        }
				#hg-info-box p {
					font-size: 2vw;
					line-height: 2.5vw;
				}
				#hg-info-box h1, #hg-info-box h2, #hg-info-box h3, #hg-info-box h4, #hg-info-box h5, #hg-info-box h6 {
					font-family: 'Architects Daughter', cursive;
					margin-bottom:  5px;
				}
				#hg-info-box h2 {
					margin-top:  10px;
					margin-bottom:  10px;
				}
				.hg-info-box-inner {
    			padding: 15px;
				}
				#hg-info-box-info,#hg-info-box-controls,#hg-info-box-options {
					display:  none;
				}
				#hg-info-box-info.active,#hg-info-box-controls.active,#hg-info-box-options.active {
					display:  block;
				}
				#hg-info-box-info .hg-start-game {
					margin-top: 20px;
				}
				#hg-info-box ul.hg-info-box-menu {
					margin: 0;
					padding:  0;
					display: flex;
    			justify-content: space-around;
				}
				#hg-info-box ul.hg-info-box-menu li {
			    list-style-type: none;
			    font-family: 'Architects Daughter', cursive;
			    margin: 5px 15px;
			    cursor:  pointer;
				}
				#hg-info-box ul.hg-info-box-menu li.active,#hg-info-box ul.hg-info-box-menu li:hover {
					position:  relative;
					display: block;
				}
				#hg-info-box ul.hg-info-box-menu li.active:before,#hg-info-box ul.hg-info-box-menu li:hover:before {
					content:  '';
					position:  absolute;
					top: 0;
			    bottom: 0;
			    left: auto;
			    right: auto;
			    width: 100%;
			    height: 90%;
			    display: block;
			    border-bottom: solid 2px #fff;
			    padding-bottom: 0px;
				}
				#hg-info-box ul.hg-info-box-menu li a {
					color:  #fff;
					text-decoration: none;
				}
				
				#hg-info-box .hg-close-info-box {
					position: absolute;
    			top: 5px;
    			right: 15px;
				}
				#hg-info-box-info .hg-start-game.hg-continue, 
				#hg-info-box-info .hg-start-game.hg-retry, 
				#hg-info-box-info .hg-start-game.hg-quit {
					display:  none;
				}
				#hg-info-box-info.hg-playing .hg-start-game.hg-continue, #hg-info-box-info.hg-win .hg-start-game.hg-continue {
					display:  inline-block;
				}
				#hg-info-box-info.hg-lose .hg-start-game.hg-retry, 
				#hg-info-box-info.hg-lose .hg-start-game.hg-quit {
					display:  inline-block;
				}
				#hg-info-box #hg-final-score p, #hg-info-box p.points {
					font-weight: bold;
					font-size: 2.7vw;
					line-height: 2.9vw;
				}
				#hg-info-box .hg-quiz-question {
					font-weight: bold;
    			font-size: 2.7vw;
    			line-height: 2.9vw;
    			margin-top: 0;
				}
				#hg-info-box .hg-quiz-answers {
					margin: 0 auto;
    			padding: 0;
    			max-width: 75%;
    			display: flex;
    			flex-wrap: wrap;
    			justify-content: center;
    			flex-direction: column;
    			align-items: center;
				}
				#hg-info-box .hg-quiz-answers li {
					list-style-type:  none;
			    font-size: 2.3vw;
			    margin-bottom: 20px;
			    line-height: 2.4vw;
			    display: inline;
			    word-break: break-all;
			    border: 1px solid #a9a5a5;
			    background: #222;
			    padding: 8px 15px;
			    border-radius: 5px;
			    cursor: pointer;
				}
				#hg-info-box #hg-info-box-content a {
    			color: #9ce0e9;
				}
  	</style>
</head>
<body>
	<div id="hg-title-screen" class="active">
		<!-- 
			Inspired by https://codepen.io/mohaiman/pen/MQqMyo
			MIT license available at link.
		-->
    <ul class="hg-squares">
      <li></li>
      <li></li>
      <li></li>
      <li></li>
      <li></li>
      <li></li>
      <li></li>
      <li></li>
      <li></li>
      <li></li>
    </ul>
    <h1 id="hg-game-title"><?php echo $game['title']; ?></h1>
		<?php if( ( !isset( $game['minigameIDs'] ) || count( $game['minigameIDs'] ) < 1 ) && ! $demo ) : ?>
			<div class="hg-error-wrap">
				<h2 class="hg-error">No minigames added yet!</h2>
			</div>
		<?php else : ?>
			<button class="hg-start-game" onclick="hgStartGame()"><i class="fa-solid fa-gamepad"></i> <span>Start</span> <i class="fa-solid fa-gamepad"></i></button>
		<?php endif; ?>
	</div>
	<div id="hg-game-mask">
		<div class="hg-loading active" id="hg-loading">
			<div></div>
			<div></div>
		</div>
		<a class="hg-replay" onclick="hgReplay()" id="hg-replay">
			<i class="fa-solid fa-rotate-left"></i>
		</a>
	</div>
	<div id="hg-embed-view-controls">
		<h5><a href="https://humanities.games" target="_blank">humanities.games</a></h5>
		<a onclick="hgFullscreen()"><i id="hg-fullscreen-icon" class="fa-solid fa-expand"></i></a>
	</div>
	<div id="hg-info-box-wrap">
		<div id="hg-info-box">
			<a onclick="hgToggleMenu()" class="hg-close-info-box" id="hg-close-info-box"><i class="fa fa-close"></i></a>
			<ul class="hg-info-box-menu">
				<li class="active" id="hg-info" onclick="hgChangeInfoTab('info')">Info</li>
				<li id="hg-controls" onclick="hgChangeInfoTab('controls')">Controls</li>
				<li onclick="hgChangeInfoTab('options')" id="hg-options">Options</li>
			</ul>
			<div id="hg-info-box-info" class="hg-info-box-inner">
				<div id="hg-info-box-content">

				</div>
				<div id="hg-final-score"></div>
				<button class="hg-start-game hg-continue" onclick="hgContinueGame()"><i class="fa-solid fa-gamepad"></i> <span>Continue</span> <i class="fa-solid fa-gamepad"></i></button>
				<button class="hg-start-game hg-retry" onclick="hgRetry()"><i class="fa-solid fa-gamepad"></i> <span>Retry</span> <i class="fa-solid fa-gamepad"></i></button>
				<button class="hg-start-game hg-quit" onclick="hgQuitGame()"><span>Quit Game</span> <i class="fa fa-arrow-right-from-bracket"></i></button>
			</div>
			<div id="hg-info-box-controls" class="hg-info-box-inner"></div>
			<div id="hg-info-box-options" class="hg-info-box-inner">
				<br />
				<button class="hg-start-game hg-retry" onclick="hgRetry()"><i class="fa-solid fa-arrows-rotate"></i> <span>Restart</span></button>
				<button class="hg-start-game" onclick="hgQuitGame()"><span>Quit Game</span> <i class="fa fa-arrow-right-from-bracket"></i></button>
			</div>
		</div>
	</div>
<!-- 	<div id="hg-share-box">

	</div> -->
		<a id="hg-embed-game-controls" onclick="hgToggleMenu()">
			<i class="fa-solid fa-info"></i>
		</a>
  	<script>
  		var minigameToLoad = 0;
  		var introAnimation = false;
  		var menuActive = false;
  		var gameEnded = false;
  		var gameText = {};
  		var gameDefaultText = {
		    controls : '',
		    title : '',
		    winTitle : '',
		    winText : '',
		    loseTitle : '',
		    loseText : '',
		    intro : ''
			}
  		var gameData = <?php echo json_encode( $game_data ); ?>;
  		console.log(gameData);
  		var iframe = document.createElement('iframe');
  		var mask = document.getElementById('hg-game-mask');
  		var loader = document.getElementById('hg-loading');
  		var replay = document.getElementById('hg-replay');
  		var titleScreen = document.getElementById('hg-title-screen');
  		var infoBoxContent = document.getElementById('hg-info-box-content');
  		var infoBoxInfo = document.getElementById('hg-info-box-info');
  		var controlBoxContent = document.getElementById('hg-info-box-controls');
  		var infoBoxInner = document.getElementById('hg-info-box');
  		var infoBox = document.getElementById('hg-info-box-wrap');
  		var finalScore = document.getElementById('hg-final-score');
  		var closeBox = document.getElementById('hg-close-info-box');
  		function launchNextMinigame() {
  			gameEnded = false;
  			infoBoxInfo.classList.add('hg-playing');
  			infoBoxInfo.classList.remove('hg-win');
  			infoBoxInfo.classList.remove('hg-lose');
  			var gameType = gameData[ minigameToLoad ].type;
  			iframe.src = '/wp-content/plugins/humanities-games/minigames/'+gameType+'/'+gameType+'.html';
  			infoBoxContent.innerHTML ="";
  			controlBoxContent.innerHTML = ""
  			finalScore.innerHTML = ""
  			closeBox.style.display = "block";
  		}
  		function hgReplay() {
  			titleScreen.classList.add('active');
    		iframe.src = null;
    		minigameToLoad = 0;
  			replay.classList.remove('active');
  		}
  		iframe.frameborder = 0;
  		iframe.allowfullscreen = true;
  		iframe.id = 'hg-player';
			document.body.appendChild( iframe );
			window.addEventListener('message', handleMessage, false);
        function handleMessage( event ) {
            try {
              var message = JSON.parse( event.data );
              switch( message.event ) {
               	case 'win' :
                	gameEnded = true;
                	infoBoxInfo.classList.remove('hg-playing');
                	infoBoxInfo.classList.add('hg-win');
                	/* Gameplay tasks could be triggered here. */
                	var winText = gameText.winText
                	if( gameText.winTitle.length > 0 ) {
                		winText = '<h2>' + gameText.winTitle + '</h2>' + winText
                	}
                	infoBoxContent.innerHTML = winText;
                	if( typeof message.text !== 'undefined' ) {
                		/* Extra text (e.g. found items) */
                		infoBoxContent.innerHTML += message.text;
                	}
                	if( typeof message.score !== 'undefined' ) {
                		finalScore.innerHTML = '<p>Score: ' + message.score + '</p>';
                	}
                	closeBox.style.display = "none"
                	hgToggleMenu()
                break;
                case 'lose' :
                	gameEnded = true;
                	infoBoxInfo.classList.remove('hg-playing');
                	infoBoxInfo.classList.add('hg-lose');
                	var loseText = gameText.loseText
                	if( gameText.loseTitle.length > 0 ) {
                		loseText = '<h2>' + gameText.loseTitle + '</h2>' + loseText
                	}
                	infoBoxContent.innerHTML = loseText;
                	if( typeof message.text !== 'undefined' ) {
                		/* Extra text (e.g. found items) */
                		infoBoxContent.innerHTML += message.text;
                	}
                	if( typeof message.score !== 'undefined' ) {
                		finalScore.innerHTML = '<p>Score: ' + message.score + '</p>';
                	}
                	closeBox.style.display = "none"
                	hgToggleMenu()
                break;
                case 'info' :
                	/* In-game alert */
                	if( typeof message.text !== 'undefined' ) {
                		/* Knowledge */
                		infoBoxContent.innerHTML = message.text;
                	}
                	/* Swap text back after seen. */
                	hgToggleMenu(function(){
                		/* So the game knows */
                		hgSendMessage({ action: 'response' });
              			infoBoxContent.innerHTML = '<h2>' + gameText.title + '</h2>' + gameText.intro;
              		})
              	break;
              	case 'quiz' :
              		hgQuizPopup( message );
              	break;
              	case 'prepare':
              		/* Remove the loader but don't toggle menu yet. */
              		/* Games with intro animations use this. */
              		mask.classList.remove('active');
              	break;
              	case 'ready' :
                	for( var prop in gameDefaultText ) {
                		if( typeof message.text !== 'undefined'
                			&& typeof message.text[ prop ] !== 'undefined' ) {
                			gameText[ prop ] = message.text[ prop ];
                		} else {
                			gameText[ prop ] = gameDefaultText[ prop ];
                		}
                	}
                	mask.classList.remove('active');
                	/* Preload Text */
                	controlBoxContent.innerHTML = gameText.controls;
                	infoBoxContent.innerHTML = '<h2>' + gameText.title + '</h2>' + gameText.intro;
                	/* 
                	Intro text. 
                	Game should pause at launch, by default.
                	*/
                	window.setTimeout(function(){
                		hgToggleMenu();
                	},1000);
                break;
                case 'load' :
                console.log(gameData);
                	hgSendMessage({ 
      							action: 'load',
      							data : gameData[ minigameToLoad ]
      						});
      					break;
      					default :
      						console.log('Uncaught message from game.');
      					break;
              }
            } catch( e ) {
                // fail silent for other events.
            }
        }


        var hgActiveQuestion = null;
        function hgChooseAnswer( index ) {
        	var correct = hgActiveQuestion.answers[ index ].correct;
        	infoBoxInfo.classList.add('hg-playing');
        	hgToggleMenu();
        	infoBoxContent.innerHTML = '<h2>' + gameText.title + '</h2>' + gameText.intro;
        	closeBox.style.display = "block"
        	hgActiveQuestion = null;
        	window.setTimeout(function(){
	        	hgSendMessage({ 
	    				action: 'response',
	    				correct: correct
	    			})
	        },10)
        }

        function hgQuizPopup( message ) {
        	/* In-game alert */
        	closeBox.style.display = "none"
        	hgActiveQuestion = message.question;
        	/* Shuffle */
        	hgActiveQuestion.answers = hgShuffleArray( hgActiveQuestion.answers );
        	infoBoxInfo.classList.remove('hg-playing');
        	var html = ''
        	html = '<p class="hg-quiz-question">' +hgActiveQuestion.question + '</p><ul class="hg-quiz-answers">'
        	for( var i = 0; i < hgActiveQuestion.answers.length; i++ ) {
        		html += '<li onclick="hgChooseAnswer('+ i +')">'+ hgActiveQuestion.answers[ i ].text +'</li>';
        	}
        	html += '</ul>'
        	infoBoxContent.innerHTML = html;
        	hgToggleMenu();
        }

        /**
         * http://sedition.com/perl/javascript-fy.html
         */
        function hgShuffleArray(array) {
				  let currentIndex = array.length,  randomIndex;
				  // While there remain elements to shuffle...
				  while (currentIndex != 0) {
				    // Pick a remaining element...
				    randomIndex = Math.floor(Math.random() * currentIndex);
				    currentIndex--;
				    // And swap it with the current element.
				    [array[currentIndex], array[randomIndex]] = [
				      array[randomIndex], array[currentIndex]];
				  }
				  return array;
				}

        /**
         * Message wrapper
         */
        function hgSendMessage( data ) {
        	data.source = 'hg-embed';
        	iframe.contentWindow.postMessage(
	          	JSON.stringify( data )
      		);
        }

        function hgContinueGame() {
        	if( gameEnded ) {
          	minigameToLoad ++;
          	if( minigameToLoad >= gameData.length ) {
							loader.classList.remove('active');
							replay.classList.add('active');
							mask.classList.add('active');
							hgToggleMenu();
						} else {
          		mask.classList.add('active');
          		window.setTimeout(function(){
          			/* TODO fix replay bug with menu. */
          			hgToggleMenu();
          			launchNextMinigame();
          		}, 300);
          	}
        	} else {
        		hgToggleMenu();
        	}
        }

        function hgRetry() {
        	gameEnded = false;
      		//menuActive = false;
          mask.classList.add('active');
      		window.setTimeout(function(){
      			hgToggleMenu();
      			hgChangeInfoTab('info');
      			launchNextMinigame();
      		}, 300);
        }

        function hgQuitGame() {
        	hgChangeInfoTab('info');
        	titleScreen.classList.add('active');
      		iframe.src = null;
      		minigameToLoad = 0;
      		gameEnded = false;
      		menuActive = false;
        }
        /**
         * Menu Functions
         */
        var hgToggleCB = null;
        function hgToggleMenu( fn = null ) {
        	if( ! menuActive ) {
        		menuActive = true;
        		hgSendMessage({ 
      				action: 'pause'
      			})
      			hgChangeInfoTab('info');
      			infoBox.classList.add('active');
      			infoBoxContent.scrollTop = 0;
      			infoBoxInner.scrollTop = 0;
      			if( fn ) {
      				hgToggleCB = fn;
      			}
        	} else {
        		infoBox.classList.remove('active');
        		menuActive = false;
        		iframe.contentWindow.focus();
        		hgSendMessage({ 
      				action: 'resume'
      			})
      			if( hgToggleCB ) {
      				/* Execute */
      				hgToggleCB();
      				hgToggleCB = null;
      			}
        	}
        }

        function hgChangeInfoTab( tabName = 'info' ) {
        	var menuItems = document.querySelectorAll('.hg-info-box-inner');
        	var menuLinks = document.querySelectorAll('ul.hg-info-box-menu li');
        	var newItem = document.getElementById('hg-info-box-'+tabName );
        	var newActiveLink = document.getElementById('hg-'+tabName );
					Array.prototype.forEach.call(menuItems, function(item, i){
						item.classList.remove('active');
					});
					newItem.classList.add('active');
					Array.prototype.forEach.call(menuLinks, function(item, i){
						item.classList.remove('active');
					});
					newActiveLink.classList.add('active');
        }
        /**
         * In case of failure
         */
        function userTryAgain( loseText = 'You Lose.' ) {
					var tryAgain = confirm( loseText +"\n\nDo you want to try again?");
					if( tryAgain ) {
					  return true;
					} else {
					  return false;
					}
        }

        function hgStartGame() {
	        mask.classList.add('active');
      		window.setTimeout(function(){
      			titleScreen.classList.remove('active');
      			launchNextMinigame();
      		}, 300);
        }

        // const hgFullScreenAvailable = document.fullscreenEnabled || 
        //                     document.mozFullscreenEnabled ||
        //                     document.webkitFullscreenEnabled ||
        //                     document.msFullscreenEnabled

        /* For now iOS devices are tricky with fullscreen, so we open in a new tab instead. */
        const hgFullScreenAvailable = document.fullscreenEnabled
        if( ! hgFullScreenAvailable ) {
        	var icon = document.getElementById('hg-fullscreen-icon');
        	icon.classList.remove('fa-expand');
        	icon.classList.add('fa-arrow-up-right-from-square');
        }

        function hgIsFullscreen(){
        	if (
					  document.fullscreenElement || /* Standard syntax */
					  document.webkitFullscreenElement || /* Safari and Opera syntax */
					  document.msFullscreenElement /* IE11 syntax */
					) {
        		return true;
        	} else {
        		return false;
        	}
				}

				function hgLaunchFullscreen(){
					  if (document.body.requestFullscreen) {
					    document.body.requestFullscreen();
					  } else if (document.body.webkitRequestFullscreen) { /* Safari */
					    document.body.webkitRequestFullscreen();
					  } else if (document.body.msRequestFullscreen) { /* IE11 */
					    document.body.msRequestFullscreen();
					  }
				}

        function hgExitFullscreen(){
      	  if (document.exitFullscreen) {
				    document.exitFullscreen();
				  } else if (document.webkitExitFullscreen) { /* Safari */
				    document.webkitExitFullscreen();
				  } else if (document.msExitFullscreen) { /* IE11 */
				    document.msExitFullscreen();
				  }
				}

        function hgFullscreen() {
        	if( hgFullScreenAvailable ) {
	        	var icon = document.getElementById('hg-fullscreen-icon');
	        	if( ! hgIsFullscreen() ) {
	        		hgLaunchFullscreen()
	        		icon.classList.remove('fa-expand');
	        		icon.classList.add('fa-compress');
	        	} else {
	        		hgExitFullscreen();
	        		icon.classList.remove('fa-compress');
	        		icon.classList.add('fa-expand');
	        	}
	        } else {
	        	var open = confirm( 'This will restart the game in a dedicated tab. Continue?' )
	        	/* Open the game in a dedicated tab ( ipad / iphone / Safari ) */
	        	if( open ) {
	        		window.open( window.location.href ,'_blank');
	        	}
	        }
        }
  	</script>
</body>
</html>
