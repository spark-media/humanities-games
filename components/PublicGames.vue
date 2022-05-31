<template>
	<div id="hg-public-games" class="hg-view">
		<!-- Greetings -->
		Public Games
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
		      	var response = await fetch('/wp-json/humanities-games/v2/init');
		      	response = await response.json();
		        console.log(response);
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
	#hg-public-games .hg-magnifier {
		max-width: 75px;
	}
</style>