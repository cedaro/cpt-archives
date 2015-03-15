<?php
/**
 * Archive object.
 *
 * @package   CPTArchives
 * @since     3.0.0
 * @link      https://github.com/cedaro/cpt-archives
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license   GPL-2.0+
 */

namespace Cedaro\CPTArchives\PostType;

use Cedaro\CPTArchives\Hooks;

/**
 * Archive object class.
 *
 * @package CPTArchives
 * @since   3.0.0
 */
class Archive {

	use Hooks;

	/**
	 * Whether slugs can be customised.
	 *
	 * @since 3.0.0
	 * @var bool|string
	 */
	public $customize_rewrites = true;

	/**
	 * Whether to show the archive submenu item in the admin menu.
	 *
	 * Set to the parent menu identifier to control where the subemnu appears.
	 *
	 * @since 3.0.0
	 * @var bool|string
	 */
	public $show_in_menu = true;

	/**
	 * Archive feature support.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	public $supports = array( 'title', 'editor' );

	/**
	 * Whether the archive has been initialized.
	 *
	 * @since 3.0.0
	 * @var bool
	 */
	protected $initialized = false;

	/**
	 * Post type name.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	protected $post_type = '';

	/**
	 * Post type of the archive CPT.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	protected $post_post_type = 'cpt_archive';

	/**
	 * Cached data about the original registered state of a post type.
	 *
	 * Includes the original rewrite, has_archive, and slug arguments.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $data = array();

	/**
	 * Constructor method.
	 *
	 * @since 3.0.0
	 *
	 * @param string $post_type  Post type name.
	 * @param array  $attributes Optional. Archive attributes.
	 */
	public function __construct( $post_type, $attributes = array() ) {
		$this->post_type = $post_type;

		if ( ! empty( $attributes ) ) {
			$this->set( $attributes );
		}

		// Initialize immediately if the post type already exists.
		if ( post_type_exists( $post_type ) ) {
			$this->initialize( $this->post_type );
		} else {
			$this->add_action( 'registered_post_type', 'initialize', 5, 2 );
		}
	}

	/**
	 * Magic getter.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $name Attribute name.
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'initialized' :
				return $this->initialized;
			case 'post_type' :
				return $this->post_type;
		}
	}

	/**
	 * Whether rewrite slugs can be customized.
	 *
	 * @since 3.0.0
	 *
	 * @param string $type Optional. Type of slugs: archives or posts.
	 * @return bool
	 */
	public function can_customize_rewrites( $type = '' ) {
		return true === $this->customize_rewrites || $type === $this->customize_rewrites;
	}

	/**
	 * Retrieve a post type archive slug.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $front Optional. How to handle the rewrite prefix. Defaults to 'with_front'.
	 * @return string Archive slug.
	 */
	public function get_slug() {
		$slug = get_option( $this->post_type . '_rewrite_slug', '' );
		return empty( $slug ) ? $this->get_default_rewrite_slug() : $slug;
	}

	/**
	 * Retrieve the archive post object.
	 *
	 * @since 3.0.0
	 *
	 * @return WP_Post
	 */
	public function get_post() {
		return get_post( $this->get_post_id() );
	}

	/**
	 * Retrieve the archive post ID.
	 *
	 * Checks an option cache first, then searches for an existing option,
	 * before creating a new post.
	 *
	 * @since 3.0.0
	 *
	 * @return int
	 */
	public function get_post_id() {
		if ( ! $this->initialized ) {
			return 0;
		}

		// Check the option cache first.
		// Prevents a database lookup for quick checks.
		$cache = get_option( 'cptarchives', array() );
		if ( isset( $cache[ $this->post_type ] ) ) {
			return $cache[ $this->post_type ];
		}

		// Search for an existing post.
		$posts = get_posts( array(
			'post_type'      => $this->post_post_type,
			'posts_per_page' => 50,
			'meta_key'       => 'archive_for_post_type',
			'meta_value'     => $this->post_type,
			'fields'         => 'ids',
		) );

		if ( ! empty( $posts ) ) {
			$post_id = reset( $posts );
		}

		// Otherwise, create a new archive post.
		// The post type's plural label is used for the post title and the
		// defined rewrite slug is used for the post_name.
		if ( empty( $post_id ) ) {
			$post_id = $this->insert_post( array(
				'post_title' => get_post_type_object( $this->post_type )->labels->name,
				'post_name'  => $this->get_slug(),
				'post_type'  => $this->post_post_type,
			) );
		}

		// Update the option cache.
		$cache[ $this->post_type ] = $post_id;
		update_option( 'cptarchives', $cache );

		return $post_id;
	}

	/**
	 * Retrieve a list of the rules WordPress generates for archives.
	 *
	 * @since 3.0.0
	 *
	 * @see register_post_type()
	 *
	 * @return array Associative array with default patterns for keys and
	 *               replacement patterns as values.
	 */
	public function get_rewrite_patterns() {
		global $wp_rewrite;

		$patterns         = array();
		$post_type_object = get_post_type_object( $this->post_type );

		if ( ! $post_type_object->has_archive ) {
			return $patterns;
		}

		$old_slug = $this->get_default_rewrite_slug();
		$new_slug = $this->get_slug();

		if ( $post_type_object->rewrite['with_front'] ) {
			$front    = substr( $wp_rewrite->front, 1 );
			$old_slug = $front . $old_slug;
			$new_slug = $front . $new_slug;
		}

		$pattern = '%s/?$';
		$patterns[ sprintf( $pattern, $old_slug ) ] = sprintf( $pattern, $new_slug );

		if ( $post_type_object->rewrite['feeds'] && $wp_rewrite->feeds ) {
			$feeds = '(' . trim( implode( '|', $wp_rewrite->feeds ) ) . ')';
			$pattern = "%s/feed/$feeds/?$";
			$patterns[ sprintf( $pattern, $old_slug ) ] = sprintf( $pattern, $new_slug );

			$pattern = "%s/$feeds/?$";
			$patterns[ sprintf( $pattern, $old_slug ) ] = sprintf( $pattern, $new_slug );
		}

		if ( $post_type_object->rewrite['pages'] ) {
			$pattern = "%s/{$wp_rewrite->pagination_base}/([0-9]{1,})/?$";
			$patterns[ sprintf( $pattern, $old_slug ) ] = sprintf( $pattern, $new_slug );
		}

		return $patterns;
	}

	/**
	 * Set model attributes.
	 *
	 * @since 3.0.0
	 *
	 * @param array $attributes Array of model attributes.
	 */
	public function set( $attributes ) {
		foreach ( array_keys( $this->to_array() ) as $key ) {
			if ( isset( $attributes[ $key ] ) ) {
				$this->{$key} = $attributes[ $key ];
			}
		}
	}

	/**
	 * Convert the model to an array.
	 *
	 * Creates an array from the model's public attributes.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function to_array() {
		return call_user_func( 'get_object_vars', $this );
	}

	/**
	 * Update a post type's permastruct.
	 *
	 * Overwrites the default permastruct rules with a new slug.
	 *
	 * @since 3.0.0
	 *
	 * @param string $post_type Post type name.
	 */
	public function update_permastruct() {
		$slug             = $this->get_slug();
		$post_type_object = get_post_type_object( $this->post_type );

		$permastruct_args = $post_type_object->rewrite;
		$permastruct_args['feed'] = $permastruct_args['feeds'];
		add_permastruct( $this->post_type, "{$slug}/%$this->post_type%", $permastruct_args );
	}

	/**
	 * Set up the archive when a post type is ready.
	 *
	 * Caches data about the post type's original state and overwrites rewrite
	 * arguments when needed.
	 *
	 * @since 3.0.0
	 *
	 * @see register_post_type()
	 *
	 * @param  string $post_type Post type name.
	 * @param  object $args      Optional. Post type arguments.
	 * @return object Updated post type arguments.
	 */
	protected function initialize( $post_type, $args = array() ) {
		global $wp_post_types;

		if ( $this->initialized || $post_type != $this->post_type || ! post_type_exists( $post_type ) ) {
			return;
		}

		if ( empty( $args ) ) {
			$args = $wp_post_types[ $post_type ];
		}

		// Cache original attributes.
		$this->initialized         = true;
		$this->data['has_archive'] = $args->has_archive;
		$this->data['rewrite']     = $args->rewrite;
		$this->data['slug']        = $this->get_default_rewrite_slug();

		if ( false !== $args->rewrite && ( is_admin() || '' != get_option( 'permalink_structure' ) ) ) {
			// Update the post type's permastruct.
			if ( $this->can_customize_rewrites( 'posts' ) ) {
				$this->update_permastruct();
			}

			// Update post type rewrite slug and has_archive arguments.
			if ( $this->can_customize_rewrites( 'archives' ) ) {
				$slug = $this->get_slug( 'with_front' );

				if ( $args->has_archive ) {
					$args->has_archive = $slug;
				}

				if ( is_array( $args->rewrite ) && ! empty( $slug ) ) {
					$args->rewrite['slug'] = $slug;
				}
			}

			// Update the post type global.
			$wp_post_types[ $post_type ] = $args;
		}

		do_action( 'cptarchives_init_archive', $this );
	}

	/**
	 * Retrieve a post type archive slug.
	 *
	 * Checks the 'has_archive' and 'with_front' args to build the slug.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $post_type Post type name.
	 * @return string Archive slug.
	 */
	protected function get_default_rewrite_slug() {
		if ( isset( $this->data['slug'] ) ) {
			return $this->data['slug'];
		}

		$post_type_object = get_post_type_object( $this->post_type );

		$slug = $post_type_object->name;

		if ( $post_type_object->has_archive && true !== $post_type_object->has_archive ) {
			$slug = $post_type_object->has_archive;
		} elseif ( ! empty( $post_type_object->rewrite['slug'] ) ) {
			$slug = $post_type_object->rewrite['slug'];
		}

		return $slug;
	}

	/**
	 * Insert an archive post into the database.
	 *
	 * @since 3.0.0
	 *
	 * @param  array $post_data Post properties.
	 * @return int The inserted post's ID.
	 */
	protected function insert_post( $post_data ) {
		$post_data = wp_parse_args( $post_data, array(
			'post_status' => 'publish',
		) );

		$post_id = wp_insert_post( $post_data );

		if ( $post_id ) {
			update_post_meta( $post_id, 'archive_for_post_type', $this->post_type );
		}

		return $post_id;
	}
}
