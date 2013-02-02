<?php
if ( ! function_exists( 'get_post_type_archive_post' ) ) :
/**
 * Get the archive post for a post type.
 *
 * @since 1.0.0
 *
 * @param string $post_type Post type name.
 * @return WP_Post The archive post or null if one hasn't been set.
 */
function get_post_type_archive_post( $post_type ) {
	$archives = get_option( 'cpt_archives' );
	return ( empty( $archives[ $post_type ] ) ) ? null : get_post( $archives[ $post_type ] );
}
endif;

if ( ! function_exists( 'get_post_type_archive_description' ) ) :
/**
 * Display a post type archive description.
 *
 * @since 1.0.0
 *
 * @param string $before Content to display before the description.
 * @param string $after Content to display after the description.
 */
function get_post_type_archive_description( $post_type = null ) {
	if ( empty( $post_type ) && ! is_post_type_archive() ) {
		return '';
	}

	$post_type = ( empty( $post_type ) ) ? get_queried_object()->name : $post_type;
	$post = get_post_type_archive_post( $post_type );

	if ( ! $post || empty( $post->post_content ) ) {
		return '';
	}

	return $post->post_content;
}
endif;

if ( ! function_exists( 'post_type_archive_description' ) ) :
/**
 * Display a post type archive description.
 *
 * @since 1.0.0
 *
 * @param string $before Content to display before the description.
 * @param string $after Content to display after the description.
 */
function post_type_archive_description( $before = '', $after = '' ) {
	$description = get_post_type_archive_description();

	if ( ! empty( $description ) ) {
		echo $before . apply_filters( 'the_content', $description ) . $after;
	}
}
endif;
