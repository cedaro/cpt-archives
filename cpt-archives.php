<?php
/**
 * Plugin Name: CPT Archives
 * Plugin URI: https://github.com/bradyvercher/wp-cpt-archives
 * Description: Manage post type archive titles, descriptions, and permalink slugs from the dashboard.
 * Version: 1.0.0
 * Author: Blazer Six
 * Author URI: http://www.blazersix.com/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package BlazerSix\CPTArchives
 * @author Brady Vercher <brady@blazersix.com>
 * @copyright Copyright (c) 2012, Blazer Six, Inc.
 * @license GPL-2.0+
 *
 * @todo Update nav menu classes to reflect current, parent, and ancestor status.
 */

/**
 * Include template tags.
 */
include( plugin_dir_path( __FILE__ ) . 'archive-template.php' );

/**
 * The main plugin class.
 */
class CPT_Archives {
	/**
	 * Setup the plugin by attaching the necessary hooks.
	 *
	 * @since 1.0.0
	 */
	public function setup() {
		add_action( 'init', array( $this, 'localize' ) );

		add_action( 'init', array( $this, 'init' ) );
		add_filter( 'post_type_link', array( $this, 'post_type_link' ), 10, 3 );
		add_filter( 'post_type_archive_link', array( $this, 'post_type_archive_link' ), 10, 2 );
		add_filter( 'post_type_archive_title', array( $this, 'post_type_archive_title' ) );

		add_action( 'post_updated', array( $this, 'post_updated' ), 10, 3 );
		add_action( 'delete_post', array( $this, 'deleted_post' ) );

		// Prevent the cpt_archive post type rules from being registered.
		add_filter( 'cpt_archive_rewrite_rules', '__return_empty_array' );

		// Replace the default rewrite rules when they're generated and if necessary.
		add_filter( 'rewrite_rules_array', array( $this, 'rewrite_rules_array' ) );

		if ( is_admin() ) {
			add_action( 'init', array( $this, 'init_admin' ), 50 );
			add_action( 'parent_file', array( $this, 'parent_file' ) );
			add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );

			if ( apply_filters( 'cpt_archives_add_submenus', true ) ) {
				add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			}
		}
	}

	/**
	 * Support localization for the plugin.
	 *
	 * @see http://www.geertdedeckere.be/article/loading-wordpress-language-files-the-right-way
	 *
	 * @since 1.0.0
	 */
	public function localize() {
		$domain = 'cpt-archives-i18n';
		// The "plugin_locale" filter is also used in load_plugin_textdomain()
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, WP_LANG_DIR . '/cpt-archives/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, plugin_dir_path( __FILE__ ) . 'languages/' );
	}

	/**
	 * Register the cpt_archive post type.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		$labels = array(
			'name'               => _x( 'Archives', 'post format general name', 'cpt-archives-i18n' ),
			'singular_name'      => _x( 'Archive', 'post format singular name', 'cpt-archives-i18n' ),
			'add_new'            => _x( 'Add New', 'cpt_archive',               'cpt-archives-i18n' ),
			'add_new_item'       => __( 'Add New Archive',                      'cpt-archives-i18n' ),
			'edit_item'          => __( 'Edit Archive',                         'cpt-archives-i18n' ),
			'new_item'           => __( 'New Archive',                          'cpt-archives-i18n' ),
			'view_item'          => __( 'View Archive',                         'cpt-archives-i18n' ),
			'search_items'       => __( 'Search Archives',                      'cpt-archives-i18n' ),
			'not_found'          => __( 'No archives found.',                   'cpt-archives-i18n' ),
			'not_found_in_trash' => __( 'No archives found in Trash.',          'cpt-archives-i18n' ),
			'all_items'          => __( 'All Archives',                         'cpt-archives-i18n' ),
			'menu_name'          => __( 'Archives',                             'cpt-archives-i18n' ),
			'name_admin_bar'     => _x( 'Archive', 'add new on admin bar',      'cpt-archives-i18n' ),
		);

		$args = array(
			'capability_type'            => array( 'post', 'posts' ),
			'capabilities'               => array(
				'delete_post'            => 'delete_cpt_archive',

				// Custom caps prevent unnecessary fields from showing up in post_submit_meta_box().
				'create_posts'           => 'create_cpt_archives',
				'delete_posts'           => 'delete_cpt_archives',
				'delete_private_posts'   => 'delete_cpt_archives',
				'delete_published_posts' => 'delete_cpt_archives',
				'delete_others_posts'    => 'delete_cpt_archives',
				'publish_posts'          => 'publish_cpt_archives',
			),
			'exclude_from_search'        => true,
			'has_archive'                => false,
			'hierarchical'               => false,
			'labels'                     => $labels,
			'map_meta_cap'               => true,
			'public'                     => true,
			'publicly_queryable'         => false,
			'rewrite'                    => 'cpt_archive', // Allows slug to be edited. Rules wont' be generated.
			'query_var'                  => false,
			'show_ui'                    => false,
			'show_in_admin_bar'          => false,
			'show_in_menu'               => false,
			'show_in_nav_menus'          => true,
			'supports'                   => array( 'title', 'editor' ),
		);

		register_post_type( 'cpt_archive', apply_filters( 'cpt_archive_post_args', $args ) );
	}

	/**
	 * Setup archive posts for post types that have support.
	 *
	 * Support for a custom archive can be added by using:
	 * add_post_type_support( 'post_type_name', 'archive' )
	 *
	 * @since 1.0.0
	 */
	public function init_admin() {
		$archives = array();

		$post_types = get_post_types( array( 'public' => true, '_builtin' => false ) );

		if ( $post_types ) {
			foreach ( $post_types as $post_type ) {
				// Look for post types that support the archive page feature.
				if ( post_type_supports( $post_type, 'archive' ) ) {
					$id = $this->create_archive( $post_type );
					if ( $id ) {
						$archives[ $post_type ] = $id;
					}
				}
			}
		}

		$this->save_active_archives( $archives );
	}

	/**
	 * Add submenu items for archives under the post type menu item.
	 *
	 * Ensures the user has the capability to edit pages in general as well
	 * as the individual page before displaying the submenu item.
	 *
	 * @since 1.0.0
	 */
	public function admin_menu() {
		$archives = $this->get_archive_ids();

		if ( empty( $archives ) ) {
			return;
		}

		// Verify the user can edit cpt_archive posts.
		$archive_type_object = get_post_type_object( 'cpt_archive' );
		if ( ! current_user_can( $archive_type_object->cap->edit_posts ) ) {
			return;
		}

		foreach ( $archives as $post_type => $archive_id ) {
			// Verify the user can edit the particular cpt_archive post in question.
			if ( ! current_user_can( $archive_type_object->cap->edit_post, $archive_id ) ) {
				continue;
			}

			// Add the submenu item.
			add_submenu_page(
				'edit.php?post_type=' . $post_type,
				$archive_type_object->labels->singular_name,
				$archive_type_object->labels->singular_name,
				$archive_type_object->cap->edit_posts,
				add_query_arg( array( 'post' => $archive_id, 'action' => 'edit' ), 'post.php' ),
				null
			);
		}
	}

	/**
	 * Highlight the corresponding top level and submenu items when editing an
	 * archive page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $parent_file A parent file identifier.
	 * @return string
	 */
	public function parent_file( $parent_file ) {
		global $post, $submenu_file;

		if ( $post && 'cpt_archive' == get_current_screen()->id && $post_type = $this->is_post_type_archive_id( $post->ID ) ) {
			$parent_file = 'edit.php?post_type=' . $post_type;
			$submenu_file = add_query_arg( array( 'post' => $post->ID, 'action' => 'edit' ), 'post.php' );
		}

		return $parent_file;
	}

	/**
	 * Archive update messages.
	 *
	 * @see /wp-admin/edit-form-advanced.php
	 *
	 * @param array $messages The array of post update messages.
	 * @return array An array with new CPT update messages.
	 */
	public function post_updated_messages( $messages ) {
		global $post;

		$messages['cpt_archive'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => sprintf( __( 'Archive updated. <a href="%s">View Archive</a>', 'cpt-archives-i18n' ), esc_url( get_permalink( $post->ID ) ) ),
			2  => __( 'Custom field updated.', 'cpt-archives-i18n' ),
			3  => __( 'Custom field deleted.', 'cpt-archives-i18n' ),
			4  => __( 'Archive updated.', 'cpt-archives-i18n' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Archive restored to revision from %s', 'cpt-archives-i18n' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf( __( 'Archive published. <a href="%s">View Archive</a>', 'cpt-archives-i18n' ), esc_url( get_permalink( $post->ID ) ) ),
			7  => __( 'Archive saved.', 'cpt-archives-i18n' ),
			8  => sprintf( __( 'Archive submitted. <a target="_blank" href="%s">Preview Archive</a>', 'cpt-archives-i18n' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ) ),
			9  => sprintf( __( 'Archive scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Archive</a>', 'cpt-archives-i18n' ),
			      // translators: Publish box date format, see http://php.net/date
			      date_i18n( __( 'M j, Y @ G:i', 'cpt-archives-i18n' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post->ID ) ) ),
			10 => sprintf( __( 'Archive draft updated. <a target="_blank" href="%s">Preview Archive</a>', 'cpt-archives-i18n' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ) ),
		);

		return $messages;
	}

	/**
	 * Get archive post IDs.
	 *
	 * @since 1.0.0
	 *
	 * @return array Associative array with post types as keys and post IDs as the values.
	 */
	public function get_archive_ids() {
		return ( $archives = get_option( 'cpt_archives' ) ) ? $archives : array();
	}

	/**
	 * Save the active archive IDs.
	 *
	 * Determines when an archive has become inactive and moves it to a
	 * separate option so that if it's activated again in the future, a new
	 * post won't be created.
	 *
	 * Will flush rewrite rules if any changes are detected.
	 *
	 * @since 1.0.0
	 *
	 * @param array $ids Active archive post IDs.
	 */
	public function save_active_archives( $ids ) {
		$archives = $this->get_archive_ids();
		$diff = array_diff_key( $archives, $ids );

		if ( count( $ids ) != count( $archives ) || $diff ) {
			$inactive = (array) get_option( 'cpt_archives_inactive' );

			// Remove $ids from $inactive.
			$inactive = array_diff_key( array_filter( $inactive ), $ids );

			// Move the diff between the $ids parameter and the $archives option to the $inactive option.
			$inactive = array_merge( $inactive, $diff );

			update_option( 'cpt_archives', $ids );
			update_option( 'cpt_archives_inactive', $inactive );

			flush_rewrite_rules();
		}
	}

	/**
	 * Creates an archive post for a post type if one doesn't exist.
	 *
	 * The post type's plural label is used for the post title and the defined
	 * rewrite slug is used for the postname.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type_name Post type slug.
	 * @return int Post ID.
	 */
	public function create_archive( $post_type ) {
		$archive_id = $this->get_post_type_archive( $post_type );
		if ( $archive_id ) {
			return $archive_id;
		}

		// Search the inactive option before creating a new page.
		$inactive = get_option( 'cpt_archives_inactive' );
		if ( $inactive && isset( $inactive[ $post_type ] ) && get_post( $inactive[ $post_type ] ) ) {
			return $inactive[ $post_type ];
		}

		// Otherwise, create a new archive post.
		$post_type_object = get_post_type_object( $post_type );

		$post = array(
			'post_title'  => $post_type_object->labels->name,
			'post_name'   => $this->get_post_type_archive_slug( $post_type ),
			'post_type'   => 'cpt_archive',
			'post_status' => 'publish',
		);

		return wp_insert_post( $post );
	}

	/**
	 * Retrieve a post type's archive slug.
	 *
	 * Checks the 'has_archive' and 'with_front' args in order to build the
	 * slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type Post type name.
	 * @return string Archive slug.
	 */
	public function get_post_type_archive_slug( $post_type ) {
		global $wp_rewrite;

		$post_type_object = get_post_type_object( $post_type );

		$slug = ( false !== $post_type_object->rewrite ) ? $post_type_object->rewrite['slug'] : $post_type_object->name;

		if ( $post_type_object->has_archive ) {
			$slug = ( true === $post_type_object->has_archive ) ? $post_type_object->rewrite['slug'] : $post_type_object->has_archive;

			if ( $post_type_object->rewrite['with_front'] ) {
				$slug = substr( $wp_rewrite->front, 1 ) . $slug;
			} else {
				$slug = $wp_rewrite->root . $slug;
			}
		}

		return $slug;
	}

	/**
	 * Replace default archive rewrite rules with custom rules based on the
	 * archive post's postname.
	 *
	 * @since 1.0.0
	 *
	 * @see register_post_type()
	 *
	 * @param array $rules Rewrite rules.
	 * @return array
	 */
	public function rewrite_rules_array( $rules ) {
		global $wp_rewrite;

		$ids = $this->get_archive_ids();

		if ( empty( $ids ) ) {
			return $rules;
		}

		foreach ( $ids as $post_type => $archive_id ) {
			$archive_post = get_post( $archive_id );
			$post_type_object = get_post_type_object( $post_type );

			$old_slug = $this->get_post_type_archive_slug( $post_type );
			$new_slug = $archive_post->post_name;

			if ( $post_type_object->rewrite['with_front'] ) {
				$new_slug = substr( $wp_rewrite->front, 1 ) . $new_slug;
			} else {
				$new_slug = $wp_rewrite->root . $new_slug;
			}

			if ( $old_slug != $new_slug ) {
				$new_rules = array();

				// Add new rules and remove existing rules.
				$rule = "%s/?$";
				$new_rules[ sprintf( $rule, $new_slug ) ] = "index.php?post_type=$post_type";
				unset( $rules[ sprintf( $rule, $old_slug ) ] );

				if ( $post_type_object->rewrite['feeds'] && $wp_rewrite->feeds ) {
					$feeds = '(' . trim( implode( '|', $wp_rewrite->feeds ) ) . ')';

					$rule = "%s/feed/$feeds/?$";
					$new_rules[ sprintf( $rule, $new_slug ) ] = "index.php?post_type=$post_type" . '&feed=$matches[1]';
					unset( $rules[ sprintf( $rule, $old_slug ) ] );

					$rule = "%s/$feeds/?$";
					$new_rules[ sprintf( $rule, $new_slug ) ] = "index.php?post_type=$post_type" . '&feed=$matches[1]';
					unset( $rules[ sprintf( $rule, $old_slug ) ] );
				}

				if ( $post_type_object->rewrite['pages'] ) {
					$rule = "%s/{$wp_rewrite->pagination_base}/([0-9]{1,})/?$";
					$new_rules[ sprintf( $rule, $new_slug ) ] = "index.php?post_type=$post_type" . '&paged=$matches[1]';
					unset( $rules[ sprintf( $rule, $old_slug ) ] );
				}

				$rules = array_merge( $new_rules, $rules );
			}
		}

		return $rules;
	}

	/**
	 * Get the archive post ID for a particular post type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type_name Post type name
	 * @return array
	 */
	public function get_post_type_archive( $post_type ) {
		$archives = $this->get_archive_ids();

		return ( empty( $archives[ $post_type ] ) ) ? array() : $archives[ $post_type ];
	}

	/**
	 * Determine if a post ID is for a post type archive post.
	 *
	 * @since 1.0.0
	 *
	 * @param int $archive_id Post ID.
	 * @return string|bool Post type name if true, otherwise false.
	 */
	public function is_post_type_archive_id( $archive_id ) {
		$archives = $this->get_archive_ids();
		return array_search( $archive_id, $archives );
	}

	/**
	 * Filter cpt_archive permalinks to match the corresponding post type's
	 * archive.
	 *
	 * @since 1.0.0
	 *
	 * @param string $permalink Default permalink.
	 * @param WP_Post $post Post object.
	 * @param bool $leavename Optional, defaults to false. Whether to keep post name.
	 * @return string Permalink.
	 */
	public function post_type_link( $permalink, $post, $leavename ) {
		global $wp_rewrite;

		if ( 'cpt_archive' == $post->post_type  ) {
			$post_type = $this->is_post_type_archive_id( $post->ID );
			$post_type_object = get_post_type_object( $post_type );

			if ( get_option( 'permalink_structure' ) ) {
				$front = '/';
				if ( isset( $post_type_object->rewrite ) && $post_type_object->rewrite['with_front'] ) {
					$front = $wp_rewrite->front;
				}

				if ( $leavename ) {
					$permalink = home_url( $front . '%postname%/' );
				} else {
					$permalink = home_url( $front . $post->post_name . '/' );
				}
			} else {
				$permalink = add_query_arg( 'post_type', $post_type, home_url( '/' ) );
			}
		}

		return $permalink;
	}

	/**
	 * Filter post type archive permalinks.
	 *
	 * @since 1.0.0
	 *
	 * @param string $link Post type archive link.
	 * @param string $post_type Post type name.
	 * @return string
	 */
	public function post_type_archive_link( $link, $post_type ) {
		if ( $archive_id = $this->get_post_type_archive( $post_type ) ) {
			$link = get_permalink( $archive_id );
		}

		return $link;
	}

	/**
	 * Flush the rewrite rules when an archive post slug is changed.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID
	 * @param WP_Post $post_after Updated post object.
	 * @param WP_Post $post_before Post object before udpate.
	 */
	public function post_updated( $post_id, $post_after, $post_before ) {
		if ( $this->is_post_type_archive_id( $post_id ) && $post_after->post_name != $post_before->post_name ) {
			flush_rewrite_rules();
		}
	}

	/**
	 * Remove the post type archive reference if it's deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 */
	public function delete_post( $post_id ) {
		if ( 'cpt_archive' != get_post_type( $post_id ) ) {
			return;
		}

		$active = $this->get_archives();
		if ( $key = array_search( $active ) ) {
			unset( $active[ $key ] );
			$this->save_active_archives( $active );
		}
	}

	/**
	 * Filter the default post_type_archive_title() template tag and replace with
	 * custom archive title.
	 *
	 * @since 1.0.0
	 *
	 * @param string $label Post type archive title.
	 * @return string
	 */
	public function post_type_archive_title( $title ) {
		$post_type_object = get_queried_object();

		if ( $page_id = $this->get_post_type_archive( $post_type_object->name ) ) {
			$page = get_post( $page_id );
			$title = $page->post_title;
		}

		return $title;
	}
}

// Initialize the plugin.
$cpt_archives = new CPT_Archives;
$cpt_archives->setup();
