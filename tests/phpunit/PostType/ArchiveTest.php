<?php

namespace Cedaro\CPTArchives\Tests\PostType;

use Cedaro\CPTArchives\PostType\Archive;

class ArchiveTest extends \WP_UnitTestCase {
	protected $plugin;

	public function setUp() {
		global $cptarchives, $wp_rewrite;
		parent::setUp();

		$this->plugin = $cptarchives;

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '/front/%postname%/' );
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

	public function test_archive_attributes() {
		$archive = new Archive( 'book', array(
			'customize_rewrites' => 'archives',
			'show_in_menu'       => 'test',
		) );

		$this->assertEquals( 'test', $archive->show_in_menu );
		$this->assertTrue( $archive->can_customize_rewrites( 'archives' ) );
		$this->assertFalse( $archive->can_customize_rewrites( 'posts' ) );
	}

	public function test_archive_slugs() {
		$archive = new Archive( 'book', array(
			'customize_rewrites' => 'archives',
			'show_in_menu'       => 'test',
		) );

		register_post_type( 'book', array( 'has_archive' => true ) );
		$this->assertEquals( 'book', $archive->get_slug() );

		$this->plugin->set_rewrite_slug( 'book', 'novel' );
		$this->assertEquals( 'novel', $archive->get_slug() );
	}

	public function test_archive_rewrite_patterns() {
		global $wp_rewrite;

		$archive = new Archive( 'book' );
		register_post_type( 'book', array( 'has_archive' => true ) );

		$patterns = $archive->get_rewrite_patterns();
		$this->assertContains( 'front/book/?$', $patterns );
		$this->assertArrayHasKey( 'front/book/?$', $patterns );

		$this->plugin->set_rewrite_slug( 'book', 'novels' );

		$patterns = $archive->get_rewrite_patterns();
		$this->assertContains( 'front/novels/?$', $patterns );
		$this->assertArrayHasKey( 'front/book/?$', $patterns );

		$wp_rewrite->set_permalink_structure( '/%postname%/' );

		$patterns = $archive->get_rewrite_patterns();
		$this->assertContains( 'novels/?$', $patterns );
		$this->assertArrayHasKey( 'book/?$', $patterns );
	}

	public function test_archive_rewrite_patterns_with_front_false() {
		$archive = new Archive( 'book' );
		register_post_type( 'book', array( 'has_archive' => true, 'rewrite' => array( 'with_front' => false ) ) );

		$patterns = $archive->get_rewrite_patterns();
		$this->assertContains( 'book/?$', $patterns );
		$this->assertArrayHasKey( 'book/?$', $patterns );

		$this->plugin->set_rewrite_slug( 'book', 'novels' );

		$patterns = $archive->get_rewrite_patterns();
		$this->assertContains( 'novels/?$', $patterns );
		$this->assertArrayHasKey( 'book/?$', $patterns );
	}

	public function test_archive_rewrite_patterns_has_archive_false() {
		$archive = new Archive( 'book' );
		register_post_type( 'book', array( 'has_archive' => false ) );

		$patterns = $archive->get_rewrite_patterns();
		$this->assertEmpty( $patterns );
	}

	public function test_archive_permastructs() {
		global $wp_rewrite;

		$archive = new Archive( 'book' );
		register_post_type( 'book', array( 'has_archive' => false ) );

		$this->assertContains( 'book', $wp_rewrite->get_extra_permastruct( 'book' ) );

		$this->plugin->set_rewrite_slug( 'book', 'novels' );
		$archive->update_permastruct();

		$this->assertContains( 'novels', $wp_rewrite->get_extra_permastruct( 'book' ) );
	}
}