<template>
	<div id="hg-edit-minigame" class="hg-view">
		<Loader v-show="loading" />
		<div v-show="!loading">
			<!-- Greetings -->
     		<h2 class="greeting greeting-edit">{{ filteredTitle }}<span @click="editMinigameDetails" class="edit-game-details"><i class="fa fa-gear"></i></span></h2>
     		<p class="minigame-template" v-if="defaults.title">Template: {{ defaults.title }}
     			<template v-if="defaults.packet">
     			  |  <a :href="defaults.packet" target="_blank" >View Packet</a>
     			</template>
     		</p>
     		<div class="hg-form-box" v-show="Object.keys(form).length > 0">
	     		<ul class="hg-form-box-menu">
					<li :class="{ active: activeTab == tab.label }" @click="changeTab( tab.label )" v-for="tab in form.tabs">{{ tab.label }}</li>
					<li @click="saveMinigame" class="hg-save" v-show="isDirty"><i class="fa-solid fa-floppy-disk"></i></li>
					<li class="hg-save" v-show="!isDirty"><router-link :to="{ name: 'play-game', params: { id: $route.params.id, site: $route.params.site }, query : { title: title }}"><i class="fa-solid fa-gamepad"></i></router-link></li>
				</ul>
				<div class="hg-form-box-inner" v-for="tab in form.tabs" v-show="activeTab == tab.label">
					<div v-for="section in tab.sections" class="hg-section-wrap">
						<h3 v-if="section.label">{{ section.label }}</h3>
						<p class="hg-field-description" v-if="section.description && section.description.length > 0"> {{ section.description }}</p>
						<div v-for="field in section.fields" class="hg-field-wrap" :class="'hg-field-wrap-'+field.type">
							<div class="hg-use-custom-wrap" v-if="field.type != 'page-group'">
								<p class="hg-use-custom-text">
									{{ customText( field.key ) }}
								</p>
								<label class="hg-switch">
	  								<input type="checkbox" v-model="customData[ field.key + '.use_custom' ]">
	  								<span class="hg-slider hg-round"></span>
								</label>
							</div>
							<div class="hg-field">
								<label class="hg-label" v-if="field.label &&field.type != 'boolean'" :class="{'hg-border': !field.description || field.description.length < 1}">
									{{ field.label }}
								</label>
								<p class="hg-field-description" v-if="field.description && field.description.length > 0"> {{ field.description }}</p>
								<!-- Field Types -->
								<!-- WYSIWYG -->
								<WYSIWYG :content="customData[ field.key + '.custom' ]" :field="field.key" @change="updateWYSIField" v-if="field.type == 'wysiwyg' && customData[ field.key + '.use_custom' ]" />
								<!-- Text -->
								<input type="text" v-model="customData[ field.key + '.custom' ]" v-if="field.type == 'text' && customData[ field.key + '.use_custom' ]" class="hg-text-field">
								<!-- Number -->
								<input type="number" v-model="customData[ field.key + '.custom' ]" v-if="field.type == 'number' && customData[ field.key + '.use_custom' ]" class="hg-text-field">
								<!-- Boolean -->
								<div class="hg-boolean" v-if="field.type == 'boolean'">
									<input class="hg-checkbox" type="checkbox" v-model="customData[ field.key + '.custom' ]" />
									<label class="hg-label hg-checkbox-label">
										{{ field.label }}
									</label>
								</div>
								<!-- Images -->
								<div class="hg-page-group" v-if="field.type == 'page-group'">
									<div class="hg-page-assets" :class="'hg-page-assets-'+field.fields.length">
        								<div class="hg-page-asset" v-for="(asset,index) in field.fields">
        									<div class="hg-use-custom-wrap">
												<p class="hg-use-custom-text">
													{{ customText( asset.key ) }}
												</p>
												<label class="hg-switch">
					  								<input type="checkbox" v-model="customData[ asset.key + '.use_custom' ]">
					  								<span class="hg-slider hg-round"></span>
												</label>
											</div>
											<div v-if="! customData[ asset.key + '.use_custom' ] || customData[ asset.key + '.custom' ]" class="hg-image-wrap">
        										<img :src="pageImage( asset.key )" v-if="pageImage( asset.key )" />
                   	 						</div>
                   	 						<div class="hg-upload-controls" v-if="!pageImage( asset.key )">
                   	 							<button class="hg-upload-button" @click="openPageUpload( field.page, 'images', asset.singleKey )">Upload Page</button>
                   	 							<p>or</p>
                   	 							<button class="hg-upload-button" @click="openMediaView( asset.key )">Choose File</button>
                   	 						</div>
                   	 						<h5 v-if="asset.label">{{ asset.label }}</h5>
                   	 						<a @click="openReplaceModal( asset.key, field.page, 'images' )" v-show="customData[ asset.key + '.use_custom' ] && customData[ asset.key + '.custom' ]" class="hg-replace-link">Replace</a>
        								</div>
            						</div>
								</div>
								<!-- Repeater -->
								<div class="hg-repeater" v-if="field.type == 'repeater'">
									<div class="hg-repeater-entry" v-for="(entry,entryIndex) in repeaterEntries( field.key )" v-if="repeaterEntries( field.key ) &&  customData[ field.key + '.use_custom' ] ">
										<ul class="hg-repeater-entry-controls">
											<li v-show="entryIndex>0"><a @click="moveEntry( field.key, entryIndex, 'up')"><i class="fa-solid fa-arrow-up"></i></a></li>
											<li v-show="entryIndex<repeaterEntries( field.key ).length - 1"><a @click="moveEntry( field.key, entryIndex, 'down')"><i class="fa-solid fa-arrow-down"></i></a></li>
											<li><a @click="deleteEntry( field.key, entryIndex )"><i class="fa-solid fa-trash-can"></i></a></li>
										</ul>
										<div v-for="(entryField,entryFieldIndex) in entry" class="hg-repeater-field">
											<!-- Optimize but it repeats our existing fields. -->
											<label class="hg-label" v-if="entryField.label && entryField.type != 'boolean'">
												{{ formatLabel( entryField.label, entryIndex ) }}
											</label>
											<p class="hg-field-description" v-if="entryField.description && entryField.description.length > 0"> {{ entryField.description }}</p>
											<!-- Field Types -->
											<!-- WYSIWYG -->
											<WYSIWYG :content="customData[ field.key + '.custom' ][ entryIndex ][entryField.key]" :field="field.key+'__'+entryIndex+'__'+entryField.key" @change="updateWYSIField" v-if="entryField.type == 'wysiwyg'" :key="entryField.key+'_'+entryIndex+'_'+reset" />
											<!-- Text -->
											<input type="text" v-model="customData[ field.key + '.custom' ][ entryIndex ][entryField.key]" v-if="entryField.type == 'text'" class="hg-text-field">
											<!-- Number -->
											<input type="number" v-model="customData[ field.key + '.custom' ][ entryIndex ][entryField.key]" v-if="entryField.type == 'number'" class="hg-text-field">
											<!-- Boolean -->
											<div class="hg-boolean" v-if="entryField.type == 'boolean'">
												<input class="hg-checkbox" type="checkbox" v-model="customData[ field.key + '.custom' ][ entryIndex ][entryField.key]" />
												<label class="hg-label hg-checkbox-label">
													{{ entryField.label }}
												</label>
											</div>
											<!-- Image -->
											<div class="hg-page-asset" v-if="entryField.type == 'image'" :class="{ 'hg-page-asset-single' : entryField.singleKey }">
												<div v-if="customData[ field.key + '.custom' ][ entryIndex ][entryField.key]" class="hg-image-wrap">
	        										<img :src="pageImage( field.key, entryIndex+'__'+entryField.key )" v-if="pageImage( field.key, entryIndex+'__'+entryField.key )" />
	                   	 						</div>
	                   	 						<div class="hg-upload-controls" v-if="!customData[ field.key + '.custom' ][ entryIndex ][entryField.key]">
	                   	 							<button class="hg-upload-button" @click="openPageUpload( entryField.page, field.key+'__'+entryIndex+'__'+entryField.key, entryField.singleKey )">Upload Page</button>
	                   	 							<p>or</p>
	                   	 							<button class="hg-upload-button" @click="openMediaView( entryField.key, field.key+'__'+entryIndex+'__'+entryField.key )">Choose File</button>
	                   	 						</div>
	                   	 						<a @click="openReplaceModal( entryField.key, entryField.page, '', entryField.singleKey, field.key+'__'+entryIndex+'__'+entryField.key )"  v-show="pageImage( field.key, entryIndex+'__'+entryField.key )" class="hg-replace-link">Replace</a>
	        								</div>
											<!-- Sub-Repeater -->
											<div class="hg-repeater hg-sub-repeater" v-if="entryField.type == 'sub-repeater'">
												<div class="hg-repeater-entry" v-for="(subEntry,subEntryIndex) in repeaterSubEntries( field.key, entryField.key, entryIndex, entryFieldIndex )" v-if="repeaterSubEntries( field.key, entryField.key, entryIndex, entryFieldIndex )">
													<ul class="hg-repeater-entry-controls">
														<li><a @click="deleteSubEntry( field.key, entryIndex, entryField.key, subEntryIndex )"><i class="fa-solid fa-trash-can"></i></a></li>
													</ul>
													<div v-for="(subEntryField, subEntryFieldIndex) in subEntry" class="hg-repeater-field hg-sub-repeater-field">
														<label class="hg-label" v-if="subEntryField.label && subEntryField.type != 'boolean'">
															{{ formatLabel( subEntryField.label, subEntryIndex ) }}
														</label>
														<p class="hg-field-description" v-if="subEntryField.description && subEntryField.description.length > 0"> {{ subEntryField.description }}</p>
														<!-- WYSIWYG -->
														<WYSIWYG :content="customData[ field.key + '.custom' ][ entryIndex ][entryField.key][ subEntryIndex ][subEntryField.key]" :field="field.key+'__'+entryIndex+'__'+entryField.key+'__'+subEntryIndex+'__'+subEntryField.key" @change="updateWYSIField" v-if="subEntryField.type == 'wysiwyg'" :key="entryField.key+'_'+entryIndex+'_'+subEntryField.key+'_'+subEntryIndex+'_'+reset" />
														<!-- Text -->
														<input type="text" v-model="customData[ field.key + '.custom' ][ entryIndex ][entryField.key][ subEntryIndex ][subEntryField.key]" v-if="subEntryField.type == 'text'" class="hg-text-field">
														<!-- Number -->
														<input type="number" v-model="customData[ field.key + '.custom' ][ entryIndex ][entryField.key][ subEntryIndex ][subEntryField.key]" v-if="subEntryField.type == 'number'" class="hg-text-field">

														<!-- Image -->
														<div class="hg-page-asset" v-if="subEntryField.type == 'image'" :class="{ 'hg-page-asset-single' : subEntryField.singleKey }">
															<div v-if="customData[ field.key + '.custom' ][ entryIndex ][entryField.key][ subEntryIndex ][subEntryField.key]" class="hg-image-wrap">
				        										<img :src="pageImage( field.key, entryIndex+'__'+entryField.key+'__'+subEntryIndex+'__'+subEntryField.key )" v-if="pageImage( field.key, entryIndex+'__'+entryField.key+'__'+subEntryIndex+'__'+subEntryField.key )" />
				                   	 						</div>
				                   	 						<div class="hg-upload-controls" v-if="!customData[ field.key + '.custom' ][ entryIndex ][entryField.key][ subEntryIndex ][subEntryField.key]">
				                   	 							<button class="hg-upload-button" @click="openPageUpload( subEntryField.page, field.key+'__'+entryIndex+'__'+entryField.key+'__'+subEntryIndex+'__'+subEntryField.key, subEntryField.singleKey )">Upload Page</button>
				                   	 							<p>or</p>
				                   	 							<button class="hg-upload-button" @click="openMediaView( subEntryField.key, field.key+'__'+entryIndex+'__'+entryField.key+'__'+subEntryIndex+'__'+subEntryField.key )">Choose File</button>
				                   	 						</div>
				                   	 						<a @click="openReplaceModal( subEntryField.key, subEntryField.page, '', subEntryField.singleKey, field.key+'__'+entryIndex+'__'+entryField.key+'__'+subEntryIndex+'__'+subEntryField.key )"  v-show="pageImage( field.key, entryIndex+'__'+entryField.key+'__'+subEntryIndex+'__'+subEntryField.key )" class="hg-replace-link">Replace</a>
				        								</div>

														<!-- Boolean -->
														<div class="hg-boolean" v-if="subEntryField.type == 'boolean'">
															<input class="hg-checkbox" type="checkbox" v-model="customData[ field.key + '.custom' ][ entryIndex ][entryField.key ][ subEntryIndex ][ subEntryField.key ]" />
															<label class="hg-label hg-checkbox-label">
																{{ subEntryField.label }}
															</label>
														</div>
													</div>
												</div>
												<a class="game-block" @click="addSubRepeaterEntry( field.key, entryField.key, entryIndex, entryFieldIndex )">
					        						<i class="fa-solid fa-plus"></i>
					        						<h3>
					        							Add
					        							<template v-if="entryField.addText">
					        								 {{ entryField.addText }}
					        							</template>
					        							<template v-else>
					        								 New
					        							</template>
					        						</h3>
					        					</a>
											</div>
										</div>
									</div>
									<div class="hg-repeater-entry hg-repeater-default" v-for="(entry,entryIndex) in repeaterEntries( field.key )" v-if="repeaterEntries( field.key ) && ! customData[ field.key + '.use_custom' ] ">
										<div v-for="(entryField, entryFieldIndex) in entry">
											<!-- Defaults -->
											<!-- Text -->
											<div v-html="formatText(entryField.value)" v-if="entryField.type == 'text' || entryField.type == 'wysiwyg' || entryField.type == 'number'" class="hg-default-text"></div>
											<!-- Boolean -->
											<div class="hg-default-boolean" v-if="entryField.type == 'boolean'">
												<p>{{ entryField.label }} - {{ booleanAsText(entryField.value) }}</p>
											</div>
											<!-- Repeater -->
											<div class="hg-repeater hg-sub-repeater" v-if="entryField.type == 'sub-repeater'">
												<div class="hg-repeater-entry" v-for="(subEntry,subEntryIndex) in repeaterSubEntries( field.key, entryField.key, entryIndex, entryFieldIndex )" v-if="repeaterSubEntries( field.key, entryField.key, entryIndex, entryFieldIndex )">
													<div v-for="(subEntryField, subEntryFieldIndex) in subEntry">
														<!-- Defaults -->
														<!-- Text -->
														<div v-html="formatText(subEntryField.value, 'sub')" v-if="subEntryField.type == 'text' || subEntryField.type == 'wysiwyg'" class="hg-default-text"></div>
														<!-- Number -->
														<div v-if="subEntryField.type == 'number'" class="hg-default-text"><p>{{ formatLabel(subEntryField.label, subEntryIndex ) }} : {{ subEntryField.value }}</p></div>
														<!-- Boolean -->
														<div class="hg-default-boolean" v-if="subEntryField.type == 'boolean'">
															<p>{{ subEntryField.label }} - {{ booleanAsText(subEntryField.value) }}</p>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
									<a class="game-block" @click="addRepeaterEntry( field.key )" v-show="customData[ field.key + '.use_custom' ]&& ( !field.max || repeaterEntries( field.key ).length < field.max )">
		        						<i class="fa-solid fa-plus"></i>
		        						<h3>
		        							Add
		        							<template v-if="field.addText">
		        								 {{ field.addText }}
		        							</template>
		        							<template v-else>
		        								 New
		        							</template>
		        						</h3>
		        					</a>
								</div>
								<!-- Defaults -->
								<!-- Text -->
								<div v-html="defaultText( field.key )" v-if=" ! customData[ field.key + '.use_custom' ] && ( field.type == 'text' || field.type == 'wysiwyg' || field.type == 'number' )" class="hg-default-text"></div>
							</div>
						</div>
					</div>
				</div>
     		</div>
			<div class="hg-modal" v-show="showModal">
	    		<div class="hg-modal-inner">
	    			<div class="hg-modal-close" @click="closeModal"><i class="fa fa-times"></i></div>
	    			<div class="hg-upload-controls" v-if="showModal=='choose-upload-method'">
            			<button class="hg-upload-button" @click="openPageUpload( pageKey, pathPrefix, singleArt )">Upload Page</button>
       	 				<p>or</p>
       	 				<button class="hg-upload-button" @click="openMediaView( assetKey )">Choose File</button>
          			</div>
	    			<PageUploader :asset-page="pageKey" :minigame="type" v-if="showModal=='page-upload'" @assets="applyPageAssets" :prefix="pathPrefix" :single="singleArt" />
	    			<form id="edit-minigame-details" @submit.prevent="updateGameDetails" v-show="showModal == 'details'">
	    				<h4>Update Details</h4>
			      		<div class="edit-game-field">
			        		<span class="game-title" v-bind:class="{ 'has-content': newTitle }">
			          			<input v-model="newTitle" name="title" data-vv-name="title" id="title" type="text" placeholder="My minigame is called...">
			        		</span>
			        		<button class="button-submit" v-bind:class="{ active: newTitle }" :disabled="!newTitle" type="submit">
			          			<template v-if="newTitle">
			            			Ok
			          			</template>
			          			<template v-else>
			            			Name Your Minigame!
			          			</template>
			        		</button>
			      		</div> 
			    	</form>
	    		</div>
	    	</div>
		</div>
	</div>
</template>

<script>
	export default {
        data () {
            return {
            	loading : false,
            	title : '',
            	type: '',
            	progress: 0,
            	form: {},
            	activeTab: 'text',
            	customData: {},
            	defaultData: {},
            	isDirty: false,
            	newTitle: '',
            	showModal: false,
            	pageKey: '',
            	assetKey: '',
            	pathPrefix: '',
            	subPath: '',
            	singleArt: null,
            	reset: 0,
            	defaults: {}
            }
        },
        computed : {
        	filteredTitle () {
      			return ( this.title != '' ) ? this.title : '(untitled)';
    			}
        },
        watch : {
        	customData : {
        		handler: function( newVal, oldVal ) {
	        		this.isDirty = true;
	        	},
	        	deep: true
	        }
        },
        methods : {
        	moveEntry( key, index, direction ) {
        		if( direction == 'up' ) {
        			this.customData[ key + '.custom' ].splice(index, 1, 
        				this.customData[ key + '.custom' ].splice(index-1, 1, this.customData[ key + '.custom' ][index])[0]
        			)
        		} else if( direction == 'down' ) {
        			this.customData[ key + '.custom' ].splice(index, 1, 
        				this.customData[ key + '.custom' ].splice(index+1, 1, this.customData[ key + '.custom' ][index])[0]
        			)        			
        		}
        		/* Force a wysi redraw */
        		this.reset = Math.floor(Date.now() / 1000)
        	},
        	deleteEntry( key, index ) {
        		var confirmDelete = confirm("Are you sure you wish to remove this entry? It cannot be undone unless you leave the page without saving.");
        		if( confirmDelete ) {
        			this.customData[ key + '.custom' ].splice( index, 1 )
        		}
        	},
        	deleteSubEntry( key, index, fieldKey, subIndex ) {
        		var confirmDelete = confirm("Are you sure you wish to remove this entry? It cannot be undone unless you leave the page without saving.");
        		if( confirmDelete ) {
        			this.customData[ key + '.custom' ][ index ][ fieldKey ].splice( subIndex, 1 )
        		}
        	},
        	booleanAsText( val ) {
        		return ( val ) ? 'Yes' : 'No'
        	},
        	repeaterSubEntries( key, subKey, entryIndex, fieldIndex ) {
        		var fields = hgLodash.get( this.defaultData, key + '.fields.'+fieldIndex+'.fields', [] );
        		var fieldObject = {}
        		for( var i = 0; i < fields.length; i++ ) {
        			fieldObject[ fields[i].key ] = fields[i];
        		}
        		if( Object.keys( fieldObject ).length > 0 ) {
	        		if( this.customData[ key + '.use_custom' ] ) {
	        			console.log( key, subKey, entryIndex, fieldIndex );
	        			var dataBlock = hgLodash.get( this.customData, key + '.custom', [] );
	        			var data = dataBlock[ entryIndex ][ subKey ];
	        			if( !data ) {
	        				data = [];
	        			}
	        		} else {
	        			var data = hgLodash.get( this.defaultData, key + '.default.'+entryIndex+'.'+subKey, [] );
	        		}
	        		var fieldData = [];
	    			for( var i = 0; i < data.length; i++ ) {
	    				fieldData[ i ] = []
	    				for( var prop in  data[ i ] ) {
	    					fieldData[ i ].push({
	    						key: prop,
	    						type: fieldObject[ prop ].type,
	    						value: data[ i ][ prop ],
	    						label: fieldObject[ prop ].label,
	    						description: fieldObject[ prop ].description,
	    						addText: fieldObject[ prop ].addText,
	    						page: fieldObject[ prop ].page,
	    						singleKey: fieldObject[ prop ].singleKey
	    					})
	    				}
	    			}
	    			console.log('sub',data,fieldData);
	    			return fieldData;	
	        	} else {
	        		return [];
	        	}
        	},
        	repeaterEntries( key ) {
        		var fields = hgLodash.get( this.defaultData, key + '.fields', [] );
        		var fieldObject = {}
        		for( var i = 0; i < fields.length; i++ ) {
        			fieldObject[ fields[i].key ] = fields[i];
        		}
        		if( Object.keys( fieldObject ).length > 0 ) {
	        		if( this.customData[ key + '.use_custom' ] ) {
	        			var data = hgLodash.get( this.customData, key + '.custom', [] );
	        		} else {
	        			var data = hgLodash.get( this.defaultData, key + '.default', [] );
	        		}
	    			var fieldData = [];
	    			for( var i = 0; i < data.length; i++ ) {
	    				fieldData[ i ] = []
	    				for( var prop in  data[ i ] ) {
	    					fieldData[ i ].push({
	    						key: prop,
	    						type: fieldObject[ prop ].type,
	    						value: data[ i ][ prop ],
	    						label: fieldObject[ prop ].label,
	    						description: fieldObject[ prop ].description,
	    						addText: fieldObject[ prop ].addText,
	    						page: fieldObject[ prop ].page,
	    						singleKey: fieldObject[ prop ].singleKey
	    					})
	    				}
	    			}
	    			return fieldData;
	    		} else {
	    			return []
	    		}
        	},
        	addSubRepeaterEntry( key, subKey, entryIndex, fieldIndex ) {
        		var newEntry = {}
        		/* Repeater fields are stored with their json object. */
        		var fields = hgLodash.get( this.defaultData, key + '.fields.'+fieldIndex+'.fields', []  );
    			for( var i = 0; i < fields.length; i++ ) {
    				newEntry[ fields[ i ].key ] = fields[ i ].start_value;
    			}
        		this.customData[ key + '.custom' ][ entryIndex ][ subKey ].push( newEntry );
        	},
        	addRepeaterEntry( key ) {
        		var newEntry = {}
        		/* Repeater fields are stored with their json object. */
        		var fields = hgLodash.get( this.defaultData, key + '.fields', [] );
    			for( var i = 0; i < fields.length; i++ ) {
    				if( Array.isArray(fields[ i ].start_value) ) {
    					newEntry[ fields[ i ].key ] = JSON.parse(JSON.stringify(fields[ i ].start_value));
    				} else {
    					newEntry[ fields[ i ].key ] = fields[ i ].start_value;
    				}
    			}
        		this.customData[ key + '.custom' ].push( newEntry );
        	},
        	applyPageAssets( assets, pathPrefix = '', singleArt = false ) {
        		/* for nested repeaters e.g. miobi */
        		var prefix = ( pathPrefix.length > 0 ) ? pathPrefix + '.' : ''
        		if( !singleArt ) {
	        		for( var key in assets ) {
	        			console.log('updating',key);
	        			hgLodash.set( this.customData, prefix + key + '.custom', assets[ key ] );
	        			hgLodash.set( this.customData, prefix + key + '.use_custom', true );
	        		}
	        	} else {
	        		if( this.subPath.length > 0 ) {
	        			var keyArray = this.subPath.split('__');
	        		} else {
	        			var keyArray = pathPrefix.split('__');
	        		}
	        		for( var key in assets ) {
	        			if( keyArray.length == 3 ) {
	        				/* Repeater */
	        				hgLodash.set( this.customData[keyArray[0] + '.custom'][keyArray[ 1 ]], keyArray[2], assets[ key ] );
	        			} else if( keyArray.length == 5 ) {
	        				/* Sub-repeater */
	        				hgLodash.set( this.customData[keyArray[0] + '.custom'][keyArray[ 1 ]][keyArray[2]][keyArray[3]], keyArray[4], assets[ key ] );
	        			}
	        		}
	        	}
        		console.log( this.customData );
        		this.closeModal();
        	},
        	openReplaceModal( key, page, prefix = '', single = false, subPath = '' ) {
        		this.pathPrefix = prefix;
        		this.assetKey = key;
        		this.pageKey = page;
        		this.singleArt = single;
        		this.subPath = subPath;
        		this.showModal = 'choose-upload-method';
        	},
        	openPageUpload( page, prefix = '', single = false ) {
        		this.pathPrefix = prefix;
        		this.pageKey = page;
        		this.singleArt = single;
        		this.showModal = 'page-upload'
        	},
        	openMediaView( key, subPath = '' ) {
			      var self = this;
			      var media_uploader = wp.media({
			          title: 'Select Artwork',
			          button: {
			            text: 'Use This Artwork'
			          },
			          library: {
			              type: 'image'
			          },
			          frame:    "post", 
			          state:    "insert", 
			          multiple: false
			      });
			      media_uploader.on("insert", function(){
			          var response = media_uploader.state().get("selection").first().toJSON();
			          var assets = {
			          	[ key ] : {
			          		id: response.id,
			          		file: response.url
			          	}
			          }
			          if( typeof response.game_original !== 'undefined' ) {
			          	assets[ key ].file = response.game_original
			          }
			          if( typeof response.game_bounds !== 'undefined' ) {
			          	assets[ key ].game_bounds = response.game_bounds
			          }
			          if( subPath.length > 0 || self.subPath.length > 0 ) {
			          	/* Repeater / Sub Repeater, treat as a single */
			          	self.applyPageAssets( assets, subPath, true );
			          } else {
			          	self.applyPageAssets( assets, '' );
			          }
			      });
			      media_uploader.open();
        	},
        	pageImage( key, subPath = '' ) {
        		if( this.customData[ key + '.use_custom' ] ) {
        			var custom = hgLodash.get( this.customData, key + '.custom', null );
        			if( custom ) {
        				if( subPath.length > 0 ) {
        					/* Repeater / Sub Repeater */
        					var keyArray = subPath.split('__');
        					if( keyArray.length == 2 ) {
		        				/* Repeater */
		        				if( custom[ keyArray[0] ][ keyArray[1] ] ) {
		        					return custom[ keyArray[0] ][ keyArray[1] ].file
		        				}
		        			} else if( keyArray.length == 4 ) {
		        				/* Sub-repeater */
		        				if( custom[ keyArray[0] ][ keyArray[1] ][ keyArray[2] ][ keyArray[3] ] ) {
		        					return custom[ keyArray[0] ][ keyArray[1] ][ keyArray[2] ][ keyArray[3] ].file
		        				}
		        			}
        				} else {
        					/* Custom has a full URL */
        					return custom.file
        				}
        			} else {
        				return null;
        			}
        		} else {
        			var defaultData = hgLodash.get( this.defaultData, key + '.default', null );
	        		if( defaultData ) {
	        			return hgPluginBase + 'minigames/' + this.type + '/' + defaultData.file;
	        		} else {
	        			return null;
	        		}
        		}
        		return null;
        	},
        	closeModal() {
        		this.showModal = false;
        		this.pageKey = '';
        		this.assetKey = '';
        		this.pathPrefix = '';
        		this.subPath = '';
        		this.singleArt = null;
        	},
        	updateGameDetails() {
        		this.isDirty = true;
        		this.title = this.newTitle;
        		this.closeModal();
        	},
        	editMinigameDetails() {
        		/* Popup with game title and status. */
        		this.newTitle = this.title;
        		this.showModal = 'details';
        	},
        	formatText( text, isSubRepeater = false ) {
        		var heading = 'h4';
        		if( isSubRepeater ) {
        			heading = 'h5';
        		}
        		if( /<\/?[a-z][\s\S]*>/i.test( text ) !== true ) {
        			/* No HTML, so add some for the view. */
        			text = '<'+heading+'>' + text + '</'+heading+'>';
        		}
        		return text;
        	},
        	formatLabel( label, index ) {
        		/* Minor templating for UX */
        		if( label.indexOf('{{ index }}') > -1 ) {
        			return label.replace('{{ index }}', index + 1 );
        		}
        		return label;
        	},
        	defaultText( key ) {
        		var defaultData = hgLodash.get( this.defaultData, key + '.default', '<p>Default Text</p>' );
        		return this.formatText(defaultData);
        	},
        	updateWYSIField( key, value ) {
        		if( key.indexOf('__') > -1 ) {
        			var keyArray = key.split('__');
        			if( keyArray.length == 3 ) {
        				/* Repeater */
        				hgLodash.set( this.customData[keyArray[0] + '.custom'][keyArray[ 1 ]], keyArray[2], value );
        			} else if( keyArray.length == 5 ) {
        				/* Sub-repeater */
        				hgLodash.set( this.customData[keyArray[0] + '.custom'][keyArray[ 1 ]][keyArray[2]][keyArray[3]], keyArray[4], value );
        			}
        		} else {
        			hgLodash.set( this.customData, key + '.custom', value );
        		}
        	},
        	customText( key ) {
        		if( this.customData[ key + '.use_custom' ] ) {
        			return 'Custom'
        		} else {
        			return 'Default'
        		}
        	},
        	async saveMinigame() {
        		var save = {};
        		/* Convert our customData back to a save json */
        		for( var prop  in this.customData ) {
        			hgLodash.set( save, prop, this.customData[ prop ] );
        		}
        		console.log( save );
        		//return;
        		try {
			      	this.loading = true;
			      	var response = await this.$store.dispatch( 'sendRequest',
	      				{ 
	      					path : '/minigame/update/' + this.$route.params.site + '/' + this.$route.params.id,
	      					method : 'POST',
	      					data : {
	      						title : this.title,
	      						minigame_data : save
	      					}
	      				}
	      			);
			        console.log( response );
			        alert('Your minigame has been saved.')
			        this.isDirty = false;
				    } catch( error ) {
				    	alert( error );
				    }
				    this.loading = false;
        	},
        	changeTab( newTab ) {
        		if( this.activeTab != newTab ) {
        			this.activeTab = newTab;
        		}
        	},
        	async setupData( response ) {
        		var defaults = response.defaults;
        		var data = response.data;
        		var user_fields = defaults.user_fields;
        		/* Creates a usable data object to watch */
        		if( typeof user_fields !== 'undefined' ) {
        			for( var i = 0; i < user_fields.length; i++ ) {
        				var field = user_fields[ i ]
        				if( hgLodash.has( data, field.key + '.custom' ) ) {
        					this.customData[ field.key + '.custom' ] = hgLodash.get( data, field.key + '.custom', -1 );
        					if( hgLodash.has( data, field.key + '.use_custom' ) ) {
        						this.customData[ field.key + '.use_custom' ] = hgLodash.get( data, field.key + '.use_custom', -1 );
        					} else {
        						this.customData[ field.key + '.use_custom' ] = ( typeof field.use_custom !== 'undefined' ) ? field.use_custom : true;
        					}
        				} else {
        					this.customData[ field.key + '.custom' ] = field.start_value
        					/* Check for a custom flag */
        					this.customData[ field.key + '.use_custom' ] = ( typeof field.use_custom !== 'undefined' ) ? field.use_custom : true;
        				}
        			}
        		}
        		console.log( 'setup', this.customData );
        		return;
        	},
		    async fetchData () {
		    	try {
			      	this.loading = true;
			      	var response = await this.$store.dispatch( 'sendRequest',
			      		{ path : '/minigames/' + this.$route.params.site + '/' + this.$route.params.id }
			      	);
			        console.log( response );
			        this.title = response.title;
			        this.type = response.type;
			        if( typeof response.defaults !== 'undefined'
			        	&& typeof response.defaults.form !== 'undefined' ) {
			        	this.defaults = response.defaults;
			        	this.form = response.defaults.form;
			        	this.activeTab = this.form.tabs[0].label;
			        	this.defaultData = response.defaults;
			        	await this.setupData( response );
			        }
			    } catch( error ) {
			    	alert( error );
			    }
			    this.loading = false;
			    return;
	    	}
      },
      async created() {
      	await this.fetchData();
      	this.isDirty = false;
      }
    }
</script>

<style>
#hg-base .minigame-template {
	font-size: 13px;
    font-weight: bold;
    margin: 0 0 20px 0;
}
#hg-base .minigame-template a {
	color:  #fff;
}
.hg-form-box {
	text-align: left;
	padding-top:  10px;
	width:  80vw;
	max-width:  100%;
}
.hg-form-box p {
	font-size: 2vw;
	line-height: 2.5vw;
}
.hg-form-box h1, .hg-form-box h2, .hg-form-box h3, .hg-form-box h4, .hg-form-box h5, .hg-form-box h6 {
	font-family: 'Architects Daughter', cursive;
	margin-bottom:  5px;
}
.hg-form-box h2 {
	margin-top:  10px;
	margin-bottom:  10px;
}
.hg-form-box  h3 {
  font-size: 30px;
  margin: 30px auto;
}
.hg-default-text {
	padding: 10px;
  text-align: center;
}
.hg-default-text p {
	margin-top: 0;
}
.hg-default-text h4 {
	font-size: 30px;
  margin: 10px 0;
  line-height: 1.2em;
}
.hg-default-text a {
	color: #9ce0e9;
}
.hg-default-boolean p {
  text-align: center;
  font-weight: bold;
  margin: 0 0 30px 0;
}

.hg-repeater-entry {
	padding: 40px 15px;
	border-bottom: solid 1px rgba(250,250,250,.2);
	position:  relative;
}
.hg-repeater-entry .hg-repeater-entry-controls {
	position: absolute;
    top: 5px;
    right: 0;
    display: flex;
    align-items: flex-start;
    margin:  0  5px;
}
.hg-repeater-entry .hg-repeater-field {
	padding:  0 0 25px 0;
}
.hg-repeater-entry .hg-repeater-entry-controls li {
	margin-bottom: 0;
}
.hg-repeater-entry .hg-repeater-entry-controls li a {
	padding: 5px 15px;
	color:  #fff;
	font-size:  20px;
	display: block;
	cursor: pointer;
}

.hg-repeater-entry:last-of-type {
	border-bottom: none;
}

.hg-sub-repeater .hg-repeater-entry {
    /*max-width: 400px;*/
    margin: 0 auto;
    /*width: 90%;*/
    padding-bottom: 5px;
    margin-bottom:  5px;
}
.hg-repeater-default .hg-sub-repeater .hg-repeater-entry {
	padding:  5px 15px;
	width:  90%;
	max-width:  400px;
}
.hg-sub-repeater .hg-repeater-entry h5 {
	font-size: 22px;
    margin: 0 0 5px 0;
}
.hg-sub-repeater .hg-repeater-entry .hg-default-boolean p {
	margin-bottom:  5px;
}

.hg-form-box-inner {
	display: flex;
  justify-content: center;
  align-items: center;
  min-height: 50vh;
  flex-direction: column;
}


.hg-form-box ul.hg-form-box-menu {
	margin: 0;
	padding:  0;
	display: flex;
    justify-content: space-evenly;
    flex-wrap: wrap;
}
.hg-form-box ul.hg-form-box-menu li {
	list-style-type: none;
	font-family: 'Architects Daughter', cursive;
	margin: 5px 15px;
	cursor:  pointer;
	font-size:  28px;
}
.hg-form-box ul.hg-form-box-menu li.active,.hg-form-box ul.hg-form-box-menu li:hover {
	position:  relative;
	display: block;
}
.hg-form-box ul.hg-form-box-menu li.active:before,.hg-form-box ul.hg-form-box-menu li:hover:before {
	content:  '';
	position:  absolute;
	bottom: 2px;
	left: auto;
	right: auto;
	width: 100%;
	height: 100%;
	display: block;
	border-bottom: solid 2px #fff;
	padding-bottom: 0px;
}
.hg-form-box ul.hg-form-box-menu li.hg-save:hover:before {
	content:  none;
}
.hg-form-box ul.hg-form-box-menu li a {
	color:  #fff;
	text-decoration: none;
}

.hg-form-box .hg-form-box-content a {
	color: #9ce0e9;
}

.hg-section-wrap {
	width:  100%;
	display: flex;
	flex-direction:  column;
	justify-content:  center;
	align-items:  center;
	flex-wrap:  wrap;
	padding:  15px;
	margin-top:  15px;
	position:  relative;
}
.hg-section-wrap:before {
	content:  '';
	display: block;
	height:  1px;
	width: 50%;
	position: absolute;
	top:  0;
	left:  50%;
	margin-left: -25%;
	background:  rgba(250,250,250,.2);
}
.hg-section-wrap:first-of-type {
	margin-top:  0;
}
.hg-section-wrap:first-of-type:before {
	content:  none;
}


.hg-field-wrap {
	position:  relative;
	padding:  20px 0 0 0;
  box-sizing: border-box;
  border: solid 2px #29373d;
  min-width:  33.33%;
  border-radius:  5px;
  width: 550px;
  margin: 20px 0;
  max-width:  100%;
}
.hg-field-wrap.hg-field-wrap-page-group {
	padding:  10px 0;
}
.hg-field {
	display:  flex;
	flex-direction: column;
}
.hg-field label.hg-label {
	font-size: 29px;
  padding-left:  10px;
  padding-bottom: 15px;
  padding-right: 100px;
  font-family: 'Architects Daughter', cursive;
  display:  block;
}
.hg-field input {
	width:  100%;
	font-size:  15px;
}
#hg-base .hg-field-description {
	margin: 0 10px 10px 10px;
    font-size: 15px;
    font-weight: bold;
}

.hg-field .hg-text-field {
  padding: 5px 10px;
  font-family: 'Architects Daughter', cursive;
  font-weight: bold;
  font-size: 30px;
  line-height: 25px;
  letter-spacing: 0.02em;
  text-align: center;
} 

.hg-use-custom-wrap {
	position: absolute;
  	top:  17px;
  	right: 15px;
  	display: flex;
    align-items: flex-start;
}
#hg-base .hg-field-wrap .hg-use-custom-wrap p {
	padding: 0;
    margin: 0;
    padding-right: 8px;
    font-size: 13px;
    font-weight: bold;
}

.hg-page-group {

}
.hg-page-assets {
    display:  flex;
    flex-wrap: wrap;
    justify-content: center;
}
.hg-page-asset {
    position: relative;
    border: solid 2px rgba(250,250,250,.05);
    border-radius: 5px;
    padding: 10px;
    width:  100%;
    max-width: 40%;
    margin: 5px;
    padding: 24px 15px 10px 15px;
    text-align: center;
    display:  flex;
    flex-direction:  column;
    box-sizing: border-box;
}
.hg-page-asset.hg-page-asset-single {
	max-width:  100%;
	padding-top: 12px;
}
.hg-page-group .hg-page-asset {
	padding-top:  40px;
}
.hg-page-group .hg-page-asset .hg-use-custom-wrap {
	top:  10px;
}
.hg-page-asset h5 {
    font-size: 20px;
    margin-top:  10px;
}
.hg-page-asset img {
	max-width:  100%;
}
.hg-page-asset .hg-upload-controls, .hg-page-asset .hg-image-wrap {
	flex: 1 0 auto;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.hg-page-asset .hg-upload-controls p {
	margin:  5px 0;
}

.hg-page-assets-1 .hg-page-asset {
	max-width:  100%;
}

.hg-upload-button {
    border: 2px solid #eee;
    color: #fff;
    background-color: transparent;
    padding: 8px 20px;
    border-radius: 8px;
    font-size: 20px;
    font-weight: bold;
    cursor: pointer;
}

#hg-app a.hg-replace-link {
	color: #9ce0e9;
  font-weight: bold;
  display: block;
  margin-top: 3px;
  cursor:  pointer;
}

#hg-app .hg-repeater .game-block {
    width: 100%;
    max-width: 95%;
    margin: 15px auto;
    display: flex;
    box-sizing: border-box;
}

.hg-boolean {
    display: flex;
    padding: 30px 15px 40px 15px;
    align-items: center;
    justify-content: center;
}

.hg-boolean input[type="checkbox"] {
	-webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    position:  relative;
    height:  30px;
    width:  30px;
    background: transparent;
    border-radius: 5px;
  	border-color:  #fff;
}
.hg-boolean input[type="checkbox"]:before {
	content: "";
  	position: absolute;
  	top: 0;
  	right: 0;
  	display:  block;
  	width: 100%;
  	height:  100%;
}
.hg-boolean input[type="checkbox"]:checked::before {
	content: "\f00c";
    font-family: "FontAwesome";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    padding: 4px 5px;
    color:  #ffc107;
    font-size: 20px;
}

.hg-boolean label.hg-label {
	padding-bottom:  0;
	padding-right:  0;
}
#hg-base .hg-field-wrap-repeater .hg-field > .hg-field-description {
	margin: 0;
    padding: 0 10px 10px 10px;
}

.hg-field-wrap-repeater .hg-field > label.hg-label.hg-border,
.hg-field-wrap-repeater .hg-field > .hg-field-description {
    border-bottom: solid 3px #2a373d;
}
.hg-repeater-entry label.hg-label {
	font-size: 24px;
}


/* 
W3 Schools 
https://www.w3schools.com/howto/howto_css_switch.asp
*/
.hg-switch {
  position:  relative;
  display: inline-block;
  width: 40px;
  height: 20px;
}

.hg-switch input { 
  opacity: 0;
  width: 0;
  height: 0;
}

.hg-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  -webkit-transition: .4s;
  transition: .4s;
}

.hg-slider:before {
  position: absolute;
  content: "";
  height: 17px;
  width: 17px;
  left: 3px;
  bottom: 2px;
  background-color: white;
  -webkit-transition: .4s;
  transition: .4s;
}

.hg-switch input:checked + .hg-slider {
  background-color: #f7d161;
}

.hg-switch input:focus + .hg-slider {
  box-shadow: 0 0 1px #2196F3;
}

.hg-switch input:checked + .hg-slider:before {
  -webkit-transform: translateX(17px);
  -ms-transform: translateX(17px);
  transform: translateX(17px);
}

/* Rounded sliders */
.hg-slider.hg-round {
  border-radius: 22px;
}

.hg-slider.hg-round:before {
  border-radius: 50%;
}



@media screen and (max-width:  500px) {
	.hg-field label {
		font-size: 22px;
	}
	.hg-form-box ul.hg-form-box-menu li {
		font-size:  23px;
	}
}
@media screen and (max-width:  554px) {
	.hg-page-asset {
		max-width:  90%;
	}
}
#hg-app #edit-minigame-details h4 {
	font-size:  25px;
}
</style>