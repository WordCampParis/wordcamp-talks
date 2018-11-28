<?php
/**
 *  WordCamp Talks Controller Class.
 *
 * @package WordCamp Talks\core\classes
 *
 * @since 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WP_REST_Posts_Controller' ) ) :

/**
 * The Custom Rest controller for Ideas.
 *
 * @since  2.0.0
 */
class WordCamp_Talks_Talks_REST_Controller extends WP_REST_Posts_Controller {
	/**
	 * Prepares a single post output for response.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_Post         $post    Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $post, $request ) {
		$response = parent::prepare_item_for_response( $post, $request );

		$talk = $response->get_data();
		$talk['talk_status'] = $talk['status'];
		$talk['status'] = 'private';
		$response->set_data( $talk );

		return $response;
	}
}

endif;
