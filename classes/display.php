<?php
class HG_Display {

    public function __construct() {
        add_action('admin_enqueue_scripts', array( $this, 'dashboard_app_css' ) );
        add_action('admin_menu', array( $this, 'setup_menu' ) );
        /* Dashboard Styling */
        add_filter( 'mce_css', array( $this, 'mce_css' ) );
        //targets the first line
        add_filter("mce_buttons", array( $this, "tinymce_editor_buttons" ), 99); 
        //targets the second line
        add_filter("mce_buttons_2", array( $this, "tinymce_editor_buttons_second_row" ), 99); 
        add_filter('tiny_mce_before_init', array( $this, "custom_tinymce_textformats" ), 99 );
        /* Game type filter on media */
        add_action( 'wp_enqueue_media', array( $this, 'game_type_media_filter' ) );
        add_filter( 'wp_prepare_attachment_for_js', array( $this, 'include_image_game_bounds' ), 10, 3 );
        /* Frontend display */
        add_filter( 'the_content', array( $this, 'apply_game_embed' ), 1 );
    }


 
    public function apply_game_embed( $content ) {
     
        /* Check if we're inside the main loop in a single Post. */
        if ( is_singular() && in_the_loop() && is_main_query() && get_post_type() == 'hgame' ) {
            $result = '<style>.hg-wrap {
                position: relative;
                padding-bottom: 56.25%; /* 16:9 */
                height: 0;
            }
            .hg-wrap iframe {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
            }</style>';
            $site = get_blog_details(get_current_blog_id());
            $result .= '<div class="hg-wrap"><iframe src="https://'.$site->domain.'/hg-embed/'.get_current_blog_id().'/'.get_the_ID().'" class="hg-player" frameborder="0" allowfullscreen=""></iframe></div>';
            return $result.$content;
        }
        return $content;
    }

    public function include_image_game_bounds(  $response, $attachment, $meta ) {
        $bounds = get_post_meta( $attachment->ID, 'game_bounds', true );
        if( $bounds ) {
            $response['game_bounds'] = json_decode( $bounds );
        }
        /* Necessary to get http / https parity. */
        $original = wp_get_original_image_url( $attachment->ID );
        $response['game_original'] = wp_make_link_relative( $original );
        return $response;
    }

    public function game_type_media_filter() {
        wp_enqueue_script( 'media-library-taxonomy-filter', plugins_url( 'views/game-type-filter.js', dirname( __FILE__ ) ), array( 'media-editor', 'media-views' ) );
        // Load 'terms' into a JavaScript variable that collection-filter.js has access to
        wp_localize_script( 'media-library-taxonomy-filter', 'MediaLibraryTaxonomyFilterData', array(
            'terms'     => get_terms( 'hg_art_category', array( 'hide_empty' => false ) ),
        ) );
        // Overrides code styling to accommodate for a third dropdown filter
        add_action( 'admin_footer', function(){
            ?>
            <style>
            .media-modal-content .media-frame select.attachment-filters {
                max-width: -webkit-calc(33% - 12px);
                max-width: calc(33% - 12px);
            }
            </style>
            <?php
        });
    }

    public function mce_css( $mce_css ) {
        $screen = get_current_screen();
        if( $screen->base == 'toplevel_page_humanities-games' ) {
            if ( ! empty( $mce_css ) ) {
                $mce_css .= ',';
            }
            $mce_css .= plugins_url( 'views/mce-editor.css', dirname( __FILE__ ) );         
        }
        return $mce_css;
    }

    public function custom_tinymce_textformats( $init_array ) {
        $screen = get_current_screen();
        if( $screen->base == 'toplevel_page_humanities-games' ) {
            $block_formats = [
                'Paragraph=p',
                'Heading=h2',
                'Subheading=h3',
            ];
            $init_array['block_formats'] = implode(';', $block_formats);
        }
        return $init_array;
    }

    public function tinymce_editor_buttons( $buttons ) {
        $screen = get_current_screen();
        if( $screen->base == 'toplevel_page_humanities-games' ) {
            return array(
                "formatselect",
                "undo", 
                "redo", 
                "separator",
                "bold", 
                "italic", 
                "underline", 
                "link",
                "bullist",
                "numlist",
                "blockquote",
                "fullscreen" 
            );
        }
        return $buttons;
    }

    public function tinymce_editor_buttons_second_row(  $buttons ) {
        $screen = get_current_screen();
        if( $screen->base == 'toplevel_page_humanities-games' ) {
            //return an empty array to remove this line
            return array();
        }
        return $buttons;
    }

    public function setup_menu() {
        add_menu_page(
            __( 'List Games', 'textdomain' ),
            __( 'Games','textdomain' ),
            'edit_hgames',
            'humanities-games',
            array( $this, 'dashboard_app' ),
            'dashicons-welcome-learn-more'
        );
    }

    public function dashboard_app_css( $hook ) {
        /* For wp upload */
        wp_enqueue_media();
        //wp_deregister_script( 'lodash' );
        /* Whether or not to hide other parts of WP for game makers */
        $user = wp_get_current_user();
        if( in_array( $hook, array('toplevel_page_humanities-games') ) ) :
        ?>
            <!-- Fonts -->
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Architects+Daughter&family=Roboto:ital,wght@0,100;0,400;1,900&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
            <!-- Admin Animations -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.8.1/lottie.min.js" integrity="sha512-V1YyTKZJrzJNhcKthpNAaohFXBnu5K9j7Qiz6gv1knFuf13TW/3vpgVVhJu9fvbdW8lb5J6czIhD4fWK2iHKXA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
            <style>
                #wpcontent {
                    padding-left: 0;
                }
                #wpwrap {
                    background: linear-gradient(135deg, #3f5058, #253339);
                }
                #hg-app {
                    color:  #fff;
                    width: 100%;
                    font-family: 'Roboto', sans-serif;
                }
                #hg-app::-webkit-scrollbar,#hg-app .hg-modal .hg-modal-inner::-webkit-scrollbar {
                    display:  none;
                }
                <?php if ( !in_array( 'administrator', $user->roles, true ) ) : ?>
                    html.wp-toolbar {
                        padding-top:  0 !important;
                    }
                    #wpadminbar,#adminmenumain {
                        display: none;
                    } 
                    body #wpfooter {
                        margin-left: 0 !important;
                        box-sizing: border-box;
                        color: #aaa;
                    }
                    #hg-app {
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        height: 100%;
                        overflow-y: scroll;
                    }
                <?php else: ?>
                    #wpcontent {
                        margin-left: 140px;
                    }
                    body #wpfooter {
                        color: #aaa;
                    }
                <?php endif; ?>
                #hg-app h1, #hg-app h2, #hg-app h3, #hg-app h4, #hg-app h5, #hg-app h6 {
                    font-family: 'Architects Daughter', cursive;
                    color:  #fff;
                }
                .hg-loading {
                    display: inline-block;
                    position: relative;
                    width: 80px;
                    height: 80px;
                    margin: 10px auto;
                }
                .hg-loading div {
                    position: absolute;
                    border: 4px solid #fff;
                    opacity: 1;
                    border-radius: 50%;
                    animation: hg-ripple 1s cubic-bezier(0, 0.2, 0.8, 1) infinite;
                }
                .hg-loading div:nth-child(2) {
                  animation-delay: -0.5s;
                }
                @keyframes hg-ripple {
                  0% {
                    top: 36px;
                    left: 36px;
                    width: 0;
                    height: 0;
                    opacity: 1;
                  }
                  100% {
                    top: 0px;
                    left: 0px;
                    width: 72px;
                    height: 72px;
                    opacity: 0;
                  }
                }
                #hg-app .hg-view {
                    margin: auto;
                    max-width:  90%;
                    padding: 50px 15px;
                }
                #hg-app h2.greeting {
                    color:  #fff;
                    font-size:  30px;
                    letter-spacing: .05em;
                    margin: 10px auto 20px auto;
                    line-height:  32px;
                }
                #hg-app .dashboard-nav a {
                    display: inline-block;
                    color: #fff;
                    padding: 15px;
                    text-decoration: none;
                    font-size: 20px;
                    background: #1d6dac;
                    margin: 10px;
                    border-radius: 5px;
                }
                #hg-app .game-blocks {
                    display: flex;
                    justify-content: center;
                    flex-wrap: wrap;
                }
                #hg-app .game-block {
                    display: inline-flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    color: #fff;
                    text-decoration: none;
                    padding: 15px 20px;
                    max-width: 275px;
                    border: solid 2px #29373d;
                    border-radius: 5px;
                    margin: 10px;
                    cursor:  pointer;
                    flex: 1;
                    min-width: 200px;
                    position: relative;
                }
                #hg-app .game-block h3 {
                    color: #fff;
                    font-size:  20px;
                    margin: 5px;
                    line-height: 22px;
                }
                #hg-app .game-block h3 span{
                    font-family: 'Roboto', sans-serif;
                    font-size: 14px;
                }
                #hg-app .game-block i {
                    font-size:  40px;
                }
                #hg-app .cancel {
                    color:  #fff;
                    display: inline-block;
                    margin-top:  20px;
                    cursor:  pointer;
                }
                #hg-app .game-meta ul {
                    margin-bottom: 0;
                }
                #hg-app .game-meta li {
                    display: inline-block;
                }
                #hg-app .game-meta li a {
                    display: flex;
                    flex-direction: column;
                    text-decoration: none;
                    color: #fff;
                    margin: 0 5px;
                    padding: 2px 8px;
                    border: solid 1px transparent;
                    border-radius: 5px;
                }
                #hg-app .game-meta li a:hover {
                    border-color: #999;
                }
                #hg-app .game-block .game-meta li a i {
                    font-size: 25px;
                    padding-bottom: 5px;
                }
                #hg-app .hg-modal {
                    position: fixed;
                    top: 0;
                    left:  0;
                    width:  100%;
                    height:  100%;
                    background:  rgba(0,0,0,.7);
                    display:  flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 5;
                }
                #hg-app .hg-modal .hg-modal-inner {
                    background: linear-gradient(135deg, #3f5058, #253339);
                    border-radius:  5px;
                    padding: 15px 30px;
                    position: relative;
                    width: 90%;
                    max-width: 500px;
                    max-height:  100%;
                    overflow-y:  scroll;
                    overflow-x:  hidden;
                }
                #hg-app .hg-modal .hg-modal-close {
                    position: absolute;
                    top: 5px;
                    right:  10px;
                    color:  #fff;
                    font-size:  16px;
                }
                #hg-app .hg-modal button {
                    display: block;
                    margin: 10px auto;
                    border: 2px solid #eee;
                    color: #fff;
                    background-color: transparent;
                    padding: 8px 20px;
                    border-radius: 8px;
                    font-size: 20px;
                    font-weight: bold;
                    cursor: pointer;
                }
                #hg-app h2.greeting-edit {
                    display:  inline-block;
                    margin-bottom:  10px;
                    border:  solid 2px #29373d;
                    border-radius: 5px;
                    padding:  10px 30px;
                    position:  relative;
                }
                #hg-app .order-label, #hg-app h2.greeting-edit span.edit-game-details {
                    position: absolute;
                    top: 0;
                    right: 0;
                    padding: 10px;
                    font-size: 15px;
                    line-height: 10px;
                    background: #29373d;
                    border-top-right-radius: 2px;
                }
                #hg-app h2.greeting-edit span.edit-game-details {
                    font-size: 12px;
                    padding: 5px 1px 5px 5px;
                    cursor:  pointer;
                }
                #hg-app .edit-game-field {
                    display:  flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                }
                #hg-app .edit-game-field input {
                    margin-bottom:  10px;
                    min-width: 300px;
                    text-align: center;
                    font-size:  20px;
                }
            </style>
        <?
        endif;
    }

    public function show_tiny_mce() {
        // conditions here
        wp_enqueue_script( 'common' );
        wp_enqueue_script( 'jquery-color' );
        wp_print_scripts('editor');
        if (function_exists('add_thickbox')) add_thickbox();
        wp_print_scripts('media-upload');
        if (function_exists('wp_tiny_mce')) wp_tiny_mce();
        wp_admin_css();
        wp_enqueue_script('utils');
        do_action("admin_print_styles-post-php");
        do_action('admin_print_styles');
        wp_enqueue_style('editor-buttons');
    }

    public function dashboard_app() {
        $this->show_tiny_mce();
        $user = wp_get_current_user();
        // Check if the specified role is present in the array.
        //if ( !in_array( 'administrator', $user->roles, true )
         //&&  in_array( 'hg_gamemaker', $user->roles, true ) ) : ?>
            <div id="hg-app">
                <router-view></router-view>
            </div>
            <script src="https://unpkg.com/vue@next"></script>
            <script src="https://unpkg.com/vuex@next"></script>
            <script src="https://unpkg.com/vue-router@4"></script>
            <script src="https://cdn.jsdelivr.net/npm/vue3-sfc-loader/dist/vue3-sfc-loader.js"></script>
            <!-- Helper -->
            <!-- <script src="<?php echo plugins_url(); ?>/humanities-games/components/tools/libs/lodash.4.17.15.js"></script> -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.21/lodash.min.js" integrity="sha512-WFN04846sdKMIP5LKNphMaWzU7YpMyCU245etK3g/2ARYbPK9Ub18eG+ljU96qKRCWh+quCY7yefSmlkQw1ANQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
            <script>
            /* No conflict with media uploader */
            var hgLodash = _.noConflict();
            const hgPluginBase = '<?php echo plugins_url(); ?>/humanities-games/';
            const hgOptions = {
                moduleCache: {
                    vue: Vue
                },
                async getFile( path ) {
                    
                    const res = await fetch('<?php echo plugins_url(); ?>/humanities-games/components'+path);
                    if ( !res.ok )
                        throw Object.assign(new Error(res.statusText + ' ' + path), { res });
                    return {
                        getContentData: asBinary => asBinary ? res.arrayBuffer() : res.text(),
                    }
                },
                addStyle(textContent) {
                    /* Adds CSS for us */
                    const style = Object.assign(document.createElement('style'), { textContent });
                    const ref = document.head.getElementsByTagName('style')[0] || null;
                    document.head.insertBefore(style, ref);
                },
            }
            const hgBase = () => loadModule( '/Base.vue', hgOptions );
            const hgDashboard = () => loadModule( '/Dashboard.vue', hgOptions );
            const hgSiteGames = () => loadModule( '/SiteGames.vue', hgOptions );
            const hgMyGames = () => loadModule( '/maker/MyGames.vue', hgOptions );
            const hgCreateGame = () => loadModule( '/maker/CreateGame.vue', hgOptions );
            const hgEditGame = () => loadModule( '/EditGame.vue', hgOptions );
            const hgEditMinigame = () => loadModule( '/EditMinigame.vue', hgOptions );
            const hgPlayGame = () => loadModule( '/PlayGame.vue', hgOptions );
            const hgCreateMinigame = () => loadModule( '/maker/CreateMinigame.vue', hgOptions );
            //const PageUploader = () => loadModule( '/tools/PageUploader.vue', hgOptions );

            const hgRoutes = [
                { path: '/', component: hgDashboard, name: 'Dashboard' },
                { path: '/games/site', component: hgSiteGames },
                { path: '/games/me', component: hgMyGames },
                { path: '/games/create', component: hgCreateGame },
                { path: '/game/edit/:site/:id', component: hgEditGame, name: 'edit-game' },
                { path: '/minigame/edit/:site/:id', component: hgEditMinigame, name: 'edit-minigame' },
                { path: '/game/play/:site/:id', component: hgPlayGame, name: 'play-game' },
                { path: '/minigame/:site/:id', component: hgPlayGame, name: 'play-demo' },
                { path: '/minigames/create/:site/:id', component: hgCreateMinigame }
            ]
            /*
            
            { path: '/game/:site/:id/minigame/:mid', component: hgMinigameSingle }
            */
            const hgRouter = VueRouter.createRouter({
                /* Hash option avoids WordPress trying to path itself */
                history : VueRouter.createWebHashHistory(),
                routes : hgRoutes
            })
            /* Vuex stores global (app) variables and methods */
            const hgStore = Vuex.createStore({
                state () {
                    return {
                        siteName : '',
                        siteID : 0,
                        subdomain : '',
                        userDisplayName : '',
                        view : ''
                    }
                },
                getters : {
                    userDisplayName ( state ) {
                        return state.userDisplayName
                    },
                    siteID ( state ) {
                        return state.siteID
                    },
                    view ( state ) {
                        return state.view
                    }
                },
                mutations : {
                    setupUser ( state, data ) {
                        state.subdomain = data.subdomain;
                        state.userDisplayName = data.userDisplayName;
                        state.siteName   = data.siteName;
                        state.siteID   = data.siteID;
                        state.view = data.view;
                    }
                },
                actions : {
                    async sendRequest( store, action = {} ) {
                        console.log('here we go');
                        action.method = (typeof action.method !== 'undefined') ? action.method : 'GET'; 
                        try {
                            var url = (typeof action.url !== 'undefined') ? action.url : '/wp-json/humanities-games/v2' + action.path;
                            if( action.method === 'GET' ) {
                                var response = await fetch( url );
                                return await response.json();
                            } else if( action.method === 'POST' ) {
                                var response = await fetch( url, {
                                    method: action.method,
                                    headers: { 'Content-Type' : 'application/json' }, 
                                    body: JSON.stringify( action.data )
                                });
                                return await response.json();
                            } else if( action.method === 'PUT' ) {
                                /* Image Upload */
                                var response = await fetch( url, {
                                    method: action.method,
                                    headers: { 'Content-Type' : 'image/jpeg' }, 
                                    body: action.data
                                });
                                var result = await response;
                                if( result.status == 200 )  {
                                    return true
                                } else {
                                    return false;
                                }
                            }
                        } catch( error ) {
                            console.log('error',error);
                            /* Better errors but for now */
                            alert(error);
                        }
                    }
                }
            });
            /* So we don't have to uglify this code with Webpack */
            const { loadModule } = window['vue3-sfc-loader'];
            /* Define the base app everything else lives within */
            const hgApp = Vue.createApp(
                Vue.defineAsyncComponent(() => loadModule('/Base.vue', hgOptions))
            );
            /* For subpages */ 
            hgApp.use( hgRouter );
            /* Vuex */
            hgApp.use( hgStore );
            /* Register Loading Spinner */
            hgApp.component( 'Loader', { template : '<div class="hg-loading"><div></div><div></div></div>' });
            /* How we upload pages to the remote API */
            hgApp.component( 'PageUploader', Vue.defineAsyncComponent(() => loadModule('/tools/PageUploader.vue', hgOptions ) ) )
            /* WP Wysiwyg */
            hgApp.component( 'WYSIWYG', Vue.defineAsyncComponent(() => loadModule('/tools/WP-WYSIWYG.vue', hgOptions ) ) )
            /* Launch the app */
            hgApp.mount('#hg-app');
            </script>
        <?php //endif;
    }
}
new HG_Display();

?>