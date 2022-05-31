<template>
	<div id="hg-base">
		<a @click="$router.back()" v-show="$route.name!=='Dashboard'" class="back-button"><i class="fa fa-arrow-left"></i><span> Back</span></a>
		<a href="/wp-login.php?action=logout" class="logout-button"><span>Logout </span><i class="fa fa-arrow-right-from-bracket"></i></a> 
		<Loader v-show="loading" />
		<router-view />
		<p class="credit"><a href="https://humanities.games" target="_blank" class="hg">humanities.games</a> is supported by the <a href="https://neh.gov" target="_blank" class="neh">National Endowment for the Humanities</a>.</p>
	</div>
</template>

<script>
	export default {
        data () {
            return {
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
		        this.loading = false;
		    }
        },
        created() {
        	this.fetchData();
        }
    }
</script>

<style>
	#hg-base {
		box-sizing:  border-box;
		display: flex;
		flex-direction: column;
		min-height: 90vh;
		width:  100%;
		padding:  15px;
		text-align:  center;
		-webkit-font-smoothing: antialiased;
		position: relative;
	}
	#hg-base p {
		font-size: 17px;
		letter-spacing: 0.02em;
		font-weight: normal;
		line-height: 22px;
		color:  #fff;
	}
	#hg-base p.credit {
		font-size: 13px;
		line-height:  15px;
		margin-top:  20px;
	}
	#hg-base p.credit a.hg, #hg-base p.credit a.neh {
		color: #fff;
	}
	#hg-base p.credit a.hg {
		font-family: 'Architects Daughter', cursive;
		text-decoration: none;
	}
	#hg-base .back-button {
		color: #fff;
	    font-weight: bold;
	    text-transform: uppercase;
	    font-size: 10px;
	    position: absolute;
    	top: 15px;
    	left: 15px;
    	cursor:  pointer;
    	z-index:  5;
	}
	#hg-base .back-button span {
		position: relative;
    	top: -2px;
    	padding-left: 2px;
    	font-size: 11px;
	}
	#hg-base .back-button i {
		font-size:  15px;
	}
	#hg-base .logout-button {
		color: #fff;
	    font-weight: bold;
	    text-transform: uppercase;
	    font-size: 10px;
	    position: absolute;
    	top: 15px;
    	right: 15px;
    	cursor:  pointer;
    	z-index:  5;
    	text-decoration: none;
	}
	#hg-base .logout-button span {
		position: relative;
    	top: -2px;
    	padding-right: 2px;
    	font-size: 11px;
	}
	#hg-base .logout-button i {
		font-size:  15px;
	}
</style>