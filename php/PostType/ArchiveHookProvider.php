<?php
/**
 * Archive post type registration and integration.
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
 * Class for registering the archive CPT and additional integration with core
 * WordPress features.
 *
 * @package CPTArchives
 * @since   3.0.0
 */
class ArchiveHookProvider {

	use Hooks;

	/**
	 * Plugin instance.
	 *
	 * @since 3.0.0
	 * @var \Cedaro\CPTArchives\Plugin
	 */
	protected $plugin;

	/**
	 * Archive post type name.
	 *
	 * @since 3.0.0
	 * @var string
	 */
	protected $post_type = 'cpt_archive';

	/**
	 * Register hooks.
	 *
	 * @since 3.0.0
	 *
	 * @param \Cedaro\CPTArchives\Plugin Main plugin instance.
	 */
	public function register( \Cedaro\CPTArchives\Plugin $plugin ) {
		$this->plugin = $plugin;

		$this->add_action( 'init',                        'register_post_type', 5 );
		$this->add_action( 'registered_post_type',        'on_post_type_registered', 0, 2 );
		$this->add_filter( 'rewrite_rules_array',         'update_rewrite_rules' );
		$this->add_action( 'post_updated',                'on_archive_update', 10, 3 );
		$this->add_action( 'delete_post',                 'on_archive_delete' );
		$this->add_filter( 'post_type_link',              'post_type_link', 10, 3 );
		$this->add_filter( 'post_type_archive_title',     'post_type_archive_title' );
		$this->add_filter( 'get_the_archive_description', 'post_type_archive_description' );
		$this->add_filter( 'wp_get_nav_menu_items',       'nav_menu_classes' );
		$this->add_action( 'admin_bar_menu',              'admin_bar_edit_menu', 80 );

		// High priority makes archive links appear last in submenus.
		$this->add_action( 'load-post.php',              'register_archive_feature_support' );
		$this->add_action( 'load-post.php',              'maybe_make_archive_viewable' );
		$this->add_action( 'admin_menu',                 'admin_menu', 100 );
		$this->add_action( 'parent_file',                'parent_file' );
		$this->add_filter( 'post_updated_messages',      'updated_messages' );
		$this->add_action( 'add_meta_boxes_cpt_archive', 'maybe_remove_slug_meta_box' );
	}

	/**
	 * Register the archive custom post type.
	 *
	 * @since 3.0.0
	 */
	protected function register_post_type() {
		$labels = array(
			'name'               => _x( 'Archives', 'post type general name', 'cpt-archives' ),
			'singular_name'      => _x( 'Archive', 'post type singular name', 'cpt-archives' ),
			'menu_name'          => _x( 'Archives', 'admin menu', 'cpt-archives' ),
			'name_admin_bar'     => _x( 'Archive', 'add new on admin bar', 'cpt-archives' ),
			'add_new'            => _x( 'Add New', 'cpt_archive', 'cpt-archives' ),
			'add_new_item'       => __( 'Add New Archive', 'cpt-archives' ),
			'new_item'           => __( 'New Archive', 'cpt-archives' ),
			'edit_item'          => __( 'Edit Archive', 'cpt-archives' ),
			'view_item'          => __( 'View Archive', 'cpt-archives' ),
			'all_items'          => __( 'All Archives', 'cpt-archives' ),
			'search_items'       => __( 'Search Archives', 'cpt-archives' ),
			'parent_item_colon'  => __( 'Parent Archives:', 'cpt-archives' ),
			'not_found'          => __( 'No archives found.', 'cpt-archives' ),
			'not_found_in_trash' => __( 'No archives found in Trash.', 'cpt-archives' ),
		);

		$args = array(
			'capability_type'            => array( 'post', 'posts' ),
			'capabilities'               => array(
				'delete_post'            => 'delete_cpt_archive',

				// Custom capabilities prevent unnecessary fields from
				// displaying in post_submit_meta_box().
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
			// This allows the slug to be edited. Rules won't be generated.
			'rewrite'                    => 'cpt_archive',
			'query_var'                  => false,
			'show_ui'                    => true,
			'show_in_admin_bar'          => false,
			'show_in_menu'               => false,
			'show_in_nav_menus'          => true,
			'supports'                   => array( 'title', 'editor' ),
		);

		register_post_type( $this->post_type,  $args );
	}

	/**
	 * Register archives for post types with 'archive' support.
	 *
	 * @since 3.0.0
	 *
	 * @see register_post_type()
	 *
	 * @param string $post_type Post type name.
	 * @param array  $args      Post type registration args.
	 */
	protected function on_post_type_registered( $post_type, $args ) {
		if ( post_type_supports( $post_type, 'archive' ) && ! $this->plugin->get_archive( $post_type ) ) {
			$this->plugin->register_archive( $post_type );
		}
	}

	/**
	 * Replace default archive rewrite rules with custom rules based on the
	 * archive post's post_name.
	 *
	 * @since 3.0.0
	 *
	 * @see register_post_type()
	 *
	 * @param  array $rules Rewrite rules.
	 * @return array
	 */
	protected function update_rewrite_rules( $rules ) {
		$archive_rewrite_patterns = array();

		foreach ( $this->plugin->get_archives() as $post_type => $archive ) {
			if ( ! post_type_exists( $post_type ) || ! $archive->can_customize_rewrites( 'archives' ) ) {
				continue;
			}

			// Merge the post type archive rules to update in the next loop.
			$archive_rewrite_patterns = array_merge( $archive_rewrite_patterns, $archive->get_rewrite_patterns() );
		}

		// Update archive rewrite rules.
		$index = 0;
		foreach ( $rules as $pattern => $route ) {
			// Replace archive rewrite rules.
			if ( isset( $archive_rewrite_patterns[ $pattern ] ) ) {
				$rules = $this->array_asplice( $rules, $index, 1, array(
					$archive_rewrite_patterns[ $pattern ] => $route,
				) );
			}
			$index++;
		}

		return $rules;
	}

	/**
	 * Unregister an archive when it's post is deleted.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id Post ID.
	 */
	protected function on_archive_delete( $post_id ) {
		$post_type = get_post_type( $post_id );

		if ( $post_type != $this->post_type ) {
			return;
		}

		$this->plugin->unregister_archive( $post_type );
	}

	/**
	 * Update the slug and flush rewrite rules when an archive post slug is changed.
	 *
	 * @since 3.0.0
	 *
	 * @param int     $post_id     Post ID
	 * @param WP_Post $post_after  Updated post object.
	 * @param WP_Post $post_before Post object before udpate.
	 */
	protected function on_archive_update( $post_id, $post_after, $post_before ) {
		if ( $this->post_type != $post_after->post_type || $post_after->post_name == $post_before->post_name ) {
			return;
		}

		$post_type = get_post_meta( $post_id, 'archive_for_post_type', true );
		$this->plugin->set_rewrite_slug( $post_type, $post_after->post_name );
		update_option( 'cptarchives_flush_rewrite_rules', true );
	}

	/**
	 * Filter archive CPT permalinks to match the corresponding post type's
	 * archive link.
	 *
	 * @since 3.0.0
	 *
	 * @param  string  $permalink Default permalink.
	 * @param  WP_Post $post      Post object.
	 * @param  bool    $leavename Optional, defaults to false. Whether to keep post name.
	 * @return string Permalink.
	 */
	protected function post_type_link( $permalink, $post, $leavename ) {
		global $wp_rewrite;

		if ( $this->post_type == $post->post_type ) {
			$post_type        = $post->archive_for_post_type;
			$post_type_object = get_post_type_object( $post_type );

			if ( 'post' == $post_type ) {
				$page_for_posts = get_option( 'page_for_posts' );
				$permalink = $page_for_posts ? get_permalink( $page_for_posts ) : home_url( '/' );
			}

			elseif ( get_option( 'permalink_structure' ) ) {
				$archive = $this->plugin->get_archive( $post_type );
				$front   = '/';

				if ( isset( $post_type_object->rewrite ) && $post_type_object->rewrite['with_front'] ) {
					$front = $wp_rewrite->front;
				}

				if ( $leavename && $archive->can_customize_rewrites() ) {
					$permalink = home_url( $front . '%postname%/' );
				} else {
					$permalink = get_post_type_archive_link( $post->archive_for_post_type );
				}
			} else {
				$permalink = add_query_arg( 'post_type', $post_type, home_url( '/' ) );
			}
		}

		return $permalink;
	}

	/**
	 * Filter the default archive title template tags and replace with the
	 * custom archive title.
	 *
	 * Works with both post_type_archive_title() and the_archive_title().
	 *
	 * @since 3.0.0
	 *
	 * @param string $title Archive title.
	 * @return string
	 */
	protected function post_type_archive_title( $title ) {
		$post_type_object = get_queried_object();
		return $this->plugin->get_archive_title( $post_type_object->name, $title );
	}

	/**
	 * Filter the default archive description template tags and replace with the
	 * custom archive description.
	 *
	 * Works with the_archive_description().
	 *
	 * @since 3.0.0
	 *
	 * @param  string $description Archive description.
	 * @return string
	 */
	protected function post_type_archive_description( $description ) {
		if ( is_post_type_archive() && $this->plugin->has_post_type_archive() ) {
			$post_type_object = get_queried_object();
			$description      = $this->plugin->get_archive_description( $post_type_object->name, $description );
		}

		return $description;
	}

	/**
	 * Add contextual nav menu item classes.
	 *
	 * @since 3.0.0
	 *
	 * @param  array $items List of menu items.
	 * @return array
	 */
	protected function nav_menu_classes( $items ) {
		global $wp;

		if ( is_404() || is_search() ) {
			return $items;
		}

		$current_url  = trailingslashit( home_url( add_query_arg( array(), $wp->request ) ) );
		$blog_page_id = get_option( 'page_for_posts' );
		$is_blog_post = is_singular( 'post' );

		$post_type_has_archive   = is_singular( array_keys( $this->plugin->get_archive_ids() ) );
		$post_type_archive_id    = $this->plugin->get_archive_id();
		$post_type_archive_link  = get_post_type_archive_link( get_post_type() );

		$current_menu_parents = array();

		foreach ( $items as $key => $item ) {
			if (
				$this->post_type == $item->object &&
				$post_type_archive_id == $item->object_id &&
				trailingslashit( $item->url ) == $current_url
			) {
				$items[ $key ]->classes[] = 'current-menu-item';
				$current_menu_parents[] = $item->menu_item_parent;
			}

			// Add 'current-menu-parent' class to CPT archive links when
			// viewing a singular template.
			if ( $post_type_has_archive && $post_type_archive_link == $item->url ) {
				$items[ $key ]->classes[] = 'current-menu-parent';
			}
		}

		// Add 'current-menu-parent' classes.
		$current_menu_parents = array_filter( $current_menu_parents );

		if ( ! empty( $current_menu_parents ) ) {
			foreach ( $items as $key => $item ) {
				if ( in_array( $item->ID, $current_menu_parents ) ) {
					$items[ $key ]->classes[] = 'current-menu-parent';
				}
			}
		}

		return $items;
	}

	/**
	 * Provide an edit link for archives in the admin bar.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar object instance.
	 */
	protected function admin_bar_edit_menu( $wp_admin_bar ) {
		if ( is_admin() || ! $this->plugin->has_post_type_archive() ) {
			return;
		}

		$archive_post_id  = $this->plugin->get_archive_id();
		$post_type_object = get_post_type_object( get_post_type( $archive_post_id ) );

		if ( empty( $post_type_object ) ) {
			return;
		}

		$wp_admin_bar->add_menu( array(
			'id'    => 'edit',
			'title' => $post_type_object->labels->edit_item,
			'href'  => get_edit_post_link( $archive_post_id ),
		) );
	}

	/**
	 * Allow post type features to be changed dynamically per archive.
	 *
	 * @since 3.0.0
	 */
	protected function register_archive_feature_support() {
		if ( $this->post_type != get_current_screen()->post_type ) {
			return;
		}

		$archive = $this->get_current_screen_archive();

		if ( empty( $archive) ) {
			return;
		}

		if ( ! empty( $archive->supports ) ) {
			add_post_type_support( $this->post_type, $archive->supports );
		} elseif ( isset( $archive->supports ) && false !== $archive->supports ) {
			add_post_type_support( $this->post_type, array( 'title', 'editor' ) );
		}
	}

	/**
	 * Dynamically update the archive post type's publicly queryable argument.
	 *
	 * Sets the publicly queryable argument to true to make the permalink
	 * editor visible.
	 *
	 * @see https://core.trac.wordpress.org/ticket/17609#comment:52
	 *
	 * @since 3.0.1
	 */
	protected function maybe_make_archive_viewable() {
		global $wp_post_types;

		$archive = $this->get_current_screen_archive();

		if ( empty( $archive) ) {
			return;
		}

		if ( $archive->can_customize_rewrites() ) {
			$wp_post_types['cpt_archive']->publicly_queryable = true;
		}
	}

	/**
	 * Add submenu items for archives under the post type menu item.
	 *
	 * Ensures the user has the capability to edit posts in general as well as
	 * the individual post before displaying the submenu item.
	 *
	 * @since 3.0.0
	 */
	protected function admin_menu() {
		$archives = $this->plugin->get_archives();
		if ( empty( $archives ) ) {
			return;
		}

		// Verify the user can edit archive posts.
		$post_type_object = get_post_type_object( $this->post_type );
		if ( ! current_user_can( $post_type_object->cap->edit_posts ) ) {
			return;
		}

		foreach ( $archives as $archive ) {
			// Skip if show_in_menu is false.
			if ( ! $archive->show_in_menu || ! post_type_exists( $archive->post_type ) ) {
				continue;
			}

			// Verify the user can edit the particular archive post in question.
			if ( ! current_user_can( $post_type_object->cap->edit_post, $archive->get_post_id() ) ) {
				continue;
			}

			$parent_slug = 'edit.php?post_type=' . $archive->get_post()->archive_for_post_type;
			if ( true !== $archive->show_in_menu ) {
				$parent_slug = $archive->show_in_menu;
			}

			// Add the submenu item.
			add_submenu_page(
				$parent_slug,
				$post_type_object->labels->singular_name,
				$post_type_object->labels->singular_name,
				$post_type_object->cap->edit_posts,
				add_query_arg( array( 'post' => $archive->get_post_id(), 'action' => 'edit' ), 'post.php' ),
				null
			);
		}
	}

	/**
	 * Highlight the corresponding top level and submenu items when editing an
	 * archive post.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $parent_file A parent file identifier.
	 * @return string
	 */
	protected function parent_file( $parent_file ) {
		global $post, $submenu_file;

		if (
			$post &&
			$this->post_type == get_current_screen()->id &&
			( $archive = $this->plugin->is_archive_id( $post->ID ) )
		) {
			$parent_file  = 'edit.php?post_type=' . $archive->post_type;
			$submenu_file = add_query_arg( array( 'post' => $post->ID, 'action' => 'edit' ), 'post.php' );

			if ( $archive->show_in_menu && true !== $archive->show_in_menu ) {
				$parent_file = $archive->show_in_menu;
			}
		}

		return $parent_file;
	}

	/**
	 * Archive update messages.
	 *
	 * @since 3.0.0
	 *
	 * @see /wp-admin/edit-form-advanced.php
	 *
	 * @param  array $messages The array of post update messages.
	 * @return array An array with new CPT update messages.
	 */
	protected function updated_messages( $messages ) {
		$post             = get_post();
		$post_type        = get_post_type( $post );
		$post_type_object = get_post_type_object( $post_type );

		if ( $this->post_type != $post_type ) {
			return $messages;
		}

		$messages[ $post_type ] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Archive updated.', 'cpt-archives' ),
			2  => __( 'Custom field updated.', 'cpt-archives' ),
			3  => __( 'Custom field deleted.', 'cpt-archives' ),
			4  => __( 'Archive updated.', 'cpt-archives' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Archive restored to revision from %s', 'cpt-archives' ), wp_post_revision_title( absint( $_GET['revision'] ), false ) ) : false,
			6  => __( 'Archive published.', 'cpt-archives' ),
			7  => __( 'Archive saved.', 'cpt-archives' ),
			8  => __( 'Archive submitted.', 'cpt-archives' ),
			9  => sprintf(
				__( 'Archive scheduled for: <strong>%1$s</strong>.', 'cpt-archives' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'cpt-archives' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Archive draft updated.', 'cpt-archives' ),
		);

		$permalink         = get_permalink( $post->ID );
		$preview_permalink = add_query_arg( 'preview', 'true', $permalink );
		$view_link         = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View archive', 'cpt-archives' ) );
		$preview_link      = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview archive', 'cpt-archives' ) );

		$messages[ $post_type ][1]  .= $view_link;
		$messages[ $post_type ][6]  .= $view_link;
		$messages[ $post_type ][9]  .= $view_link;
		$messages[ $post_type ][8]  .= $preview_link;
		$messages[ $post_type ][10] .= $preview_link;

		return $messages;
	}

	/**
	 * Remove the slug meta box for a CPT archive if rewrite rule customization
	 * is disabled.
	 *
	 * @since 3.0.0
	 *
	 * @param string $post_type Post type name.
	 * @param WP_Post $post Post object.
	 */
	protected function maybe_remove_slug_meta_box( $post ) {
		$archive = $this->plugin->get_archive( $post->archive_for_post_type );
		if ( ! $archive || ! $archive->can_customize_rewrites() ) {
			remove_meta_box( 'slugdiv', $this->post_type, 'normal' );
		}
	}

	/**
	 * Retrieve the archive post for the current archive screen.
	 *
	 * @since 3.0.1
	 *
	 * @return WP_Post|null
	 */
	protected function get_current_screen_archive() {
		if ( $this->post_type != get_current_screen()->post_type ) {
			return null;
		}

		if ( isset( $_GET['post'] ) ) {
			$post_id = (int) $_GET['post'];
		} elseif ( isset( $_POST['post_ID'] ) ) {
			$post_id = (int) $_POST['post_ID'];
		}

		if ( empty( $post_id ) ) {
			return null;
		}

		$post_type = get_post( $post_id )->archive_for_post_type;
		return $this->plugin->get_archive( $post_type );
	}

	/**
	 * Remove a portion of an associative array, optionally replace it and
	 * maintain the keys.
	 *
	 * @since 3.0.0
	 *
	 * @see array_splice()
	 *
	 * @param  array $input       The input array.
	 * @param  int   $offset      The position to start from.
	 * @param  int   $length      Optional. The number of elements to remove. Defaults to 0.
	 * @param  mixed $replacement Optional. Item(s) to replace removed elements.
	 * @return array The modified array.
	 */
	protected function array_asplice( $input, $offset, $length = 0, $replacement = array() ) {
		$start = array_slice( $input, 0, $offset, true );
		$end   = array_slice( $input, $offset + $length, null, true );
		return $start + $replacement + $end;
	}
}
