<template>
	<div id="hg-edit-game" class="hg-view">
		<Loader v-show="loading" />
		<template v-if="!loading">
			<!-- Greetings -->
     		<h2 class="greeting greeting-edit">{{ filteredTitle }}<span @click="editGameDetails" class="edit-game-details"><i class="fa fa-gear"></i></span></h2>
     		<p v-if="status == 'draft'" class="hg-game-status"><i class="fa-solid fa-arrow-right"></i><a @click="publishGame">Click here when your game is ready to share with the world!</a><i class="fa-solid fa-arrow-left"></i></p>
     		<p v-if="status == 'publish'" class="hg-game-status">Share: <a :href="link" target="_blank">{{ link }}</a></p>
     		<p>
		      Games are made of one or more minigames that the player must complete in order to progress.
		    </p>
			<div class="game-blocks">
		        <div class="game-block" v-for="( game, index ) in activeMinigames" v-show="activeMinigames.length > 0">
		        	<span class="game-meta">
		            <ul>
		              <li>
		                <router-link :to="{ name: 'play-game', params: { id: game.id, site: game.site }, query : { title: game.title }}"><i class="fa-solid fa-gamepad"></i>Play</router-link>
		              </li>
		              <li v-show="game.owner">
		                <router-link :to="{ name: 'edit-minigame', params: { id: game.id, site: game.site }}"><i class="fa fa-pencil"></i>Edit</router-link>
		              </li>
		              <li v-show="game.owner && activeMinigames.length > 1" @click="moveMinigameModal('active', game.id, index )">
		                <a><i class="fa fa-arrow-right-arrow-left"></i>Move</a>
		              </li>
		            </ul>
		          </span>
		          <h3>{{ game.title }}<span v-if="game.status=='draft'"><br />(draft)</span></h3>
		          <div class="order-label">{{ index + 1 }}</div>
		        </div>
		        <!-- Create -->
		        <a @click="goToCreate" class="game-block">
		        	<i class="fa-solid fa-plus"></i>
		        	<h3>Create New Minigame</h3>
		        </a>
		    </div>
		    <a @click="showInactive = true" v-if="inactiveMinigames.length > 0&&!showInactive" class="show-inactive">Show Inactive Minigames</a>
		    <template v-if="inactiveMinigames.length > 0&&showInactive">
		    	<hr>
		    	<h2 class="greeting">Inactive Minigames</h2>
		    	<p>You can still work on these, but the players won't be able to enjoy them until you add them into the order above.</p>
		    	<div class="game-blocks">
			    	<div class="game-block" v-for="game in inactiveMinigames">
			        	<span class="game-meta">
			            <ul>
			              <li>
			                <router-link :to="{ name: 'play-game', params: { id: game.id, site: game.site }, query : { title: game.title }}"><i class="fa-solid fa-gamepad"></i>Play</router-link>
			              </li>
			              <li v-show="game.owner">
			                <router-link :to="{ name: 'edit-minigame', params: { id: game.id, site: game.site }}"><i class="fa fa-pencil"></i>Edit</router-link>
			              </li>
			              <li v-show="game.owner" @click="moveMinigameModal('inactive', game.id )">
			                <a><i class="fa fa-arrow-right-arrow-left"></i>Move</a>
			              </li>
			            </ul>
			          </span>
			          <h3>{{ game.title }}<span v-if="game.status=='draft'"><br />(draft)</span></h3>
			        </div>
			    </div>
		    </template>
		    <div class="hg-modal" v-show="showModal">
		    	<div class="hg-modal-inner" v-show="showModal == 'details'">
		    		<div class="hg-modal-close" @click="closeMoveModal"><i class="fa fa-times"></i></div>
		    		<form id="edit-game-details" @submit.prevent="updateGameDetails">
		    			<h4>Update Details</h4>
				      	<div class="edit-game-field">
				        <span class="game-title" v-bind:class="{ 'has-content': newTitle }">
				          <input v-model="newTitle" name="title" data-vv-name="title" id="title" type="text" placeholder="My game is called...">
				        </span>
				        <div class="game-status">
				        	<select v-model="newStatus">
				        		<option value="publish">Publish</option>
				        		<option value="draft">Draft</option>
				        	</select>
				        </div>
				        <button class="button-submit" v-bind:class="{ active: newTitle }" :disabled="!newTitle" type="submit">
				          <template v-if="newTitle">
				            Update
				          </template>
				          <template v-else>
				            Name Your Game!
				          </template>
				        </button>
				      </div> 
				    </form>
		    	</div>
		    	<div class="hg-modal-inner" v-show="showModal == 'move'">
		    		<div class="hg-modal-close" @click="closeMoveModal"><i class="fa fa-times"></i></div>
		    		<button v-show="modalMinigameStatus == 'active'&&modalMinigameIndex > 0" @click="moveMinigame('before')">Make one level earlier</button>
		    		<button v-show="modalMinigameStatus == 'active'&&modalMinigameIndex < (activeMinigames.length - 1)" @click="moveMinigame('after')">Make one level later</button>
		    		<button v-show="modalMinigameStatus == 'active'" @click="moveMinigame('remove')">Remove from game order</button>
		    		<button v-show="modalMinigameStatus == 'inactive'" @click="moveMinigame('append')">Add to end of game order</button>
		    	</div>
		    </div>
		</template>
	</div>
</template>

<script>
	export default {
        data () {
            return {
            	loading : false,
            	title : '',
            	newTitle: '',
            	status: 'draft',
            	newStatus: 'draft',
            	link: '',
            	minigames : {},
            	order : [],
            	showInactive: false,
            	showModal: false,
            	modalMinigameStatus: null,
            	modalMinigameID: 0,
            	modalMinigameIndex: -1
            }
        },
        computed : {
        	filteredTitle () {
      			return ( this.title != '' ) ? this.title : '(untitled)';
    		},
    		hasMinigames() {
    			return ( Object.keys(this.minigames).length > 0 );
    		},
    		activeMinigames() {
    			var minigames = [];
    			for( var i = 0; i < this.order.length; i++ ) {
    				if( typeof this.minigames[ this.order[ i ] ] !== 'undefined' ) {
    					minigames.push( this.minigames[ this.order[ i ] ] );
    				}
    			}
    			return minigames;
    		},
    		inactiveMinigames() {
    			var minigames = [];
    			var createdGames = JSON.parse(JSON.stringify(this.minigames));
    			for( var id in this.minigames ) {
    				if( this.order.indexOf( id ) < 0 ) {
    					minigames.push( createdGames[ id ] )
    				}
    			}
    			console.log( minigames );
    			return minigames;
    		}
        },
        methods : {
        	async updateGameDetails() {
		    	try {
			      	this.loading = true;
			      	var response = await this.$store.dispatch( 'sendRequest',
			      		{ 
			      			path : '/games/update/' + this.$route.params.site + '/' + this.$route.params.id,
			      			method : 'POST',
			      			data : {
			      				title: this.newTitle,
			      				status: this.newStatus
			      			}
			      		}
			      	);
			        console.log( response );
			        alert("Your game has been updated.");
			        this.status = response.post_status;
			        this.title = response.title;
			        this.link = response.link;
			        this.closeMoveModal();
			    } catch( error ) {
			    	alert( error );
			    }
			    this.loading = false;
        	},
        	async publishGame() {
        		var ready = confirm("Ready to publish your game?");
        		if( ready ) {
			    	try {
				      	this.loading = true;
				      	var response = await this.$store.dispatch( 'sendRequest',
				      		{ 
				      			path : '/games/publish/' + this.$route.params.site + '/' + this.$route.params.id,
				      			method : 'POST' 
				      		}
				      	);
				        console.log( response );
				        this.status = response.post_status;
				        if( typeof response.link !== 'undefined' ) {
				        	this.link = response.link;
				        }
				        console.log(this.link);
				    } catch( error ) {
				    	alert( error );
				    }
				    this.loading = false;
        		}
        	},
        	editGameDetails() {
        		/* Popup with game title and status. */
        		this.newTitle = this.title;
        		this.newStatus = this.status;
        		this.showModal = 'details';
        	},
        	async moveMinigame( action ) {
        		/* Before, after, remove, append */
        		try {
        			this.showModal = false;
			      	this.loading = true;
			      	var response = await this.$store.dispatch( 'sendRequest',
	      				{ 
	      					path : '/games/order/' + this.$route.params.site + '/' + this.$route.params.id,
	      					method : 'POST',
	      					data : {
	      						action : action,
	      						mid : this.modalMinigameID
	      					}
	      				}
	      			);
			        console.log( response );
			        this.order = response.order;
			    } catch( error ) {
			    	alert( error );
			    }
			    this.closeMoveModal();
			    this.loading = false;
        	},
        	moveMinigameModal( currentStatus, minigameID, index = -1 ) {
        		console.log( currentStatus, minigameID );
        		this.modalMinigameStatus = currentStatus;
        		this.modalMinigameID = minigameID;
        		this.modalMinigameIndex = index;
        		this.showModal = 'move';
        	},
        	closeMoveModal() {
        		this.modalMinigameStatus = null;
        		this.modalMinigameID = 0;
        		this.modalMinigameIndex = -1;
        		this.showModal = false;
        	},
		    async fetchData () {
		    	try {
			      	this.loading = true;
			      	var response = await this.$store.dispatch( 'sendRequest',
			      		{ path : '/games/' + this.$route.params.site + '/' + this.$route.params.id }
			      	);
			        console.log( response );
			        this.title = response.title;
			        this.status = response.post_status;
			        this.link = response.link;
			        this.minigames = response.minigames;
			        this.order = response.order;
			    } catch( error ) {
			    	alert( error );
			    }
			    this.loading = false;
		    },
		    goToCreate() {
		    	/* Forward to the create page. */
		        this.$router.push({ 
		        	path: '/minigames/create/' + this.$route.params.site + '/' + this.$route.params.id,
		        	query: { first: !this.hasMinigames, gameTitle: this.filteredTitle  }
		        });
		    }
        },
        created() {
        	this.fetchData();
        }
    }
</script>

<style>

	hr {
		border-color: rgba(240,240,240,.1);
    	border-width: 1px;
    	border-bottom: none;
    	margin: 30px auto;
	}
	.show-inactive {
		display:  block;
		margin-top:  20px;
		color:  #fff;
		cursor:  pointer;
	}
	.button-submit {
		font-size: 20px;
		padding:  10px 15px;
	}
	.button-submit.active {
		background: #1d6dac;
		color:  #fff;
		font-weight:  bold;
    	border: solid 1px #333;
	}
	.hg-game-status {
		margin: 0;
    	font-size: 15px;
	}
	#hg-app p.hg-game-status {
	    margin: 0;
	    font-size: 15px;
	    font-weight: bold;
	    margin-bottom: 30px;
	}
	p.hg-game-status i {
	    padding: 0 10px;
	    color: #ffc107;
	    font-size: 24px;
	    line-height: 15px;
	    position: relative;
	    top: 4px;
	}
	p.hg-game-status a {
		color:  #fff;
		cursor:  pointer;
		word-break:  break-word;
	} 
	#hg-app #edit-game-details h4 {
		font-size:  25px;
	}
	.game-status {
		width: 50%;
		min-width: 200px;
    	margin-bottom: 20px;
	}
	.game-status select {
		width:  100%;
	}
</style>