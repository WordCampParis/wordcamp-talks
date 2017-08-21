<?php
/**
 * WordCamp Talks Functions.
 *
 * @package WordCamp Talks
 * @subpackage core/functions
 *
 * @since 1.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Globals *******************************************************************/

/**
 * Get the plugin's current version.
 *
 * @since 1.0.0
 *
 * @return string Plugin's current version
 */
function wct_get_version() {
	return wct()->version;
}

/**
 * Get the DB verion of the plugin.
 *
 * Used to check wether to run the upgrade
 * routine of the plugin.
 * @see  core/upgrade > wct_is_upgrade()
 *
 * @since 1.0.0
 *
 * @return string DB version of the plugin
 */
function wct_db_version() {
	return get_option( '_wc_talks_version', 0 );
}

/**
 * Get plugin's basename.
 *
 * @since 1.0.0
 *
 * @return string Plugin's basename
 */
function wct_get_basename() {
	return apply_filters( 'wct_get_basename', wct()->basename );
}

/**
 * Get plugin's main path.
 *
 * @since 1.0.0
 *
 * @return string plugin's main path
 */
function wct_get_plugin_dir() {
	return apply_filters( 'wct_get_plugin_dir', wct()->plugin_dir );
}

/**
 * Get plugin's main url.
 *
 * @since 1.0.0
 *
 * @return string plugin's main url
 */
function wct_get_plugin_url() {
	return apply_filters( 'wct_get_plugin_url', wct()->plugin_url );
}

/**
 * Register JavaScripts into WP_Scripts.
 *
 * @since 1.1.0
 */
function wct_register_scripts() {
	if ( ! wct_is_talks() ) {
		return;
	}

	// Register jquery Raty
	wp_register_script(
		'jquery-raty',
		wct_get_js_script( 'jquery.raty' ),
		array( 'jquery' ),
		'2.7.0.imath',
		true
	);

	// Register tagging
	wp_register_script(
		'tagging',
		wct_get_js_script( 'tagging' ),
		array( 'jquery' ),
		'1.3.1',
		true
	);

	wct_enqueue_style();
}
add_action( 'wp_enqueue_scripts', 'wct_register_scripts', 1 );

/**
 * Get plugin's javascript url
 *
 * That's where the plugin's js file are all available.
 *
 * @since 1.0.0
 *
 * @return string plugin's javascript url
 */
function wct_get_js_url() {
	return apply_filters( 'wct_get_js_url', wct()->js_url );
}

/**
 * Get a specific javascript file url (minified or not).
 *
 * @since 1.0.0
 *
 * @param  string $script the name of the script
 * @return string         url to the minified or regular script
 */
function wct_get_js_script( $script = '' ) {
	$min = '.min';

	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		$min = '';
	}

	return wct_get_js_url() . $script . $min . '.js';
}

/**
 * Get plugin's path to includes directory.
 *
 * @since 1.0.0
 *
 * @return string includes directory path
 */
function wct_get_includes_dir() {
	return wct()->includes_dir;
}

/**
 * Get plugin's url to includes directory.
 *
 * @since 1.0.0
 *
 * @return string includes directory url
 */
function wct_get_includes_url() {
	return wct()->includes_url;
}

/**
 * Get plugin's path to templates directory
 *
 * That's where all specific plugin's templates are located
 * You can create a directory called 'wordcamp-talks' in your theme
 * copy the content of this folder in it and customize the templates
 * from your theme's 'wordcamp-talks' directory. Templates in there
 * will override plugin's default ones.
 *
 * @since 1.0.0
 *
 * @return string path to templates directory
 */
function wct_get_templates_dir() {
	return wct()->templates_dir;
}

/**
 * Set a global var to be used by the plugin at different times
 * during WordPress loading process.
 *
 * @since 1.0.0
 *
 * @param  string $var_key   the key to access to the globalized value
 * @param  mixed  $var_value a value to globalize, can be object, array, int.. whatever
 */
function wct_set_global( $var_key = '', $var_value ='' ) {
	return wct()->set_global( $var_key, $var_value );
}

/**
 * Get a global var set thanks to wct_set_global().
 *
 * @since 1.0.0
 *
 * @param  string $var_key the key to access to the globalized value
 * @return mixed           the globalized value for the requested key
 */
function wct_get_global( $var_key = '' ) {
	return wct()->get_global( $var_key );
}

/**
 * Load Plugin's translations.
 *
 * @since 1.1.0
 */
function wct_load_textdomain() {
	$wct = wct();

	// Use regular locale
	if ( ! function_exists( 'get_user_locale' ) ) {
		// Look in global /wp-content/languages/plugins/
		load_plugin_textdomain( $wct->domain );

	// Use user locale instead
	} else {
		/**
		 * Filter here to edit this plugin locale.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value  The locale.
		 * @param string $domain The plugin domain.
		 */
		$locale = apply_filters( 'wordcamp_talks_locale', get_user_locale(), $wct->domain );

		if ( empty( $locale ) ) {
			$mofile = $wct->domain . '.mo';
		} else {
			$mofile = sprintf( '%1$s-%2$s.mo', $wct->domain, $locale );
		}

		/**
		 * Filter here to use another dir than the regular plugin lang dir
		 *
		 * @since 1.0.0
		 *
		 * @param string $value  Absolute path to the mo file.
		 * @param string $mofile The mofile file name.
		 * @param string $locale The current locale.
		 */
		$mofile_dir = apply_filters( 'wordcamp_talks_lang_dir', $wct->lang_dir . $mofile, $mofile, $locale );

		// Try to see if a GlotPress generated language is available first.
		if ( ! load_textdomain( $wct->domain, WP_LANG_DIR . '/plugins/' . $mofile ) ) {
			load_textdomain( $wct->domain, $mofile_dir );
		}
	}
}

/**
 * Main archive page title.
 *
 * @since  1.0.0
 * @since  1.1.0 The option has been removed.
 *
 * @return string Title of the Talks archive page
 */
function wct_archive_title() {
	return _x( 'Talk Proposals', 'Title of the main archive page.', 'wordcamp-talks' );
}

/** Post Type (talks) *********************************************************/

/**
 * Outputs the post type identifier (talks) for the plugin.
 *
 * @since 1.0.0
 *
 * @return string the post type identifier
 */
function wct_post_type() {
	echo wct_get_post_type();
}

	/**
	 * Gets the post type identifier (talks).
	 *
	 * @since 1.0.0
	 *
	 * @return string the post type identifier
	 */
	function wct_get_post_type() {
		return apply_filters( 'wct_get_post_type', wct()->post_type );
	}

/**
 * Gets plugin's main post type init arguments.
 *
 * @since 1.0.0
 *
 * @return array the init arguments for the 'talks' post type
 */
function wct_post_type_register_args() {
	return array(
		'public'              => true,
		'query_var'           => wct_get_post_type(),
		'rewrite'             => array(
			'slug'            => wct_talk_slug(),
			'with_front'      => false
		),
		'has_archive'         => wct_root_slug(),
		'exclude_from_search' => true,
		'show_in_nav_menus'   => false,
		'show_in_admin_bar'   => current_user_can( 'wct_talks_admin' ),
		'menu_icon'           => 'dashicons-controls-volumeon',
		'supports'            => array( 'title', 'editor', 'author', 'comments', 'revisions' ),
		'taxonomies'          => array(
			wct_get_category(),
			wct_get_tag()
		),
		'capability_type'     => array( 'talk', 'talks' ),
		'capabilities'        => wct_get_post_type_caps(),
		'delete_with_user'    => true,
		'can_export'          => true,
	);
}

/**
 * Gets the labels for the plugin's post type.
 *
 * @since 1.0.0
 *
 * @return array post type labels
 */
function wct_post_type_register_labels() {
	return apply_filters( 'wct_post_type_register_labels', array(
		'labels' => array(
			'name'                  => __( 'Talk Proposals',                     'wordcamp-talks' ),
			'menu_name'             => _x( 'Talk Proposals', 'Main Plugin menu', 'wordcamp-talks' ),
			'all_items'             => __( 'All Talk Proposals',                 'wordcamp-talks' ),
			'singular_name'         => __( 'Talk Proposal',                      'wordcamp-talks' ),
			'add_new'               => __( 'Add New Talk Proposal',              'wordcamp-talks' ),
			'add_new_item'          => __( 'Add New Talk Proposal',              'wordcamp-talks' ),
			'edit_item'             => __( 'Edit Talk Proposal',                 'wordcamp-talks' ),
			'new_item'              => __( 'New Talk Proposal',                  'wordcamp-talks' ),
			'view_item'             => __( 'View Talk Proposal',                 'wordcamp-talks' ),
			'search_items'          => __( 'Search Talk Proposals',              'wordcamp-talks' ),
			'not_found'             => __( 'No Talk Proposals Found',            'wordcamp-talks' ),
			'not_found_in_trash'    => __( 'No Talk Proposals Found in Trash',   'wordcamp-talks' ),
			'insert_into_item'      => __( 'Insert into Talk Proposal',          'wordcamp-talks' ),
			'uploaded_to_this_item' => __( 'Uploaded to this Talk Proposal',     'wordcamp-talks' ),
			'filter_items_list'     => __( 'Filter Talk Proposals list',         'wordcamp-talks' ),
			'items_list_navigation' => __( 'Talk Proposals list navigation',     'wordcamp-talks' ),
			'items_list'            => __( 'Talk Proposals list',                'wordcamp-talks' ),
		)
	) );
}

/**
 * Get the Talk statuses
 *
 * @since  1.1.0
 *
 * @return array The list of talk statuses.
 */
function wct_get_statuses() {
	/**
	 * Filter here to add statuses.
	 *
	 * @since  1.1.0
	 *
	 * @param array $value The list of talk statuses.
	 */
	return apply_filters( 'wct_get_statuses', array(
		'wct_pending'   => __( 'Pending',      'wordcamp-talks' ),
		'wct_shortlist' => __( 'Short-listed', 'wordcamp-talks' ),
		'wct_selected'  => __( 'Selected',     'wordcamp-talks' ),
		'wct_rejected'  => __( 'Rejected',     'wordcamp-talks' ),
	) );
}

/**
 * Get the Talk status label count.
 *
 * @since  1.1.0
 *
 * @return string The Talk status label count.
 */
function wct_get_status_label_count( $status = '' ) {
	$label_count = $status;

	$labels      = array(
		'wct_pending'   => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', 'wordcamp-talks' ),
		'wct_shortlist' => _n_noop( 'Short-listed <span class="count">(%s)</span>', 'Short-listed <span class="count">(%s)</span>', 'wordcamp-talks' ),
		'wct_selected'  => _n_noop( 'Selected <span class="count">(%s)</span>', 'Selected <span class="count">(%s)</span>', 'wordcamp-talks' ),
		'wct_rejected'  => _n_noop( 'Rejected <span class="count">(%s)</span>', 'Rejected <span class="count">(%s)</span>', 'wordcamp-talks' ),
	);

	if ( isset( $labels[$status] ) ) {
		$label_count = $labels[$status];
	}

	return $label_count;
}

/**
 * Check if a given status is supported by the plugin.
 *
 * @since  1.1.0
 *
 * @param  string $status Name for the status
 * @return boolean        True if supported. False otherwise.
 */
function wct_is_supported_statuses( $status = '' ) {
	$statuses = wct_get_statuses();
	return isset( $statuses[ $status ] );
}

/**
 * Default publishing status.
 *
 * @since 1.0.0
 * @since 1.1.0 Default status is now Plugin's custom pending status.
 *
 * @return string the default status.
 */
function wct_default_talk_status() {
	return 'wct_pending';
}

/**
 * Get plugin's post type "category" identifier (talk_categories).
 *
 * @since 1.0.0
 *
 * @return string hierarchical taxonomy identifier
 */
function wct_get_category() {
	return apply_filters( 'wct_get_category', wct()->category );
}

/**
 * Gets the "category" taxonomy init arguments.
 *
 * @since 1.0.0
 *
 * @return array taxonomy init arguments
 */
function wct_category_register_args() {
	return apply_filters( 'wct_category_register_args', array(
		'rewrite'               => array(
			'slug'              => wct_category_slug(),
			'with_front'        => false,
			'hierarchical'      => false,
		),
		'capabilities'          => wct_get_category_caps(),
		'update_count_callback' => '_update_generic_term_count',
		'query_var'             => wct_get_category(),
		'hierarchical'          => true,
		'show_in_nav_menus'     => false,
		'public'                => true,
		'show_tagcloud'         => false,
	) );
}

/**
 * Get the "category" taxonomy labels.
 *
 * @since 1.0.0
 *
 * @return array "category" taxonomy labels
 */
function wct_category_register_labels() {
	return apply_filters( 'wct_category_register_labels', array(
		'labels' => array(
			'name'             => __( 'Talk Proposal Categories',        'wordcamp-talks' ),
			'singular_name'    => __( 'Talk Proposal Category',          'wordcamp-talks' ),
			'edit_item'        => __( 'Edit Talk Proposal Category',     'wordcamp-talks' ),
			'update_item'      => __( 'Update Talk Proposal Category',   'wordcamp-talks' ),
			'add_new_item'     => __( 'Add New Talk Proposal Category',  'wordcamp-talks' ),
			'new_item_name'    => __( 'New Talk Proposal Category Name', 'wordcamp-talks' ),
			'all_items'        => __( 'All Categories',                  'wordcamp-talks' ),
			'search_items'     => __( 'Search Talk Proposal Categories', 'wordcamp-talks' ),
			'parent_item'      => __( 'Parent Talk Proposal Category',   'wordcamp-talks' ),
			'parent_item_colon'=> __( 'Parent Talk Proposal Category:',  'wordcamp-talks' ),
		)
	) );
}

/**
 * Get plugin's post type "tag" identifier (talk_tags).
 *
 * @since 1.0.0
 *
 * @return string non hierarchical taxonomy identifier
 */
function wct_get_tag() {
	return apply_filters( 'wct_get_tag', wct()->tag );
}

/**
 * Gets the "tag" taxonomy init arguments.
 *
 * @since 1.0.0
 *
 * @return array taxonomy init arguments
 */
function wct_tag_register_args() {
	return apply_filters( 'wct_tag_register_args', array(
		'rewrite'               => array(
			'slug'              => wct_tag_slug(),
			'with_front'        => false,
			'hierarchical'      => false,
		),
		'capabilities'          => wct_get_tag_caps(),
		'update_count_callback' => '_update_generic_term_count',
		'query_var'             => wct_get_tag(),
		'hierarchical'          => false,
		'show_in_nav_menus'     => false,
		'public'                => true,
		'show_tagcloud'         => true,
	) );
}

/**
 * Get the "tag" taxonomy labels.
 *
 * @since 1.0.0
 *
 * @return array "tag" taxonomy labels
 */
function wct_tag_register_labels() {
	return apply_filters( 'wct_tag_register_labels', array(
		'labels' => array(
			'name'                       => __( 'Talk Proposal Tags',                              'wordcamp-talks' ),
			'singular_name'              => __( 'Talk Proposal Tag',                               'wordcamp-talks' ),
			'edit_item'                  => __( 'Edit Talk Proposal',                              'wordcamp-talks' ),
			'update_item'                => __( 'Update Talk Proposal',                            'wordcamp-talks' ),
			'add_new_item'               => __( 'Add New Talk Proposal Tag',                       'wordcamp-talks' ),
			'new_item_name'              => __( 'New Talk Proposal Tag Name',                      'wordcamp-talks' ),
			'all_items'                  => __( 'All Talk Proposal Tags',                          'wordcamp-talks' ),
			'search_items'               => __( 'Search Talk Proposal Tags',                       'wordcamp-talks' ),
			'popular_items'              => __( 'Popular Talk Proposal Tags',                      'wordcamp-talks' ),
			'separate_items_with_commas' => __( 'Separate Talk Proposal tags with commas',         'wordcamp-talks' ),
			'add_or_remove_items'        => __( 'Add or remove Talk Proposal tags',                'wordcamp-talks' ),
			'choose_from_most_used'      => __( 'Choose from the most popular Talk Proposal tags', 'wordcamp-talks' )
		)
	) );
}

/**
 * Register WordCamp Talk Proposals objects.
 *
 * @since  1.1.0
 */
function wct_register_objects() {

	/** Post Types ***********************************************************/

	// Register the Talks post-type
	register_post_type(
		wct_get_post_type(),
		array_merge(
			wct_post_type_register_labels(),
			wct_post_type_register_args()
		)
	);

	/** Post Statuses ********************************************************/

	foreach ( (array) wct_get_statuses() as $name => $status ) {
		register_post_status( $name, array(
			'label'                     => $status,
			'private'                   => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => wct_get_status_label_count( $name ),
			'_is_wc_talk'               => true,
		) );
	}

	/** Taxonomies ************************************************************/

	// Register the category taxonomy
	register_taxonomy(
		wct_get_category(),
		wct_get_post_type(),
		array_merge(
			wct_category_register_labels(),
			wct_category_register_args()
		)
	);

	// Register the tag taxonomy
	register_taxonomy(
		wct_get_tag(),
		wct_get_post_type(),
		array_merge(
			wct_tag_register_labels(),
			wct_tag_register_args()
		)
	);
}

/** Urls **********************************************************************/

/**
 * Gets plugin's post type main url.
 *
 * @since 1.0.0
 *
 * @return string root url for the post type
 */
function wct_get_root_url() {
	/**
	 * Filter here to edit the root url.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $value The root url.
	 */
	return apply_filters( 'wct_get_root_url', get_post_type_archive_link( wct_get_post_type() ) );
}

/**
 * Gets a specific "category" term url.
 *
 * @since 1.0.0
 *
 * @param  object $category The term to build the url for
 * @return string           Url to reach all talks categorized with the requested term
 */
function wct_get_category_url( $category = null ) {
	if ( empty( $category ) ) {
		$category = wct_get_current_term();
	}

	$term_link = get_term_link( $category, wct_get_category() );

	/**
	 * Filter here to edit the category url.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $term_link Url to reach the talks categorized with the term
	 * @param  object $category  The term for this taxonomy
	 */
	return apply_filters( 'wct_get_category_url', $term_link, $category );
}

/**
 * Gets a specific "tag" term url.
 *
 * @since 1.0.0
 *
 * @param  object $tag The term to build the url for
 * @return string      Url to reach all talks tagged with the requested term
 */
function wct_get_tag_url( $tag = '' ) {
	if ( empty( $tag ) ) {
		$tag = wct_get_current_term();
	}

	$term_link = get_term_link( $tag, wct_get_tag() );

	/**
	 * Filter here to edit the tag url.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $term_link Url to reach the talks tagged with the term
	 * @param  object $tag       The term for this taxonomy
	 */
	return apply_filters( 'wct_get_tag_url', $term_link, $tag );
}

/**
 * Gets a global redirect url
 *
 * Used after posting a talk failed
 * Defaults to root url
 *
 * @since 1.0.0
 *
 * @return string the url to redirect the user to
 */
function wct_get_redirect_url() {
	/**
	 * Filter here to edit the redirect url.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $value The root url.
	 */
	return apply_filters( 'wct_get_redirect_url', wct_get_root_url() );
}

/**
 * Gets the url to the form to submit new talks
 *
 * So far only adding new talks is supported, but
 * there will surely be an edit action to allow users
 * to edit their talks. Reason of the $type param.
 *
 * @since 1.0.0
 *
 * @global $wp_rewrite
 * @param  string $type action (defaults to new)
 * @param  string $talk_name the post name of the talk to edit
 * @return string the url of the form to add talks
 */
function wct_get_form_url( $type = '', $talk_name = '' ) {
	global $wp_rewrite;

	if ( empty( $type ) ) {
		$type = wct_addnew_slug();
	}

	/**
	 * Early filter to override form url before being built
	 *
	 * @since  1.0.0
	 *
	 * @param mixed false or url to override
	 * @param string $type (only add new for now)
	 */
	$early_form_url = apply_filters( 'wct_pre_get_form_url', false, $type, $talk_name );

	if ( ! empty( $early_form_url ) ) {
		return $early_form_url;
	}

	// Pretty permalinks
	if ( $wp_rewrite->using_permalinks() ) {
		$url = $wp_rewrite->root . wct_action_slug() . '/%' . wct_action_rewrite_id() . '%';

		$url = str_replace( '%' . wct_action_rewrite_id() . '%', $type, $url );
		$url = home_url( user_trailingslashit( $url ) );

	// Unpretty permalinks
	} else {
		$url = add_query_arg( array( wct_action_rewrite_id() => $type ), home_url( '/' ) );
	}

	if ( $type == wct_edit_slug() && ! empty( $talk_name ) ) {
		$url = add_query_arg( wct_get_post_type(), $talk_name, $url );
	}

	/**
	 * Filter to override form url after being built.
	 *
	 * @since  1.0.0
	 *
	 * @param string url to override
	 * @param string $type add new or edit
	 * @param string $talk_name the post name of the talk to edit
	 */
	return apply_filters( 'wct_get_form_url', $url, $type, $talk_name );
}

/** Feedbacks *****************************************************************/

/**
 * Sanitize the feedback.
 *
 * NB: the strong tag is used by WordPress during signup.
 *
 * @param  string $text The text to sanitize.
 * @return string       The sanitized text.
 */
function wct_sanitize_feedback( $text = '' ) {
	$text = wp_kses( $text, array(
		'a'      => array( 'href' => true ),
		'strong' => array(),
		'img'    => array(
			'src'    => true,
			'height' => true,
			'width'  => true,
			'class'  => true,
			'alt'    => true
		),
	) );

	return wp_unslash( $text );
}

/**
 * Get the feedback message or the list of feedback messages to output to the user.
 *
 * @since 1.0.0
 *
 * @param string|array  $type  The type of the feedback (success, error or info) or
 *                             An associative array keyed by the type of feedback and
 *                             containing the list of message ids.
 * @param  bool|int     $id    False or the ID of the feedback message to get.
 * @return string|array        The feedback message or a list of feedback messages.
 */
function wct_get_feedback_messages( $type = '', $id = false ) {
	$messages = apply_filters( 'wct_get_feedback_messages', array(
		'success' => array(
			1 => __( 'Saved successfully',                                                                     'wordcamp-talks' ),
			2 => __( 'Registration complete. Please check your mailbox.',                                      'wordcamp-talks' ),
			3 => __( 'The Talk Proposal was successfully created.',                                            'wordcamp-talks' ),
			4 => __( 'The Talk Proposal was successfully updated.',                                            'wordcamp-talks' ),
			5 => __( 'For your convenience, you have been automagically logged in.',                           'wordcamp-talks' ),
			6 => __( 'Make sure to check you received the email we sent you to reset your password.',          'wordcamp-talks' ),
			7 => __( 'Otherwise, edit your email and password from your profile before logging off the site.', 'wordcamp-talks' ),
			8 => __( 'Profile successfully updated.',                                                          'wordcamp-talks' ),
		),
		'error' => array(
			1  => __( 'Something went wrong, please try again',                           'wordcamp-talks' ),
			2  => __( 'You are not allowed to edit this Talk Proposal.',                  'wordcamp-talks' ),
			3  => __( 'You are not allowed to publish Talk Proposals',                    'wordcamp-talks' ),
			4  => __( 'Title and description are required fields.',                       'wordcamp-talks' ),
			5  => __( 'Something went wrong while trying to save your Talk Proposal.',    'wordcamp-talks' ),
			7  => __( 'Please choose a username having at least 4 characters.',           'wordcamp-talks' ),
			8  => __( 'Please fill all required fields.',                                 'wordcamp-talks' ),
			9  => __( 'The Talk Proposal you are trying to edit does not seem to exist.', 'wordcamp-talks' ),
			10 => __( 'Something went wrong while trying to update your Talk Proposal.',  'wordcamp-talks' ),
			11 => __( 'You are not allowed to edit this user profile.',                   'wordcamp-talks' ),
			12 => __( 'We were not able to validate your profile on WordPress.org.',      'wordcamp-talks' ),
			14 => __( 'Your biographical information are required',                       'wordcamp-talks' ),
		),
		'info'  => array(
			1 => __( 'This Talk Proposal is already being edited by another user.', 'wordcamp-talks' ),
			2 => __( 'Your Talk Proposal is currently awaiting moderation.',        'wordcamp-talks' ),
			3 => __( 'This user has not filled any public profile informations.',   'wordcamp-talks' ),
			4 => __( 'Your profile is empty. You can edit it at anytime.',          'wordcamp-talks' ),
			5 => sprintf(
				__( 'Please, share a little %s about yourself as we will use it if/once your Talk Proposal is selected.', 'wordcamp-talks' ),
				sprintf ( '<a href="%1$s">%2$s</a>',
					esc_url( wct_users_get_logged_in_profile_url() ),
					__( 'biographical information', 'wordcamp-talks' )
				)
			),
			6 => sprintf(
				__( 'Please, make sure to review your %s as we will use it if/once your Talk Proposal is selected.', 'wordcamp-talks' ),
				sprintf ( '<a href="%1$s">%2$s</a>',
					esc_url( wct_users_get_logged_in_profile_url() ),
					__( 'biographical information', 'wordcamp-talks' )
				)
			),
		),
	) );

	if ( empty( $type ) ) {
		return $messages;
	}

	if ( ! is_array( $type ) && isset( $messages[ $type ] ) ) {
		$messages = $messages[ $type ];

		if ( false === $id || ! isset( $messages[ $type ][ $id ] ) ) {
			return $messages;
		}

		return $messages[ $type ][ $id ];
	}

	foreach ( $type as $kt => $kv ) {
		$message_ids = array_filter( wp_parse_id_list( $kv ) );

		// If we have ids, get the corresponding messages.
		if ( $message_ids ) {
			$type[ $kt ] = array_intersect_key( $messages[ $kt ], array_flip( $message_ids ) );
		}
	}

	return $type;
}

/**
 * Explode arrays of values before using WordPress's add_query_arg() function.
 *
 * @since  1.0.0
 *
 * @param  array  $args The query arguments to add to the url.
 * @param  string $url  The url.
 * @return string       The url with query arguments.
 */
function wct_add_feedback_args( $args = array(), $url = '' ) {
	foreach ( $args as $k => $v ) {
		if ( ! is_array( $v ) ) {
			continue;
		}

		$args[ $k ] = join( ',', $v );
	}

	return add_query_arg( $args, $url );
}

/**
 * Add a new feedback message to inform the user.
 *
 * @since 1.0.0
 *
 * @param  array $feedback_data A list of feedback message or message ids keyed by their type.
 */
function wct_add_message( $feedback_data = array() ) {
	// Success is the default
	if ( empty( $feedback_data ) ) {
		$feedback_data = array(
			'success' => array( 1 ),
		);
	}

	wct_set_global( 'feedback', $feedback_data );
}

/**
 * Sets a new message to inform user.
 *
 * @since 1.0.0
 */
function wct_set_user_feedback() {
	// Check the URL query to find a feedback message
	$current_url = parse_url( $_SERVER['REQUEST_URI'] );

	if ( ! empty( $current_url['query'] ) ) {
		$vars = wp_parse_args( $current_url['query'] );

		$feedback = array_intersect_key( $vars, array(
			'error'   => true,
			'success' => true,
			'info'    => true,
		) );
	}

	if ( empty( $feedback ) ) {
		return;
	}

	wct_set_global( 'feedback', $feedback );
}

/**
 * Displays the feedback message to the user.
 *
 * @since 1.0.0
 *
 * @return string HTML Output.
 */
function wct_user_feedback() {
	$feedback = wct_get_global( 'feedback' );

	if ( empty( $feedback ) || ! is_array( $feedback ) || ! empty( $feedback['admin_notices'] ) ) {
		return;
	}

	$messages = wct_get_feedback_messages( $feedback );

	if ( empty( $messages ) ) {
		return;
	}

	foreach ( (array) $messages as $class => $message ) : ?>
		<div class="message <?php echo esc_attr( $class ); ?>">
			<p>
				<?php if ( is_array( $message ) ) :
						echo( join( '</p><p>', array_map( 'wct_sanitize_feedback', $message ) ) );

					else :
						echo wct_sanitize_feedback( $message );

				endif ; ?>
			</p>
		</div>
	<?php endforeach;
}

/** Rating Talks **************************************************************/

/**
 * Checks wether the builtin rating system should be used
 *
 * In previous versions of the plugin this was an option that
 * could be deactivated from plugin settings. This is no more
 * the case, as i think like comments, this is a core functionality
 * when managing talks. To deactivate the ratings, use the filter.
 *
 * @since 1.0.0
 *
 * @param  integer $default By default enabled.
 * @return boolean          True if disabled, false if enabled.
 */
function wct_is_rating_disabled( $default = 0 ) {
	return (bool) apply_filters( 'wct_is_rating_disabled', $default );
}

/**
 * Gets a fallback hintlist for ratings.
 *
 * @since 1.0.0
 *
 * @return array the hintlist
 */
function wct_get_hint_list() {
	$hintlist = wct_hint_list();

	if ( empty( $hintlist ) ) {
		$hintlist = array(
			esc_html__( 'bad',      'wordcamp-talks' ),
			esc_html__( 'poor',     'wordcamp-talks' ),
			esc_html__( 'regular',  'wordcamp-talks' ),
			esc_html__( 'good',     'wordcamp-talks' ),
			esc_html__( 'gorgeous', 'wordcamp-talks' )
		);
	}

	return $hintlist;
}

/**
 * Count rating stats for a specific talk or gets the rating of a specific user for a given talk.
 *
 * @since 1.0.0
 *
 * @param  integer $id      the ID of the talk object
 * @param  integer $user_id the user id
 * @param  boolean $details whether to include detailed stats
 * @return mixed            int|array the rate of the user or the stats
 */
function wct_count_ratings( $id = 0, $user_id = 0, $details = false ) {
	// Init a default array
	$retarray = array(
		'average' => 0,
		'users'   => array()
	);
	// Init a default user rating
	$user_rating = 0;

	// No talk, try to find it in the query loop
	if ( empty( $id ) ) {
		if ( ! wct()->query_loop->talk->ID ) {
			return $retarray;
		} else {
			$id = wct()->query_loop->talk->ID;
		}
	}

	// Get all the rates for the talk
	$rates = get_post_meta( $id, '_wc_talks_rates', true );

	// Build the stats
	if ( ! empty( $rates ) && is_array( $rates ) ) {
		foreach ( $rates as $rate => $users ) {
			// We need the user's rating
			if ( ! empty( $user_id ) && in_array( $user_id, (array) $users['user_ids'] ) ) {
				$user_rating = $rate;

			// We need average rating
			} else {
				$retarray['users'] = array_merge( $retarray['users'], (array) $users['user_ids'] );
				$retarray['average'] += $rate * count( (array) $users['user_ids'] );

				if ( ! empty( $details ) ) {
					$retarray['details'][ $rate ] = (array) $users['user_ids'];
				}
			}
		}
	}

	// Return the user rating
	if ( ! empty( $user_id ) ) {
		return $user_rating;
	}

	if ( ! empty( $retarray['users'] ) ) {
		$retarray['average'] = number_format( $retarray['average'] / count( $retarray['users'] ), 1 );
	} else {
		$retarray['average'] = 0;
	}

	return $retarray;
}

/**
 * Delete a specific rate for a given talk.
 *
 * This action is only available from the talk edit Administration screen
 * @see  WordCamp_Talks_Admin->maybe_delete_rate() in admin/admin.
 *
 * @since 1.0.0
 *
 * @param  integer $talk    The ID of the talk.
 * @param  integer $user_id The ID of the user.
 * @return mixed            The new average rating or false if no more rates.
 */
function wct_delete_rate( $talk = 0, $user_id = 0 ) {
	if ( empty( $talk ) || empty( $user_id ) ) {
		return false;
	}

	$rates = get_post_meta( $talk, '_wc_talks_rates', true );

	if ( empty( $rates ) ) {
		return false;
	} else {
		foreach ( $rates as $rate => $users ) {
			if ( in_array( $user_id, (array) $users['user_ids'] ) ) {
				$rates[ $rate ]['user_ids'] = array_diff( $users['user_ids'], array( $user_id ) );

				// Unset the rate if no more users.
				if ( count( $rates[ $rate ]['user_ids'] ) == 0 ) {
					unset( $rates[ $rate ] );
				}
			}
		}
	}

	if ( update_post_meta( $talk, '_wc_talks_rates', $rates ) ) {
		$ratings = wct_count_ratings( $talk );
		update_post_meta( $talk, '_wc_talks_average_rate', $ratings['average'] );

		/**
		 * Hook here to perform custom actions.
		 * Used internally to clean rates count cache.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $talk the ID of the talk
		 * @param  int $user_id the ID of the user
		 * @param  string       the formatted average.
		 */
		do_action( 'wct_deleted_rate', $talk, $user_id, $ratings['average'] );

		return $ratings['average'];
	} else {
		return false;
	}
}

/**
 * Saves a new rate for the talk.
 *
 * @since 1.0.0
 *
 * @param  integer $talk    The ID of the talk.
 * @param  integer $user_id The ID of the user.
 * @param  integer $rate    The rate of the user.
 * @return mixed            The new average rating or false if no more rates.
 */
function wct_add_rate( $talk = 0, $user_id = 0, $rate = 0 ) {
	if ( empty( $talk ) || empty( $user_id ) || empty( $rate ) ) {
		return false;
	}

	$rates = get_post_meta( $talk, '_wc_talks_rates', true );

	if ( empty( $rates ) ) {
		$rates = array( $rate => array( 'user_ids' => array( 'u-' . $user_id => $user_id ) ) );
	} else if ( ! empty( $rates[ $rate ] ) && ! in_array( $user_id, $rates[ $rate ]['user_ids'] ) ) {
		$rates[ $rate ]['user_ids'] = array_merge( $rates[ $rate ]['user_ids'], array( 'u-' . $user_id => $user_id ) );
	} else if ( empty( $rates[ $rate ] ) ) {
		$rates = $rates + array( $rate => array( 'user_ids' => array( 'u-' . $user_id => $user_id ) ) );
	} else {
		return false;
	}

	if ( update_post_meta( $talk, '_wc_talks_rates', $rates ) ) {
		$ratings = wct_count_ratings( $talk );
		update_post_meta( $talk, '_wc_talks_average_rate', $ratings['average'] );

		/**
		 * Hook here to perform custom actions.
		 * Used internally to clean rates count cache.
		 *
		 * @since  1.0.0
		 *
		 * @param  int $talk the ID of the talk
		 * @param  int $user_id the ID of the user
		 * @param  int $rate the user's rating
		 * @param  string       the formatted average.
		 */
		do_action( 'wct_added_rate', $talk, $user_id, $rate, $ratings['average'] );

		return $ratings['average'];
	} else {
		return false;
	}
}

/**
 * Intercepts the user ajax action to rate the talk.
 *
 * @since 1.0.0
 *
 * @return mixed the average rate or 0
 */
function wct_ajax_rate() {
	if ( ! current_user_can( 'rate_talks' ) ) {
		exit( '0' );
	}

	$user_id = wct_users_current_user_id();
	$talk = $rate = 0;

	if ( ! empty( $_POST['talk'] ) ) {
		$talk = absint( $_POST['talk'] );
	}

	if ( ! empty( $_POST['rate'] ) ) {
		$rate = absint( $_POST['rate'] );
	}

	check_ajax_referer( 'wct_rate', 'wpnonce' );

	$new_average_rate = wct_add_rate( $talk, $user_id, $rate );

	// If the user can't see other ratings, simply return the rating he just gave.
	if ( ! current_user_can( 'view_talk_rates' ) ) {
		$new_average_rate = number_format( $rate, 1 );
	}

	if ( empty( $new_average_rate ) ) {
		exit( '0' );
	} else {
		exit( $new_average_rate );
	}
}

/**
 * Order the talks by rates when requested.
 *
 * This function is hooking to WordPress 'posts_clauses' filter. As the
 * rating query is first built by using a specific WP_Meta_Query, we need
 * to also make sure the ORDER BY clause of the sql query is customized.
 *
 * @since 1.0.0
 *
 * @param  array    $clauses  The talk query sql parts.
 * @param  WP_Query $wp_query The WordPress query object.
 * @return array              New order clauses if needed.
 */
function wct_set_rates_count_orderby( $clauses = array(), $wp_query = null ) {

	if ( ( wct_is_talks() || wct_is_admin() || wct_get_global( 'rating_widget' ) ) && wct_is_orderby( 'rates_count' ) ) {
		preg_match( '/\(?(\S*).meta_key = \'_wc_talks_average_rate\'/', $clauses['where'], $matches );
		if ( ! empty( $matches[1] ) ) {
			// default order
			$order = 'DESC';

			// Specific case for plugin's administration screens.
			if ( ! empty( $clauses['orderby'] ) && 'ASC' == strtoupper( substr( $clauses['orderby'], -3 ) ) ) {
				$order = 'ASC';
			}

			$clauses['orderby'] = "{$matches[1]}.meta_value + 0 {$order}";
		}
	}

	return $clauses;
}

/**
 * Retrieve total rates for a user.
 *
 * @since 1.0.0
 *
 * @global $wpdb
 * @param  int $user_id the User ID.
 * @return int Rates count.
 */
function wct_count_user_rates( $user_id = 0 ) {
	$count = 0;

	if ( empty( $user_id ) ) {
		return $count;
	}

	global $wpdb;
	$user_id = (int) $user_id;

	$count = wp_cache_get( "talk_rates_count_{$user_id}", 'wct' );

	if ( false !== $count ) {
		return $count;
	}

	$like  = '%' . $wpdb->esc_like( ';i:' . $user_id .';' ) . '%';
	$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( post_id ) FROM {$wpdb->postmeta} WHERE meta_key= %s AND meta_value LIKE %s", '_wc_talks_rates', $like ) );

	wp_cache_set( "talk_rates_count_{$user_id}", $count, 'wct' );

	return $count;
}

/**
 * Clean the user's rates count cache
 *
 * @since 1.0.0
 *
 * @param integer $talk_id the talk ID.
 * @param integer $user_id the user ID.
 */
function wct_clean_rates_count_cache( $talk_id, $user_id = 0 ) {
	// Bail if no user id
	if ( empty( $user_id ) ) {
		return;
	}

	$user_id = (int) $user_id;

	wp_cache_delete( "talk_rates_count_{$user_id}", 'wct' );
}

/** Utilities *****************************************************************/

/**
 * Creates a specific excerpt for the content of a talk.
 *
 * @since 1.0.0
 *
 * @param  string  $text   the content to truncate
 * @param  integer $length the number of words
 * @param  string  $more   the more string
 * @return string          the excerpt of a talk
 */
function wct_create_excerpt( $text = '', $length = 55, $more = ' [&hellip;]', $nofilter = false ) {
	if ( empty( $text ) ) {
		return $text;
	}

	$text = strip_shortcodes( $text );

	/**
	 * Used internally to sanitize outputs
	 * @see  core/filters
	 *
	 * @param string $text the content without shortcodes
	 */
	$text = apply_filters( 'wct_create_excerpt_text', $text );

	$text = str_replace( ']]>', ']]&gt;', $text );

	if ( false === $nofilter ) {
		/**
		 * Filter the number of words in an excerpt.
		 */
		$excerpt_length = apply_filters( 'excerpt_length', $length );
		/**
		 * Filter the string in the "more" link displayed after a trimmed excerpt.
		 */
		$excerpt_more = apply_filters( 'excerpt_more', $more );
	} else {
		$excerpt_length = $length;
		$excerpt_more   = $more;
	}

	return wp_trim_words( $text, $excerpt_length, $excerpt_more );
}

/**
 * Prepare & sanitize the content to be output in a csv file.
 *
 * @since 1.0.0
 *
 * @param  string $content the content
 * @return string          the content to be displayed in a csv file
 */
function wct_generate_csv_content( $content = '' ) {
	// Avoid some chars
	$content = str_replace( array( '&#8212;', '"' ), array( 0, "'" ), $content );

	// Strip shortcodes
	$content = strip_shortcodes( $content );

	// Strip slashes
	$content = wp_unslash( $content );

	// Strip all tags
	$content = wp_strip_all_tags( $content, true );

	// Make sure =, +, -, @ are not the first char of the field.
	if ( in_array( mb_substr( $content, 0, 1 ), array( '=', '+', '-', '@' ), true ) ) {
		$content = "'" . $content;
	}

	return $content;
}

/**
 * Specific tag cloud count text callback
 *
 * By Default, WordPress uses "topic/s", This will
 * make sure "talk/s" will be used instead. Unfortunately
 * it's only possible in front end tag clouds.
 *
 * @since 1.0.0
 *
 * @param  integer $count Number of talks associated with the tag.
 * @return string         The count text for talks.
 */
function wct_tag_cloud_count_callback( $count = 0 ) {
	return sprintf( _nx( '%s Talk Proposal', '%s Talk Proposals', $count, 'talks tag cloud count text', 'wordcamp-talks' ), number_format_i18n( $count )  );
}

/**
 * Filters the tag cloud args by referencing a specific count text callback
 * if the plugin's "tag" taxonomy is requested.
 *
 * @since 1.0.0
 *
 * @param  array  $args The tag cloud arguments.
 * @return array        The arguments with the new count text callback if needed.
 */
function wct_tag_cloud_args( $args = array() ) {
	if( ! empty( $args['taxonomy'] ) && wct_get_tag() == $args['taxonomy'] ) {
		$args['topic_count_text_callback'] = 'wct_tag_cloud_count_callback';
	}

	return $args;
}

/**
 * Generates a talk tags cloud.
 *
 * Used when writing a new talk to allow the author to choose
 * one or more popular talk tags.
 *
 * @since 1.0.0
 *
 * @param  integer $number Number of tag to display
 * @param  array   $args   The tag cloud args
 * @return array           Associative array containing the number of tags and the content of the cloud.
 */
function wct_generate_tag_cloud( $number = 10, $args = array() ) {
	$r = array( 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' );

	$tags = get_terms( wct_get_tag(), apply_filters( 'wct_generate_tag_cloud_args', $r ) );

	if ( empty( $tags ) ) {
		return;
	}

	foreach ( $tags as $key => $tag ) {
		$tags[ $key ]->link = '#';
		$tags[ $key ]->id = $tag->term_id;
	}

	$args = wp_parse_args( $args,
		wct_tag_cloud_args( array( 'taxonomy' => wct_get_tag() ) )
	);

	return array(
		'number'   => count( $tags ),
		'tagcloud' => wp_generate_tag_cloud( $tags, $args )
	);
}

/**
 * Filters WP Editor Buttons depending on plugin's settings.
 *
 * @since 1.0.0
 *
 * @param  array  $buttons The list of buttons for the editor.
 * @return array           The filtered list of buttons to match plugin's needs.
 */
function wct_teeny_button_filter( $buttons = array() ) {
	$remove_buttons = array(
		'wp_more',
		'spellchecker',
		'wp_adv',
	);

	// Add the image and fullscreen button
	$buttons = array_diff( $buttons, $remove_buttons, array( 'fullscreen' ) );

	// Only add the image and fullscreen buttons for talk edits.
	if ( ! wct_get_global( 'public_profile_labels' ) ) {
		array_push( $buttons, 'image', 'fullscreen' );

	// For user descriptions, be consistent with allowed tags.
	} else {
		$buttons = array_diff( $buttons, array(
			'formatselect',
			'bullist',
			'numlist',
			'alignleft',
			'alignright',
			'aligncenter',
		) );
	}

	return $buttons;
}

/**
 * Since WP 4.3 _WP_Editors is now including the format_for_editor filter to sanitize
 * the content to edit. As we were using format_to_edit to sanitize the editor content,
 * it's then sanitized twice and tinymce fails to wysiwyg!
 *
 * So we just need to only apply format_to_edit if WP < 4.3!
 *
 * @since  1.0.0
 *
 * @param  string $text The editor content.
 * @return string       The sanitized text or the text without any changes.
 */
function wct_format_to_edit( $text = '' ) {
	if ( function_exists( 'format_for_editor' ) ) {
		return $text;
	}

	return format_to_edit( $text );
}

/**
 * Adds wct to global cache groups
 *
 * Mainly used to cach comments about talks count.
 *
 * @since 1.0.0
 */
function wct_cache_global_group() {
	wp_cache_add_global_groups( array( 'wct' ) );
}

/**
 * Adds a shortcut to plugin's Admin screens using the appearence menus.
 *
 * @since 1.0.0
 *
 * @param  WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance
 */
function wct_adminbar_menu( $wp_admin_bar = null ){
	$use_admin_bar = apply_filters( 'wct_adminbar_menu', true );

	if ( empty( $use_admin_bar ) ) {
		return;
	}

	if ( ! empty( $wp_admin_bar ) && current_user_can( 'edit_talks' ) ) {
		$menu_url = add_query_arg( 'post_type', wct_get_post_type(), admin_url( 'edit.php' ) );
		$wp_admin_bar->add_menu( array(
			'parent' => 'appearance',
			'id'     => 'wc_talks',
			'title'  => _x( 'WordCamp Talk Proposals', 'Admin bar menu', 'wordcamp-talks' ),
			'href'   => $menu_url,
		) );
	}
}

/**
 * Are we on a WordCamp.org site ?
 *
 * @since  1.1.0
 *
 * @return boolean True if the current site is a WordCamp.org site. False otherwise.
 */
function wct_is_wordcamp_site() {
	return (bool) wct()->is_wordcamp_site;
}

/**
 * Checks wether signups are allowed.
 *
 * @since 1.0.0
 *
 * @return bool true if user signups are allowed, false otherwise
 */
function wct_is_signup_allowed() {
	// Default to single site option
	$option = 'users_can_register';

	// Multisite config is using the registration site meta
	if ( is_multisite() ) {
		$option = 'registration';
	}

	$registration_status = get_site_option( $option, 0 );

	if ( is_numeric( $registration_status ) ) {
		$registration_status = (int) $registration_status;
	}

	// On multisite config, just deal with user signups and avoid blog signups
	$signup_allowed = ( 1 === $registration_status || 'user' === $registration_status );

	return (bool) apply_filters( 'wct_is_signup_allowed', $signup_allowed );
}

/**
 * Checks wether signups are allowed for current blog.
 *
 * @since 1.0.0
 *
 * @return bool true if signups are allowed for current site, false otherwise
 */
function wct_is_signup_allowed_for_current_blog() {
	$signups_allowed = wct_is_signup_allowed();

	if ( ! is_multisite() ) {
		return $signups_allowed;
	}

	return apply_filters( 'wct_is_signup_allowed_for_current_blog', wct_allow_signups() );
}

/** Actions handler **********************************************************/

/**
 * Runs front-end actions.
 *
 * @since  1.0.0
 * @since  1.1.0 It's no more a hook wrapper.
 */
function wct_actions() {
	// Sets user feedbacks if needed
	wct_set_user_feedback();

	// Posts a new talk if requested.
	wct_talks_post_talk();

	// Updates an existing talk if requested.
	wct_talks_update_talk();

	// Registers a new user if needed.
	wct_users_signup_user();

	// Saves a user profile if requested.
	wct_users_edit_profile();
}
