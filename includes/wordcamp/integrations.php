<?php
/**
 * WordCamp Integration functions.
 *
 * Functions to integrate with WordCamp Post types:
 * - session
 * - speaker
 *
 * @since 1.1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Signups are not allowed, WordCamp Sites are using the WordPress SSO.
 * As a result, new users need to register on https://login.wordpress.org.
 */
add_filter( 'wct_allow_signups', '__return_false' );

/**
 * Disable unneeded default options for WordCamp.org sites.
 *
 * @since  1.1.0
 *
 * @param  array  $options Default plugin options.
 * @return array           WordCamp.org site's plugin options.
 */
function wct_wordcamp_get_default_options( $options = array() ) {
	return array_diff_key( $options, array(
		'_wc_talks_private_fields_list' => false,
		'_wc_talks_public_fields_list'  => false,
		'_wc_talks_signup_fields'       => false,
		'_wc_talks_autolog_enabled'     => false,
		'_wc_talks_allow_signups'       => false,
		'_wc_talks_user_default_role'   => false,
	) );
}
add_filter( 'wct_get_default_options', 'wct_wordcamp_get_default_options' );

/**
 * Get a WordPress.org user by his/her user_nicename
 *
 * @since  1.1.0
 *
 * @param  string $slug The user_nicename.
 * @return WP_User      The User object found.
 */
function wct_wordcamp_get_user_by_slug( $slug = '' ) {
	if ( function_exists( 'wcorg_get_user_by_canonical_names' ) ) {
		return wcorg_get_user_by_canonical_names( $slug );
	}

	return get_user_by( 'slug', $slug );
}

/**
 * Gets a speaker post type for the WordPress.org user.
 *
 * @since  1.1.0
 *
 * @param  string $wporg_username The user_nicename.
 * @return WP_Post|False          The speaker post type. False if not found.
 */
function wct_wordcamp_get_speaker( $wporg_username = '' ) {
	if ( ! $wporg_username ) {
		return false;
	}

	$wporg_user = wct_wordcamp_get_user_by_slug( $wporg_username );

	if ( ! $wporg_user->ID ) {
		return false;
	}

	$speakers = new WP_Query;
	$speaker = $speakers->query( array(
		'post_status' => 'any',
		'post_type'   => 'wcb_speaker',
		'meta_query' => array( array(
			'key'     => '_wcpt_user_id',
			'value'   => $wporg_user->ID,
			'type'    => 'NUMERIC',
			'compare' => '='
		) ),
		'posts_per_page' => 1,
	) );

	return reset( $speaker );
}

/**
 * Override the Description's check on submitted talk proposal.
 *
 * @since  1.1.0
 *
 * @param  string  $description The user description
 * @param  WP_User $user        The user object
 * @return string               The speaker description if found, otherwise the user description.
 */
function wct_wordcamp_get_current_speaker_description( $description = '', $user ) {
	if ( ! isset( $user->user_nicename ) ) {
		return $description;
	}

	$speaker = wct_wordcamp_get_speaker( $user->user_nicename );

	if ( ! $speaker ) {
		return $description;
	}

	return $speaker->post_content;
}
add_filter( 'wct_users_get_current_user_description', 'wct_wordcamp_get_current_speaker_description', 10, 2 );

/**
 * Override the Display Name and the Description of the displayed user.
 *
 * @since  1.1.0
 *
 * @param  WP_User $user  The displayed user object passed by reference.
 * @param  array  $fields The information fields to check.
 */
function wct_wordcamp_set_displayed_speaker( &$user, $fields = array() ) {
	if ( ! isset( $user->user_nicename ) ) {
		return;
	}

	$speaker = wct_wordcamp_get_speaker( $user->user_nicename );

	if ( ! $speaker ) {
		return;
	}

	$map = array(
		'display_name'     => 'post_title',
		'user_description' => 'post_content',
	);

	$data_to_edit = array();

	foreach( $fields as $field ) {
		if ( ! isset( $map[$field] ) ) {
			continue;
		}

		if ( isset( $user->filter ) && 'edit' === $user->filter ) {
			if ( 'user_description' === $field ) {
				$data_to_edit[$field] = $speaker->{$map[$field]};
			} else {
				$data_to_edit[$field] = sanitize_user_field( $field, $speaker->{$map[$field]}, $user->ID, 'edit' );
			}
		} else {
			$user->{$field} = $speaker->{$map[$field]};
		}
	}

	if ( $data_to_edit ) {
		$user->data_to_edit = wp_parse_args( $data_to_edit, $user->data_to_edit );
	}
}
add_action( 'wct_users_set_displayed_user', 'wct_wordcamp_set_displayed_speaker', 10, 2 );

/**
 * Short-circuit the front-end profile edits.
 *
 * The real user's WordPress.org profile won't be edited here, it's
 * the Speaker's post type that will be edited.
 *
 * @since  1.1.0
 *
 * @param  array  $feedback The feedback list. Leaved empty it stops the short-circuit.
 * @param  Object $userdata The Userdata object containing the profile information to edit.
 * @return array            The feedback list to inform if the edits succeeded or failed.
 */
function wct_wordcamp_edit_displayed_speaker( $feedback = array(), $userdata = null ) {
	if ( empty( $userdata->ID ) ) {
		return $feedback;
	}

	// The description is required
	if ( empty( $userdata->description ) ) {
		return array( 'error' => 14 );
	}

	// Validate the speaker
	$user = get_user_by( 'id', $userdata->ID );

	if ( ! $user->ID ) {
		return array( 'error' => 12 );
	}

	unset( $userdata->ID );
	$postarr = wp_parse_args( (array) $userdata, array(
		'description'  => $user->description,
		'display_name' => $user->display_name,
	) );

	// Is it a profile update ?
	$speaker = wct_wordcamp_get_speaker( $user->user_nicename );

	if ( ! empty( $speaker->ID ) ) {
		$result = wp_update_post( array(
			'ID'           => $speaker->ID,
			'post_title'   => $postarr['display_name'],
			'post_content' => $postarr['description'],
		) );
	} else {
		$result = wp_insert_post( array(
			'post_type'    => 'wcb_speaker',
			'post_status'  => 'pending',
			'post_title'   => $postarr['display_name'],
			'post_content' => $postarr['description'],
			'meta_input'   => array(
				'_wcpt_user_id'      => $user->ID,
				'_wcb_speaker_email' => $user->user_email,
			)
		) );
	}

	if ( is_wp_error( $result ) ) {
		return array( 'error' => 1 );
	}

	return array( 'success' => 8 );
}
add_filter( 'wct_users_pre_edit_profile', 'wct_wordcamp_edit_displayed_speaker', 10, 2 );

/**
 * Get a session for a given Talk Proposal.
 *
 * @since  1.1.0
 *
 * @param  integer $talk_id The ID of the Talk Proposal.
 * @return WP_Post          The linked session if found. False otherwise.
 */
function wct_wordcamp_get_session( $talk_id = 0 ) {
	if ( empty( $talk_id ) ) {
		return false;
	}

	$sessions = new WP_Query;
	$session = $sessions->query( array(
		'post_status' => 'any',
		'post_type'   => 'wcb_session',
		'meta_query' => array( array(
			'key'     => '_wct_proposal_id',
			'value'   => $talk_id,
			'type'    => 'NUMERIC',
			'compare' => '='
		) ),
		'posts_per_page' => 1,
	) );

	return reset( $session );
}

/**
 * Init a session out of a Talk Proposal
 *
 * @since  1.1.0
 *
 * @param  array  $args {
 *   An array of arguments.
 *
 *   @type string  $post_title   Required. The Session's title.
 *   @type string  $post_content Required. The Session's content.
 *   @type integer $post_author  Required. The ID of the Talk Proposal's author.
 *   @type string  $post_status  Optional. The post status of the Session. Defaults to 'draft'.
 *   @type array   $meta_input   An array of Post meta. The _wct_proposal_id key must be set.
 * }
 * @return integer|WP_Error The ID of the generated session. A WP Error otherwise.
 */
function wct_wordcamp_init_session( $args = array() ) {
	$r = wp_parse_args( $args, array(
		'post_title'   => '',
		'post_content' => '',
		'post_author'  => 0,
		'post_status'  => 'draft',
		'meta_input'   => array(),
	) );

	if ( ! $r['post_title'] || ! $r['post_content'] || ! $r['post_author'] || empty( $r['meta_input']['_wct_proposal_id'] ) ) {
		return new WP_Error( 'missing_parameter', __( 'The title, content, author or Talk Proposal ID were not provided.', 'wordcamp-talks' ) );
	}

	// Validate the proposal
	$proposal = get_post( $r['meta_input']['_wct_proposal_id'] );

	if ( 'wct_selected' !== get_post_status( $proposal ) || wct_get_post_type() !== get_post_type( $proposal ) ) {
		return new WP_Error( 'invalid_proposal', __( 'The Talk Proposal is not selected or unknown.', 'wordcamp-talks' ) );
	}

	if ( wct_wordcamp_get_session( $proposal->ID ) ) {
		return new WP_Error( 'session_exists', __( 'There can be only one session for a given Talk Proposal', 'wordcamp-talks' ) );
	}

	// Validate the user.
	$user = get_user_by( 'id', $r['post_author'] );

	if ( empty( $user->user_nicename ) ) {
		return new WP_Error( 'missing_user', __( 'User unknown.', 'wordcamp-talks' ) );
	}

	// Validate the speaker.
	$speaker = wct_wordcamp_get_speaker( $user->user_nicename );

	if ( ! $speaker || empty( $speaker->ID ) ) {
		return new WP_Error( 'missing_speaker', __( 'Speaker unknown.', 'wordcamp-talks' ) );
	}

	// Make sure the speaker is published.
	if ( 'publish' !== get_post_status( $speaker ) ) {
		wp_update_post( array(
			'ID'          => $speaker->ID,
			'post_status' => 'publish',
		) );
	}

	$r['meta_input'] = array_merge( $r['meta_input'], array(
		'_wcpt_speaker_id'      => $speaker->ID,
		'_wcb_session_speakers' => rtrim( $speaker->post_title, ',' ) . ',',
	) );

	return wp_insert_post( $r );
}
