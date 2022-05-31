<template>
	<div id="hg-minigame-create">
  	<img src="https://assets.humanities.games/images/maestro-birds.svg" class="hg-harmony" />
  	<h2 class="greeting">Create Minigame{{ filteredTitle }}</h2>
  	<Loader v-show="loading" />
  	<div class="game-blocks" v-show="!loading">
        <div class="game-block" v-for="( game, slug ) in minigames">
        	<span class="game-meta">
            <ul>
               <li>
                <router-link :to="{ name: 'play-demo', params: { id: slug, site: 'demo' }, query : { title: game.name }}"><i class="fa-solid fa-gamepad"></i>Demo</router-link>
              </li>
              <li>
                <a @click="createMinigame( slug )"><i class="fa fa-plus"></i>Add to Game</router-link>
              </li>
            </ul>
          </span>
          <h3>{{ game.name }}</h3>
        </div>
      </div>
	</div>
</template>

<script>
	export default {
		components : {},
    data () {
      return {
				loading: false,
				minigames: []
      }
    },
    computed : {
    	filteredTitle() {
    		return ( this.$route.query.gameTitle ) ? ' for ' + this.$route.query.gameTitle : '';
    	}
    },
		methods : {
			async fetchData () {
	    	try {
		      	this.loading = true;
		      	var response = await this.$store.dispatch( 'sendRequest',
		      		{ path : '/minigames/options' }
		      	);
		        console.log( response );
		        this.minigames = response.minigames;
		    } catch( error ) {
		    	alert( error );
		    }
		    this.loading = false;
	    },
	    async createMinigame( type ) {
        this.loading = true;
        try {
        	var data = {
        		type : type
        	}
        	/* If this is going to be attached to a game right away. */
        	if( this.$route.params.id && this.$route.params.id > 0 ) {
        		data.gameID = this.$route.params.id
        		data.siteID = this.$route.params.site
        	}
        	var response = await this.$store.dispatch( 'sendRequest',
	      		{ 
	      			path : '/minigame/create',
	      			method : 'POST',
	      			data : data
	      		}
	      	);
          console.log( response );
          if( response.created === true ){
          	/* Replace so we go back to the game overview. */
          	this.$router.replace({ name: 'edit-minigame', params: { site: this.$route.params.site, id: response.id }});
          }
         	this.loading = false;
        } catch(error) {
          this.loading = false;
          console.log(error);
        }
	    }
		},
		created() {
			this.fetchData();
		}
	}
</script>

<style scoped>
	#hg-minigame-create {
		margin: auto;
		max-width:  90%;
	}
	#hg-minigame-create .hg-harmony {
		max-width: 120px;
	}
	#hg-minigame-create h2.greeting {
		color:  #fff;
		font-size:  30px;
		letter-spacing: .05em;
		margin: 10px auto 20px auto;
		line-height:  32px;
	}
	#hg-minigame-create .new-game-field {
		display:  flex;
		flex-direction: column;
    justify-content: center;
    align-items: center;
	}
	#hg-minigame-create .new-game-field input {
		margin-bottom:  10px;
		min-width: 300px;
		text-align: center;
		font-size:  20px;
	}
	#hg-minigame-create .button-submit {
		font-size: 20px;
	}
	#hg-minigame-create .button-submit.active {
		background: #1d6dac;
		color:  #fff;
		font-weight:  bold;
    border: solid 1px #333;
	}
	#hg-minigame-create .cancel {
		color:  #fff;
		display: inline-block;
		margin-top:  20px;
		cursor:  pointer;
	}
</style>