<template>
	<div id="hg-game-create" class="hg-view">
		<Loader v-show="loading" />
  	<img src="https://assets.humanities.games/images/harmony.svg" class="hg-harmony"  v-show="!loading"/>
  	<h2 class="greeting" v-show="!loading">Alright, time to make a game.</h2>
    <p v-show="!loading">
      To start, give your game a name.<br />You can make just one minigame, or put a few together for an even greater journey!
    </p>
    <form id="create-game" @submit.prevent="createGame" v-show="!loading">
      <div class="new-game-field">
        <span class="new-game-title" v-bind:class="{ 'has-content': newGameTitle }">
          <input v-model="newGameTitle" name="title" data-vv-name="title" id="title" type="text" placeholder="My game is called...">
        </span>
        <button class="button button-submit" v-bind:class="{ active: newGameTitle }" :disabled="!newGameTitle" type="submit">
          <template v-if="newGameTitle">
            Make it happen!
          </template>
          <template v-else>
            Name Your Game!
          </template>
        </button>
        <a @click="$router.back()" class="cancel" v-show="$route.query.first == 'false'">Cancel</a>
      </div> 
    </form>
	</div>
</template>

<script>
	export default {
		components : {},
    data () {
      return {
      	games: [],
				loading: false,
				newGameTitle: null
      }
    },
    computed : {
    	hasGames() {
    		return ( this.games.length > 0 );
    	}
		},
		methods : {
	    async createGame() {
        this.loading = true;
        try {
        	var response = await this.$store.dispatch( 'sendRequest',
	      		{ 
	      			path : '/game/create',
	      			method : 'POST',
	      			data : {
	      				title : this.newGameTitle
	      			}
	      		}
	      	);
          console.log( response );
          if( response.created === true ){
          	/* Site ID for created should match. */
            this.$router.push({ name: 'edit-game', params: { site: this.$store.getters.siteID, id: response.id }});
          }
        } catch(error) {
          this.loading = false;
          console.log(error);
        }
	    }
		}
	}
</script>

<style scoped>
	#hg-game-create .hg-harmony {
		max-width: 60px;
	}
	#hg-game-create .new-game-field {
		display:  flex;
		flex-direction: column;
    justify-content: center;
    align-items: center;
	}
	#hg-game-create .new-game-field input {
		margin-bottom:  10px;
		min-width: 300px;
		text-align: center;
		font-size:  20px;
	}
	#hg-game-create .button-submit {
		font-size: 20px;
	}
	#hg-game-create .button-submit.active {
		background: #1d6dac;
		color:  #fff;
		font-weight:  bold;
    border: solid 1px #333;
	}
	#hg-game-create .cancel {
		color:  #fff;
		display: inline-block;
		margin-top:  20px;
		cursor:  pointer;
	}
</style>