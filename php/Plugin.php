<?php
/**
 * The main plugin file.
 *
 * @package   CPTArchives
 * @since     3.0.0
 * @link      https://github.com/cedaro/cpt-archives
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license   GPL-2.0+
 */

namespace Cedaro\CPTArchives;

use Cedaro\CPTArchives\Hooks;

/**
 * Main plugin class.
 *
 * @package CPTArchives
 * @since   3.0.0
 */
class Plugin {

	use Hooks;

	/**
	 * Archive object class.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	protected $archive_class = '';

	/**
	 * Map of post types and archive post IDs.
	 *
	 * @var array An associative array with post types as the keys and archive
	 *            post IDs as the values.
	 */
	protected $archive_map = array();

	/**
	 * List of archive objects.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $archives = array();

	/**
	 * Main plugin file path.
	 *
	 * @since 3.0.0
	 * @var string Absolute path to the main plugin file.
	 */
	protected $plugin_file;

	/**
	 * Load the plugin.
	 *
	 * @since 3.0.0
	 */
	public function load() {
		$this->load_textdomain();

		$this->add_action( 'cptarchives_init_archive', 'on_archive_setup' );
		$this->add_action( 'init',                     'maybe_flush_rewrite_rules', 1000 );

		// Prevent 'cpt_archive' post type rewrite rules from being registered.
		add_filter( 'cpt_archive_rewrite_rules', '__return_empty_array' );
	}

	/**
	 * Retrieve the archive for a post type.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $post_type Optional. Post type name. Defaults to the current post type.
	 * @return PostType\Archive|null
	 */
	public function get_archive( $post_type = null ) {
		$post_type = $post_type ? $post_type : $this->get_the_post_type();
		return isset( $this->archives[ $post_type ] ) ? $this->archives[ $post_type ] : null;
	}

	/**
	 * Retrieve the post ID for a post type archive.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $post_type_name Optional. Post type name. Defaults to the current post type.
	 * @return int
	 */
	public function get_archive_id( $post_type = null ) {
		$archive = $this->get_archive( $post_type );
		return $archive ? $archive->get_post_id() : 0;
	}

	/**
	 * Retrieve the title of a post type archive.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $post_type Optional. Post type name. Defaults to the current post type.
	 * @param  string $title     Optional. Fallback title.
	 * @return string
	 */
	public function get_archive_title( $post_type = null, $title = '' ) {
		$archive = $this->get_archive( $post_type );

		if ( $archive ) {
			$title = $archive->get_post()->post_title;
		}

		return $title;
	}

	/**
	 * Retrieve the description of a post type archive.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $post_type   Optional. Post type name. Defaults to the current post type.
	 * @param  string $description Optional. Fallback description.
	 * @return string
	 */
	public function get_archive_description( $post_type = null, $description = '' ) {
		$archive = $this->get_archive( $post_type );

		if ( $archive ) {
			$description = $archive->get_post()->post_content;
		}

		return $description;
	}

	/**
	 * Retrieve archive meta.
	 *
	 * @since 3.0.0
	 *
	 * @param string $key Optional. The meta key to retrieve. By default, returns data for all keys.
	 * @param bool $single Optional. Whether to return a single value.
	 * @param mixed $default Optional. A default value to return if the requested meta doesn't exist.
	 * @param string $post_type Optional. The post type archive to retrieve meta data for. Defaults to the current post type.
	 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
	 */
	public function get_archive_meta( $key = '', $single = false, $default = null, $post_type = null ) {
		$archive = $this->get_archive( $post_type );
		if ( ! $archive ) {
			return null;
		}

		$value = get_post_meta( $archive->get_post_id(), $key, $single );
		if ( empty( $value ) && ! empty( $default ) ) {
			$value = $default;
		}

		return apply_filters( 'cptarchives_get_archive_meta', $value, $key, $single, $default, $post_type );
	}

	/**
	 * Retrieve registered archives.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_archives() {
		return $this->archives;
	}

	/**
	 * Retrieve archive post IDs.
	 *
	 * @since 3.0.0
	 *
	 * @return array Associative array with post types as keys and post IDs as the values.
	 */
	public function get_archive_ids() {
		return $this->archive_map;
	}

	/**
	 * Retrieve the post type for the current request.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_the_post_type() {
		$post_type = get_query_var( 'post_type' );

		if ( is_array( $post_type ) ) {
			$post_type = reset( $post_type );
		}

		return $post_type;
	}

	/**
	 * Retrieve the main plugin file path.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_plugin_file() {
		return $this->plugin_file;
	}

	/**
	 * Whether the current query has a corresponding archive post.
	 *
	 * @since 3.0.0
	 *
	 * @param array|string $post_types Optional. A post type name or array of
	 *                                 post type names.
	 * @return bool
	 */
	public function has_post_type_archive( $post_types = array() ) {
		if ( empty( $post_types ) ) {
			$post_types = array_keys( $this->get_archive_ids() );
		}

		return is_post_type_archive( $post_types );
	}

	/**
	 * Determine if a post ID is for an archive post.
	 *
	 * @since 3.0.0
	 *
	 * @param  int $post_id Post ID.
	 * @return PostType\Archive|bool  Post type archive object if true, otherwise false.
	 */
	public function is_archive_id( $post_id ) {
		$archives  = $this->get_archive_ids();
		$post_type = array_search( $post_id, $archives );
		return $post_type ? $this->get_archive( $post_type ) : false;
	}

	/**
	 * Create an archive post for a post type.
	 *
	 * @since 3.0.0
	 *
	 * This should be called before the post type has been registered.
	 *
	 * @param string $post_type Post type name.
	 * @param array  $args {
	 *     An array of arguments. Optional.
	 *
	 *     @type bool|string $customize_rewrites
	 *     @type string      $show_in_menu       Admin menu parent slug.
	 * }
	 * @return PostType\Archive
	 */
	public function register_archive( $post_type, $args = array() ) {
		// Store the archive in a local collection.
		$archive = new $this->archive_class( $post_type, $args );
		$this->archives[ $post_type ] = $archive;

		return $archive;
	}

	/**
	 * Register a hook provider.
	 *
	 * @since 3.0.0
	 *
	 * @param  object $provider Hook provider.
	 * @return $this
	 */
	public function register_hooks( $provider ) {
		$provider->register( $this );
		return $this;
	}

	/**
	 * Unregister an archive.
	 *
	 * @since 3.0.0
	 *
	 * @param string $post_type Post type name.
	 */
	public function unregister_archive( $post_type ) {
		unset( $this->archives[ $post_type ] );
		unset( $this->archive_map[ $post_type ] );

		$archives = get_option( 'cptarchives', array() );
		if ( ! empty( $archives[ $post_type ] ) ) {
			unset( $archives[ $post_type ] );
			update_option( 'cptarchives', $archives );
		}
	}

	/**
	 * Set the class name for archive objects.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $class Archive class name.
	 * @return Plugin Returns itself to allow chaining.
	 */
	public function set_archive_class( $class ) {
		$this->archive_class = $class;
		return $this;
	}

	/**
	 * Set the plugin file.
	 *
	 * @since 3.0.0
	 *
	 * @param string $file Absolute path to the main plugin file.
	 * @return Plugin Returns itself to allow chaining.
	 */
	public function set_plugin_file( $file ) {
		$this->plugin_file = $file;
		return $this;
	}

	/**
	 * Set the rewrite slug for a post type.
	 *
	 * @since 3.0.0
	 *
	 * @param string $post_type Post type name.
	 * @param string $slug      Post type slug.
	 */
	public function set_rewrite_slug( $post_type, $slug ) {
		update_option( $post_type . '_rewrite_slug', $slug );
	}

	/**
	 * Localize the plugin's strings.
	 *
	 * @since 3.0.0
	 */
	protected function load_textdomain() {
		$plugin_rel_path = dirname( plugin_basename( $this->get_plugin_file() ) ) . '/languages';
		load_plugin_textdomain( 'cpt-archives', false, $plugin_rel_path );
	}

	/**
	 * Cache initialized archives in a local map.
	 *
	 * @since 3.0.0
	 *
	 * @param PostType\Archive $archive Archive object.
	 */
	protected function on_archive_setup( $archive ) {
		$this->archive_map[ $archive->post_type ] = $archive->get_post_id();
	}

	/**
	 * Flush rewrite rules if the flag is true.
	 *
	 * @since 3.0.0
	 */
	protected function maybe_flush_rewrite_rules() {
		if ( get_option( 'cptarchives_flush_rewrite_rules', false ) ) {
			flush_rewrite_rules();
			update_option( 'cptarchives_flush_rewrite_rules', false );
		}
	}
}
