<?php
/**
 * WordCamp Talks Users tags.
 *
 * @package WordCamp Talks
 * @subpackage users/tags
 *
 * @since 1.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Outputs user's profile nav.
 *
 * @since 1.0.0
 */
function wct_users_the_user_nav() {
	echo wct_users_get_user_nav();
}

	/**
	 * Gets user's profile nav.
	 *
	 * @since 1.0.0
	 */
	function wct_users_get_user_nav() {
		// Get displayed user id.
		$user_id = wct_users_displayed_user_id();

		// If not set, we're not on a user's profile.
		if ( empty( $user_id ) ) {
			return;
		}

		// Get username.
		$username = wct_users_get_displayed_user_username();

		// Get nav items for the user displayed.
		$nav_items = wct_users_get_profile_nav_items( $user_id, $username );

		if ( empty( $nav_items ) ) {
			return;
		}

		$user_nav = '<ul class="user-nav">';

		foreach ( $nav_items as $nav_item ) {
			$class =  ! empty( $nav_item['current'] ) ? ' class="current"' : '';
			$user_nav .= '<li' . $class .'>';
			$user_nav .= '<a href="' . esc_url( $nav_item['url'] ) . '" title="' . esc_attr( $nav_item['title'] ) . '">' . esc_html( $nav_item['title'] ) . '</a>';
			$user_nav .= '</li>';
		}

		$user_nav .= '</ul>';

		/**
		 * Filter the user nav output.
		 *
		 * @since  1.0.0
		 *
		 * @param string $user_nav      User nav output.
		 * @param int    $user_id       the user ID.
		 * @param string $user_nicename the username.
		 */
		return apply_filters( 'wct_users_get_user_nav', $user_nav, $user_id, $username );
	}

/**
 * Outputs user's profile avatar
 *
 * @since 1.0.0
 */
function wct_users_the_user_profile_avatar() {
	echo wct_users_get_user_profile_avatar();
}

	/**
	 * Gets user's profile avatar.
	 *
	 * @since 1.0.0
	 */
	function wct_users_get_user_profile_avatar() {
		return get_avatar( wct_users_displayed_user_id(), '150' );
	}

/**
 * Outputs user's profile display name
 *
 * @since 1.0.0
 */
function wct_users_user_profile_display_name() {
	echo wct_users_get_user_profile_display_name();
}

	/**
	 * Gets user's profile display name
	 *
	 * @since 1.0.0
	 */
	function wct_users_get_user_profile_display_name() {
		return esc_html( wct_users_get_displayed_user_displayname() );
	}

/**
 * Append displayed user's rating in talks header when viewing his rates profile.
 *
 * @since 1.0.0
 *
 * @param integer $id      The talk ID
 * @param integer $user_id The user ID
 */
function wct_users_the_user_talk_rating( $id = 0, $user_id = 0 ) {
	if ( ! current_user_can( 'view_talk_rates' ) ) {
		return;
	}

	echo wct_users_get_user_talk_rating( $id, $user_id );
}

	/**
	 * Gets displayed user's rating for a given talk
	 *
	 * @since 1.0.0
	 *
	 * @param integer $id      the talk ID
	 * @param integer $user_id the user ID
	 */
	function wct_users_get_user_talk_rating( $id = 0, $user_id = 0 ) {
		if ( ! wct_is_user_profile_rates() ) {
			return;
		}

		if ( empty( $id ) ) {
			$query_loop = wct_get_global( 'query_loop' );

			if ( ! empty( $query_loop->talk->ID ) ) {
				$id = $query_loop->talk->ID;
			}
		}

		if ( empty( $user_id ) ) {
			$user_id = wct_users_displayed_user_id();
		}

		if ( empty( $user_id ) || empty( $id ) ) {
			return;
		}

		$user_rating = wct_count_ratings( $id, $user_id );

		if ( empty( $user_rating ) || is_array( $user_rating ) ) {
			return false;
		}

		$username = wct_users_get_displayed_user_username();

		$output = '<a class="user-rating-link" href="' . esc_url( wct_users_get_user_profile_url( $user_id, $username ) ) . '" title="' . esc_attr( $username ) . '">';
		$output .= get_avatar( $user_id, 20 ) . sprintf( _n( 'rated 1 star', 'rated %s stars', $user_rating, 'wordcamp-talks' ), $user_rating ) . '</a>';

		return $output;
	}

/**
 * Displays the signup fields.
 *
 * @since  1.0.0
 */
function wct_users_the_signup_fields() {
	echo wct_users_get_signup_fields();
}

	/**
	 * Gets the signup fields output.
	 *
	 * @since  1.0.0
	 *
	 * @return string HTML Output.
	 */
	function wct_users_get_signup_fields() {
		$output = '';

		/**
		 * Use this filter to add descriptions to your fields or remove these ones.
		 *
		 * @since  1.1.0
		 *
		 * @param array $value The fields description.
		 */
		$fields_description = apply_filters( 'wct_users_get_signup_fields_description', array(
			'user_login' => sprintf(
				__( 'A WordPress.org username is required, if you don\'t have one yet, you can %s.', 'wordcamp-talks' ),
				'<a href="https://login.wordpress.org/register">' . __( 'create one', 'wordcamp-talks' ) . '</a>'
			),
			'user_email' => sprintf(
				__( 'An email linked to your Gravatar profile is required. If you don\'t have one yet, you can %s.', 'wordcamp-talks' ),
				sprintf( '<a href="%1$s">%2$s</a>',
					/* Translators: Use the url that corresponds to your country. Eg: France is https://fr.gravatar.com/ */
					esc_url( __( 'https://en.gravatar.com/', 'wordcamp-talks' ) ),
					esc_html__( 'create one', 'wordcamp-talks' )
				)
			),
		) );

		if ( ! wct_talk_sync_wp_org_profile() ) {
			$fields_description['user_login'] = __( 'Make sure your username is at least 4 characters long.', 'wordcamp-talks' );
		}

		foreach ( (array) wct_user_get_fields() as $key => $label ) {
			// reset
			$sanitized = array(
				'key'   => sanitize_key( $key ),
				'label' => esc_html( $label ),
				'value' => '',
			);

			if ( ! empty( $_POST['wct_signup'][ $sanitized['key'] ] ) ) {
				$sanitized['value'] = apply_filters( "wct_users_get_signup_field_{$key}", $_POST['wct_signup'][ $sanitized['key'] ] );
			}

			$required = apply_filters( 'wct_users_is_signup_field_required', false, $key );
			$required_output = false;

			if ( ! empty( $required ) || in_array( $key, array( 'user_login', 'user_email' ), true ) ) {
				$required_output = '<span class="required">*</span>';
			}

			$output .= '<label for="_wct_signup_' . esc_attr( $sanitized['key'] ) . '">' . esc_html( $sanitized['label'] ) . ' ' . $required_output . '</label>';

			// Description is a text area.
			if ( 'description' === $sanitized['key'] ) {
				$output .= '<textarea id="_wct_signup_' . esc_attr( $sanitized['key'] ) . '" name="wct_signup[' . esc_attr( $sanitized['key'] ) . ']">' . esc_textarea( $sanitized['value'] ) . '</textarea>';

			// Language is a select box.
			} elseif ( 'locale' === $sanitized['key'] ) {
				$languages = get_available_languages();

				if ( empty( $languages ) ) {
					continue;
				}

				$output .=  wp_dropdown_languages( array(
					'name'                        => 'wct_signup[' . esc_attr( $sanitized['key'] ) . ']',
					'id'                          => '_wct_signup_' . esc_attr( $sanitized['key'] ),
					'selected'                    => get_locale(),
					'languages'                   => $languages,
					'show_available_translations' => false,
					'echo'                        => 0,
				) );

			// Default is text field.
			} else {
				$output .= '<input type="text" id="_wct_signup_' . esc_attr( $sanitized['key'] ) . '" name="wct_signup[' . esc_attr( $sanitized['key'] ) . ']" value="' . esc_attr( $sanitized['value'] ) . '"/>';
			}

			if ( isset( $fields_description[$key] ) ) {
				$output .= sprintf( '<p class="description">%s</p>', wp_kses( $fields_description[$key], array( 'a' => array( 'href' => true ) ) ) );
			}
		}

		return $output;
	}

/**
 * Displays the signup actions.
 *
 * @since  1.0.0
 */
function wct_users_the_signup_submit() {
	$wct = wct();

	wp_nonce_field( 'wct_signup' ); ?>

	<input type="reset" value="<?php esc_attr_e( 'Reset', 'wordcamp-talks' ) ;?>"/>
	<input type="submit" value="<?php esc_attr_e( 'Sign-up', 'wordcamp-talks' ) ;?>" name="wct_signup[signup]"/>
	<?php
}

/**
 * Outputs css classes for the Front-end profile.
 *
 * @since  1.1.0
 */
function wct_users_profile_classes() {
	$classes = array( 'user-infos' );

	if ( wct_users_can_edit_profile() ) {
		$classes[] = 'editable';
	}

	echo ' class="' . join( ' ', $classes ) . '"';
}

/**
 * Get the public profile fields for the user's info template on front-end
 *
 * @since  1.0.0
 *
 * @return array The list of field keys to display.
 */
function wct_users_public_profile_infos() {
	return wct_users_get_displayed_user_information( 'display' );
}

/**
 * Check if a field's key has a corresponding value for the user.
 *
 * @since  1.0.0
 *
 * @param  string $info The field key.
 * @return bool         True if the user has filled the field. False otherwise.
 */
function wct_users_public_profile_has_info( $info = '' ) {
	if ( empty( $info ) ) {
		return false;
	}

	return ! empty( wct()->displayed_user->{$info} );
}

/**
 * While Iterating fields, count the empty ones.
 *
 * @since  1.0.0
 */
function wct_users_public_empty_info() {
	$empty_info = (int) wct_get_global( 'empty_info' );

	wct_set_global( 'empty_info', $empty_info + 1 );
}

/**
 * Displays the field label.
 *
 * @since  1.0.0
 *
 * @param  string $info The field key.
 */
function wct_users_public_profile_label( $info = '' ) {
	if ( empty( $info ) ) {
		return;
	}

	$labels = wct_get_global( 'public_profile_labels' );

	if ( ! isset( $labels[ $info ] ) ) {
		return;
	}

	echo esc_html( apply_filters( 'wct_users_public_label', $labels[ $info ], $info ) );
}

/**
 * Displays the field value.
 *
 * @since  1.0.0
 *
 * @param  string $info The field key.
 */
function wct_users_public_profile_value( $info = '' ) {
	if ( empty( $info ) && ! wct_is_current_user_profile() ) {
		return;
	}

	if ( isset( wct_users_displayed_user()->data_to_edit ) ) {
		if ( 'user_description' === $info ) {

			// Use a WP Editor as the target is the Speaker post type.
			if ( wct_is_wordcamp_site() ) {
				add_filter( 'mce_buttons', 'wct_teeny_button_filter', 10, 1 );

				/**
				 * Apply sanitization to the description.
				 *
				 * @since  1.1.0
				 *
				 * @param string $value The description to edit.
				 */
				$content = apply_filters( 'wct_talks_get_editor_content', wct_users_displayed_user()->data_to_edit[$info] );

				wp_editor( $content, $info, array(
					'textarea_name' => $info,
					'wpautop'       => true,
					'media_buttons' => false,
					'editor_class'  => 'wc-talks-tinymce',
					'textarea_rows' => 8,
					'teeny'         => false,
					'dfw'           => false,
					'tinymce'       => true,
					'quicktags'     => false
				) );

				remove_filter( 'mce_buttons', 'wct_teeny_button_filter', 10, 1 );

			// Use a regular textarea as the target is the User's description.
			} else {
				printf( '<textarea name="%1$s">%2$s</textarea>', esc_attr( $info ), wct_users_displayed_user()->data_to_edit[$info] );
			}

			printf( '<p class="description">%s</p>', esc_html__( 'Your bio will be used to introduce yourself in case one of your Talk Proposals is selected (Required).', 'wordcamp-talks' ) );
		} else {
			printf( '<input type="text" name="%1$s" value="%2$s"/>', esc_attr( $info ), wct_users_displayed_user()->data_to_edit[$info] );
		}
	} else {
		/**
		 * Used to sanitize the output.
		 *
		 * @since  1.0.0
		 *
		 * @param  string $info The info to output.
		 */
		echo apply_filters( 'wct_users_public_value', wct()->displayed_user->{$info}, $info );
	}
}

/**
 * Displays the submit button of the front-end profile edit form.
 *
 * @since 1.1.0
 */
function wct_users_public_profile_submit() {
	wp_nonce_field( 'wct-edit-profile' );
	?>
	<input type="submit" name="wct_users_edit_profile" value="<?php esc_attr_e( 'Edit profile', 'wordcamp-talks' ); ?>"/>
	<?php
}

/**
 * Check if no fields were filled by the user.
 *
 * @since  1.0.0
 *
 * @return bool True if the user didn't filled any fields. False otherwise.
 */
function wct_users_public_empty_profile() {
	$empty_info = (int) wct_get_global( 'empty_info' );
	$labels     = wct_get_global( 'public_profile_labels' );

	if ( $empty_info && $empty_info === count( $labels ) ) {
		$feedback = array( 'info' => array( 3 ) );

		if ( wct_is_current_user_profile() ) {
			$feedback = array( 'info' => array( 4 ) );
		}

		wct_set_global( 'feedback', $feedback );

		return true;
	}

	return false;
}
