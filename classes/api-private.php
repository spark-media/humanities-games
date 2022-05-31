<?php
/**
 * This API connects makers and admins to varied routes, 
 * both in mobile and on desktop.
 */
class HG_API {

  /**
   * User object.
   */
  public $user;
  /**
   * If a user is authenticated with
   * Advanced Access Manager plugin,
   * which enables mobile app access via JSON Web Token.
   * False by default.
   */
  public $aam_user = false;

  /**
   * Response object.
   */
  public $response = array();

  /**
   * TODO move this into an options page.
   */
  private $api_url = 'https://api.humanities.games/page/v1.0';
  private $api_key = '';
  /**
   * Setup
   * Private APIs rely on either native WP sessions
   * or the Advanced Access Manager plugin,
   * which enables JWT capabilities for mobile apps.
   */
  public function __construct() {
    global $current_user;
    $this->api_key = get_site_option( 'hg_api_key', '' );
    $this->namespace     = '/humanities-games/v2';
    $this->resource_name = 'games';
    $this->aam_user = AAM::api()->getUser();
    /* AAM for the platform */
    if( $this->aam_user ) {
      $id = $this->aam_user->getId();
      wp_set_current_user($id);
      $sites = get_blogs_of_user( $id );
      $site_ids = array_keys( $sites );
      switch_to_blog( $site_ids[0] ); 
      $this->user = get_userdata($id);
      $this->user->sites = $sites;
      $this->user->site_ids = $site_ids;
    } else {
      /* For desktop if AAM is not enabled. */
      wp_get_current_user();
      if ( is_user_logged_in() ) { 
        $id = get_current_user_id();
        $site_ids = array_keys( $sites );
        switch_to_blog( $site_ids[0] ); 
        $this->user = get_userdata($id);
        $this->user->sites = $sites;
        $this->user->site_ids = $site_ids;
      } 
    }
  }

  /**
   * @return if a user is authenticated
   * and has roles considered to be an admin.
   */
    /* Valid roles */
  public $admin_roles = array('administrator');
  public function is_site_admin() {
    if( array_intersect($this->admin_roles, $this->user->roles ) ) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * @return if a user is authenticated
   * and has roles considered to be a game maker.
   */
  public $maker_roles = array('hg_gamemaker');
  public function is_game_maker() {
    if( array_intersect($this->maker_roles, $this->user->roles ) ) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * When required, $minigameTypes holds minigame options.
   * These live in games.json inside the plugin folder.
   * @return 
   */
  public $minigameTypes;

  /**
   * Retrieves defaults.json for a game type, 
   * which includes form fields.
   * @param $type the slug of the game type
   */
  public static function fetch_game_type( $type ) {
    $directory = plugin_dir_url(dirname(__FILE__));
    $url = $directory . 'minigames/' . $type . '/defaults.json';
    /* Make the request */
    $request = wp_remote_get( $url );
    /**
     * If the remote request fails, 
     * wp_remote_get() will return a WP_Error,  
     */
    if( is_wp_error( $request ) ) {
        /* Abort */
        return false;
    }
    /* Retrieve the data */
    $body = wp_remote_retrieve_body( $request );
    /* Convert to array */
    return json_decode( $body );
  }

  /**
   * Gets the list of active games, stored in games.json
   */
  public static function fetch_active_game_types() {
    $directory = plugin_dir_url(dirname(__FILE__));
    $url = $directory . 'minigames.json';
    /* Make the request */
    $request = wp_remote_get( $url );
    /**
     * If the remote request fails, 
     * wp_remote_get() will return a WP_Error,  
     */
    if( is_wp_error( $request ) ) {
        /* Abort */
        return false;
    }
    /* Retrieve the data */
    $body = wp_remote_retrieve_body( $request );
    $body = json_decode( $body );
    /* Convert to array */
    return $body;
  }

  /**
   * Merges the response properties if globals are always sent.
   * @param  array $response The function response.
   * @return 
   */
  public function return_data( $response ) {
    /* Merge in any "always" data */
    $this->response = array_merge( $this->response, $response );
    return rest_ensure_response( $this->response );
  }

  /**
   * Functions that check a user can do a request.
   */
  public $permissions = array(
    'create' => 'create_permissions_check',
    'read' => 'get_permissions_check',
    'update' => 'create_permissions_check',
    'delete' => 'create_permissions_check',
    'admin' => 'admin_permissons_check'
  );

  /**
   * Routes for our API and what they do.
   * By default, get_permissions_check 
   * is the permission check.
   */
  public $routes = array(
    /**
     * Initialize
     * Loads basic user/site data.
     * If user has existing games it also retrieves.
     */
    'init' => array(
      'function' => 'init',
      'method' => 'GET',
      'permission' => 'read'
    ),
    /**
     * Create a parent game.
     * @param title - Name of the game.
     */
    'game/create' => array(
      'function' => 'create_game',
      'method' => 'POST',
      'permission' => 'create'
    ),
    /**
     * Create a minigame within a game.
     * @param id - the parent game.
     */
    'minigame/create' => array(
      'function' => 'create_minigame',
      'method' => 'POST',
      'permission' => 'create'
    ),
    /**
     * Get either user games or site games.
     * @param type - 'me' or 'site'.
     */
    'list/me' => array(
      'function' => 'list_games',
      'method' => 'GET',
      'permission' => 'read'
    ),
    'list/site' => array(
      'function' => 'list_site_games',
      'method' => 'GET',
      'permission' => 'read'
    ),
    /**
     * Retrieve a parent game.
     * @param id - the existing game.
     */
    'games/(?P<sid>[\d]+)/(?P<id>[\d]+)' => array(
      'function' => 'get_game',
      'method' => 'GET',
      'permission' => 'read'
    ),
    /**
     * Retrieve a specific minigame.
     * @param sid - the site id.
     * @param mid - the minigame id.
     */
    'minigames/(?P<sid>[\d]+)/(?P<mid>[\d]+)' => array(
      'function' => 'get_minigame',
      'method' => 'GET',
      'permission' => 'read'
    ),
    /**
     * Update a parent game.
     * @param id - the parent game id.
     */
    'games/publish/(?P<sid>[\d]+)/(?P<id>[\d]+)' => array(
      'function' => 'publish_game',
      'method' => 'POST',
      'permission' => 'update'
    ), 
    'games/update/(?P<sid>[\d]+)/(?P<id>[\d]+)' => array(
      'function' => 'update_game',
      'method' => 'POST',
      'permission' => 'update'
    ),
    /**
     * Update minigame order for a parent game.
     * @param id - the parent game id.
     */
    'games/order/(?P<sid>[\d]+)/(?P<id>[\d]+)' => array(
      'function' => 'update_minigame_order',
      'method' => 'POST',
      'permission' => 'update'
    ),
    /**
     * Update a minigame.
     * @param id - the parent game id.
     * @param mid - the minigame id.
     */
    'minigame/update/(?P<sid>[\d]+)/(?P<mid>[\d]+)' => array(
      'function' => 'update_minigame',
      'method' => 'POST',
      'permission' => 'update'
    ),
    /**
     * Copy an existing game and its minigame components.
     * @param id - the existing game.
     */
    'game/copy/(?P<id>[\d]+)' => array(
      'function' => 'copy_game',
      'method' => 'POST',
      'permission' => 'create'
    ),
    /**
     * Delete a game and its minigame components.
     * @param id - the existing game.
     */
    'game/delete/(?P<id>[\d]+)' => array(
      'function' => 'delete_game',
      'method' => 'POST',
      'permission' => 'delete'
    ),
    /**
     * Delete a minigame from a game.
     * @param id - the parent game id.
     * @param mid - the minigame id.
     */
    'game/(?P<id>[\d]+)/minigame/(?P<mid>[\d]+)/delete' => array(
      'function' => 'delete_minigame',
      'method' => 'POST',
      'permission' => 'delete'
    ),
    /**
     * List minigame options from games.json
     */
    'minigames/options' => array(
      'function' => 'list_minigame_options',
      'method' => 'GET',
      'permission' => 'read'
    ),
    /**
     * Retrieve an AWS upload URL to process a packet page.
     */
    'page/process/(?P<sid>[\d]+)/(?P<mid>[\d]+)/(?P<pname>[a-zA-Z0-9_]+)' => array(
      'function' => 'request_page_processing',
      'method' => 'GET',
      'permission' => 'read'
    ),
    /**
     * Check on the status of a packet page.
     */
    'page/status/(?P<sid>[\d]+)/(?P<mid>[\d]+)/(?P<pname>[a-zA-Z0-9_]+)/(?P<sub>[a-zA-Z0-9_-]+)' => array(
      'function' => 'check_page_processing',
      'method' => 'GET',
      'permission' => 'read'
    ),
    /**
     * Upload images from the packet page to WP and return 
     */
    'page/apply/(?P<sid>[\d]+)/(?P<mid>[\d]+)' => array(
      'function' => 'store_processed_art',
      'method' => 'POST',
      'permission' => 'update'
    ),
  );

  /**
   * Register the routes to make them live.
   */
  public function register_routes() {
    foreach( $this->routes as $path => $data ) {
      $options = array(
        'methods'   => $data['method'],
        'callback'  => array( $this, $data['function'] ),
        'permission_callback' => array( $this, $this->permissions[ $data['permission'] ] )
      );
      /* TODO update permissions check. */
      register_rest_route( 
        $this->namespace, '/' . $path, array( $options ) 
      );
    }
  }

  /**
   * Load basic user/site data.
   * If a user has games, retrieve the parent games.
   * @param WP_REST_Request $request Current request.
   */
  public function init( $request ) {

    /* What we expect to return and defaults */
    $response = array(
      'siteName' => 'Site',
      'siteID' => 0,
      'subdomain' => '',
      'userDisplayName' => '',
      'view' => 'maker'
    );

    /* Fetch first (likely only) site they are a member of. */
    $site_name = $this->user->sites[ $this->user->site_ids[0] ]->blogname;
    $response[ 'siteName' ] = $site_name;
    $subdomain = str_replace( '.humanities.games', '', $this->user->sites[ $this->user->site_ids[0] ]->domain );
    $response[ 'siteID' ] = get_current_blog_id();
    /* Used for site-specific links */
    /* TODO multisite check */
    $response[ 'subdomain' ] = $subdomain;

    /* User display name. */
    $response[ 'userDisplayName' ] = $this->user->display_name;
    
    /* Check privileges to adjust response */
    $admin = $this->is_site_admin();
    if( $admin ) {
      $response[ 'view' ] = 'admin';
    }
    /* Return */
    return $this->return_data( $response );
  }

   /**
   * Gets either the user's game or the site's games.
   * @param WP_REST_Request $request Current request.
   */
  public function list_minigame_options( $request ) {
    /* What we expect to return and defaults */
    $response = array(
      'minigames' => array()
    );

    /* Fetch game types */
    $this->minigameTypes = $this::fetch_active_game_types();
    if( $this->minigameTypes ) {
      $response[ 'minigames' ] = $this->minigameTypes->games;
    }

    /* Return */
    return $this->return_data( $response );
  } 

  /**
   * Gets the site's games.
   * @param WP_REST_Request $request Current request.
   */
  public function list_site_games( $request ) {
    /* What we expect to return and defaults */
    $response = array(
      'games' => array()
    );
    /* TODO site vs user. */
    /* Fetch parent games */
    $args = array(
      'post_type' => "hgame",
      'posts_per_page' => -1,
      'post_status' => array( 'publish', 'draft' )
    );
    /* If an admin, allow draft. */
    if( $admin ) {
      $args['post_status'] = array( 'publish', 'draft' );
    }
    $counter = 0;
    $query = new WP_Query( $args );
    while( $query->have_posts() ) {
      $query->the_post();
      $id = get_the_ID();
      $response[ 'games' ][ $counter ][ 'title' ] = html_entity_decode( get_the_title() );
      $response[ 'games' ][ $counter ][ 'id' ] = get_the_ID();
      $response[ 'games' ][ $counter ][ 'status' ] = get_post_status();
      $response[ 'games' ][ $counter ][ 'link' ] = get_permalink();
      $response[ 'games' ][ $counter ][ 'site' ] = get_current_blog_id();

      /* Owner status */
      $response[ 'games' ][ $counter ][ 'owner' ] = false;
      if( get_the_author_meta( 'ID' ) == $this->user->ID ) {
        $response[ 'games' ][ $counter ][ 'owner' ] = true;
      }
      $response[ 'games' ][ $counter ][ 'author' ] = get_the_author_meta( 'user_nicename' );

      $counter ++;
    }
    /* Return */
    return $this->return_data( $response );
  }

  /**
   * Gets the user's games.
   * @param WP_REST_Request $request Current request.
   */
  public function list_games( $request ) {
    /* What we expect to return and defaults */
    $response = array(
      'games' => array()
    );
    /* TODO site vs user. */
    /* Fetch parent games */
    $args = array(
      'post_type' => "hgame",
      'posts_per_page' => -1,
      'post_status' => array( 'publish', 'draft', 'trash' )
    );
    /* If not an admin, get only games related to the user. */
    if( !$admin ) {
      $args['author'] = $this->user->ID;
    }
    $counter = 0;
    $query = new WP_Query( $args );
    while( $query->have_posts() ) {
      $query->the_post();
      $id = get_the_ID();
      $response[ 'games' ][ $counter ][ 'title' ] = html_entity_decode( get_the_title() );
      $response[ 'games' ][ $counter ][ 'id' ] = get_the_ID();
      $response[ 'games' ][ $counter ][ 'status' ] = get_post_status();
      $response[ 'games' ][ $counter ][ 'link' ] = get_permalink();
      $response[ 'games' ][ $counter ][ 'site' ] = get_current_blog_id();

      /* Owner status */
      $response[ 'games' ][ $counter ][ 'owner' ] = false;
      if( get_the_author_meta( 'ID' ) == $this->user->ID ) {
        $response[ 'games' ][ $counter ][ 'owner' ] = true;
      }

      $counter ++;
    }
    /* Return */
    return $this->return_data( $response );
  }

  /**
   * Creates a WP entry for hgame
   * @param WP_REST_Request $request Current request.
   * @param title is expected - the name of the game.
   */
  public function create_game( $request ) {
    /* What we expect to return and defaults */
    $response = array(
      'created' => false,
      'id' => 0
    ); 

    /* Create post object */
    $title = ( string ) $request->get_param( 'title' );
    $author = $this->user->ID;
    $title = htmlentities( html_entity_decode( wp_strip_all_tags( $title ) ) );
    $game_post = array(
      'post_title'    =>  $title,
      'post_content'  => " ",
      'post_status'   => 'draft',
      'post_author'   => $author,
      'post_type' => 'hgame'
    );

    /* Insert the post into the database */
    $new_id = wp_insert_post( $game_post, true );
    if( $new_id > 0 ){
      $response[ 'created' ] = true;
      $response[ 'id' ] = $new_id;
    }

    /* Return */
    return $this->return_data( $response );
  }

  /**
   * Retrieves a single game by ID
   * @param site_id is the site id
   * @param game_id is the game id
   */
  public static function get_game_by_id( $game_id = 0, $site_id = 0 ) {
    /* TODO */
  }

  /**
   * Retrieves a single game.
   * @param WP_REST_Request $request Current request.
   * @param sid is the site id
   * @param id is the game id
   */
  public function get_game( $request ) {
    /* What we expect to return and defaults */
    $response = array(
      'minigames' => array(),
      'order' => array(),
      'title' => '',
      'post_status' => 'publish',
      'link' => ''
    );

    /* Save variables */
    $game_id = (int) $request['id'];
    $site_id = (int) $request['sid'];

    /**
     * Retrieve the game, then check if either
     * A. User is game owner, or
     * B. Game is published status and set to public in meta
     * User IDs are unique network-wide.
     */
    if( get_current_blog_id() != $site_id ) {
      /* Change to correct blog. */
      switch_to_blog( $site_id );
    }

    $post = get_post( $game_id );
    if ( empty( $post ) ) {
      /* Return */
      return $this->return_data( $response );
    }

    /* See if owner */
    $continue = false;
    $owner = false;
    if( $post->post_author != $this->user->ID ) {
      /* See if visibility is set to public */
      if( metadata_exists( 'hgame', $post->ID, 'visibility' ) ) {
        if( get_post_meta( $post->ID, 'visibility', true ) == 'public' && $post->post_status == 'publish' ) {
          /* It is published and public. */
          $continue = true;
        }
      }
    } else {
      /* Owner */
      $continue = true;
      $owner = true;
    }

    if( ! $continue ) {
      /* Return */
      return $this->return_data( $response );
    }

    /* Minigame order */
    $order = get_post_meta( $post->ID, 'minigame_order', true );
    if( $order ) {
      $response[ 'order' ] = $order;
    }

    if( $post->post_status == 'publish' ) {
      $response['link'] = get_permalink( $post->ID );
    }

    /**
     * Minigames save connected games as an array (so they can appear in multiple)
     */
    $args = array(
      'post_type' => 'hminigame',
      'posts_per_page' => -1,
      'meta_query' => array(
        array(
          'key'     => 'in_hgame',
          'value'   => serialize( strval( $game_id ) ),
          'compare' => 'LIKE'
        )
      )
    );
    $minigames = array();
    $query = new WP_Query( $args );
    while( $query->have_posts() ) : $query->the_post();
      /* Retrieve all minigames. */
      $mid = get_the_ID();
      $minigames[ $mid ]['title'] = html_entity_decode( get_the_title() );
      $minigames[ $mid ][ 'type' ] = get_post_meta( $mid, 'type', true );
      $minigames[ $mid ][ 'site' ] = $site_id;
      $minigames[ $mid ][ 'id' ] = $mid;
      $minigames[ $mid ][ 'owner' ] = $owner;
      $counter ++;
    endwhile;

    /* Apply our response */
    foreach ( $response as $key => $prop ) {
      if( isset( $post->{ $key } ) ) {
        $response[ $key ] = $post->{ $key };
      }
    }
    $response[ 'title' ] = $post->post_title;
    $response[ 'minigames' ] = $minigames;

    /* Return */
    return $this->return_data( $response );
  }

  public function update_minigame_order( $request ) {
    /* What we expect to return and defaults */
    $response = array(
      'success' => false,
      'order' => array()
    );

    /* Save variables */
    $game_id = (int) $request['id'];
    $site_id = (int) $request['sid'];

    /**
     * Retrieve the game, then check if either
     * A. User is game owner, or
     * B. Game is published status and set to public in meta
     * User IDs are unique network-wide.
     */
    if( get_current_blog_id() != $site_id ) {
      /* Change to correct blog. */
      switch_to_blog( $site_id );
    }

    $post = get_post( $game_id );
    if ( empty( $post ) ) {
      /* Return */
      return $this->return_data( $response );
    }

    /* See if owner */
    $continue = false;
    $owner = false;
    if( $post->post_author != $this->user->ID ) {
      /* See if visibility is set to public */
      if( metadata_exists( 'hgame', $post->ID, 'visibility' ) ) {
        if( get_post_meta( $post->ID, 'visibility', true ) == 'public' && $post->post_status == 'publish' ) {
          /* It is published and public. */
          $continue = true;
        }
      }
    } else {
      /* Owner */
      $continue = true;
      $owner = true;
    }

    if( ! $continue ) {
      /* Return */
      return $this->return_data( $response );
    }

    /* Get the post meta. Cast minigame IDs as strings. */
    $minigame_id = ( string ) $request->get_param( 'mid' );
    $order_action = ( string ) $request->get_param( 'action' );
    $valid_actions = array( 'before', 'after', 'append', 'remove' );
    if( !in_array( $order_action, $valid_actions ) ) {
      return $this->return_data( $response );
    }
    $order = get_post_meta( $post->ID, 'minigame_order', true );
    $success = false;
    /* Complete the task. */
    switch( $order_action ) {
      case 'before' :
        $index = array_search( $minigame_id, $order );
        if ( $index !== false && $index > 0 ) {
          unset( $order[ $index ] );
          $order = array_values( $order );
          $index--;
          array_splice( $order, $index, 0, $minigame_id ); 
          /* Save */
          update_post_meta( $post->ID, 'minigame_order', $order );
          $success = true;
        }
      break;
      case 'after' :
        $index = array_search( $minigame_id, $order );
        if ( $index !== false && $index < ( count( $order ) - 1 ) ) {
          unset( $order[ $index ] );
          $order = array_values( $order );
          $index++;
          array_splice( $order, $index, 0, $minigame_id ); 
          /* Save */
          update_post_meta( $post->ID, 'minigame_order', $order );
          $success = true;
        }
      break;
      case 'append' : 
        /* Append */
        $order[] = $minigame_id;
        /* Save */
        update_post_meta( $post->ID, 'minigame_order', $order );
        $success = true;
      break;
      case 'remove' :
        $index = array_search( $minigame_id, $order);
        if ( $index !== false ) {
          array_splice( $order, $index, 1 );
          /* Save */
          update_post_meta( $post->ID, 'minigame_order', $order );
          $success = true;
        }
      break;
    }

    /* Apply the response */
    $response[ 'order' ] = $order;
    $response[ 'success' ] = $success;

    /* Return */
    return $this->return_data( $response );
  }

  /**
   * Copy a game and all minigames.
   * @param id - Game ID.
   */
  public function copy_game( $request ) {
    /* This method requires $wpdb */
    global $wpdb;

    /* What we expect to return and defaults */
    $response = array(
      'success' => false,
      'message' => ''
    );
   
    /* Set the required parameters. */
    $id = (int) $request[ 'id' ];
    $post = get_post( $id );

    /*
     * For now, no user can copy anyone else's game.
     */
    if( $post->post_author != $this->user->ID ) {
      $response[ 'message' ] = 'You can only copy your own games.';
      return $this->return_data( $response );
    }

    /*
     * if post data exists, create the post duplicate
     */
    if ( ! isset( $post ) || $post == null ) {
      $response[ 'message' ] = 'This game cannot be found.';
      return $this->return_data( $response );
    } else {

      $response[ 'message' ] = 'So far so good.';
      return $this->return_data( $response );
      /* Update with new post data */
      $post->post_status = 'draft';

      /* For when people can remix other games. */
      $post->post_author = $new_post_author;
      $post->post_title = $post->post_title . " (Copy)";

      /* Give a new ID */
      unset( $post->ID );

      /* Insert the post by wp_insert_post() function */
      $new_post_id = wp_insert_post( $post );

      /**
       * For now, no taxonomy associations.
       */
      /*
      $taxonomies = get_object_taxonomies( $post->post_type ); 
      // returns array of taxonomy names for post type, ex array("category", "post_tag"); 
      foreach ($taxonomies as $taxonomy) {
        $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs') );
        wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
      }
      */
      /*
       * Duplicate all post meta
       */
      $post_meta_infos = $wpdb->get_results( "SELECT meta_key, meta_value FROM ".$wpdb->postmeta . " WHERE post_id=" . $id );
      if ( count( $post_meta_infos ) != 0 ) {
        $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
        foreach ($post_meta_infos as $meta_info) {
          $meta_key = $meta_info->meta_key;
          $meta_value = addslashes( $meta_info->meta_value );
          $sql_query_sel[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
        }
        $sql_query.= implode(" UNION ALL ", $sql_query_sel);
        $wpdb->query($sql_query);
      }
      $this->copy_game_minigames( $id, $new_post_id, $new_post_author );

      /* Return */
      $response[ 'success' ] = true;
      return $this->return_data( $response );
    }
  }

  protected function copy_game_minigames( $oldID, $newID, $author ) {
    global $wpdb;
    $args = array('post_type'=>'hgame'.$oldID.'_minigame','posts_per_page'=>-1);
    $array = array();
    $minigameIDs = array();
    $counter = 0;
    $query = new WP_Query($args);
    while($query->have_posts()) :
      $query->the_post();

      /*
       * new post data array
       */
      $oldMinigameID = get_the_ID();
      $title = get_the_title();
      $content = get_the_content();
      $args = array(
        'post_author'    => $author,
        'post_content'   => $content,
        'post_status'    => 'publish',
        'post_title'     => $title,
        'post_type'      => 'hgame'.$newID.'_minigame',
      );

      /*
       * insert the post by wp_insert_post() function
       */
      $new_post_id = wp_insert_post( $args );
      /* Create an array to convert the meta tag*/
      $minigameIDs[$oldMinigameID] = $new_post_id;
      /*
       * get all current post terms ad set them to the new post draft
       */
      // $taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
      // foreach ($taxonomies as $taxonomy) {
      //   $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
      //   wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
      // }

      /*
       * duplicate all post meta just in two SQL queries
       */
      $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM ".$wpdb->postmeta." WHERE post_id=".$oldMinigameID);
      if (count($post_meta_infos)!=0) {
        $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
        foreach ($post_meta_infos as $meta_info) {
          $meta_key = $meta_info->meta_key;
          $meta_value = addslashes($meta_info->meta_value);
          $sql_query_sel[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
        }
        $sql_query.= implode(" UNION ALL ", $sql_query_sel);
        $wpdb->query($sql_query);
      }
    endwhile;
    /* convert the minigame_order meta for the new post to correct for IDs */
    $minigames = get_post_meta($newID,'minigame_order');
    //$minigames = $minigames[0];
    //print_r($minigames[0]);
    foreach($minigames[0] as $key => $minigame){
      if(array_key_exists($minigame['id'],$minigameIDs)){
        /* 
        $minigames is the array of old IDs that are activated
        $key is the position (indexed array)
        $minigameIDs is the array of old/new IDs
        $minigame is the oldID that references the new ID
        */
        $minigames[$key]['id'] = $minigameIDs[$minigame['id']];
      }
    }
    update_post_meta($newID,'minigame_order',$minigames);
  }


  /**
   * Creates a WP entry for hminigame
   * @param WP_REST_Request $request Current request.
   * @param type is expected - the unique slug of the minigame.
   * @param gameID is possible - the game to attach it to.
   * @param siteID is expected
   */
  public function create_minigame( $request ) {
    /* What we expect to return and defaults */
    $response = array(
      'created' => false,
      'added' => false,
      'id' => 0,
      'title' => ''
    );

    /* Parse the game type */
    $type = ( string ) $request->get_param( 'type' );
    $game_id = (int) $request->get_param( 'gameID' );
    $site_id = (int) $request->get_param( 'siteID' );

    if( get_current_blog_id() != $site_id ) {
      /* Change to correct blog. */
      switch_to_blog( $site_id );
    }

    /* Get games.json for the default title. */
    $this->minigameTypes = $this::fetch_active_game_types();
    if( ! $this->minigameTypes 
      || ! isset( $this->minigameTypes->games ) 
      || ! isset( $this->minigameTypes->games->{ $type } ) ) {
      return $this->return_data( $response );
    }

    /* Default Title */
    $response[ 'title' ] = $this->minigameTypes->games->{ $type }->name;

    /* Create post object */
    $title = $response[ 'title' ];
    $author = $this->user->ID;
    $minigame_post = array(
      'post_title'    =>  $title,
      'post_content'  => " ",
      'post_status'   => 'publish',
      'post_author'   => $author,
      'post_type' => 'hminigame'
    );

    /* Insert the post into the database */
    $new_id = wp_insert_post( $minigame_post, true );
    if( $new_id > 0 ){
      $response[ 'created' ] = true;
      $response[ 'id' ] = $new_id;
      /* Add the post meta type */
      update_post_meta( $new_id, 'type', $type );
      /* If a game ID was sent, add it to an array of games, if allowed. */
      if( $game_id 
        && ( $this->is_site_admin() 
            || get_post_field( 'post_author', $game_id ) == $this->user->ID ) ) {
        if( $this->add_minigame_to_game( $new_id, $game_id ) ) {
          $response[ 'added' ] = true;
        }
      }
    }

    /* Return */
    return $this->return_data( $response );
  }

  /**
   * Attaches a minigame to a game, and active queue.
   * @param [type] $minigame_id The minigame to add
   * @param [type] $game_id     The game to connect it with.
   */
  protected function add_minigame_to_game( $minigame_id, $game_id ) {
    /* Start the added process with a flag */
    $added = false;
    /* Attach it to the game at least, if not the order. */
    update_post_meta( $minigame_id, 'in_hgame', array( strval( $game_id ) ) );
    /* For arrays we cast as strings for easier lookup */
    $minigame_id_string = strval( $minigame_id );
    /* See if the game has minigame_order set */
    if( ! metadata_exists('post', $game_id, 'minigame_order') ) {
      /* Create it */
      update_post_meta( $game_id, 'minigame_order', array( $minigame_id_string ) );
      $added = true;
    } else {
      /* Retrieve the current record. True is due to serializing */
      $order = get_post_meta( $game_id, 'minigame_order', true );
      /* Make sure it is not a duplicate. */
      if( ! in_array( $minigame_id_string, $order ) ) {
        /* Append */
        $order[] = $minigame_id_string;
        /* Save */
        update_post_meta( $game_id, 'minigame_order', $order );
        $added = true;
      }
    }
    return $added;
  }

  public function check_page_processing( $request ) {
    /* What we expect to return and defaults */
    $response = array(
      'success' => false,
      'status' => '',
      'assets' => array(),
      'submission_id' => 0,
      'error' => null
    );

    /* Save variables */
    $minigame_id = ( int ) $request['mid'];
    $site_id = ( int ) $request['sid'];
    $page_name = ( string ) $request['pname'];
    $submission_id = ( string ) $request['sub'];

    /**
     * Retrieve the game, then check if either
     * A. User is game owner, or
     * B. Game is published status and set to public in meta
     * User IDs are unique network-wide.
     */
    if( get_current_blog_id() != $site_id ) {
      /* Change to correct blog. */
      switch_to_blog( $site_id );
    }

    $post = get_post( $minigame_id );
    if ( empty( $post ) ) {
      /* Return */
      return $this->return_data( $response );
    }

    /* See if owner */
    $continue = false;
    $owner = false;
    if( $post->post_author == $this->user->ID ) {
      /* Owner */
      $continue = true;
      $owner = true;
    }

    if( ! $continue ) {
      /* Return */
      return $this->return_data( $response );
    }

    /* Get the minigame type */
    $minigame_type = get_post_meta( $minigame_id, 'type', true );

    /* Request the endpoint TODO more nuance */
    $url = $this->api_url.'/check/'.$minigame_type.'/'.$page_name . '/' . $submission_id;
    $remote = wp_remote_get( $url ,
      array( 
        'timeout' => 10,
        'headers' => array( 
          'x-api-key' => $this->api_key
        )
      )
    );
    $body = wp_remote_retrieve_body( $remote );
    $result = json_decode( $body );

    /* Apply our response */
    foreach ( $response as $key => $prop ) {
      if( isset( $result->{ $key } ) ) {
        $response[ $key ] = $result->{ $key };
      }
    }

    /* Return */
    return $this->return_data( $response );
  }

  public function request_page_processing( $request ) {
    /* What we expect to return and defaults */
    $response = array(
      'success' => false,
      'upload_url' => '',
      'submission_id' => 0,
      'error' => null
    );

    /* Save variables */
    $minigame_id = ( int ) $request['mid'];
    $site_id = ( int ) $request['sid'];
    $page_name = ( string ) $request['pname'];

    /**
     * Retrieve the game, then check if either
     * A. User is game owner, or
     * B. Game is published status and set to public in meta
     * User IDs are unique network-wide.
     */
    if( get_current_blog_id() != $site_id ) {
      /* Change to correct blog. */
      switch_to_blog( $site_id );
    }

    $post = get_post( $minigame_id );
    if ( empty( $post ) ) {
      /* Return */
      return $this->return_data( $response );
    }

    /* See if owner */
    $continue = false;
    $owner = false;
    if( $post->post_author == $this->user->ID ) {
      /* Owner */
      $continue = true;
      $owner = true;
    }

    if( ! $continue ) {
      /* Return */
      return $this->return_data( $response );
    }

    /* Get the minigame type */
    $minigame_type = get_post_meta( $minigame_id, 'type', true );

    /* Request the endpoint TODO more nuance */
    $remote = wp_remote_get( $this->api_url.'/process/'.$minigame_type.'/'.$page_name ,
      array( 
        'timeout' => 10,
        'headers' => array( 
          'x-api-key' => $this->api_key
        )
      )
    );
    $body = wp_remote_retrieve_body( $remote );
    $result = json_decode( $body );

    /* Apply our response */
    foreach ( $response as $key => $prop ) {
      if( isset( $result->{ $key } ) ) {
        $response[ $key ] = $result->{ $key };
      }
    }

    /* Return */
    return $this->return_data( $response );
  }

  /**
   * Download images from the API service and store as new WP uploads
   * Return the IDs and URLs for each image
   */
  public function store_processed_art( $request ) {
    /* What we expect to return and defaults */
    $response = array(
      'success' => false,
      'assets' => array()
    );

    /* Save variables */
    $minigame_id = (int) $request['mid'];
    $site_id = (int) $request['sid'];

    if( get_current_blog_id() != $site_id ) {
      /* Change to correct blog. */
      switch_to_blog( $site_id );
    }

    if( ! user_can( $this->user->ID, 'upload_files' ) ) {
      /* Return */
      return $this->return_data( $response );      
    }

    /* Get the minigame type */
    $minigame_type = get_post_meta( $minigame_id, 'type', true );

    /* Loop through each param */
    $assets = $request->get_param( 'assets' );
    /* Necessary for uploading */
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once( ABSPATH . '/wp-admin/includes/taxonomy.php');

    /* Check / Create minigame category for  media. */
    $game_type = $this::fetch_game_type( $minigame_type );
    if( ! get_term_by('slug', $minigame_type, 'hg_art_category' ) ) {
      wp_insert_category( 
        array (
          'taxonomy' => 'hg_art_category',
          'cat_nicename' => $minigame_type,
          'cat_name' => $game_type->title
        ) 
      );
    }

    if( $assets && count( $assets ) > 0 ) {
      foreach( $assets as $asset ) {

        /* Upload the image */
        $attachment_id = media_sideload_image( 
          $asset[ 'url' ], 
          $minigame_id, 
          $asset['label'] . ' for ' . $game_type->title, 
          'id' 
        );

        /* Save transparency bounds */
        if( isset( $asset[ 'bounds' ] ) ) {
          add_post_meta( $attachment_id, 'game_bounds', json_encode( $asset[ 'bounds' ] ) );
        }

        /* Assign minigame category to the image */
        wp_set_object_terms( $attachment_id, $minigame_type, 'hg_art_category' );
        
        /* Return to store with game data. */
        $response['assets'][ $asset['file_name'] ][ 'id' ] = $attachment_id;
        $response['assets'][ $asset['file_name'] ][ 'bounds' ] = $asset['bounds'];
        /* Make sure we return the full version (if large). */
        $new_file = wp_get_original_image_url( $attachment_id );
        $response['assets'][ $asset['file_name'] ][ 'file' ] = $new_file;
      }

      /* Complete */
      $response[ 'success' ] = true;
    }

    /* Return */
    return $this->return_data( $response );
  }

  public function get_minigame( $request ) {
    /* What we expect to return and defaults */
    $response = array(
      'title' => '',
      'type' => '',
      'data' => array(),
      'defaults' => new stdClass()
    );

    /* Save variables */
    $minigame_id = (int) $request['mid'];
    $site_id = (int) $request['sid'];

    /**
     * Retrieve the game, then check if either
     * A. User is game owner, or
     * B. Game is published status and set to public in meta
     * User IDs are unique network-wide.
     */
    if( get_current_blog_id() != $site_id ) {
      /* Change to correct blog. */
      switch_to_blog( $site_id );
    }

    $post = get_post( $minigame_id );
    if ( empty( $post ) ) {
      /* Return */
      return $this->return_data( $response );
    }

    /* See if owner */
    $continue = false;
    $owner = false;
    if( $post->post_author == $this->user->ID ) {
      /* Owner */
      $continue = true;
      $owner = true;
    }

    if( ! $continue ) {
      /* Return */
      return $this->return_data( $response );
    }

    /* Get the minigame type */
    $minigame_type = get_post_meta( $minigame_id, 'type', true );

    /* Get the minigame save data, if any */
    $minigame_data = get_post_meta( $minigame_id, 'minigame_data', true );
    if( ! empty( $minigame_data ) ) {
      $response['data'] = $minigame_data;
    }

    /* Fetch minigame fields by type and merge with data. */
    $default_data = $this::fetch_game_type( $minigame_type );

    /* Apply our response */
    foreach ( $response as $key => $prop ) {
      if( isset( $post->{ $key } ) ) {
        $response[ $key ] = $post->{ $key };
      }
    }
    $response[ 'title' ] = $post->post_title;
    $response[ 'type' ] = $minigame_type;
    $response[ 'defaults' ] = $default_data;
    /* Return */
    return $this->return_data( $response );
  }

    public function delete_game( $request ) {
      // Create post object
      $id = (int) $request['id'];
      $post = get_post( $id );
      //check for admin
      $current_user = wp_get_current_user();
      $new_post_author = $current_user->ID;
      if($new_post_author != $post->post_author && !current_user_can( 'edit_others_posts' ) ){
        /* Only admins can copy games from other users. */
        return rest_ensure_response( array('error' => 'You can only delete your own games.') );
      }
      //delete post
      wp_delete_post($id);
      $args = array('post_type'=>'hgame'.$id.'_minigame','posts_per_page'=>-1);
      $query = new WP_Query($args);
      while($query->have_posts()) :
        $query->the_post();
        $minigameID = get_the_ID();
        wp_delete_post($minigameID);
      endwhile;
      return rest_ensure_response(array('deleted'=>true));
    }

    public function delete_minigame( $request ) {
      // Create post object
      $id = (int) $request['mid'];
      $game = (int) $request['id'];
      $post = get_post( $id );
      //check for admin
      $current_user = wp_get_current_user();
      $new_post_author = $current_user->ID;
      if($new_post_author != $post->post_author && !current_user_can( 'edit_others_posts' ) ){
        /* Only admins can copy games from other users. */
        return rest_ensure_response( array('error' => 'You can only delete your own minigames.') );
      }
      //delete post
      wp_delete_post($id);
      /* TODO Update content for the game it is attached to. */
      $keep = array();
      $minigames = get_post_meta($game,'minigame_order');
      foreach($minigames[0] as $minigame){
        if($minigame['id']!=$id){
          $keep[] = $minigame;
        }
      }
      update_post_meta($game,'minigame_order',$keep);
      return rest_ensure_response(array('deleted'=>true));
    }

    public function update_game( $request ) {
      /* What we expect to return and defaults */
      $response = array(
        'success' => false,
        'status' => 'draft',
        'title' => '',
        'link' => ''
      );

      /* Save variables */
      $game_id = (int) $request['id'];
      $site_id = (int) $request['sid'];

      if( get_current_blog_id() != $site_id ) {
        /* Change to correct blog. */
        switch_to_blog( $site_id );
      }

      $post = get_post( $game_id );
      if ( empty( $post ) ) {
        /* Return */
        return $this->return_data( $response );
      }

      /* See if owner */
      $continue = false;
      $owner = false;
      if( $post->post_author != $this->user->ID ) {
        /* See if visibility is set to public */
        if( metadata_exists( 'hgame', $post->ID, 'visibility' ) ) {
          if( get_post_meta( $post->ID, 'visibility', true ) == 'public' && $post->post_status == 'publish' ) {
            /* It is published and public. */
            $continue = true;
          }
        }
      } else {
        /* Owner */
        $continue = true;
        $owner = true;
      }

      if( ! $continue ) {
        /* Return */
        return $this->return_data( $response );
      }

      $newTitle = $request->get_param( 'title' );
      $newStatus = $request->get_param( 'status' );
      /* Save Game */
      $game_update = array(
        'ID'           => $game_id,
        'post_status' => $newStatus,
        'post_title' => $newTitle
      );
      $success = wp_update_post( $game_update );
      if( $success ) {
        $response[ 'success' ] = true;
      }
      $response[ 'status' ] = get_post_status( $game_id );
      $response[ 'title' ] = get_the_title( $game_id );
      $response[ 'link' ] = get_permalink( $game_id );
      /* Return */
      return $this->return_data( $response );
    }

    protected function upload_image( $imageComponent, $name, $id, $mid = 0 ) {
      /* Upload image to proper place. */
      //Get the base-64 string from data
      $filteredData=substr($imageComponent['dataUrl'], strpos($imageComponent['dataUrl'], ",")+1);

      //Decode the string
      $unencodedData=base64_decode($filteredData);
      $uploads = wp_upload_dir();
      $path = '/humanities-games/'.$id.'/';
      if( $mid > 0 ) {
        $path .= 'minigames/'.$mid.'/';
      }
      //Save the image
      if (!is_dir($uploads['basedir'].$path)) {
        // dir doesn't exist, make it
        mkdir($uploads['basedir'].$path,0775,true);
      }
      file_put_contents($uploads['basedir'].$path.$name.'.png', $unencodedData);
      return $uploads['baseurl'].$path.$name.'.png';
    }

  /**
   * Publish a game by id.
   * @param  [type] $request [description]
   * @return [type]          [description]
   */
  public function publish_game( $request ) {
    /* What we expect to return and defaults */
    $response = array(
      'success' => false,
      'post_status' => 'draft',
      'link' => ''
    );

    /* Save variables */
    $game_id = (int) $request['id'];
    $site_id = (int) $request['sid'];
    if( get_current_blog_id() != $site_id ) {
      /* Change to correct blog. */
      switch_to_blog( $site_id );
    }
    $post = get_post( $game_id );
    if ( empty( $post ) ) {
      /* Return */
      return $this->return_data( $response );
    }

    /* See if owner */
    $continue = false;
    $owner = false;
    if( $post->post_author != $this->user->ID ) {
      /* See if visibility is set to public */
      if( metadata_exists( 'hgame', $post->ID, 'visibility' ) ) {
        if( get_post_meta( $post->ID, 'visibility', true ) == 'public' && $post->post_status == 'publish' ) {
          /* It is published and public. */
          $continue = true;
        }
      }
    } else {
      /* Owner */
      $continue = true;
      $owner = true;
    }

    if( ! $continue ) {
      /* Return */
      return $this->return_data( $response );
    }

    /* Save Game */
    $game_update = array(
      'ID'           => $game_id,
      'post_status' => 'publish',
    );
    $success = wp_update_post( $game_update );
    if( $success ) {
      $response[ 'success' ] = true;
      $response[ 'post_status' ] = 'publish';
      $response[ 'link' ] = get_permalink( $game_id );
    }
    /* Return */
    return $this->return_data( $response );
  }
  /**
   * Update a minigame by id.
   * @param  [type] $request [description]
   * @return [type]          [description]
   */
  public function update_minigame( $request ) {
    /* What we expect to return and defaults */
    $response = array(
      'success' => false
    );

    /* Save variables */
    $minigame_id = (int) $request['mid'];
    $site_id = (int) $request['sid'];

    /**
     * Retrieve the game, then check if either
     * A. User is game owner, or
     * B. Game is published status and set to public in meta
     * User IDs are unique network-wide.
     */
    if( get_current_blog_id() != $site_id ) {
      /* Change to correct blog. */
      switch_to_blog( $site_id );
    }

    $post = get_post( $minigame_id );
    if ( empty( $post ) ) {
      /* Return */
      return $this->return_data( $response );
    }

    /* See if owner */
    $continue = false;
    $owner = false;
    if( $post->post_author != $this->user->ID ) {
      /* See if visibility is set to public */
      if( metadata_exists( 'hgame', $post->ID, 'visibility' ) ) {
        if( get_post_meta( $post->ID, 'visibility', true ) == 'public' && $post->post_status == 'publish' ) {
          /* It is published and public. */
          $continue = true;
        }
      }
    } else {
      /* Owner */
      $continue = true;
      $owner = true;
    }

    if( ! $continue ) {
      /* Return */
      return $this->return_data( $response );
    }

    /* Save the minigame data. */
    $data = $request->get_param( 'minigame_data' );
    $minigame_data = $data;
    update_post_meta( $minigame_id, 'minigame_data',  $minigame_data );

    /* Save Minigame title */
    $minigame_update = array(
      'ID'           => $minigame_id,
      'post_title'   => $request->get_param( 'title' ),
      'post_content' => ' ',
    );
    $success = wp_update_post( $minigame_update );
    if( $success ) {
      $response[ 'success' ] = true;
    }
    /* Return */
    return $this->return_data( $response );
  }

    /**
     * Check permissions for the request.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_permissions_check( $request ) {
      if ( ! $this->is_site_admin() && ! $this->is_game_maker() ) {
        return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view the post resource.' . $this->user->ID ), array( 'status' => $this->authorization_status_code() ) );
      }
      return true;
    }

    public function create_permissions_check( $request ) {
      if ( ! $this->is_site_admin() && ! $this->is_game_maker() ) {

        return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view the post resource.' ), array( 'status' => $this->authorization_status_code() ) );

      }
      return true;
    }



    /**
     * Matches the post data to the schema we want.
     *
     * @param WP_Post $post The comment object whose response is being prepared.
     */
    public function prepare_item_for_response( $post, $request ) {
      $post_data = array();

      $schema = $this->get_item_schema( $request );

        // We are also renaming the fields to more understandable names.
      if ( isset( $schema['properties']['id'] ) ) {
        $post_data['id'] = (int) $post->ID;
      }

      if ( isset( $schema['properties']['content'] ) ) {
        $post_data['content'] = apply_filters( 'the_content', $post->post_content, $post );
      }

      return rest_ensure_response( $post_data );
    }



    /**
     * Get our sample schema for a post.
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_item_schema( $request ) {
      $schema = array(
            // This tells the spec of JSON Schema we are using which is draft 4.
        '$schema'              => 'http://json-schema.org/draft-04/schema#',
            // The title property marks the identity of the resource.
        'title'                => 'post',
        'type'                 => 'object',
            // In JSON Schema you can specify object properties in the properties attribute.
        'properties'           => array(
          'id' => array(
            'description'  => esc_html__( 'Unique identifier for the object.', 'my-textdomain' ),
            'type'         => 'integer',
            'context'      => array( 'view', 'edit', 'embed' ),
            'readonly'     => true,
          ),
          'content' => array(
            'description'  => esc_html__( 'The content for the object.', 'my-textdomain' ),
            'type'         => 'string',
          ),
        ),
      );
      return $schema;
    }

    /** 
     * Sets up the proper HTTP status code for authorization.
     */
    public function authorization_status_code() {
      $status = 401;
      if ( is_user_logged_in() ) {
        $status = 403;
      }
      return $status;
    }
  }