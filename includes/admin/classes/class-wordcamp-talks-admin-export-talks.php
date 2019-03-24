<?php
/**
 * WordCamp Talks Export table.
 *
 * @package WordCamp Talks
 * @subpackage admin/classes
 *
 * @since 1.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * List table class used to export talks.
 *
 * @since 1.3.0
 */
class WordCamp_Talks_Admin_Export_Talks extends WP_Posts_List_Table {
    /**
	 * Handles the title column output.
	 *
	 * @since 1.3.0
	 *
	 * @param WP_Post $post The current WP_Post object.
	 */
    public function column_title( $post ) {
        echo esc_html( $post->post_title );
    }

    /**
	 * Handles the comments column output.
	 *
	 * @since 1.3.0
	 *
	 * @param WP_Post $post The current WP_Post object.
	 */
    public function column_comments( $post ) {
        $approved_comments = get_comments_number( $post );
		echo number_format_i18n( $approved_comments );
    }
}
