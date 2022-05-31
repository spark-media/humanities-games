<template>
	<div id="hg-site-games" class="hg-view">
		<Loader v-show="loading" />
		<template v-if="!loading">
    		<h2 class="greeting">{{ siteName }}</h2>
    		<div class="game-blocks">
        		<div class="game-block"v-for="game in games" v-show="hasGames">
        			<span class="game-meta">
            			<ul>
              				<li v-if="game.link">
                				<router-link :to="{ name: 'play-game', params: { id: game.id, site: game.site }, query : { title: game.title }}"><i class="fa-solid fa-gamepad"></i>Play</router-link>
              				</li>
              				<li v-show="game.owner">
                				<router-link :to="{ name: 'edit-game', params: { id: game.id, site: game.site }}"><i class="fa fa-pencil"></i>Edit</router-link>
              				</li>
            			</ul>
          			</span>
          			<h3>{{ game.title }}<span v-if="game.author"><br />by {{ game.author }}</span></h3>
        		</div>  
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
    		},
        	siteName() {
        		return 'Class'
        	}
        },
        methods : {
		    async fetchData () {
		      	this.games = [];
		      	this.loading = true;
		      	var response = await this.$store.dispatch( 'sendRequest',
		      		{ path : '/list/site' }
		      	);
		        console.log(response);
		        if( typeof response.games !== 'undefined' && response.games.length > 0 ){
		          this.games = response.games;
		        }
		        this.loading = false;
		    }
        },
        created() {
        	this.fetchData();
        }
    }
</script>

<style scoped>

</style>