<template>
	<div id="hg-dashboard" class="hg-view">
		<!-- Greetings -->
		<img src="https://assets.humanities.games/images/maestro-magnifier.svg" class="hg-magnifier" />
		<h2 class="greeting">Hi there!</h2>
		<p>Welcome to <em>humanities.games</em>, where you can make fun games to play that might just teach your friends and family something new about what you are learning, too.</p>
		<div class="dashboard-nav">
    		<router-link to="/games/me">Make Games</router-link>
    		<router-link to="/games/site">Play Class Games</router-link>
  		</div>
	</div>
</template>

<script>
	export default {
        data () {
            return {
            	view : 'maker',
            	games : [],
            	hasGames : false,
            	loading : true
            }
        },
        methods : {
        	async fetchData () {
        		var response = await this.$store.dispatch( 'sendRequest',
		      		{ path : '/init' }
		      	);
		        console.log( response );
		        /* For global use */
		        this.$store.commit( 'setupUser', response );
		        if( response.games.length > 0 ){
		          this.games = response.games;
		        }
		        this.loading = false;
		    }
        },
        created() {
        	//this.fetchData();
        }
    }
</script>

<style scoped>
	#hg-dashboard .hg-magnifier {
		max-width: 75px;
	}
	#hg-dashboard h2.greeting,#hg-dashboard h2.admin-controls {
		color:  #fff;
		font-size:  30px;
		letter-spacing: .05em;
		margin: 10px auto 20px auto;
		line-height:  32px;
	}
	#hg-dashboard h2.admin-controls {
		margin-top: 30px;
		margin-bottom: 10px;
	}
</style>