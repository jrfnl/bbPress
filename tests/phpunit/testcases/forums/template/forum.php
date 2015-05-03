<?php

/**
 * Tests for the `bbp_*_forum_*()` template functions.
 *
 * @group forums
 * @group template
 * @group forum
 */
class BBP_Tests_Forums_Template_Forum extends BBP_UnitTestCase {

	/**
	 * @covers ::bbp_forum_id
	 * @covers ::bbp_get_forum_id
	 */
	public function test_bbp_get_forum_id() {
		$f = $this->factory->forum->create();

		$forum_id = bbp_get_forum_id( $f );
		$this->assertSame( $f, $forum_id );
	}

	/**
	 * @covers ::bbp_get_forum
	 * @todo   Implement test_bbp_get_forum().
	 */
	public function test_bbp_get_forum() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers ::bbp_forum_permalink
	 * @covers ::bbp_get_forum_permalink
	 */
	public function test_bbp_get_forum_permalink() {

		if ( is_multisite() ) {
			$this->markTestSkipped( 'Skipping URL tests in multiste for now.' );
		}

		$f = $this->factory->forum->create();

		$forum_permalink = bbp_get_forum_permalink( $f );
		$this->expectOutputString( $forum_permalink );
		bbp_forum_permalink( $f );

		$forum = bbp_get_forum_permalink( $f );
		$this->assertSame( 'http://' . WP_TESTS_DOMAIN . '/?forum=forum-1', $forum );
	}

	/**
	 * @covers ::bbp_forum_title
	 * @covers ::bbp_get_forum_title
	 */
	public function test_bbp_get_forum_title() {
		$f = $this->factory->forum->create();

		$forum = bbp_get_forum_title( $f );
		$this->assertSame( 'Forum 1', $forum );
	}

	/**
	 * @covers ::bbp_forum_archive_title
	 * @covers ::bbp_get_forum_archive_title
	 * @todo   Implement test_bbp_forum_archive_title().
	 * @todo   Implement test_bbp_get_forum_archive_title().
	 */
	public function test_bbp_get_forum_archive_title() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers ::bbp_forum_content
	 * @covers ::bbp_get_forum_content
	 */
	public function test_bbp_get_forum_content() {
		$f = $this->factory->forum->create();

		$forum = bbp_get_forum_content( $f );
		$this->assertSame( 'Content of Forum 1', $forum );
	}

	/**
	 * @covers ::bbp_forum_freshness_link
	 * @covers ::bbp_get_forum_freshness_link
	 */
	public function test_bbp_get_forum_freshness_link() {

		if ( is_multisite() ) {
			$this->markTestSkipped( 'Skipping URL tests in multiste for now.' );
		}

		$f = $this->factory->forum->create();

		$fresh_link = bbp_get_forum_freshness_link( $f );
		$this->assertSame( 'No Topics', $fresh_link );

		$t = $this->factory->topic->create( array(
			'post_parent' => $f,
		) );

		bbp_update_forum( array(
			'forum_id' => $f,
		) );

		$fresh_link = bbp_get_forum_freshness_link( $f );
		$this->assertSame( '<a href="http://example.org/?topic=topic-1" title="Topic 1">right now</a>', $fresh_link );
	}

	/**
	 * @covers ::bbp_forum_parent_id
	 * @covers ::bbp_get_forum_parent_id
	 */
	public function test_bbp_get_forum_parent_id() {
		$f1 = $this->factory->forum->create();

		$forum_id = bbp_get_forum_parent_id( $f1 );
		$this->assertSame( 0, $forum_id );

		$f2 = $this->factory->forum->create( array(
			'post_parent' => $f1,
		) );

		$forum_id = bbp_get_forum_parent_id( $f2 );
		$this->assertSame( $f1, $forum_id );
	}

	/**
	 * @covers ::bbp_get_forum_ancestors
	 * @todo   Implement test_bbp_get_forum_ancestors().
	 */
	public function test_bbp_get_forum_ancestors() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers ::bbp_forum_get_subforums
	 * @todo   Implement test_bbp_forum_get_subforums().
	 */
	public function test_bbp_forum_get_subforums() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers ::bbp_list_forums
	 * @todo   Implement test_bbp_list_forums().
	 */
	public function test_bbp_list_forums() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers ::bbp_forum_subscription_link
	 * @covers ::bbp_get_forum_subscription_link
	 * @todo   Implement test_bbp_get_forum_subscription_link().
	 */
	public function test_bbp_get_forum_subscription_link() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers ::bbp_forum_topics_link
	 * @covers ::bbp_get_forum_topics_link
	 * @todo   Implement test_bbp_get_forum_topics_link().
	 */
	public function test_bbp_get_forum_topics_link() {
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers ::bbp_forum_class
	 * @covers ::bbp_get_forum_class
	 */
	public function test_bbp_get_forum_class() {

		$this->forum_id = $this->factory->forum->create();

		$found = bbp_get_forum_class( $this->forum_id );

		$this->assertContains( 'bbp-forum-status-open', $found );
		$this->assertNotContains( 'bbp-has-subforums', $found );
		$this->assertNotContains( 'status-category', $found );

		bbp_categorize_forum( $this->forum_id );
		$found = bbp_get_forum_class( $this->forum_id );
		$this->assertContains( 'status-category', $found );
		$this->assertNotContains( 'bbp-forum-visibility-private', $found );

		bbp_privatize_forum( $this->forum_id );
		$found = bbp_get_forum_class( $this->forum_id );
		$this->assertContains( 'bbp-forum-visibility-private', $found );
	}

	/**
	 * @covers ::bbp_single_forum_description
	 * @covers ::bbp_get_single_forum_description
	 */
	public function test_bbp_get_single_forum_description() {
		$f = $this->factory->forum->create();

		$forum = bbp_get_single_forum_description( $f );
		$this->assertSame( '<div class="bbp-template-notice info"><ul><li class="bbp-forum-description">This forum is empty.</li></ul></div>', $forum );
	}
}
