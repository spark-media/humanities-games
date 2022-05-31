<?php
class HG_Main {

	public $version = '2.0';
	public $db_version = '2.0';
	private $file_base;

    public function __construct() {
    	$this->file_base = plugin_dir_path( dirname( __FILE__ ) ) . 'humanities-games.php';
        register_activation_hook( $this->file_base, array($this,'activate') );
        register_deactivation_hook( $this->file_base, array($this,'deactivate') );

		/* Send makers to the app. */
		add_filter( 'login_redirect', array($this,'redirect_makers_to_app' ), 10, 3 );
		add_action( 'init', array($this,'register_games') );
		add_action( 'init', array( $this, 'setup_rewrites' ) );
		add_filter( 'query_vars', array( $this, 'setup_query_vars' ) );
		add_action( 'template_include', array( $this, 'embed_template' ) );
		add_action( 'rest_api_init', array( $this, 'register_api_routes' ) );
		remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
		add_filter( 'rest_pre_serve_request', array($this,'init_cors'));
		/* Check for updates */
		add_action( 'plugins_loaded', array( $this, 'update_check' ) );
		/* New multisite setup */
		add_action( 'wp_initialize_site', array( $this, 'setup_site_roles' ), 9999 );
		/* API key */
		add_action( 'network_admin_menu', array($this,'add_settings_page' ) );
		add_action( 'network_admin_edit_hg-update', array( $this, 'settings_update' ) );
    }

    /**
     * Makes any necessary changes to previous versions.
     * For now, old versions will not be checked to meter upgrade tasks.
     */
    public function update_check() {
    	if ( get_option( 'hg_db_version' ) != $this->db_version ) {
        	$this->upgrade_database();
    	}
    	if ( get_option( 'hg_plugin_version' ) != $this->version ) {
        	$this->upgrade_plugin();
    	}
    }

    /**
     * Tasks that are required for new or updated plugin version.
     */
    private function upgrade_plugin() {
    	update_option( 'hg_plugin_version', $this->version );
    }

    /**
     * Tasks required for a Database upgrade.
     */
    private function upgrade_database() {
    	update_option( 'hg_db_version', $this->db_version );
    }

    public function setup_rewrites() {
    	add_rewrite_rule(
		    '^hg-embed/([a-zA-Z0-9-]+)/([a-zA-Z0-9-]+)\/?([a-zA-Z0-9-]+)?\/?$',
		    'index.php?hgsite=$matches[1]&hgame=$matches[2]',
		    'top'
		);
    }

    public function add_settings_page() {
    	add_submenu_page(
      	'settings.php',
      	__( 'humanities.games Settings', 'multisite-settings' ),
      	__( 'humanities.games Settings', 'multisite-settings' ),
      	'manage_network_options',
      	'hg-settings',
      	array( $this, 'plugin_settings_page' )
    	);

    	// Register a new section on the page.
	    add_settings_section(
	      'hg-section',
	      __( 'API Access', 'hg-settings' ),
	      array( $this, 'section_first' ),
	      'hg-settings'
	    );
	    // Register a variable and add a field to update it.
	    register_setting( 'hg-settings', 'hg_api_key' );
	    add_settings_field(
	      'hg_api_key',
	      __( 'API Key', 'multisite-settings' ),
	      array( $this, 'hg_api_key' ), // callback.
	      'hg-settings', // page.
	      'hg-section' // section.
	    );
    	//add_options_page( 'humanities.games', 'humanities.games', 'manage_options', 'hg-settings', array($this,'plugin_settings_page') );
	}

	public function plugin_settings_page() {
     	if ( isset( $_GET['updated'] ) ) : ?>
      		<div id="message" class="updated notice is-dismissible">
        		<p><?php esc_html_e( 'API Key Updated', 'multisite-settings' ); ?></p>
      		</div>
    	<?php endif; ?>
    		<div class="wrap">
      			<h1><?php echo esc_attr( get_admin_page_title() ); ?></h1>
      			<form action="edit.php?action=hg-update" method="POST">
        		<?php
            		settings_fields( 'hg-settings' );
            		do_settings_sections( 'hg-settings' );
            		submit_button();
        		?>
      			</form>
    		</div>
    	<?php
	}

	public function section_first() {
    	esc_html_e( 'To process paper packets, a free external service must be linked. You can request an API key by emailing info@humanities.games with the subject line "humanities.games API Access."', 'hg-settings' );
  	}

  	public function hg_api_key() {
    	$val = get_site_option( 'hg_api_key', '' );
    	echo '<input type="text" name="hg_api_key" value="' . esc_attr( $val ) . '" />';
  	}

  	/**
   	* Multisite options require its own update function. Here we make the actual update.
   	*/
  	public function settings_update() {
    	check_admin_referer( 'hg-settings-options' );
    	global $new_whitelist_options;

    	$options = $new_whitelist_options[ 'hg-settings' ];
    	foreach ( $options as $option ) {
      		if ( isset( $_POST[ $option ] ) ) {
        		update_site_option( $option, $_POST[ $option ] );
      		}
    	}

    	wp_safe_redirect(
      		add_query_arg(
        		array(
          			'page'    => 'hg-settings',
          			'updated' => 'true',
        		),
        		network_admin_url( 'settings.php' )
      		)
    	);
    	exit;
  	}

    public function setup_query_vars( $query_vars ) {
    	$query_vars[] = 'hgsite';
    	$query_vars[] = 'hgame';
    	return $query_vars;
    }

    public function redirect_makers_to_app( $redirect_to, $request, $user ) {
	    global $user;
	    if ( isset( $user->roles ) && is_array( $user->roles ) ) {

	        if ( in_array( 'hg_gamemaker', $user->roles ) && ! in_array( 'administrator', $user->roles ) ) {
	            return admin_url( '/admin.php?page=humanities-games' );
	        } else {
	            return $redirect_to;
	        }
	    } else {
	        return $redirect_to;
	    }
	}

    public function embed_template( $template ) {
    	if ( get_query_var( 'hgsite' ) == false 
    		|| get_query_var( 'hgsite' ) == ''
    		|| get_query_var( 'hgame' ) == false 
    		|| get_query_var( 'hgame' ) == '' ) {
        	return $template;
    	}
    	return plugin_dir_path( dirname( __FILE__ ) ) . '/views/embed.php';
    }

	/**
	 * Activate the plugin.
	*/
	public function activate() { 
	    /* Create the hg_gamemaker role */
	    $this->setup_roles();
	    /* Run installation if new or updated. */
	    $this->update_check();
	    /* Register the games and flush so the permalink structure works. */
	    $this->register_games();
	    $this->setup_rewrites();
	    flush_rewrite_rules();
	}

	public function deactivate() {
		$this->remove_roles();
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}

	public function register_api_routes() {
		$controller = new HG_API();
    	$controller->register_routes();
	}

	public function setup_roles() {
    	$roles_set = get_option( 'hg_roles_set' );
	    if(!$roles_set){
	        add_role('hg_gamemaker', 'Game Maker', array(
	            'read' => true,
	            'upload_files' => true,
	            'edit_hgames' => true,
	            'edit_published_hgames' => true,
	            'publish_hgames' => true,
	            'delete_hgames' => true,
	            'delete_published_hgames' => true
	        ));
	        update_option( 'hg_roles_set', true );
	    }
    }

	public function setup_site_roles( $new_site ) {
		switch_to_blog( $new_site->blog_id );
		$this->setup_roles();
	    restore_current_blog();
    }

    private function remove_roles() {
    	remove_role('hg_gamemaker');
    	update_option('hg_roles_set',false);
    }

	public function register_games() {
		global $post;
		if(!post_type_exists('hgame')) {
			$labels = array(
			'name'               => _x( 'Games', 'post type general name', 'humanities-games' ),
			'singular_name'      => _x( 'Game', 'post type singular name', 'humanities-games' ),
			'menu_name'          => _x( 'Games', 'admin menu', 'humanities-games' ),
			'name_admin_bar'     => _x( 'Game', 'add new on admin bar', 'humanities-games' ),
			'add_new'            => _x( 'Add New', 'book', 'humanities-games' ),
			'add_new_item'       => __( 'Add New Game', 'humanities-games' ),
			'new_item'           => __( 'New Game', 'humanities-games' ),
			'edit_item'          => __( 'Edit Game', 'humanities-games' ),
			'view_item'          => __( 'View Game', 'humanities-games' ),
			'all_items'          => __( 'All Games', 'humanities-games' ),
			'search_items'       => __( 'Search Games', 'humanities-games' ),
			'parent_item_colon'  => __( 'Parent Games:', 'humanities-games' ),
			'not_found'          => __( 'No games found.', 'humanities-games' ),
			'not_found_in_trash' => __( 'No games found in Trash.', 'humanities-games' )
			);

			$args = array(
				"labels" => $labels,
				"description" => "",
				"public" => true,
				"show_ui" => false,
				"has_archive" => false,
				"show_in_menu" => true,
				"exclude_from_search" => true,
				"capability_type" => "post",
				'capabilities' => array(
			        'edit_post' => 'edit_hgame',
			        'edit_posts' => 'edit_hgames',
			        'edit_others_posts' => 'edit_other_hgames',
			        'publish_posts' => 'publish_hgames',
			        'read_post' => 'read_hgame',
			        'read_private_posts' => 'read_private_hgame',
			        'delete_post' => 'delete_hgame',
			        'edit_published_posts' => 'edit_published_hgame'
			    ),
				"map_meta_cap" => true,
				"hierarchical" => false,
				"rewrite" => array( "slug" => "game", "with_front" => true ),
				"query_var" => false,
				"supports" => [ "title","author" ]
								
			);
			register_post_type( "hgame", $args );
		}
		/* Minigames */
		if(!post_type_exists('hminigame')) {
			$labels = array(
			'name'               => _x( 'Minigames', 'post type general name', 'humanities-games' ),
			'singular_name'      => _x( 'Minigame', 'post type singular name', 'humanities-games' ),
			'menu_name'          => _x( 'Minigames', 'admin menu', 'humanities-games' ),
			'name_admin_bar'     => _x( 'Minigame', 'add new on admin bar', 'humanities-games' ),
			'add_new'            => _x( 'Add New', 'book', 'humanities-games' ),
			'add_new_item'       => __( 'Add New Minigame', 'humanities-games' ),
			'new_item'           => __( 'New Minigame', 'humanities-games' ),
			'edit_item'          => __( 'Edit Minigame', 'humanities-games' ),
			'view_item'          => __( 'View Minigame', 'humanities-games' ),
			'all_items'          => __( 'All Minigames', 'humanities-games' ),
			'search_items'       => __( 'Search Minigames', 'humanities-games' ),
			'parent_item_colon'  => __( 'Parent Minigames:', 'humanities-games' ),
			'not_found'          => __( 'No minigames found.', 'humanities-games' ),
			'not_found_in_trash' => __( 'No minigames found in Trash.', 'humanities-games' )
			);

			/**
			 * Unlike games, minigames have no public display.
			 * To publish a single minigame, create a game
			 * then add a minigame as the single entry.
			 * This allows us to publish by default, 
			 * since "publishing" is adding to the active order.
			 */
			$args = array(
				"labels" => $labels,
				"description" => "",
				"public" => false,
				"show_ui" => false,
				"has_archive" => false,
				"show_in_menu" => false,
				"exclude_from_search" => true,
				"capability_type" => "post",
				'capabilities' => array(
			        'edit_post' => 'edit_hgame',
			        'edit_posts' => 'edit_hgames',
			        'edit_others_posts' => 'edit_other_hgames',
			        'publish_posts' => 'publish_hgames',
			        'read_post' => 'read_hgame',
			        'read_private_posts' => 'read_private_hgame',
			        'delete_post' => 'delete_hgame',
			        'edit_published_posts' => 'edit_published_hgame'
			    ),
				"map_meta_cap" => true,
				"hierarchical" => false,
				"rewrite" => array( "slug" => "minigame", "with_front" => false ),
				"query_var" => false,
				"supports" => [ "title","author" ]
								
			);
			register_post_type( "hminigame", $args );
		}
		/**
		 * Register a custom attachment taxonomy
		 */
		$labels = array(
			"name" => __( "Game Art Categories", "humanities-games" ),
			"singular_name" => __( "Game Art Category", "humanities-games" ),
		);
		$args = array(
			"label" => __( "Game Art Categories", "humanities-games" ),
			"labels" => $labels,
			"public" => false,
			"publicly_queryable" => false,
			"hierarchical" => true,
			"show_ui" => true,
			"show_in_menu" => false,
			"show_in_nav_menus" => true,
			"query_var" => true,
			"rewrite" => array( 'slug' => 'game-art-category', 'with_front' => false, ),
			"show_admin_column" => true,
			"show_in_rest" => false,
			"rest_base" => "hg_art_category",
			"rest_controller_class" => "WP_REST_Terms_Controller",
			"show_in_quick_edit" => false,
		);
		register_taxonomy( "hg_art_category", array( "attachment" ), $args );
	}

	public function init_cors( $value ) {
  		$origin_url = '*';
		// Check if production environment or not
		// if (ENVIRONMENT === 'production') {
		//   $origin_url = 'https://linguinecode.com';
		// }
		header( 'Access-Control-Allow-Origin: ' . $origin_url );
		header( 'Access-Control-Allow-Methods: GET' );
		header( 'Access-Control-Allow-Credentials: true' );
		return $value;
	}

}
new HG_Main();

?>