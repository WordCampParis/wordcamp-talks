<?php

/**
 * @group wordcamp
 */
class WordCampTalkProposalsTest_WordCamp_Integrations extends WordCampTalkProposalsTest {

	public function test_wct_register_post_type() {
		$expected              = array( 'wcb_speaker', 'wcb_session', 'talks' );
		$post_types_registered = array_intersect( get_post_types(), $expected );

		$this->assertEquals( $expected, array_values( $post_types_registered ) );
	}

	public function test_wct_wordcamp_get_default_options() {
		$default_options = wct_get_default_options();
		$this->assertTrue( ! isset( $default_options['_wc_talks_signup_fields'] ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_wct_is_signup_allowed_for_current_blog() {
		add_filter( 'wct_allow_signups', '__return_true', 9 );
		add_filter( 'pre_site_option_registration', array( $this, 'return_user' ), 9 );

		$this->assertFalse( wct_is_signup_allowed_for_current_blog() );

		remove_filter( 'pre_site_option_registration', array( $this, 'return_user' ), 9 );
		remove_filter( 'wct_allow_signups', '__return_true', 9 );
	}

	public function return_user() {
		return 'user';
	}

	public function test_wct_wordcamp_get_speaker() {
		$u = $this->factory->user->create_and_get( array(
			'user_login'   => 'speaker',
			'display_name' => 'WordCamp Speaker',
			'user_email'   => 'speaker@wordcamp.org',
			'description'  => 'I am a speaker',
		) );

		$s = $this->factory->post->create( array(
			'post_type'    => 'wcb_speaker',
			'post_status'  => 'pending',
			'post_title'   => $u->display_name,
			'post_content' => $u->user_description,
			'meta_input'   => array(
				'_wcpt_user_id'      => $u->ID,
				'_wcb_speaker_email' => $u->user_email,
			)
		) );

		$wct_speaker = wct_wordcamp_get_speaker( 'speaker' );

		$this->assertEquals( $u->user_description, $wct_speaker->post_content );
	}

	public function test_wct_wordcamp_get_speaker_no_result() {
		$u = $this->factory->user->create_and_get( array(
			'user_login'   => 'oouch',
		) );

		$wct_speaker = wct_wordcamp_get_speaker( 'oouch' );

		$this->assertFalse( $wct_speaker );
	}

	public function test_wct_wordcamp_get_current_speaker_description() {
		$u = $this->factory->user->create_and_get( array(
			'user_login'   => 'speaker',
			'display_name' => 'WordCamp Speaker',
			'user_email'   => 'speaker@wordcamp.org',
			'description'  => 'I am a speaker',
		) );

		$s = $this->factory->post->create( array(
			'post_type'    => 'wcb_speaker',
			'post_status'  => 'pending',
			'post_title'   => $u->display_name,
			'post_content' => '',
			'meta_input'   => array(
				'_wcpt_user_id'      => $u->ID,
				'_wcb_speaker_email' => $u->user_email,
			)
		) );

		$current_user_id = get_current_user_id();

		wp_set_current_user( $u->ID );

		$description = wct_users_get_current_user_description();

		$this->assertEmpty( $description );

		wp_set_current_user( $current_user_id );

		$this->factory->post->update_object( $s, array(
			'post_content' => 'Speaker Bio',
		) );

		wp_set_current_user( $u->ID );

		$description = wct_users_get_current_user_description();
		$this->assertEquals( 'Speaker Bio', $description );

		wp_set_current_user( $current_user_id );
	}

	public function test_wct_wordcamp_set_displayed_speaker_self() {
		$u = $this->factory->user->create_and_get( array(
			'user_login'   => 'tazbar',
			'display_name' => 'Taz Bar',
			'user_email'   => 'taz@bar.org',
		) );

		$s = $this->factory->post->create( array(
			'post_type'    => 'wcb_speaker',
			'post_status'  => 'pending',
			'post_title'   => $u->display_name,
			'post_content' => 'I am a bartaz',
			'meta_input'   => array(
				'_wcpt_user_id'      => $u->ID,
				'_wcb_speaker_email' => $u->user_email,
			),
		) );

		$current_user_id = get_current_user_id();
		wp_set_current_user( $u->ID );

		$this->go_to( wct_users_get_logged_in_profile_url() );

		// Init the profile fields loop.
		wct_users_public_profile_infos();

		$displayed = wct_users_displayed_user();

		$this->assertTrue( 'I am a bartaz' === $displayed->data_to_edit['user_description'] );
		$this->assertTrue( 'Taz Bar' === $displayed->data_to_edit['display_name'] );

		wp_set_current_user( $current_user_id );
	}

	public function test_wct_users_public_profile_infos_other() {
		$u = $this->factory->user->create_and_get( array(
			'user_login'   => 'barfoo',
		) );

		$s = $this->factory->post->create( array(
			'post_type'    => 'wcb_speaker',
			'post_status'  => 'pending',
			'post_title'   => 'Bar Foo',
			'post_content' => 'I am a barfoo',
			'meta_input'   => array(
				'_wcpt_user_id'      => $u->ID,
				'_wcb_speaker_email' => $u->user_email,
			),
		) );

		set_current_screen( 'dashboard' );
		$this->do_admin_init();

		$r = $this->factory->user->create( array(
			'role' => 'rater',
		) );

		set_current_screen( 'front' );
		$current_user_id = get_current_user_id();

		wp_set_current_user( $r );

		$this->go_to( wct_users_get_user_profile_url( $u->ID ) );

		// Init the profile fields loop.
		wct_users_public_profile_infos();

		$displayed = wct_users_displayed_user();

		$this->assertTrue( 'I am a barfoo' === $displayed->user_description );
		$this->assertTrue( 'Bar Foo' === $displayed->display_name );

		wp_set_current_user( $current_user_id );
	}

	public function test_wct_wordcamp_init_session_invalid_proposal() {
		$u = $this->factory->user->create( array(
			'user_login'   => 'barfoo',
		) );

		$p = $this->factory->post->create_and_get( array( 'post_status' => 'draft', 'post_author' => $u ) );

		$result = wct_wordcamp_init_session( array(
			'post_title'   => $p->post_title,
			'post_content' => $p->post_content,
			'post_author'  => $u,
			'meta_input'   => array( '_wct_proposal_id' => $p->ID ),
		) );

		$this->assertTrue( 'invalid_proposal' === $result->get_error_code() );
	}

	public function test_wct_wordcamp_init_session_session_exists() {
		$u = $this->factory->user->create( array(
			'user_login'   => 'foobar',
		) );

		$tp = $this->factory->post->create_and_get( array(
			'post_type'   => wct_get_post_type(),
			'post_status' => 'wct_selected',
			'post_author' => $u,
		) );

		$s = $this->factory->post->create( array(
			'post_type'    => 'wcb_session',
			'post_status'  => 'draft',
			'post_title'   => $tp->post_title,
			'post_content' => $tp->post_content,
			'meta_input'   => array(
				'_wct_proposal_id' => $tp->ID,
			),
		) );

		$result = wct_wordcamp_init_session( array(
			'post_title'   => $tp->post_title,
			'post_content' => $tp->post_content,
			'post_author'  => $u,
			'meta_input'   => array( '_wct_proposal_id' => $tp->ID ),
		) );

		$this->assertTrue( 'session_exists' === $result->get_error_code() );
	}

	public function test_wct_wordcamp_init_session_missing_speaker() {
		$u = $this->factory->user->create( array(
			'user_login'   => 'tazball',
		) );

		$tp = $this->factory->post->create_and_get( array(
			'post_type'   => wct_get_post_type(),
			'post_status' => 'wct_selected',
			'post_author' => $u,
		) );

		$result = wct_wordcamp_init_session( array(
			'post_title'   => $tp->post_title,
			'post_content' => $tp->post_content,
			'post_author'  => $u,
			'meta_input'   => array( '_wct_proposal_id' => $tp->ID ),
		) );

		$this->assertTrue( 'missing_speaker' === $result->get_error_code() );
	}

	public function test_wct_wordcamp_init_session_success() {
		$u = $this->factory->user->create_and_get( array(
			'user_login'   => 'balltaz',
			'display_name' => 'Ball Taz',
			'description'  => 'I am Ball Taz!',
		) );

		$s = $this->factory->post->create( array(
			'post_type'    => 'wcb_speaker',
			'post_status'  => 'pending',
			'post_title'   => $u->display_name,
			'post_content' => $u->description,
			'meta_input'   => array(
				'_wcpt_user_id'      => $u->ID,
				'_wcb_speaker_email' => $u->user_email,
			),
		) );

		$tp = $this->factory->post->create_and_get( array(
			'post_type'   => wct_get_post_type(),
			'post_status' => 'wct_selected',
			'post_author' => $u->ID,
		) );

		$result = wct_wordcamp_init_session( array(
			'post_title'   => $tp->post_title,
			'post_content' => $tp->post_content,
			'post_author'  => $u->ID,
			'meta_input'   => array( '_wct_proposal_id' => $tp->ID ),
		) );

		$this->assertTrue( (int) $tp->ID === (int) get_post_meta( $result, '_wct_proposal_id', true ) );
		$this->assertTrue( (int) $s === (int) get_post_meta( $result, '_wcpt_speaker_id', true ) );
		$this->assertTrue( $u->display_name . ',' === get_post_meta( $result, '_wcb_session_speakers', true ) );

		$this->assertTrue( 'publish' === get_post_status( $s ) );
	}
}
