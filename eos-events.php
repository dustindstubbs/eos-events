<?php

/**
 * @package eos_events
 */

/*
Plugin Name: EOS Events
Description: A quick solution for managing and displaying events.
Version: 0.1.3
Author: Dustin Stubbs
License GPLv2 or later
*/

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class eos_events
{

	public $plugin;

	//Passing variable to __construct for classes
	function __construct() {
		$this->plugin = plugin_basename( __FILE__ );
		add_action ( 'init', array( $this, 'eos_eventsPostType' ) );
	}

	function register() {
		// Admin panel css
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		// frontend css
		// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

		// Add admin pages
		add_action( 'admin_menu', array( $this, 'AdminPages' ) );

		// Add plugin page links
		add_filter( "plugin_action_links_$this->plugin", array( $this, 'settingsLink') );

		// Post columns
		add_filter( "manage_event_posts_columns", array( $this, 'eos_events_posts_columns') );

		add_action( "manage_event_posts_custom_column", array( $this, 'eos_events_posts_column' ), 10, 2 );

		add_filter( 'manage_edit-event_sortable_columns', array( $this, 'eos_events_sortable_columns' ) );

		add_action( 'pre_get_posts', array( $this, 'eos_events_orderby' ) );
		
		//Schedule the event trash cron
		if ( ! wp_next_scheduled( 'old_events_cron_hook' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'old_events_cron_hook' );
		}

		add_action( 'old_events_cron_hook', array( $this, 'trash_events' ) );

		add_shortcode( 'event_banner', array( $this, 'event_banner' ) );

		add_shortcode( 'event_callout', array( $this, 'event_callout' ) );

		add_shortcode( 'event_time', array( $this, 'event_time' ) );

	}

	public function event_time() {
		// Shortcode to display just the current event's time
		$event_date = get_post_meta( get_the_ID(), 'eos_events_meta_date', true );
		$event_time = get_post_meta( get_the_ID(), 'eos_events_meta_time', true );
		
		if ( !empty( $event_date ) ) {
			$event_date = date("l,\&\\n\b\s\p\;F\&\\n\b\s\p\;jS", strtotime( $event_date ) );
			$event_date .= ( !empty($event_date) && !empty($event_time) ) ? ' at ' : '';
		}else{
			$event_date = '';
		}
		if ( !empty( $event_time ) ) {
			if ( date( "i", strtotime( $event_time ) ) == '00' ) {
				$event_time = date( "ga", strtotime( $event_time ) );
			}else{
				$event_time = date( "g:ia", strtotime( $event_time ) );
			}
		}else{
			$event_time = '';
		}

		return $event_date.$event_time;

	}

	public function event_callout() {
		$event_ob = get_posts( array( 'post_type' => 'event' ) );
		if ( !empty( $event_ob[0] ) ) { 
			$event_ob = $event_ob[0];
		}
		// Make the date looks pretty no matter what
		if ( $event_ob != null ) {
			$event_date = get_post_meta( $event_ob->{'ID'}, 'eos_events_meta_date', true );
			if ( $event_date != null ) {
				$event_date = date("l,\&\\n\b\s\p\;F\&\\n\b\s\p\;jS", strtotime( $event_date ) );
			}else{
				$event_date = '';
			}
			$event_time = get_post_meta( $event_ob->{'ID'}, 'eos_events_meta_time', true );
			if ( $event_time != null ) {
				if ( date( "i", strtotime( $event_time ) ) == '00' ) {
					$event_time = ' at '. date( "ga", strtotime( $event_time ) );
				}else{
					$event_time = ' at '. date( "g:ia", strtotime( $event_time ) );
				}
			}else{
				$event_time = '';
			}
			$post_thumb = get_the_post_thumbnail_url( $event_ob->{'ID'} );
			$post_thumb = ($post_thumb != null) ? $post_thumb : plugins_url( '/assets/event-placeholder.webp', __FILE__ ) ;
			ob_start();?>
			<div class="row">
				<div class="col-lg-6 order-lg-2">
					<img alt="Current event featured image" class="rounded ratio ratio-16x9" src="<?php echo $post_thumb ?>">
				</div>
				<div class="col-lg-6 order-lg-1 my-auto pt-5 pe-lg-5">
					<h2 class="mb-3"><?php echo $event_ob->{'post_title'} ?></h2>
					<p class="text-muted"><?php echo $event_date ?><?php echo $event_time ?></p>
					<p><?php echo str_replace( '...', '', get_the_excerpt( $event_ob->{'ID'} ) ) ?></p>
					<a href="<?php echo get_permalink( $event_ob->{'ID'} ) ?>" class="btn btn-dark mt-2">Read More →</a>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}
	}

	public function event_banner() {
		// Shortcode to display most recent events as BootStrap banners (set display limit in settings)
		$event_ob = get_posts( array( 'post_type' => 'event' ) );
		$banner_limit = get_option('eos_events_settings_option_name')['banner_limit_0'] ?? 2;
		if ( $event_ob != null ) {
			$i = 0;
			foreach ( $event_ob as $event_post) {
				$i++;
				$event_date = get_post_meta( $event_post->{'ID'}, 'eos_events_meta_date', true );
				if ( $event_date != null ) {
					$event_date = ' –&nbsp;' . date('M.\&\\n\b\s\p\;jS', strtotime( $event_date ) );
				}else{
					$event_date = '';
				}
				$event_time = get_post_meta( $event_post->{'ID'}, 'eos_events_meta_time', true );
				if ( $event_time != null ) {
					if ( date( "i", strtotime( $event_time ) ) == '00' ) {
						$event_time = date( "ga", strtotime( $event_time ) );
					}else{
						$event_time = date( "g:ia", strtotime( $event_time ) );
					}
				}else{
					$event_time = '';
				}
				?>

				
					<div id="banner-<?php echo $event_post->{'ID'} ?>" class="eos-banner container-fluid text-center p-2 d-flex justify-content-center align-items-center <?php echo ($i % 2 == 0 ) ? 'bg-light' : 'bg-dark text-white'; ?>">
					    <!-- The intention of the commented lines below is to allow users to X out of events they've seen, which would be stored in a browser cookie -->
						<!--<div style="min-width:1em;min-height:1em"></div>-->
						<a class="text-decoration-none" style="color:inherit" href="<?php echo get_permalink($event_post->{'ID'}); ?>"><span class="eos-banner-title"><?php echo $event_post->{'post_title'} ?></span><?php echo $event_date ?>&nbsp;<?php echo $event_time ?>&nbsp;→</a>
						<!--<svg onclick="eos_banner_close(banner-<?php echo $event_post->{'ID'} ?>)" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="currentcolor" height=".7em" width="1em" version="1.1" id="Capa_1" viewBox="0 0 460.775 460.775" xml:space="preserve" class=""><path d="M285.08,230.397L456.218,59.27c6.076-6.077,6.076-15.911,0-21.986L423.511,4.565c-2.913-2.911-6.866-4.55-10.992-4.55  c-4.127,0-8.08,1.639-10.993,4.55l-171.138,171.14L59.25,4.565c-2.913-2.911-6.866-4.55-10.993-4.55  c-4.126,0-8.08,1.639-10.992,4.55L4.558,37.284c-6.077,6.075-6.077,15.909,0,21.986l171.138,171.128L4.575,401.505  c-6.074,6.077-6.074,15.911,0,21.986l32.709,32.719c2.911,2.911,6.865,4.55,10.992,4.55c4.127,0,8.08-1.639,10.994-4.55  l171.117-171.12l171.118,171.12c2.913,2.911,6.866,4.55,10.993,4.55c4.128,0,8.081-1.639,10.992-4.55l32.709-32.719  c6.074-6.075,6.074-15.909,0-21.986L285.08,230.397z"></path></svg>-->
					</div>
				</a>

				<?php
				// Banner limit based on setting field
				if($i==$banner_limit) break; // Stop after showing 3 posts
			}
		}
	}

	//Cron function
	public function trash_events() {
		// Trash any events that are before today's date
		if ( !empty( get_posts( array( 'post_type' => 'event' ) ) ) ) {
			foreach (get_posts(array('post_type' => 'event')) as $event_post) {
				if (!empty(get_post_meta( $event_post->{'ID'}, 'eos_events_meta_date', true ))) {
					if (date('Ymd') > date("Ymd", strtotime(get_post_meta( $event_post->{'ID'}, 'eos_events_meta_date', true )))) {
						wp_trash_post( $event_post->{'ID'} );
					}
				}
			}
		}
	}

	public function eos_events_orderby( $query ) {
	  if( ! is_admin() || ! $query->is_main_query() ) {
	    return;
	  }

	  if ( 'status' === $query->get( 'orderby') ) {
	    $query->set( 'meta_key', 'eos_events_meta_date' );
	    $query->set( 'orderby', 'meta_value' );
	  }
	}

	public function eos_events_sortable_columns( $columns ) {
	  $columns['status'] = 'status';

	  return $columns;
	}

	public function eos_events_posts_columns( $columns ) {
	  $columns = array(
	        'cb' => $columns['cb'],
	        'title' => __( 'Event' ),
	        'eos_date' => __( 'Date of event', 'event' ),
	      );
	  return $columns;
	}

	public function eos_events_posts_column( $column, $post_id ) {

		if ( 'eos_date' == $column ) {
		  if (get_post_meta( $post_id, 'eos_events_meta_date', true ) != null) {
		    $theDate = date("l, F jS", strtotime(get_post_meta( $post_id, 'eos_events_meta_date', true )));
		    if (get_post_meta( $post_id, 'eos_events_meta_time', true ) != null) {
		  	  $theDate .= ', ' . date("g:i a", strtotime(get_post_meta( $post_id, 'eos_events_meta_time', true )));
		    }
		    echo $theDate;
		  }
		}
		
	  }

	public function settingsLink( $links ) {
		$settingsLink = '<a href="tools.php?page=eos-events-settings">Settings</a>';
		$sendLink = '<a href="edit.php?post_type=event">Manage Events</a>';
		array_push( $links, $settingsLink );
		array_push( $links, $sendLink );
		return $links;
	}

	public function AdminPages() {
		add_menu_page( 
			'eos_events',
			'Events',
			'edit_pages',
			'eos_events_plugin',
			'',
			'dashicons-calendar',
			2
		);
	}

	public function adminIndex() {
		require_once plugin_dir_path( __FILE__ ) . 'templates/admin.php';
	}

	function activate() {
		// generate a CPT for eos_events Events
		$this->eos_eventsPostType();

		flush_rewrite_rules();
	}

	function deactivate() {
		flush_rewrite_rules();
	}

	public function eos_eventsPostType() {
		$labels = array(
		    'name'               => _x( 'Events', 'post type general name' ),
		    'singular_name'      => _x( 'Event', 'post type singular name' ),
		    'add_new'            => _x( 'Add New', 'Event' ),
		    'add_new_item'       => __( 'Add New Event' ),
		    'edit_item'          => __( 'Edit Event' ),
		    'new_item'           => __( 'New Event' ),
		    'all_items'          => __( 'All Events' ),
		    'view_item'          => __( 'View Event' ),
		    'search_items'       => __( 'Search Events' ),
		    'not_found'          => __( 'No events found' ),
		    'not_found_in_trash' => __( 'No events found in the Trash' ), 
		    'parent_item_colon'  => '’',
		    'menu_name'          => 'Events'
		);

		$args = array(
			'public' => true,
			'label' => 'Events',
			'hierarchical' => true,
			'labels' => $labels,
			'show_in_menu' => 'eos_events_plugin',
			'has_archive'   => false, 
			'show_in_rest' => true, // To use Gutenberg editor.
			'publicly_queryable'  => true,
			'query_var' => true,
			'supports' => array(
				'title',
				'editor',
				'thumbnail',
				'excerpt'
			), 
			'rewrite' => array(
				'slug' => 'event',
			)
		);

		register_post_type( 'event', $args );
	}

	function enqueue() {
		//enqueue all of our scripts
		wp_enqueue_style( 'eos_eventsstyle', plugins_url( '/assets/style.css', __FILE__ ) );
		wp_enqueue_script( 'eos_eventsscript', plugins_url( '/assets/main.js', __FILE__ ) );
	}

}

if ( class_exists( 'eos_events' ) ) {
	$eos_events = new eos_events();
	$eos_events->register();
}

require_once plugin_dir_path( __FILE__ ) . 'templates/post-meta.php';

require_once plugin_dir_path( __FILE__ ) . 'templates/settings.php';

// activation
register_activation_hook( __FILE__, array( $eos_events, 'activate' ) );

// deactivation
register_deactivation_hook( __FILE__, array( $eos_events, 'deactivate' ) );



