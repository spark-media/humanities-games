<template>
	<div id="hg-my-games" class="hg-view">
		<Loader v-show="loading" />
		<template v-if="!loading">
    	<h2 class="greeting">My Games</h2>
    	<div class="game-blocks">
        <div class="game-block"v-for="game in games">
        	<span class="game-meta">
            <ul>
              <li v-if="game.link">
                <router-link :to="{ name: 'play-game', params: { id: game.id, site: game.site }, query : { title: game.title }}"><i class="fa-solid fa-gamepad"></i>Play</router-link>
              </li>
              <li v-show="game.owner">
                <router-link :to="{ name: 'edit-game', params: { id: game.id, site: game.site }}"><i class="fa fa-pencil"></i>Edit</router-link>
              </li> 
              <!-- <li v-show="game.owner">
                <a v-on:click="remixGame( game.id )"><i class="fa fa-record-vinyl"></i>Remix</a>
              </li> --> 
            </ul>
          </span>
          <h3>{{ game.title }}<span v-if="game.status=='draft'"><br />(draft)</span></h3>
        </div>
        <!-- Create -->
				<a @click="goToCreate" class="game-block">
        	<i class="fa-solid fa-plus"></i>
        	<h3>Create New Game</h3>
        </a>
      </div>
    </template>
	</div>
</template>

<script>
	export default {
    data () {
      return {
      	games: [],
				loading: false
      }
    },
    computed : {
    	hasGames() {
    		return ( this.games.length > 0 );
    	}
		},
		methods : {
			goToCreate() {
			  /* Forward to the create page. */
        this.$router.push({ 
        	path: '/games/create', 
        	query: { first: !this.hasGames }
        });
			},
	    async fetchData () {
	      	this.games = [];
	      	this.loading = true;
	      	var response = await this.$store.dispatch( 'sendRequest',
	      		{ path : '/list/me' }
	      	);
	        console.log(response);
	        if( typeof response.games !== 'undefined' && response.games.length > 0 ){
	          this.games = response.games;
	        }
	        this.loading = false;
	    },
	    async remixGame( id ) {
	    	/* Additional details. */
	    	if( confirm("Remixing will copy this game so you can make changes without affecting the original. Continue?") ) {
	    		this.loading = true;
	        try {
	        	var response = await this.$store.dispatch( 'sendRequest',
		      		{ 
		      			path : '/game/copy/' + id,
		      			method : 'POST',
		      			data : {}
		      		}
		      	);
	          console.log( response );
	          if( response.success == true ){
		          /* Refresh the game list. */
		          await this.fetchData();
		        } else if ( typeof response.message !== 'undefined' ) {
		        	alert( response.message );
		        	this.loading = false;
		        }
	        } catch( error ) {
	          this.loading = false;
	          console.log(error);
	        }
	    	}
	    }
		},
		created () {
		  this.fetchData();
		}
	}
</script>

<style scoped>

</style>