<?php

namespace Cedaro\CPTArchives\Tests;

class PluginTest extends \WP_UnitTestCase {
	protected $plugin;

	public function setUp() {
		global $cptarchives, $wp_rewrite;
		parent::setUp();

		$this->plugin = $cptarchives;

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		$wp_rewrite->flush_rules();
	}

	public function tearDown() {
		global $wp_rewrite;
		_unregister_post_type( 'book' );
		$this->plugin->unregister_archive( 'book' );
		$wp_rewrite->init();
		$wp_rewrite->extra_rules_top = $wp_rewrite->extra_permastructs = array();
		parent::tearDown();
	}

	public function test_register_archive() {
		$this->plugin->register_archive( 'book' );
		register_post_type( 'book' );
		$post_id = $this->plugin->get_archive_id( 'book' );

		// Ensure a post was inserted.
		$this->assertTrue( is_int( $post_id ) );

		$this->assertFalse( get_permalink( $post_id ) );
		$this->assertFalse( get_post_type_archive_link( 'book' ) );
	}

	public function test_register_archive_permalinks_disabled() {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '' );

		$this->plugin->register_archive( 'book' );
		register_post_type( 'book', array( 'has_archive' => true ) );
		$post_id = $this->plugin->get_archive_id( 'book' );

		// Ensure a post was inserted.
		$this->assertTrue( is_int( $post_id ) );

		$expected = home_url( '/?post_type=book' );
		$this->assertEquals( $expected, get_permalink( $post_id ) );
		$this->assertEquals( $expected, get_post_type_archive_link( 'book' ) );
	}

	public function test_register_archive_for_unknown_post_type() {
		$this->plugin->register_archive( 'movie' );
		$post_id = $this->plugin->get_archive_id( 'movie' );

		// Ensure a post wasn't inserted.
		$this->assertEquals( 0, $post_id );

		_unregister_post_type( 'movie' );
		$this->plugin->unregister_archive( 'movie' );
	}

	public function test_post_type_supports_archive_feature() {
		register_post_type( 'book', array( 'has_archive' => true, 'supports' => array( 'archive' ) ) );
		$post_id = $this->plugin->get_archive_id( 'book' );

		// Ensure a post was inserted.
		$this->assertTrue( is_int( $post_id ) );

		$expected = home_url( '/book/' );
		$this->assertEquals( $expected, get_permalink( $post_id ) );
		$this->assertEquals( $expected, get_post_type_archive_link( 'book' ) );
	}

	public function test_post_type_rewrite_false() {
		$this->plugin->register_archive( 'book' );
		register_post_type( 'book', array( 'has_archive' => true, 'rewrite' => false ) );

		$post_id = $this->plugin->get_archive_id( 'book' );

		$expected = home_url( '/?post_type=book' );
		$this->assertEquals( $expected, get_permalink( $post_id ) );
		$this->assertEquals( $expected, get_post_type_archive_link( 'book' ) );
	}

	public function test_post_type_has_archive_string() {
		$this->plugin->register_archive( 'book' );
		register_post_type( 'book', array( 'has_archive' => 'novel' ) );
		$post_id = $this->plugin->get_archive_id( 'book' );

		$expected = home_url( '/novel/' );
		$this->assertEquals( $expected, get_permalink( $post_id ) );
		$this->assertEquals( $expected, get_post_type_archive_link( 'book' ) );
	}

	public function test_post_type_rewrite_slug() {
		$this->plugin->register_archive( 'book' );
		register_post_type( 'book', array( 'has_archive' => true, 'rewrite' => array( 'slug' => 'novel' ) ) );

		$post_id = $this->plugin->get_archive_id( 'book' );

		$expected = home_url( '/novel/' );
		$this->assertEquals( $expected, get_permalink( $post_id ) );
		$this->assertEquals( $expected, get_post_type_archive_link( 'book' ) );
	}

	public function test_post_type_different_archive_rewrite_slugs() {
		$this->plugin->register_archive( 'book' );
		register_post_type( 'book', array( 'has_archive' => 'stories', 'rewrite' => array( 'slug' => 'novel' ) ) );

		$post_id = $this->plugin->get_archive_id( 'book' );

		$expected = home_url( '/stories/' );
		$this->assertEquals( $expected, get_permalink( $post_id ) );
		$this->assertEquals( $expected, get_post_type_archive_link( 'book' ) );
	}

	public function test_post_type_rewrite_with_front() {
		global $wp_rewrite;

		$wp_rewrite->set_permalink_structure( '/blog/%postname%/' );
		$wp_rewrite->flush_rules();

		$this->plugin->register_archive( 'book' );
		register_post_type( 'book', array( 'has_archive' => 'novel', 'rewrite' => array( 'with_front' => true ) ) );

		$post_id = $this->plugin->get_archive_id( 'book' );

		$expected = home_url( '/blog/novel/' );
		$this->assertEquals( $expected, get_permalink( $post_id ) );
		$this->assertEquals( $expected, get_post_type_archive_link( 'book' ) );
	}

	public function test_update_rewrite_slug() {
		global $wp_rewrite;

		$archive = $this->plugin->register_archive( 'book' );
		register_post_type( 'book', array( 'has_archive' => true ) );
		$post_id = $this->plugin->get_archive_id( 'book' );
		wp_update_post( array( 'ID' => $post_id, 'post_name' => 'novels' ) );

		// Ensure the option was updated.
		$this->assertEquals( 'novels', get_option( 'book' . '_rewrite_slug' ) );

		// Ensure the post type structs are updated.
		$archive->update_permastruct();
		$this->assertContains( 'novels', $wp_rewrite->get_extra_permastruct( 'book' ) );
	}

	public function test_update_rewrite_slug_permalinks() {
		$this->plugin->set_rewrite_slug( 'book', 'novels' );
		$archive = $this->plugin->register_archive( 'book' );
		register_post_type( 'book', array( 'has_archive' => true ) );
		$post_id = $this->plugin->get_archive_id( 'book' );

		$expected = home_url( '/novels/' );
		$this->assertEquals( $expected, get_permalink( $post_id ) );
		$this->assertEquals( $expected, get_post_type_archive_link( 'book' ) );

		$post_id = $this->factory->post->create( array( 'post_type' => 'book', 'post_name' => 'Moby-Dick' ) );
		$this->assertEquals( home_url( '/novels/moby-dick/' ), get_permalink( $post_id ) );
	}

	public function test_update_rewrite_slug_has_archive_string() {
		$this->plugin->set_rewrite_slug( 'book', 'novels' );
		$archive = $this->plugin->register_archive( 'book' );
		register_post_type( 'book', array( 'has_archive' => 'books' ) );
		$post_id = $this->plugin->get_archive_id( 'book' );

		$expected = home_url( '/novels/' );
		$this->assertEquals( $expected, get_permalink( $post_id ) );
		$this->assertEquals( $expected, get_post_type_archive_link( 'book' ) );

		$post_id = $this->factory->post->create( array( 'post_type' => 'book', 'post_name' => 'Moby-Dick' ) );
		$this->assertEquals( home_url( '/novels/moby-dick/' ), get_permalink( $post_id ) );
	}

	public function test_update_rewrite_slug_customize_rewrites_false() {
		$this->plugin->set_rewrite_slug( 'book', 'novels' );
		$archive = $this->plugin->register_archive( 'book', array( 'customize_rewrites' => false ) );
		register_post_type( 'book', array( 'has_archive' => true ) );
		$post_id = $this->plugin->get_archive_id( 'book' );

		$expected = home_url( '/book/' );
		$this->assertEquals( $expected, get_permalink( $post_id ) );
		$this->assertEquals( $expected, get_post_type_archive_link( 'book' ) );

		$post_id = $this->factory->post->create( array( 'post_type' => 'book', 'post_name' => 'Moby-Dick' ) );
		$this->assertEquals( home_url( '/book/moby-dick/' ), get_permalink( $post_id ) );
	}

	public function test_update_rewrite_slug_customize_rewrites_archives_only() {
		$this->plugin->set_rewrite_slug( 'book', 'novels' );
		$this->plugin->register_archive( 'book', array( 'customize_rewrites' => 'archives' ) );
		register_post_type( 'book', array( 'has_archive' => true ) );
		$post_id = $this->plugin->get_archive_id( 'book' );

		$expected = home_url( '/novels/' );
		$this->assertEquals( $expected, get_permalink( $post_id ) );
		$this->assertEquals( $expected, get_post_type_archive_link( 'book' ) );

		$post_id = $this->factory->post->create( array( 'post_type' => 'book', 'post_name' => 'Moby-Dick' ) );
		$this->assertEquals( home_url( '/book/moby-dick/' ), get_permalink( $post_id ) );
	}

	public function test_update_rewrite_slug_customize_rewrites_posts_only() {
		$this->plugin->set_rewrite_slug( 'book', 'novels' );
		$this->plugin->register_archive( 'book', array( 'customize_rewrites' => 'posts' ) );
		register_post_type( 'book', array( 'has_archive' => true ) );
		$post_id = $this->plugin->get_archive_id( 'book' );

		$expected = home_url( '/book/' );
		$this->assertEquals( $expected, get_permalink( $post_id ) );
		$this->assertEquals( $expected, get_post_type_archive_link( 'book' ) );

		$post_id = $this->factory->post->create( array( 'post_type' => 'book', 'post_name' => 'Moby-Dick' ) );
		$this->assertEquals( home_url( '/novels/moby-dick/' ), get_permalink( $post_id ) );
	}
}
