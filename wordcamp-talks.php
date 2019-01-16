<?php
/*
Plugin Name: WordCamp Talk Proposals
Plugin URI: https://github.com/WordCampParis/wordcamp-talks/
Description: WordCamp Talk Proposals Management Tool
Version: 1.2.0-alpha
Requires at least: 4.6.1
Tested up to: 5.0
License: GNU/GPL 2
Author: imath
Author URI: http://imathi.eu/
Text Domain: wordcamp-talks
Domain Path: /languages/
GitHub Plugin URI: https://github.com/WordCampParis/wordcamp-talks/
*/

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WordCamp_Talks' ) ) :
/**
 * Main plugin's class
 *
 * Sets the needed globalized vars, includes the required
 * files and registers post type stuff.
 *
 * @package WordCamp Talks
 *
 * @since 1.0.0
 */
final class WordCamp_Talks {

	/**
	 * Plugin's main instance
	 *
	 * @var object
	 */
	protected static $instance;

	/**
	 * Initialize the plugin
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->setup_globals();
		$this->includes();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @return object A single instance of this class.
	 */
	public static function start() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Setups plugin's globals
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {
		// Version
		$this->version = '1.2.0-alpha';

		// Domain
		$this->domain = 'wordcamp-talks';

		// Base name
		$this->file       = __FILE__;
		$this->basename   = plugin_basename( $this->file );

		// Path and URL
		$this->plugin_dir = plugin_dir_path( $this->file );
		$this->plugin_url = plugin_dir_url( $this->file );
		$this->js_url     = trailingslashit( $this->plugin_url . 'js' );
		$this->lang_dir   = trailingslashit( $this->plugin_dir . 'languages' );

		// Includes
		$this->includes_dir = trailingslashit( $this->plugin_dir . 'includes' );
		$this->includes_url = trailingslashit( $this->plugin_url . 'includes' );

		// Default templates location
		$this->templates_dir = trailingslashit( $this->plugin_dir . 'templates' );

		// Post types / taxonomies default ids
		$this->post_type = 'talks';
		$this->category  = 'talk_categories';
		$this->tag       = 'talk_tags';

		// Pretty links ?
		$this->pretty_links = get_option( 'permalink_structure' );
		$this->slugs        = get_option( '_wc_talks_slugs', array() );

		// template globals
		$this->is_talks         = false;
		$this->template_file    = false;
		$this->main_query       = array();
		$this->query_loop       = false;
		$this->per_page         = get_option( 'posts_per_page' );
		$this->is_talks_archive = false;
		$this->is_category      = false;
		$this->is_tag           = false;
		$this->current_term     = false;
		$this->is_user          = false;
		$this->is_user_rates    = false;
		$this->is_user_comments = false;
		$this->is_action        = false;
		$this->is_new           = false;
		$this->is_edit          = false;
		$this->is_search        = false;
		$this->orderby          = false;
		$this->needs_reset      = false;

		// User globals
		$this->displayed_user   = new WP_User();
		$this->current_user     = new WP_User();
		$this->feedback         = array();

		// Is the plugin activated on a WordCamp.org site ?
		$this->is_wordcamp_site = class_exists( 'WordCamp_Post_Types_Plugin' );
	}

	/**
	 * Includes plugin's needed files
	 *
	 * @since 1.0.0
	 *
	 * @uses  is_admin() to check for WordPress Administration
	 */
	private function includes() {
		// Class autoloader.
		spl_autoload_register( array( $this, 'autoload' ) );

		require $this->includes_dir . 'core/options.php';
		require $this->includes_dir . 'core/functions.php';
		require $this->includes_dir . 'core/rewrites.php';
		require $this->includes_dir . 'core/capabilities.php';
		require $this->includes_dir . 'core/upgrade.php';
		require $this->includes_dir . 'core/template-functions.php';
		require $this->includes_dir . 'core/template-loader.php';

		require $this->includes_dir . 'comments/functions.php';
		require $this->includes_dir . 'comments/tags.php';

		require $this->includes_dir . 'talks/functions.php';
		require $this->includes_dir . 'talks/tags.php';

		require $this->includes_dir . 'users/functions.php';
		require $this->includes_dir . 'users/tags.php';

		require $this->includes_dir . 'core/actions.php';
		require $this->includes_dir . 'core/filters.php';

		if ( $this->is_wordcamp_site ) {
			require $this->includes_dir . 'wordcamp/integrations.php';
		}

		/**
		 * Add specific functions for the current site
		 */
		if ( file_exists( WP_PLUGIN_DIR . '/wct-functions.php' ) ) {
			require WP_PLUGIN_DIR . '/wct-functions.php';
		}

		/**
		 * On multisite configs, load current blog's specific functions
		 */
		if ( is_multisite() && file_exists( WP_PLUGIN_DIR . '/wct-' . get_current_blog_id() . '-functions.php' ) ) {
			require WP_PLUGIN_DIR . '/wct-' . get_current_blog_id() . '-functions.php';
		}
	}

	/**
	 * Class Autoload function
	 *
	 * @since  1.1.0
	 *
	 * @param  string $class The class name.
	 */
	public function autoload( $class ) {
		$name = str_replace( '_', '-', strtolower( $class ) );

		if ( false === strpos( $name, $this->domain ) ) {
			return;
		}

		$folder    = null;
		$subfolder = 'classes';
		$prefix    = 'class';
		$parts     = explode( '-', $name );

		if ( isset( $parts[2] ) ) {
			$folder = $parts[2];
		}

		// Class Name => subfolder
		$map = array_fill_keys( array(
			'WordCamp_Talks_Talks_List_Categories',
			'WordCamp_Talks_Talks_Popular',
			'WordCamp_Talks_Users_Top_Contributors',
			'WordCamp_Talks_Comments_Recent',
		), 'widgets');

		if ( isset( $map[ $class ] ) ) {
			$subfolder = $map[ $class ];
			$prefix    = 'widget';
		}

		$path = sprintf( '%1$s%2$s/%3$s/%4$s-%5$s.php', $this->includes_dir, $folder, $subfolder, $prefix, $name );

		// Sanity check.
		if ( ! file_exists( $path ) ) {
			return;
		}

		require $path;
	}

	/**
	 * Setups a globalized var for a later use
	 *
	 * @package WordCamp Talks
	 *
	 * @since 1.0.0
	 *
	 * @param string $var   The key to access to the globalized var
	 * @param mixed  $value The value of the globalized var
	 */
	public function set_global( $var = '', $value = null ) {
		if ( empty( $var ) || empty( $value ) ) {
			return false;
		}

		$this->{$var} = $value;
	}

	/**
	 * Gets a globalized var
	 *
	 * @package WordCamp Talks
	 *
	 * @since 1.0.0
	 *
	 * @param  string $var the key to access to the globalized var
	 * @return mixed       the value of the globalized var
	 */
	public function get_global( $var = '' ) {
		if ( empty( $var ) || empty( $this->{$var} ) ) {
			return false;
		}

		return $this->{$var};
	}
}

endif;

/**
 * Plugin's Bootstrap Function
 *
 * @package WordCamp Talks
 *
 * @since 1.0.0
 */
function wct() {
	return WordCamp_Talks::start();
}
add_action( 'plugins_loaded', 'wct', 5 );
