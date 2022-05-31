<template>
  <div class="wp-core-ui wp-editor-wrap tmce-active has-dfw">
    <div :id="'wp-content-editor-tools-'+field" class="wp-editor-tools hide-if-no-js">
      <!-- <div :id="'wp-content-media-buttons-'+field" class="wp-media-buttons">
        <button type="button" id="insert-media-button" class="button insert-media add_media" :data-editor="field">
          <span class="wp-media-buttons-icon"></span> Add Media
        </button>
      </div> -->
      <!--<div class="wp-editor-tabs">
        <button type="button" :id="'content-tmce-'+field" class="wp-switch-editor switch-tmce" data-wp-editor-id="content">Visual</button>
        <button type="button" :id="'content-html-'+field" class="wp-switch-editor switch-html" data-wp-editor-id="content">Text</button>
      </div>-->
    </div>
    <div id="wp-content-editor-container" class="wp-editor-container">
      <div id="ed_toolbar" class="quicktags-toolbar"></div>
      <textarea class="wp-editor-area" style="height: 200px" autocomplete="off" cols="40" :name="field" :id="field">{{ newContent }}</textarea>
    </div>
  </div>
</template>

<script>
export default {
  props: ['content','field'],
  data () {
    return {
      newContent: '',
      editor : null
    }
  },
  mounted () {
    console.log('wysi mount');
    /* Set what we were given */
    if( this.content && this.content.length > 0 )  {
      this.newContent = this.content;
    }
    this.textareaToTinymce();
  },
  beforeUnmount () {
    jQuery('#'+this.field+' .mce-i-link').unbind('click');
    tinyMCE.execCommand( 'mceRemoveEditor', true, this.field );
  },
  methods: {
    textareaToTinymce () {
      var self =this;
      if ( typeof( tinyMCE ) == "object" && typeof( tinyMCE.execCommand ) == "function" ) {
        tinyMCE.execCommand("mceAddEditor", true, this.field );
        /* Pass the changes up the chain. */
        tinyMCE.get( this.field ).on("change", function( e, c ) {
          self.$emit( 'change', self.field, tinyMCE.get( self.field ).getContent() );
        });
        tinyMCE.get( this.field ).setContent( this.newContent );
        /* Connect WP Link */
        jQuery('#'+this.field+' .mce-i-link').parent().click(function(e){
          wpActiveEditor = false;
          wpLink.open( self.field );
        });
      }
    }
  }
}
</script>

<style scoped>
.mce-container, .wp-editor-container {
  border-radius: 5px;
}
</style>