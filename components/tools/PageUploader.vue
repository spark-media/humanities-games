<template>
    <div class="hg-page-uploader">
        <!-- If multiple -->
        <div class="hg-modal-arrow-right" v-show="!loading&&single&&assets.length > 1" @click="goRight"><i class="fa fa-arrow-right"></i></div>
        <div class="hg-modal-arrow-left" v-show="!loading&&single&&assets.length > 1" @click="goLeft"><i class="fa fa-arrow-left"></i></div>
        <div class="hg-upload-wrapper" v-show="assets.length < 1">
            <button class="hg-upload-button" v-show="!loading">Choose Page</button>
    	    <input type="file" accept="image/jpeg" :ref="minigame+'_'+assetPage" @change="chooseImage(minigame+'_'+assetPage)"  class="hg-image-input"  v-show="!loading"/>
            <Loader v-show="loading" />
    	    <p v-show="loading" class="hg-asset-progress">{{ progress }}</p>
            <p v-show="!loading">You will be able to choose which art to use.</p>
        </div>
    	<template v-if="assets.length > 0">
            <Loader v-show="loading" />
            <div class="hg-page-assets"  :class="{ [ 'hg-page-assets-' + assets.length ] : !single, 'hg-page-assets-1' : single }" v-show="!loading">
        		<div class="hg-page-asset" v-for="(asset,index) in assets" v-show="!single||index==active">
                    <div class="hg-remove-asset" @click="removeAsset(index)"><i class="fa fa-times"></i></div>
        			<img :src="asset.url"/>
                    <h5 v-if="asset.label">{{ asset.label }}</h5>
        		</div>
            </div>
            <p class="hg-choose-prompt" v-show="!loading&&!single">Remove what you don't want to replace.</p>
            <p class="hg-choose-prompt" v-show="!loading&&single&&assets.length>1">
                Use the arrows to choose the artwork you want to use for this {{ assetPage }}.
                <template v-if="!saveOtherArt">
                     You can save all the options for later use by checking the box next to "Save Other Art."
                </template>
            </p>
            <p v-if="!loading&&single&&assets.length>1&&saveOtherArt" class="hg-save-all-text">
                Other saved art from this page will be in the Media Library, which you can reach by pressing "Choose File" or "Replace" once you confirm this option.
            </p>
            <div class="hg-art-submit-options" v-show="!loading">
                <div class="hg-boolean" v-if="single&&assets.length>1">
                    <input class="hg-checkbox" type="checkbox" v-model="saveOtherArt" />
                    <label class="hg-label hg-checkbox-label">
                        <h3>Save Other Art</h3>
                    </label>
                </div>
                <button class="hg-choose-art" type="submit" @click="insertArt">Use Art</button>
            </div>
    	</template>
    </div>
</template>

<script>
export default {
	props: ['assetPage','minigame','prefix','single'],
    data () {
        return {
            loading : false,
            progress: '',
            submissionID: 0,
            assets: [],
            active: 0,
            saveOtherArt: false
        }
    },
    methods : {
        goLeft() {
            if( this.active < 1 ) {
                this.active = this.assets.length - 1;
            } else {
                this.active--;
            }
        },
        goRight() {
            if( this.active >= this.assets.length - 1 ) {
                this.active = 0;
            } else {
                this.active++;
            }
        },
        async insertArt() {
            /* Send an api request with the assets */
            try {
                this.loading = true;
                var data = {
                    assets: this.assets
                }

                var singleKey = null
                /* In this case we store the key to return just one, but upload everything. */
                if( this.single && this.saveOtherArt ) {
                    singleKey = this.assets[ this.active ].file_name;
                }

                /* In this case we only upload the chosen file. */
                if( this.single && !this.saveOtherArt ) {
                    data.assets = [ this.assets[ this.active ] ];
                    data.assets[0].file_name = this.single;
                }
                var response = await this.$store.dispatch( 'sendRequest',
                    {   
                        path : '/page/apply/' + this.$route.params.site + '/' + this.$route.params.id,
                        method: 'POST',
                        data: data
                    }
                );
                if( this.saveOtherArt && singleKey ) {
                    var singleAsset = {
                        [ singleKey ] : response.assets[ singleKey ]
                    }
                    /* Add the details back into the object via emit. */
                    this.$emit( 'assets', singleAsset, this.prefix, this.single );
                } else {
                    /* Add the details back into the object via emit. */
                    this.$emit( 'assets', response.assets, this.prefix, this.single );
                }
                console.log(response)
            } catch( error ) {
                alert( error );
            }
            this.loading = false;
        },
        removeAsset( index ) {
            this.assets.splice( index, 1 );
        },
    	async checkStatus() {
    		try {
                var response = await this.$store.dispatch( 'sendRequest',
                    { path : '/page/status/' + this.$route.params.site + '/' + this.$route.params.id + '/' + this.assetPage + '/' + this.submissionID }
                );
                if( typeof response.status !== 'undefined'
                	&& response.status == 'complete' ) {
                    this.progress = ''
                	console.log('complete')
                	this.assets = response.assets;
                	this.loading = false;
                } else {
                	/* Set a timer to check the upload process */
                    setTimeout( this.checkStatus, 5000 );
                }
                console.log(response);
            } catch( error ) {
                alert( error );
            }
    	},
        async chooseImage( key ) {
            var image = this.$refs[key].files.item(0);
            this.progress = 'Preparing...'
            var url = await this.fetchUploadURL();
            try {
                this.loading = true;
                this.progress = 'Uploading...'
                console.log('uploading');
                var result = await this.$store.dispatch( 'sendRequest',
                    {   
                        url: url,
                        method: 'PUT',
                        data: image
                    }
                );
                if( result ) {
                    this.progress = 'Finding Art...'
                    /* Set a timer to check the upload process */
                    setTimeout( this.checkStatus, 5000 );
                }
                console.log( result );
            } catch( error ) {
                alert( error );
            }
        },
        async fetchUploadURL () {
            try {
                this.loading = true;
                var response = await this.$store.dispatch( 'sendRequest',
                    { path : '/page/process/' + this.$route.params.site + '/' + this.$route.params.id + '/' + this.assetPage }
                );
                console.log(response)
                /* Save for later reference. */
                this.submissionID = response.submission_id
                return response.upload_url
            } catch( error ) {
                alert( error );
            }
            this.loading = false;
        }
    }
}
</script>

<style>
.hg-page-uploader {
    text-align: center;
    padding: 15px;
}
.hg-upload-wrapper {
    position:  relative;
    padding:  0 15px 20px 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    overflow:  hidden;
}
.hg-upload-wrapper input {
    font-size: 100px;
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0;
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

.hg-page-assets-1 .hg-page-asset {
    max-width:  100%;
}
.hg-remove-asset {
    position: absolute;
    top:  2px;
    right:  4px;
    color:  #fff;
    font-size:  20px;
}
#hg-base .hg-page-uploader .hg-asset-progress {
    font-family: 'Architects Daughter', cursive;
    font-weight: bold;
    font-size: 25px;
}
.hg-choose-prompt {
    font-size:  20px;
}

/*.hg-choose-art {
    z-index: 1;
    padding: 10px 20px;
    color: #ddd;
    background: #111;
    border: solid 1px #222;
    border-radius: 3px;
    letter-spacing: 0.02em;
    font-size: 15px;
    font-weight: bold;
    cursor:  pointer;
    transition:  background,border 100ms linear;
}*/
.hg-choose-art span {
    padding-left:  10px;
    padding-right:  10px;
    position: relative;
    top: -2px;
}
.hg-choose-art i {
    color:  #fff;
    font-size:  20px;
    transition: color 100ms linear;
}
.hg-choose-art:hover {
    background: #222;
    border: solid 1px #333;
}
.hg-choose-art:hover i {
    color:  rgba(255, 193, 7, 1);
}

.hg-modal-arrow-right, .hg-modal-arrow-left {
    position: absolute;
    top: 35%;
    left: 6px;
    font-size: 27px;
    cursor: pointer;
}
.hg-modal-arrow-right {
    left: auto;
    right: 6px;
}

.hg-page-uploader .hg-boolean {
    padding: 10px 15px;
}
.hg-art-submit-options {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
}
.hg-art-submit-options .hg-label h3 {
    margin:  0;
    font-size: 20px;
    padding-left:  10px;
}
#hg-app p.hg-save-all-text {
    font-weight: bold;
    border: solid 1px #aaa;
    border-radius: 5px;
    padding: 15px 10px;
}

</style>