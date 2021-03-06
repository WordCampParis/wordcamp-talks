<?php
/**
 * WordCamp Talks Users functions.
 *
 * Functions specific to users
 *
 * @package WordCamp Talks
 * @subpackage users/functions
 *
 * @since 1.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Set/Get User Datas **********************************************************/

/**
 * Sets up the current user.
 *
 * @since 1.1.0
 */
function wct_users_set_current_user() {
	wct()->current_user = wp_get_current_user();
}

/**
 * Gets current user ID
 *
 * @since 1.0.0
 *
 * @return int the logged in user ID
 */
function wct_users_current_user_id() {
	return (int) wct()->current_user->ID;
}

/**
 * Gets current user user nicename
 *
 * @since 1.0.0
 *
 * @return string the logged in username
 */
function wct_users_current_user_nicename() {
	return wct()->current_user->user_nicename;
}

/**
 * Get the current user's description field.
 *
 * @since  1.1.0
 *
 * @return string The current user description.
 */
function wct_users_get_current_user_description() {
	$user_description = '';
	$user             = wp_get_current_user();

	if ( ! empty( $user->ID ) ) {
		$user_description = $user->description;
	}

	/**
	 * Filter here to replace the User description by something else.
	 *
	 * eg: A speaker's post type content.
	 *
	 * @since  1.1.0
	 *
	 * @param  string  $user_description The user description.
	 * @param  WP_User $user             The user oject.
	 */
	return apply_filters( 'wct_users_get_current_user_description', $user_description, $user );
}

/**
 * Capability check for the front-end profile editing.
 *
 * @since  1.1.0
 *
 * @return boolean True if the user can edit the profile. False otherwise.
 */
function wct_users_can_edit_profile() {
	return current_user_can( 'manage_network_users' ) || wct_is_current_user_profile();
}

/**
 * Get the user contact methods according to the context
 *
 * @since 1.0.0
 *
 * @param  array $methods The user contact methods.
 * @return array          The user contact methods.
 */
function wct_users_contactmethods( $methods = array(), $context = 'admin' ) {
	if ( 'admin' === $context ) {
		$methods = array_merge( $methods, wct_user_private_fields_list(), wct_user_public_fields_list() );
	} elseif ( function_exists( "wct_user_{$context}_fields_list" ) ) {
		$methods = call_user_func( "wct_user_{$context}_fields_list" );
	}

	return apply_filters( 'wct_users_contactmethods', array_filter( $methods ), $context );
}

/**
 * Get All user contact methods.
 *
 * @since 1.0.0
 *
 * @return array All user contact methods.
 */
function wct_users_get_all_contact_methods() {
	$wp_fields = array(
		'description' => __( 'Biographical Info', 'wordcamp-talks' ),
		'user_url'    => __( 'Website', 'wordcamp-talks' ),
	);

	// Use WordPress 4.7 new user locale feature if available.
	if ( function_exists( 'get_user_locale' ) ) {
		$wp_fields['locale'] = __( 'Language', 'wordcamp-talks' );
	}

	return apply_filters( 'wct_users_get_all_contact_methods', array_merge( $wp_fields, wp_get_user_contact_methods() ) );
}

/**
 * Get the information fields to display on the front end profile
 *
 * @since  1.1.0
 *
 * @param  string $context The context of use: display or save.
 * @return array            The list of information fields.
 */
function wct_users_get_displayed_user_information( $context = 'display' ) {
	$public_information = wct_get_global( 'public_profile_labels' );

	if ( ! $public_information ) {
		$public_information = array_merge( array(
			'display_name'     => __( 'Name', 'wordcamp-talks' ),
			'user_description' => __( 'Biographical Info', 'wordcamp-talks' ),
			'user_url'         => __( 'Website', 'wordcamp-talks' ),
		), wct_users_contactmethods( array(), 'public' ) );

		if ( wct_is_wordcamp_site() ) {
			unset( $public_information['user_url'] );
		}

		wct_set_global( 'public_profile_labels', $public_information );
	}

	if ( 'save' === $context ) {
		return $public_information;
	}

	return array_keys( $public_information );
}

/**
 * Set up the displayed user for display or edit.
 *
 * @since  1.1.0
 *
 * @param  WP_User $user The user object (Required).
 * @return boolean       True if the displayed user was set. False otherwise.
 */
function wct_users_set_displayed_user( WP_User $user ) {
	// The displayed user is set, only one user can be displayed.
	if ( (int) $user->ID !== wct_get_global( 'is_user' ) ) {
		return false;
	}

	$fields = wct_users_get_displayed_user_information();

	if ( wct_users_can_edit_profile() ) {
		$user->filter = 'edit';

		$data_to_edit = array();

		foreach ( $fields as $dk ) {
			$key = $dk;

			if ( 'user_description' === $key ) {
				// Makes sure WordPress will escape it for us.
				$key = 'description';
			}

			$data_to_edit[$dk] = $user->{$key};
		}

		$user->data_to_edit = $data_to_edit;
	}

	/**
	 * Hook here to edit the Displayed user's information
	 *
	 * @since  1.1.0
	 *
	 * @param  array $value {
	 *   An array containing the user and information fields.
	 *
	 *   @type WP_User $user   The displayed user passed by reference.
	 *   @type array   $fields The editable fields of the front end profile.
	 * }
	 */
	do_action_ref_array( 'wct_users_set_displayed_user', array( &$user, $fields ) );

	wct_set_global( 'displayed_user', $user );

	return true;
}

/**
 * Gets displayed user object if set.
 *
 * @since 1.0.0
 *
 * @return null|WP_User The displayed user if set. Null otherwise.
 */
function wct_users_displayed_user() {
	$displayed_user = null;
	$wct            = wct();

	if ( ! empty( $wct->displayed_user->ID ) && is_a( $wct->displayed_user, 'WP_User' ) ) {
		$displayed_user = $wct->displayed_user;
	}

	return $displayed_user;
}

/**
 * Gets displayed user ID.
 *
 * @since 1.0.0
 *
 * @return int the displayed user ID.
 */
function wct_users_displayed_user_id() {
	return (int) apply_filters( 'wct_users_displayed_user_id', wct()->displayed_user->ID );
}

/**
 * Gets displayed user user nicename.
 *
 * @since 1.0.0
 *
 * @return string the displayed user username.
 */
function wct_users_get_displayed_user_username() {
	return apply_filters( 'wct_users_get_displayed_user_username', wct()->displayed_user->user_nicename );
}

/**
 * Gets displayed user display name.
 *
 * @since 1.0.0
 *
 * @return string the displayed user display name.
 */
function wct_users_get_displayed_user_displayname() {
	return apply_filters( 'wct_users_get_displayed_user_displayname', wct()->displayed_user->display_name );
}

/**
 * Gets one specific or all attribute about a user.
 *
 * @since 1.0.0
 *
 * @return mixed WP_User/string/array/int the user object or one of its attribute.
 */
function wct_users_get_user_data( $field = '', $value ='', $attribute = 'all'  ) {
	$user = get_user_by( $field, $value );

	if ( empty( $user ) ) {
		return false;
	}

	if ( 'all' == $attribute ) {
		return $user;
	} else {
		return $user->{$attribute};
	}
}

/** User urls *****************************************************************/

/**
 * Gets URL to the edit profile page of a user.
 *
 * @since 1.0.0
 *
 * @param  int $user_id User id.
 * @return string       User profile edit url.
 */
function wct_users_get_user_profile_edit_url( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		$user_id = wct_users_displayed_user_id();
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	$profile_url = '';

	if ( user_can( $user_id, 'read' ) ) {
		$profile_url = get_edit_profile_url( $user_id );
	} elseif ( is_multisite() ) {
		$profile_url = get_dashboard_url( $user_id, 'profile.php' );
	}

	/**
	 * Filter the user profile url once it has been built
	 *
	 * @since 1.0.0
	 *
	 * @param string $profile_url Profile Edit Url.
	 * @param int    $user_id     the user ID.
	 */
	return apply_filters( 'wct_users_get_user_profile_edit_url', $profile_url, $user_id );
}

/**
 * Gets the displayed user's profile url.
 *
 * @since 1.0.0
 *
 * @param  string $type profile, rates, comments.
 * @return string       url of the profile type.
 */
function wct_users_get_displayed_profile_url( $type = 'profile' ) {
	$user_id = wct_users_displayed_user_id();
	$username = wct_users_get_displayed_user_username();

	$profile_url = call_user_func_array( 'wct_users_get_user_' . $type . '_url', array( $user_id, $username ) );

	/**
	 * Filter here to edit the displayed user's profile url.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $profile_url Url to the profile part.
	 * @param  string $type        The requested part (profile, rates or comments).
	 */
	return apply_filters( 'wct_users_get_displayed_profile_url', $profile_url, $type );
}

/**
 * Gets the logged in user's profile url.
 *
 * @since 1.0.0
 *
 * @param  string $type profile, rates, comments.
 * @return string       url of the profile type.
 */
function wct_users_get_logged_in_profile_url( $type = 'profile' ) {
	$user_id = wct_users_current_user_id();
	$username = wct_users_current_user_nicename();

	$profile_url = call_user_func_array( 'wct_users_get_user_' . $type . '_url', array( $user_id, $username ) );

	/**
	 * Filter here to edit the loggedin user's profile url.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $profile_url Url to the profile part.
	 * @param  string $type        The requested part (profile, rates or comments).
	 */
	return apply_filters( 'wct_users_get_logged_in_profile_url', $profile_url, $type );
}

/**
 * Gets URL to the main profile page of a user.
 *
 * @since 1.0.0
 *
 * @global $wp_rewrite
 * @param  integer $user_id       The User id.
 * @param  string  $user_nicename Optional. User nicename.
 * @param  boolean $no_filter     Whether to allow filters.
 * @return string                 User profile url.
 */
function wct_users_get_user_profile_url( $user_id = 0, $user_nicename = '', $nofilter = false ) {
	global $wp_rewrite;

	// Bail if no user id provided
	if ( empty( $user_id ) ) {
		return false;
	}

	if ( false === $nofilter ) {
		/**
		 * Filter here to shortcircuit the function.
		 *
		 * @since  1.0.0
		 *
		 * @param integer $user_id       The user ID
		 * @param string  $user_nicename The username
		 */
		$early_profile_url = apply_filters( 'wct_users_pre_get_user_profile_url', (int) $user_id, $user_nicename );
		if ( is_string( $early_profile_url ) ) {
			return $early_profile_url;
		}
	}

	// Pretty permalinks
	if ( $wp_rewrite->using_permalinks() ) {
		$url = $wp_rewrite->root . wct_user_slug() . '/%' . wct_user_rewrite_id() . '%';

		// Get username if not passed
		if ( empty( $user_nicename ) ) {
			$user_nicename = wct_users_get_user_data( 'id', $user_id, 'user_nicename' );
		}

		$url = str_replace( '%' . wct_user_rewrite_id() . '%', $user_nicename, $url );
		$url = home_url( user_trailingslashit( $url ) );

	// Unpretty permalinks
	} else {
		$url = add_query_arg( array( wct_user_rewrite_id() => $user_id ), home_url( '/' ) );
	}

	if ( false === $nofilter ) {
		/**
		 * Filter the user profile url once it has been built
		 *
		 * @since  1.0.0
		 *
		 * @param string $url           Profile Url
		 * @param int    $user_id       the user ID
		 * @param string $user_nicename the username
		 */
		return apply_filters( 'wct_users_get_user_profile_url', $url, $user_id, $user_nicename );
	} else {
		return $url;
	}
}

/**
 * Gets URL to the talks profile page of a user.
 *
 * @since 1.0.0
 *
 * @global $wp_rewrite
 * @param  integer $user_id       The User id.
 * @param  string  $user_nicename Optional. User nicename.
 * @return string                 To talks section of the profile url.
 */
function wct_users_get_user_talks_url( $user_id = 0, $user_nicename = '' ) {
	global $wp_rewrite;

	// Bail if no user id provided
	if ( empty( $user_id ) ) {
		return false;
	}

	/**
	 * Filter here to shortcircuit the function.
	 *
	 * @since  1.0.0
	 *
	 * @param integer $user_id       The user ID
	 * @param string  $user_nicename The username
	 */
	$early_profile_url = apply_filters( 'wct_users_pre_get_user_talks_url', (int) $user_id, $user_nicename );
	if ( is_string( $early_profile_url ) ) {
		return $early_profile_url;
	}

	// Pretty permalinks
	if ( $wp_rewrite->using_permalinks() ) {
		$url = $wp_rewrite->root . wct_user_slug() . '/%' . wct_user_rewrite_id() . '%/' . wct_user_talks_slug();

		// Get username if not passed
		if ( empty( $user_nicename ) ) {
			$user_nicename = wct_users_get_user_data( 'id', $user_id, 'user_nicename' );
		}

		$url = str_replace( '%' . wct_user_rewrite_id() . '%', $user_nicename, $url );
		$url = home_url( user_trailingslashit( $url ) );

	// Unpretty permalinks
	} else {
		$url = add_query_arg( array( wct_user_rewrite_id() => $user_id, wct_user_talks_rewrite_id() => '1' ), home_url( '/' ) );
	}

	/**
	 * Filter the talks section of the profile url once it has built it.
	 *
	 * @since  1.0.0
	 *
	 * @param string $url           Talks section of the  profile Url.
	 * @param int    $user_id       the user ID.
	 * @param string $user_nicename the username.
	 */
	return apply_filters( 'wct_users_get_user_talks_url', $url, $user_id, $user_nicename );
}

/**
 * Gets URL to the rates profile page of a user.
 *
 * @since 1.0.0
 *
 * @global $wp_rewrite
 * @param  integer $user_id       The User id.
 * @param  string  $user_nicename Optional. User nicename.
 * @return string                 Rates profile url.
 */
function wct_users_get_user_rates_url( $user_id = 0, $user_nicename = '' ) {
	global $wp_rewrite;

	// Bail if no user id provided
	if ( empty( $user_id ) ) {
		return false;
	}

	/**
	 * Filter here to shortcircuit the function.
	 *
	 * @since  1.0.0
	 *
	 * @param int    $user_id       the user ID.
	 * @param string $user_nicename the username.
	 */
	$early_profile_url = apply_filters( 'wct_users_pre_get_user_rates_url', (int) $user_id, $user_nicename );
	if ( is_string( $early_profile_url ) ) {
		return $early_profile_url;
	}


	// Pretty permalinks
	if ( $wp_rewrite->using_permalinks() ) {
		$url = $wp_rewrite->root . wct_user_slug() . '/%' . wct_user_rewrite_id() . '%/' . wct_user_rates_slug();

		// Get username if not passed
		if ( empty( $user_nicename ) ) {
			$user_nicename = wct_users_get_user_data( 'id', $user_id, 'user_nicename' );
		}

		$url = str_replace( '%' . wct_user_rewrite_id() . '%', $user_nicename, $url );
		$url = home_url( user_trailingslashit( $url ) );

	// Unpretty permalinks
	} else {
		$url = add_query_arg( array( wct_user_rewrite_id() => $user_id, wct_user_rates_rewrite_id() => '1' ), home_url( '/' ) );
	}

	/**
	 * Filter the rates profile url once it has been built.
	 *
	 * @since  1.0.0
	 *
	 * @param string $url           Rates profile Url.
	 * @param int    $user_id       the user ID.
	 * @param string $user_nicename the username.
	 */
	return apply_filters( 'wct_users_get_user_rates_url', $url, $user_id, $user_nicename );
}

/**
 * Gets URL to the "to rate" profile page of a user.
 *
 * @since 1.0.0
 *
 * @global $wp_rewrite
 * @param  integer $user_id       The User id.
 * @param  string  $user_nicename Optional. User nicename.
 * @return string                 To rate profile url.
 */
function wct_users_get_user_to_rate_url( $user_id = 0, $user_nicename = '' ) {
	global $wp_rewrite;

	// Bail if no user id provided
	if ( empty( $user_id ) ) {
		return false;
	}

	/**
	 * Filter here to shortcircuit the function.
	 *
	 * @since  1.0.0
	 *
	 * @param integer $user_id       the user ID.
	 * @param string  $user_nicename the username.
	 */
	$early_profile_url = apply_filters( 'wct_users_pre_get_user_to_rate_url', (int) $user_id, $user_nicename );
	if ( is_string( $early_profile_url ) ) {
		return $early_profile_url;
	}

	// Pretty permalinks
	if ( $wp_rewrite->using_permalinks() ) {
		$url = $wp_rewrite->root . wct_user_slug() . '/%' . wct_user_rewrite_id() . '%/' . wct_user_to_rate_slug();

		// Get username if not passed
		if ( empty( $user_nicename ) ) {
			$user_nicename = wct_users_get_user_data( 'id', $user_id, 'user_nicename' );
		}

		$url = str_replace( '%' . wct_user_rewrite_id() . '%', $user_nicename, $url );
		$url = home_url( user_trailingslashit( $url ) );

	// Unpretty permalinks
	} else {
		$url = add_query_arg( array( wct_user_rewrite_id() => $user_id, wct_user_to_rate_rewrite_id() => '1' ), home_url( '/' ) );
	}

	/**
	 * Filter the "to rate" profile url once it has built it.
	 *
	 * @since  1.0.0
	 *
	 * @param string  $url           To rate profile Url.
	 * @param integer $user_id       The user ID.
	 * @param string  $user_nicename The username.
	 */
	return apply_filters( 'wct_users_get_user_to_rate_url', $url, $user_id, $user_nicename );
}

/**
 * Gets URL to the "archive" profile page of a user.
 *
 * @since 1.2.0
 *
 * @global $wp_rewrite
 * @param  integer $user_id       The User id.
 * @param  string  $user_nicename Optional. User nicename.
 * @return string                 Archive profile url.
 */
function wct_users_get_user_archive_url( $user_id = 0, $user_nicename = '' ) {
	global $wp_rewrite;

	// Bail if no user id provided
	if ( empty( $user_id ) ) {
		return false;
	}

	/**
	 * Filter here to shortcircuit the function.
	 *
	 * @since  1.2.0
	 *
	 * @param integer $user_id       the user ID.
	 * @param string  $user_nicename the username.
	 */
	$early_profile_url = apply_filters( 'wct_users_pre_get_user_archive_url', (int) $user_id, $user_nicename );
	if ( is_string( $early_profile_url ) ) {
		return $early_profile_url;
	}

	// Pretty permalinks
	if ( $wp_rewrite->using_permalinks() ) {
		$url = $wp_rewrite->root . wct_user_slug() . '/%' . wct_user_rewrite_id() . '%/' . wct_user_archive_slug();

		// Get username if not passed
		if ( empty( $user_nicename ) ) {
			$user_nicename = wct_users_get_user_data( 'id', $user_id, 'user_nicename' );
		}

		$url = str_replace( '%' . wct_user_rewrite_id() . '%', $user_nicename, $url );
		$url = home_url( user_trailingslashit( $url ) );

	// Unpretty permalinks
	} else {
		$url = add_query_arg( array( wct_user_rewrite_id() => $user_id, wct_user_archive_rewrite_id() => '1' ), home_url( '/' ) );
	}

	/**
	 * Filter the "archive" profile url once it has built it.
	 *
	 * @since  1.2.0
	 *
	 * @param string  $url           To rate profile Url.
	 * @param integer $user_id       The user ID.
	 * @param string  $user_nicename The username.
	 */
	return apply_filters( 'wct_users_get_user_archive_url', $url, $user_id, $user_nicename );
}

/**
 * Gets URL to the comments profile page of a user.
 *
 * @since 1.0.0
 *
 * @global $wp_rewrite
 * @param  integer $user_id       The User id.
 * @param  string  $user_nicename Optional. User nicename.
 * @return string                 Comments profile url.
 */
function wct_users_get_user_comments_url( $user_id = 0, $user_nicename = '' ) {
	global $wp_rewrite;

	// Bail if no user id provided
	if ( empty( $user_id ) ) {
		return false;
	}

	/**
	 * Filter here to shortcircuit the function.
	 *
	 * @since  1.0.0
	 *
	 * @param integer $user_id       The user ID.
	 * @param string  $user_nicename The username.
	 */
	$early_profile_url = apply_filters( 'wct_users_pre_get_user_comments_url', (int) $user_id, $user_nicename );
	if ( is_string( $early_profile_url ) ) {
		return $early_profile_url;
	}


	// Pretty permalinks
	if ( $wp_rewrite->using_permalinks() ) {
		$url = $wp_rewrite->root . wct_user_slug() . '/%' . wct_user_rewrite_id() . '%/' . wct_user_comments_slug();

		// Get username if not passed
		if ( empty( $user_nicename ) ) {
			$user_nicename = wct_users_get_user_data( 'id', $user_id, 'user_nicename' );
		}

		$url = str_replace( '%' . wct_user_rewrite_id() . '%', $user_nicename, $url );
		$url = home_url( user_trailingslashit( $url ) );

	// Unpretty permalinks
	} else {
		$url = add_query_arg( array( wct_user_rewrite_id() => $user_id, wct_user_comments_rewrite_id() => '1' ), home_url( '/' ) );
	}

	/**
	 * Filter the comments profile url once it has been built.
	 *
	 * @since  1.0.0
	 *
	 * @param string  $url           Rates profile Url.
	 * @param integer $user_id       The user ID.
	 * @param string  $user_nicename The username.
	 */
	return apply_filters( 'wct_users_get_user_comments_url', $url, $user_id, $user_nicename );
}

/**
 * Gets the signup url
 *
 * @since 1.0.0
 *
 * @global  $wp_rewrite
 * @return string signup url
 */
function wct_users_get_signup_url() {
	global $wp_rewrite;

	/**
	 * Early filter to override form url before being built.
	 *
	 * @since  1.0.0
	 *
	 * @param mixed false or url to override
	 */
	$early_signup_url = apply_filters( 'wct_users_pre_get_signup_url', false );

	if ( ! empty( $early_signup_url ) ) {
		return $early_signup_url;
	}

	// Pretty permalinks
	if ( $wp_rewrite->using_permalinks() ) {
		$signup_url = $wp_rewrite->root . wct_action_slug() . '/%' . wct_action_rewrite_id() . '%';

		$signup_url = str_replace( '%' . wct_action_rewrite_id() . '%', wct_signup_slug(), $signup_url );
		$signup_url = home_url( user_trailingslashit( $signup_url ) );

	// Unpretty permalinks
	} else {
		$signup_url = add_query_arg( array( wct_action_rewrite_id() => wct_signup_slug() ), home_url( '/' ) );
	}

	/**
	 * Filter to override form url after being built.
	 *
	 * @since  1.0.0
	 *
	 * @param string url to override
	 */
	return apply_filters( 'wct_get_form_url', $signup_url );
}

/** Template functions ********************************************************/

/**
 * Enqueues Users description editing scripts.
 *
 * @since 1.0.0
 */
function wct_users_enqueue_scripts() {
	if ( ! wct_is_user_profile() ) {
		return;
	}

	// Viewing another user's profile with no sharing dialog box doesn't need js.
	if ( ! wct_is_current_user_profile() ) {
		return;
	}

	$js_vars = array(
		'is_profile' => 1,
	);

	wp_enqueue_script ( 'wc-talks-script', wct_get_js_script( 'script' ), array( 'jquery' ), wct_get_version(), true );
	wp_localize_script( 'wc-talks-script', 'wct_vars', apply_filters( 'wct_users_current_profile_script', $js_vars ) );
}
add_action( 'wp_enqueue_scripts', 'wct_users_enqueue_scripts', 14 );

/**
 * Builds user's profile nav.
 *
 * @since 1.0.0
 *
 * @param  integer $user_id       The User id.
 * @param  string  $user_nicename Optional. User nicename.
 * @param  boolean $nofilter.     Whether to fire filters or not.
 * @return array                  The nav items organized in an associative array.
 */
function wct_users_get_profile_nav_items( $user_id = 0, $username ='', $nofilter = false ) {
	// Bail if no id or username are provided.
	if ( empty( $user_id ) || empty( $username ) ) {
		return array();
	}

	$nav_items = array(
		'profile' => array(
			'title'   => __( 'Profile', 'wordcamp-talks' ),
			'url'     => wct_users_get_user_profile_url( $user_id, $username ),
			'current' => wct_is_user_profile_home(),
			'slug'    => sanitize_title( _x( 'home', 'user profile slug', 'wordcamp-talks' ) ),
		),
		'talks' => array(
			'title'   => __( 'Submitted', 'wordcamp-talks' ),
			'url'     => wct_users_get_user_talks_url( $user_id, $username ),
			'current' => wct_is_user_profile_talks(),
			'slug'    => sanitize_title( _x( 'talks', 'user talks profile slug', 'wordcamp-talks' ) ),
		),
		'archive' => array(
			'title'   => __( 'Archived', 'wordcamp-talks' ),
			'url'     => wct_users_get_user_archive_url( $user_id, $username ),
			'current' => wct_is_user_profile_archive(),
			'slug'    => sanitize_title( _x( 'archive', 'user archive profile slug', 'wordcamp-talks' ) ),
		),
	);

	// Remove the talks nav if user can't publish some!
	if ( ! user_can( $user_id, 'publish_talks' ) ) {
		unset( $nav_items['talks'], $nav_items['archive'] );
	}

	if ( user_can( $user_id, 'comment_talks' ) ) {
		$nav_items[ 'comments' ] = array(
			'title'   => __( 'Commented', 'wordcamp-talks' ),
			'url'     => wct_users_get_user_comments_url( $user_id, $username ),
			'current' => wct_is_user_profile_comments(),
			'slug'    => wct_user_comments_slug(),
		);
	}

	if ( ! wct_is_rating_disabled() && user_can( $user_id, 'rate_talks' ) ) {
		$nav_items = array_merge( $nav_items, array(
			'rates' => array(
				'title'   => __( 'Rated', 'wordcamp-talks' ),
				'url'     => wct_users_get_user_rates_url( $user_id, $username ),
				'current' => wct_is_user_profile_rates(),
				'slug'    => wct_user_rates_slug(),
			),
			'to_rate' => array(
				'title'   => __( 'To rate', 'wordcamp-talks' ),
				'url'     => wct_users_get_user_to_rate_url( $user_id, $username ),
				'current' => wct_is_user_profile_to_rate(),
				'slug'    => 'to-rate',
			),
		) );
	}

	if ( false === $nofilter ) {
		/**
		 * Filter the available user's profile nav items.
		 *
		 * @since  1.0.0
		 *
		 * @param array   $nav_items The nav items.
		 * @param integer $user_id   The user ID.
		 * @param string  $username  The username.
		 */
		return apply_filters( 'wct_users_get_profile_nav_items', $nav_items, $user_id, $username );
	} else {
		return $nav_items;
	}
}

/** Handle User actions *******************************************************/

/**
 * Saves Front-end profile edits
 *
 * @since  1.1.0
 */
function wct_users_edit_profile() {
	// Bail if not a post request
	if ( 'POST' != strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
		return;
	}

	// Bail if not a post talk request
	if ( empty( $_POST['wct_users_edit_profile'] ) ) {
		return;
	}

	// Check nonce
	check_admin_referer( 'wct-edit-profile' );

	$redirect = wct_users_get_displayed_profile_url();

	// Check capacity
	if ( ! wct_users_can_edit_profile() ) {
		// Redirect to main archive page and inform the user he cannot edit the profile.
		wp_safe_redirect( add_query_arg( 'error', 11, $redirect ) );
		exit();
	}

	$fields = array_intersect_key( $_POST, wct_users_get_displayed_user_information( 'save' ) );

	if ( isset( $fields['user_description'] ) ) {
		$fields['description'] = trim( $fields['user_description'] );
		unset( $fields['user_description'] );
	}

	foreach ( $fields as $kf => $vf ) {
		if ( 'description' === $kf ) {
			$fields[$kf] = wct_users_sanitize_user_description( $vf );
		} else {
			$fields[$kf] = sanitize_text_field( $vf );
		}
	}

	$fields['ID'] = (int) wct_users_displayed_user_id();
	$userdata     = (object) $fields;

	/**
	 * Filter here to shortcircuit the function.
	 *
	 * @since  1.1.0
	 *
	 * @param array  $result   An array containing the result type as key and the feedback ID as value.
	 * @param object $userdata The User data to update.
	 */
	$result = apply_filters( 'wct_users_pre_edit_profile', array(), $userdata );

	if ( empty( $result['error'] ) && empty( $result['success'] ) ) {
		$updated = wp_update_user( $userdata );

		if ( is_wp_error( $updated ) ) {
			$result = array( 'error' => 1 );
		} else {
			$result = array( 'success' => 8 );
		}
	}

	wp_safe_redirect( add_query_arg( $result, $redirect ) );
	exit();
}

/**
 * Hooks to deleted_user to perform additional actions
 *
 * When a user is deleted, we need to be sure the talks he shared are also
 * deleted to avoid troubles in edit screens as the post author field will found
 * no user. I also remove rates.
 *
 * The main problem here (excepting error notices) is ownership of the talk. To avoid any
 * troubles, deleting when user leaves seems to be the safest. If you have a different point
 * of view, you can remove_action( 'deleted_user', 'wct_users_delete_user_data', 10, 1 )
 * and use a different way of managing this. I advise you to make sure talks are reattributed to
 * an existing user ID. About rates, there's no problem if a non existing user ID is in the rating
 * list of a talk.
 *
 * @since 1.0.0
 */
function wct_users_delete_user_data( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		return;
	}

	// Make sure we don't miss any talks
	add_filter( 'wct_talks_get_status', 'wct_talks_get_all_status', 10, 1 );

	// Get user's talks, in case of multisite
	$user_talks = wct_talks_get_talks( array(
		'per_page' => -1,
		'author'   => $user_id,
	) );

	// remove asap
	remove_filter( 'wct_talks_get_status', 'wct_talks_get_all_status', 10, 1 );

	/**
	 * We're forcing talks to be deleted definitively
	 * Using this filter you can set it to only be trashed
	 *
	 * @param bool   $force_delete true to permanently delete, false to trash
	 */
	$force_delete = apply_filters( 'wct_users_delete_user_force_delete', true );

	// If any delete them
	if ( ! empty( $user_talks['talks'] ) ) {
		foreach ( $user_talks['talks'] as $user_talk ) {
			/**
			 * WordPress is using a check on native post types
			 * so we can't just pass $force_delete to wp_delete_post().
			 */
			if ( empty( $force_delete ) ) {
				/**
				 * @param  int ID of the talk being trashed
				 * @param  int $user_id the user id
				 */
				do_action( 'wct_users_before_trash_user_data', $user_talk->ID, $user_id );

				wp_trash_post( $user_talk->ID );
			} else {
				/**
				 * @param  int ID of the talk being trashed
				 * @param  int $user_id the user id
				 */
				do_action( 'wct_users_before_delete_user_data', $user_talk->ID, $user_id );

				wp_delete_post( $user_talk->ID, true );
			}
		}
	}

	// Ratings are on, try to delete them.
	if ( ! wct_is_rating_disabled() ) {
		// Make sure we don't miss any talks
		add_filter( 'wct_talks_get_status', 'wct_talks_get_all_status', 10, 1 );

		// Get user's rates
		$rated_talks = wct_talks_get_talks( array(
			'per_page' => -1,
			'meta_query' => array( array(
				'key'     => '_wc_talks_rates',
				'value'   => ';i:' . $user_id . ';',
				'compare' => 'LIKE'
			) ),
		) );

		// remove asap
		remove_filter( 'wct_talks_get_status', 'wct_talks_get_all_status', 10, 1 );

		// If any delete them.
		if ( ! empty( $rated_talks['talks'] ) ) {

			foreach ( $rated_talks['talks'] as $talk ) {
				wct_delete_rate( $talk->ID, $user_id );
			}

			/**
			 * @param int $user_id the user ID
			 */
			do_action( 'wct_delete_user_rates', $user_id );
		}
	}

	/**
	 * @param int $user_id the user ID
	 */
	do_action( 'wct_users_deleted_user_data', $user_id );
}

/**
 * Get talk authors sorted by count.
 *
 * count_many_users_posts() does not match the need.
 *
 * @since 1.0.0
 * @since 1.1.0 It's now possible to get a single user's Talk Proposals count.
 *
 * @global  $wpdb
 * @param   integer       $max     The number of users to limit the query
 * @param   integer       $user_id The ID of a single user. Optional.
 * @return  array|integer          List of users ordered by talks count.
 *                                 Or the specific requested user's count.
 */
function wct_users_talks_count_by_user( $max = 10, $user_id = null ) {
	global $wpdb;

	$sql = array();

	if ( ! $user_id ) {
		$sql['select']  = "SELECT p.post_author, COUNT(p.ID) as count_talks, u.user_nicename";
		$sql['from']    = "FROM {$wpdb->posts} p LEFT JOIN {$wpdb->users} u ON ( p.post_author = u.ID )";
	} else {
		$sql['select']  = "SELECT COUNT(*) as count_talks";
		$sql['from']    = "FROM {$wpdb->posts}";
	}

	if ( current_user_can( 'view_other_profiles' ) || ( $user_id && (int) get_current_user_id() === (int) $user_id ) ) {
		$sql['where']   = str_replace( 'post_status = \'private\'', 'post_status IN( "' . join( array_keys( wct_get_statuses() ), '","' ) . '")', get_posts_by_author_sql( wct_get_post_type(), true, null, false ) );
	} else {
		$sql['where']   = get_posts_by_author_sql( wct_get_post_type(), true, null, true );
	}

	if ( ! $user_id ) {
		$sql['groupby'] = 'GROUP BY p.post_author';
		$sql['order']   = 'ORDER BY count_talks DESC';
		$sql['limit']   = $wpdb->prepare( 'LIMIT 0, %d', $max );
	} else {
		$sql['limit'] = 'LIMIT 1';
	}

	$query = apply_filters( 'wct_users_talks_count_by_user_query', join( ' ', $sql ), $sql, $max );

	if ( ! $user_id ) {
		return $wpdb->get_results( $query );
	}

	return (int) $wpdb->get_var( $query );
}

/**
 * Filter to set the WordCamp Talk status query for `count_many_users_posts`.
 *
 * @since 1.3.0
 *
 * @param  string $query The query used by `count_many_users_posts`.
 * @return string        The query `count_many_users_posts` should use for Talk proposals.
 */
function _wct_query_many_users_talks( $query = '' ) {
	return str_replace( 'post_status = \'publish\'', 'post_status IN( "' . join( array_keys( wct_get_statuses() ), '","' ) . '")', $query );
}

/**
 * Count Talk proposals for many applicants.
 *
 * @since 1.3.0
 *
 * @param  array $users The list of user ids to get the talk proposals count for.
 * @return array        The list of Talk proposals count keyed by the corresponding user IDs.
 */
function _wct_count_many_users_talks( $users = array() ) {
	$users = wp_parse_id_list( $users );

	add_filter( 'query', '_wct_query_many_users_talks', 10, 1 );

	$count = count_many_users_posts( $users, wct_get_post_type(), true );

	remove_filter( 'query', '_wct_query_many_users_talks', 10, 1 );

	return $count;
}

/**
 * Get the default role for a user (used in multisite configs).
 *
 * @since 1.0.0
 */
function wct_users_get_default_role() {
	return apply_filters( 'wct_users_get_default_role', get_option( 'default_role', 'subscriber' ) );
}

/**
 * Get the signup key if the user registered.
 *
 * @since 1.0.0
 *
 * @global $wpdb
 * @param  string $user       user login
 * @param  string $user_email user email
 * @param  string $key        activation key
 * @param  array  $meta       the signup's meta data
 */
function wct_users_intercept_activation_key( $user, $user_email = '', $key = '', $meta = array() ) {
	if ( ! empty( $key ) && ! empty( $user_email ) ) {
		wct_set_global( 'activation_key', array( $user_email => $key ) );
	}

	return false;
}

/**
 * Update the $wpdb->signups table in case of a multisite config.
 *
 * @since 1.0.0
 *
 * @global $wpdb
 * @param  array $signup the signup required data
 * @param  int $user_id  the user ID
 */
function wct_users_update_signups_table( $user_id = 0 ) {
	global $wpdb;

	if ( empty( $user_id ) ) {
		return;
	}

	$user = wct_users_get_user_data( 'id', $user_id );

	if ( empty( $user->user_login ) || empty( $user->user_email ) ) {
		return;
	}

	add_filter( 'wpmu_signup_user_notification', 'wct_users_intercept_activation_key', 10, 4 );
	wpmu_signup_user( $user->user_login, $user->user_email, array( 'add_to_blog' => get_current_blog_id(), 'new_role' => wct_users_get_default_role() ) );
	remove_filter( 'wpmu_signup_user_notification', 'wct_users_intercept_activation_key', 10, 4 );

	$key = wct_get_global( 'activation_key' );

	if ( empty( $key[ $user->user_email ] ) ) {
		return;

	// Reset the global
	} else {
		wct_set_global( 'activation_key', array() );
	}

	$wpdb->update( $wpdb->signups,
		array( 'active' => 1, 'activated' => current_time( 'mysql', true ) ),
		array( 'activation_key' => $key[ $user->user_email ] )
	);
}

/**
 * Reset a password by leaving the Activation key.
 *
 * @since 1.0.0
 *
 * @param string $password The Password to reset.
 * @param int    $user_id  The User id to reset the password for.
 */
function wct_reset_password( $password, $user_id ) {
	global $wpdb;

	$hash = wp_hash_password( $password );
	$wpdb->update( $wpdb->users, array( 'user_pass' => $hash ), array('ID' => $user_id ) );

	wp_cache_delete( $user_id, 'users' );
}

/**
 * Signup a new user.
 *
 * @since 1.0.0
 *
 * @param bool $exit whether to exit or not
 */
function wct_users_signup_user( $exit = true ) {
	// Bail if not a post request
	if ( 'POST' != strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
		return;
	}

	// Bail if not a post talk request
	if ( empty( $_POST['wct_signup'] ) || ! is_array( $_POST['wct_signup'] ) ) {
		return;
	}

	// Check nonce
	check_admin_referer( 'wct_signup' );

	$redirect     = wct_get_redirect_url();
	$is_multisite = is_multisite();

	/**
	 * Set the feedback array.
	 */
	$feedback = array(
		'error'   => array(),
		'success' => array(),
		'info'    => array(),
	);

	$user_login = false;

	if ( ! empty( $_POST['wct_signup']['user_login'] ) ) {
		$user_login = $_POST['wct_signup']['user_login'];
	}

	// Force the login to exist and to be at least 4 characters long
	if ( 4 > mb_strlen( $user_login ) ) {
		$feedback['error'][] = 7;
	}

	$user_email = false;
	if ( ! empty( $_POST['wct_signup']['user_email'] ) ) {
		$user_email = $_POST['wct_signup']['user_email'];
	}

	// Do we need to edit the user once created ?
	$edit_user = array_diff_key(
		$_POST['wct_signup'],
		array(
			'signup'     => 'signup',
			'user_login' => 'user_login',
			'user_email' => 'user_email',
		)
	);

	/**
	 * Perform actions before the required fields check
	 *
	 * @since 1.0.0
	 *
	 * @param  string $user_login the user login
	 * @param  string $user_email the user email
	 * @param  array  $edit_user  all extra user fields
	 * @param  array  $feedback   the feedback array
	 */
	do_action_ref_array( 'wct_users_before_signup_field_required', array( $user_login, $user_email, $edit_user, $feedback ) );

	foreach ( $edit_user as $key => $value ) {

		if ( ! apply_filters( 'wct_users_is_signup_field_required', false, $key ) ) {
			continue;
		}

		if ( empty( $value ) && false === array_search( 8, $feedback['error'] ) ) {
			$feedback['error'][] = 8;
		}
	}

	// Stop the process and ask to fill all fields.
	if ( ! empty( $feedback['error'] ) ) {
		//Add feedback to the user
		wct_add_message( array_filter( $feedback ) );
		return;
	}

	/**
	 * Perform actions before the user is created
	 *
	 * @param  string $user_login the user login
	 * @param  string $user_email the user email
	 * @param  array  $edit_user  all extra user fields
	 */
	do_action( 'wct_users_before_signup_user', $user_login, $user_email, $edit_user );

	// Do the profiles.wordpress.org check/syncing.
	if ( wct_talk_sync_wp_org_profile() ) {
		$wporg_profile = new WordCamp_Talks_Users_Profile_Parser;
		$profile_data  = $wporg_profile->parse( $user_login );

		if ( empty( $profile_data ) || ! empty( $profile_data['errors'] ) ) {
			$profile_error = 12;

			if ( is_wp_error( $profile_data['errors'] ) ) {
				$profile_error = $profile_data['errors']->get_error_message();
			}

			// Add feedback to the user
			wct_add_message( array(
				'error' => $profile_error,
			) );

			return;
		} else {
			// Use the profiles.wordpress.org display name and description, if found.
			$to_edit = array_intersect_key( $profile_data, array(
				'display_name' => true,
				'description'  => true,
			) );

			$edit_user = array_merge( $edit_user, $to_edit );
		}
	}

	// Defaults to user name and user email
	$signup_array = array( 'user_name' => $user_login, 'user_email' => $user_email );

	// Sanitize the signup on multisite configs.
	if ( true === (bool) $is_multisite ) {
		$signup_array = wpmu_validate_user_signup( $user_login, $user_email );

		if ( is_wp_error( $signup_array['errors'] ) && $signup_array['errors']->get_error_code() ) {
			// Add feedback to the user
			wct_add_message( array(
				'error' => $signup_array['errors']->get_error_messages(),
			) );
			return;
		}

		// Filter the rp login url for WordPress 4.3
		add_filter( 'wp_mail', 'wct_multisite_user_notification', 10, 1 );
	}

	// Register the user
	$user = register_new_user( $signup_array['user_name'], $signup_array['user_email'] );

	// Stop filtering the rp login url
	if ( true === (bool) $is_multisite ) {
		remove_filter( 'wp_mail', 'wct_multisite_user_notification', 10, 1 );
	}

	/**
	 * Perform actions after the user is created
	 *
	 * @param  string             $user_login the user login
	 * @param  string             $user_email the user email
	 * @param  array              $edit_user  all extra user fields
	 * @param  mixed int|WP_Error $user the user id or an error
	 */
	do_action( 'wct_users_after_signup_user', $user_login, $user_email, $edit_user, $user );

	if ( is_wp_error( $user ) ) {
		// Add feedback to the user
		wct_add_message( array(
			'error' => $user->get_error_messages(),
		) );
		return;

	// User is created, now we need to eventually edit him
	} else {
		// The user registered to submit talks.
		update_user_meta( $user, '_wc_talks_registered', 1 );

		if ( ! empty( $edit_user ) )  {

			$userdata = new stdClass();
			$userdata = (object) $edit_user;
			$userdata->ID = $user;

			if ( isset( $userdata->locale ) && '' === $userdata->locale ) {
				$userdata->locale = 'en_US';
			}

			/**
			 * Just before the user is updated, this will only be available
			 * if custom fields/contact methods are used.
			 *
			 * @param object $userdata the userdata to update
			 */
			$userdata = apply_filters( 'wct_users_signup_userdata', $userdata );

			// Edit the user
			if ( wp_update_user( $userdata ) ) {
				/**
				 * Any extra field not using contact methods or WordPress built in user fields can hook here
				 *
				 * @param int $user the user id
				 * @param array $edit_user the submitted user fields
				 */
				do_action( 'wct_users_signup_user_created', $user, $edit_user );
			}
		}

		// Make sure an entry is added to the $wpdb->signups table
		if ( true === (bool) $is_multisite ) {
			wct_users_update_signups_table( $user );
		}

		// Set the default redirect.
		$redirect = add_query_arg( 'success', 2, $redirect );

		// If Autolog is on, Log the new user in and redirect him to the talk form.
		if ( wct_user_autolog_after_signup() ) {
			$loggedin_user = get_user_by( 'id', $user );

			if ( isset( $loggedin_user->user_login ) ) {
				$signon_data = array(
					'user_login'    => $loggedin_user->user_login,
					'user_password' => wp_generate_password( 12, false ),
				);

				// Reset Password without removing the activation key.
				wct_reset_password( $signon_data['user_password'], $loggedin_user->ID );

				// Log the user in
				$signed_in = wp_signon( $signon_data );

				// Redirect the loggedin user to the Submit form
				if ( ! is_wp_error( $signed_in ) ) {
					$redirect = add_query_arg( 'success', '5,6,7', wct_get_form_url() );
				}
			}
		}

		// Finally invite the user to check his email.
		wp_safe_redirect( $redirect );

		if ( $exit ) {
			exit();
		}
	}
}

/**
 * Get user fields.
 *
 * @since 1.0.0
 *
 * @param  string $type whether we're on a signup form or not
 */
function wct_user_get_fields( $type = 'signup' ) {
	$fields = wct_users_get_all_contact_methods();

	if ( 'signup' == $type ) {
		$signup = array_flip( wct_user_signup_fields() );

		$fields = array_merge(
			apply_filters( 'wct_user_get_signup_fields', array(
				'user_login' => __( 'Username',   'wordcamp-talks' ),
				'user_email' => __( 'E-mail',     'wordcamp-talks' ),
			) ),
			array_intersect_key( $fields, $signup )
		);
	}

	return apply_filters( 'wct_user_get_fields', $fields, $type );
}

/**
 * Redirect the loggedin user to its profile as already a member
 * Or redirect WP (non multisite) register form to signup form.
 *
 * @since 1.0.0
 *
 * @param  string $context the template context
 */
function wct_user_signup_redirect( $context = '' ) {
	// Bail if signup is not allowed
	if ( ! wct_is_signup_allowed_for_current_blog() ) {
		return;
	}

	if ( is_user_logged_in() && 'signup' == $context ) {
		wp_safe_redirect( wct_users_get_logged_in_profile_url() );
		exit();
	} else if ( ! empty( $_SERVER['SCRIPT_NAME'] ) && false !== strpos( $_SERVER['SCRIPT_NAME'], 'wp-login.php' ) && ! empty( $_REQUEST['action'] ) &&  'register' == $_REQUEST['action'] ) {
		wp_safe_redirect( wct_users_get_signup_url() );
		exit();
	} else {
		if ( 'signup' == $context )  {

			do_action( 'wct_user_signup_override' );
		}
		return;
	}
}

/**
 * Filter the user notification content to make sure the password
 * will be set on the Website he registered to.
 *
 * @since 1.0.0
 *
 * @param array  $mail_attr
 * @return array $mail_attr
 */
function wct_multisite_user_notification( $mail_attr = array() ) {
	if ( ! did_action( 'retrieve_password_key' ) ) {
		return $mail_attr;
	}

	$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

	if ( empty( $mail_attr['subject'] ) || sprintf( _x( '[%s] Your username and password info', 'Use the same translation as WP Core', 'wordcamp-talks' ), $blogname ) !== $mail_attr['subject'] ) {
		return $mail_attr;
	}

	if ( empty( $mail_attr['message'] ) ) {
		return $mail_attr;
	}

	preg_match( '/<(.+?)>/', $mail_attr['message'], $match );

	if ( ! empty( $match[1] ) ) {

		$login_url = wct_add_filter_network_site_url( $match[1], '', 'login', false );
		$mail_attr['message'] = str_replace( $match[1], $login_url, $mail_attr['message'] );
	}

	return $mail_attr;
}

/**
 * Dynamically add a filter to network_site_url in case the user
 * is setting his password from the site's login form where the
 * plugin is activated.
 *
 * @since 1.0.0
 */
function wct_user_setpassword_redirect() {
	if ( ! is_multisite() || ! wct_is_signup_allowed_for_current_blog() ) {
		return;
	}

	add_filter( 'network_site_url', 'wct_add_filter_network_site_url', 10, 3 );
}

/**
 * Temporarly filter network_site_url to use site_url instead.
 *
 * @since 1.0.0
 *
 * @param  string $url      Required. the network site url.
 * @param  string $path     Optional. Path relative to the site url.
 * @param  string $scheme   Optional. Scheme to give the site url context.
 * @param  bool   $redirect whether to include a redirect to query arg to the url or not.
 * @return string           Site url link.
 */
function wct_add_filter_network_site_url( $site_url, $path = '', $scheme = null, $redirect = true ) {
	if ( ! is_multisite() || ! wct_is_signup_allowed_for_current_blog() ) {
		return $site_url;
	}

	$current_site = get_current_site();
	$url = set_url_scheme( 'http://' . $current_site->domain . $current_site->path, $scheme );

	if ( false !== strpos( $site_url, $url ) ) {
		$blog_url = trailingslashit( site_url() );
		$site_url = str_replace( $url, $blog_url, $site_url );

		if ( true === $redirect ) {
			$site_url = esc_url( add_query_arg( 'wct_redirect_to', urlencode( $blog_url ), $site_url ) );
		}
	}

	return $site_url;
}

/**
 * Remove the filter on network_site_url
 *
 * @since 1.0.0
 */
function wct_remove_filter_network_site_url() {
	if ( ! is_multisite() || ! wct_is_signup_allowed_for_current_blog() ) {
		return;
	}

	remove_filter( 'network_site_url', 'wct_add_filter_network_site_url', 10, 3 );
}
add_action( 'resetpass_form', 'wct_remove_filter_network_site_url' );

/**
 * Add a filter 'login_url' to eventually set the 'redirect_to' query arg
 *
 * @since 1.0.0
 */
function wct_multisite_add_filter_login_url() {
	if ( ! is_multisite() || ! wct_is_signup_allowed_for_current_blog() ) {
		return;
	}

	add_filter( 'login_url', 'wct_multisite_filter_login_url', 1 );
}
add_action( 'validate_password_reset', 'wct_multisite_add_filter_login_url' );

/**
 * Filter to add a 'redirect_to' query arg to login_url
 *
 * @since 1.0.0
 */
function wct_multisite_filter_login_url( $login_url ) {
	if ( ! empty( $_GET['wct_redirect_to'] ) ) {
		$login_url = add_query_arg( 'redirect_to', $_GET['wct_redirect_to'], $login_url );
	}

	return $login_url;
}

/**
 * Set a role on the site of the network if needed.
 *
 * @since 1.0.0
 */
function wct_maybe_set_current_user_role() {
	if ( ! is_multisite() || is_super_admin() ) {
		return;
	}

	$current_user = wct()->current_user;

	if ( empty( $current_user->ID ) || ! empty( $current_user->roles ) || ! wct_get_user_default_role() ) {
		return;
	}

	$current_user->set_role( wct_users_get_default_role() );
}
add_action( 'wct_talks_before_talk_save', 'wct_maybe_set_current_user_role', 1 );

/**
 * Get the stat for the the requested type (number of talks, comments or rates)
 *
 * @since 1.0.0
 *
 * @param string $type    the type of stat to get (eg: 'profile', 'comments', 'rates')
 * @param int    $user_id the User ID to get the stat for
 */
function wct_users_get_stat_for( $type = '', $user_id = 0 ) {
	$count = 0;

	if ( empty( $type ) ) {
		return $count;
	}

	if ( empty( $user_id ) ) {
		$user_id = wct_users_displayed_user_id();
	}

	if ( empty( $user_id ) ) {
		return $$count;
	}

	if ( 'talks' === $type ) {
		$count = count_user_posts( $user_id, wct_get_post_type() );
	} elseif ( 'comments' === $type ) {
		$count = wct_comments_count_comments( $user_id );
	} elseif ( 'rates' === $type ) {
		$count = wct_count_user_rates( $user_id );
	}

	/**
	 * Filter the user stats by type (number of talks "profile", "comments" or "rates").
	 *
	 * @since 1.0.0
	 *
	 * @param  int    $count the stat for the requested type.
	 * @param  string $type "profile", "comments" or "rates".
	 * @param  int    $user_id The user ID.
	 */
	return (int) apply_filters( 'wct_users_get_stat_for', $count, $type, $user_id );
}

/**
 * Sanitize the user description for display
 *
 * @since  1.0.0
 *
 * @param  string $text The description content.
 * @return string       The sanitized content.
 */
function wct_users_sanitize_user_description( $text = '' ) {
	$allowed_html = wp_kses_allowed_html( 'user_description' );

	return wpautop( wp_kses( $text, $allowed_html ) );
}

/**
 * Sanitize public fields for display
 *
 * @since  1.0.0
 *
 * @param  string $value The field value.
 * @param  string $key   The field key.
 * @return string        The sanitized field value.
 */
function wct_users_sanitize_public_profile_field( $value = '', $key = '' ) {
	$filters = array(
		'wp_filter_kses',
		'make_clickable',
		'wp_rel_nofollow',
		'wptexturize',
		'convert_smilies',
		'convert_chars',
		'wp_unslash',
	);

	if ( 'user_description' === $key ) {
		$filters[0] = 'wct_users_sanitize_user_description';
	}

	foreach ( $filters as $filter ) {
		$value = call_user_func( $filter, $value );
	}

	return $value;
}

/**
 * Directly approve comments made by raters.
 *
 * @since  1.0.0
 *
 * @param  int|WP_Error $approved    1 if approved, 0 or spam if not, or an error object.
 * @param  array        $commentdata The list of comment's parameter.
 * @return int                       1 if approved, 0 or spam if not.
 */
function wct_users_raters_approved( $approved = 0, $commentdata = array() ) {
	/**
	 * Comment is already approved or there was an error:
	 * no need to carry on.
	 */
	if ( is_wp_error( $approved ) || 1 === (int) $approved ) {
		return $approved;
	}

	if ( ! empty( $commentdata['user_id'] ) && current_user_can( 'comment_talks' ) ) {
		$approved = 1;
	}

	return $approved;
}
