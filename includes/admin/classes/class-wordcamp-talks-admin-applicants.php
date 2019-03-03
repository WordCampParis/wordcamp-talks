<?php
/**
 * WordCamp Talks Applicants Administration.
 *
 * @package WordCamp Talks
 * @subpackage admin/classes
 *
 * @since 1.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * List table class for Applicant admin page.
 *
 * @since 1.3.0
 */
class WordCamp_Talks_Admin_Applicants extends WP_Users_List_Table {
	/**
	 * Constructor.
	 *
	 * @since 1.3.0
	 */
	public function __construct() {
		// Define singular and plural labels, as well as whether we support AJAX.
		parent::__construct( array(
			'ajax'     => false,
			'plural'   => 'applicants',
			'singular' => 'applicant',
			'screen'   => get_current_screen()->id,
		) );
	}

	/**
	 * Set up applicants for display in the list table.
	 *
	 * @since 1.3.0
	 */
	public function prepare_items() {
		global $usersearch;

		$usersearch          = isset( $_REQUEST['s'] ) ? $_REQUEST['s'] : '';
		$applicants_per_page = $this->get_items_per_page( str_replace( '-', '_', "{$this->screen->id}_per_page" ) );
		$paged               = $this->get_pagenum();

		$args = array(
			'offset'     => ( $paged - 1 ) * $applicants_per_page,
			'number'     => $applicants_per_page,
            'usersearch' => $usersearch,
            'role'       => array( 'subscriber' ),
			'fields'     => 'all_with_meta',
		);

		if ( isset( $_REQUEST['orderby'] ) ) {
			$args['orderby'] = $_REQUEST['orderby'];
		}

		if ( isset( $_REQUEST['order'] ) ) {
			$args['order'] = $_REQUEST['order'];
        }

		// Query the user IDs for this page
		$applicants_search = new WP_User_Query( $args );

		$this->items = $applicants_search->get_results();

		$this->set_pagination_args(
			array(
				'total_items' => $applicants_search->get_total(),
				'per_page'    => $applicants_per_page,
			)
		);
	}

	/**
	 * Display the applicants screen views
	 *
	 * @since 1.3.0
	 */
	public function views() {
        return array();
	}

	/**
	 * Get rid of the extra nav.
	 *
	 * @since 1.3.0
	 *
	 * @param array $which Current table nav item.
	 */
	public function extra_tablenav( $which ) {
		return;
	}

	/**
	 * Applicants columns.
	 *
	 * @since 1.3.0
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'username'   => __( 'Username',       'wordcamp-talks' ),
			'name'       => __( 'Name',           'wordcamp-talks' ),
			'email'      => __( 'Email',          'wordcamp-talks' ),
			'talks'      => __( 'Talk proposals', 'wordcamp-talks' ),
		);
	}

	/**
	 * Specific bulk actions for applicants.
	 *
	 * @since 1.3.0
	 */
	public function get_bulk_actions() {
        return array();
	}

	/**
	 * The text shown when no applicants are found.
	 *
	 * @since 1.3.0
	 */
	public function no_items() {
        esc_html_e( 'No applicants found.', 'wordcamp-talks' );
	}

	/**
	 * The columns signups can be reordered with.
	 *
	 * @since 1.3.0
	 */
	public function get_sortable_columns() {
		return array(
			'username'   => 'login',
			'email'      => 'email',
		);
	}

	/**
	 * Display applicants rows.
	 *
	 * @since 1.3.0
	 */
	public function display_rows() {
        $style       = '';
        $talks_count = _wct_count_many_users_talks( array_keys( $this->items ) );

		foreach ( $this->items as $userid => $applicants ) {
			$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
			echo "\n\t" . $this->single_row( $applicants, $style, '', $talks_count );
		}
	}

	/**
	 * Display an applicant row.
	 *
	 * @since 1.3.0
	 *
	 * @see WP_List_Table::single_row() for explanation of params.
	 *
	 * @param object|null $applicants    Applicant object.
	 * @param string      $style         Styles for the row.
	 * @param string      $role          Role to be assigned to user.
	 * @param int         $talks_count   Numper of Talk proposals.
	 * @return void
	 */
	public function single_row( $applicants = null, $style = '', $role = '', $talks_count = array() ) {
        // Attach the number of Talks to the user.
        if ( is_array( $talks_count ) && isset( $talks_count[ $applicants->ID ] ) ) {
            $applicants->number_of_talks = (int) $talks_count[ $applicants->ID ];
        }

		echo '<tr' . $style . ' id="applicant-' . esc_attr( $applicants->ID ) . '">';
		echo $this->single_row_columns( $applicants );
		echo '</tr>';
	}

	/**
	 * Markup for the checkbox used to select applicants for bulk actions.
	 *
	 * @since 1.3.0
	 *
	 * @param object|null $applicants Applicant object.
	 */
	public function column_cb( $applicants = null ) {
	?>
		<label class="screen-reader-text" for="applicant_<?php echo intval( $applicants->ID ); ?>"><?php
			/* translators: accessibility text */
			printf( esc_html__( 'Select applicant: %s', 'wordcamp-talks' ), $applicants->user_login );
		?></label>
		<input type="checkbox" id="applicant_<?php echo intval( $applicants->ID ) ?>" name="applicants[]" value="<?php echo esc_attr( $applicants->ID ) ?>" />
		<?php
	}

	/**
	 * The row actions for the applicant.
	 *
	 * @since 1.3.0
	 *
	 * @param object|null $applicants Applicant object.
	 */
	public function column_username( $applicants = null ) {
		$avatar	= get_avatar( $applicants->user_email, 32 );

		echo $avatar . sprintf( '<strong>%s</strong><br/>', $applicants->user_login );

		$actions = array();

		echo $this->row_actions( $actions );
	}

	/**
	 * Display user name, if any.
	 *
	 * @since 1.3.0
	 *
	 * @param object|null $applicants Applicant object.
	 */
	public function column_name( $applicants = null ) {
		echo esc_html( $applicants->display_name );
	}

	/**
	 * Display user email.
	 *
	 * @since 1.3.0
	 *
	 * @param object|null $applicants Applicant object.
	 */
	public function column_email( $applicants = null ) {
		printf( '<a href="mailto:%1$s">%2$s</a>', esc_attr( $applicants->user_email ), esc_html( $applicants->user_email ) );
	}

	/**
	 * Display registration date.
	 *
	 * @since 1.3.0
	 *
	 * @param object|null $applicants Applicant object.
	 */
	public function column_talks( $applicants = null ) {
        $talks_count = 0;

        if ( isset( $applicants->number_of_talks ) && $applicants->number_of_talks ) {
            $applicant_talks_url = add_query_arg(
                array(
                    'post_type' => wct_get_post_type(),
                    'author' => $applicants->ID
                ),
                admin_url( 'edit.php' )
            );

            $talks_count = sprintf( '<a href="%1$s" class="edit">
                <span aria-hidden="true">%2$d</span>
                <span class="screen-reader-text">%3$s</span>
                </a>',
                esc_url( $applicant_talks_url ),
                $applicants->number_of_talks,
                sprintf( _n( '%s talk proposal by this applicant', '%s talk proposals by this applicant', $applicants->number_of_talks, 'wordcamp-talks' ),
                    number_format_i18n( $applicants->number_of_talks )
                )
            );
        }

		echo $talks_count;
	}
}
