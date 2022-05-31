<template>
	<div id="hg-play-game">
		<!-- <Loader v-show="loading" /> -->
		<div class="hg-player-wrap">
			<div class="hg-player-inner">
				<iframe :src="iframeURL" class="hg-player" frameborder="0" allowfullscreen></iframe>
			</div>
		</div>
		<h3 v-if="$route.query.title">{{ $route.query.title }}</h3>
	</div>
</template>

<script>
	export default {
		props: ['id', 'site', 'title'],
        data () {
            return {
            	loading : true,
            	minigames : [],
            	minigameOrder : []
            }
        },
        computed : {
        	iframeURL() {
        		return '/hg-embed/' 
        		+ this.$route.params.site
        		+ '/' + this.$route.params.id
        	}
        },
        methods : {
		    async fetchData () {
		    	try {
			      	this.loading = true;
			      	var response = await this.$store.dispatch( 'sendRequest',
			      		{ path : '/games/' + this.$route.params.site + '/' + this.$route.params.id }
			      	);
			        console.log(response);
			        this.title = response.title;
			        this.minigames = response.minigames;
			    } catch( error ) {
			    	this.loading = false;
			    	alert( error );
			    }
		    }
        },
        created() {
        	//this.fetchData();
        }
    }
</script>

<style scoped>
	#hg-play-game {
		max-width: 100%;
    	margin: 30px 0;
	}
	#hg-play-game h2.greeting {
		color:  #fff;
		font-size:  30px;
		letter-spacing: .05em;
		margin: 10px auto 20px auto;
	}
	h3 {
		color:  #fff;
		font-size:  20px;
		letter-spacing:  0.02em;
	} 
	.dashboard-nav a {
		display: inline-block;
	    color: #fff;
	    padding: 15px;
	    text-decoration: none;
	    font-size: 20px;
	    background: #1d6dac;
	    margin: 10px;
	    border-radius: 5px;
	}
	.hg-player-wrap {
		width:  100%;
		max-width:  900px;
		margin:  0 auto;
	}
	.hg-player-inner {
  		position: relative;
  		padding-bottom: 56.25%; /* 16:9 */
  		height: 0;
	}
	.hg-player-inner iframe {
  		position: absolute;
  		top: 0;
  		left: 0;
  		width: 100%;
  		height: 100%;
	}

</style>