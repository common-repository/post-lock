<?php
/*
 * Plugin Name: Post Lock
 * Plugin URI: http://wordpress.org/plugins/post-lock/
 * Description: Post Lock is meant to prevent accidental or premature publishing of content by requiring a user to enter a password to update or publish a post.
 * Author: George Gecewicz
 * Version: 1.0
 * Author URI: http://ggwi.cz/
 * License: GPLv2 or later
 * Textdomain: post-lock
 */

defined( 'WPINC' ) or die;

/**
 * Gets post types on which the post lock should be enabled.
 * 
 * @since 1.0
 *
 * @return array
 */
function post_lock_get_post_types() {

	$ignored = apply_filters( 'post_lock_ignored_types', array( 'attachment' ) );

	$all_types = get_post_types( array( 'public' => true ), 'objects' );

	foreach ( $ignored as $key => $slug ) {
		if ( isset( $all_types[ $slug ] ) )
			unset( $all_types[ $slug ] );
	}

	foreach ( $all_types as $post_type => $post_type_object ) {			
		$all_types[ $post_type ] = $post_type_object->labels->singular_name;
	}

	return apply_filters( 'post_lock_enabled_post_types', (array) $all_types );
}

/**
 * Checks if an item's post type has Post Lock enabled.
 * 
 * @since 1.0
 *
 * @param int $item_id Optional, will default to current global $post ID.
 * @return bool
 */
function post_lock_is_item_allowed_post_type( $item_id = 0 ) {

	$item_id = ( 0 == $item_id ? get_the_ID() : absint( $item_id ) );
	
	if ( ! $item_id ) {
		return false;
	}

	// Get just the actual post type slugs (array keys). The labels (array values) are used elsewhere.
	$post_type_slugs = array_keys( post_lock_get_post_types() );

	if ( in_array( get_post_type( $item_id ), $post_type_slugs ) ) {
		return true;
	}

	return false;
}

/**
 * Some characters are hard to read and differentiate, especially in a browser's JS prompt.
 * This function replaces such characters with more legible ones in a six-character key.
 * This screenshot is an example of the problem: https://cldup.com/xIr1YWosFK.png
 *
 * @since 1.0
 *
 * @return string
 */
function post_lock_get_key() {
	$password = wp_generate_password( 6, false );
	
	$hard_to_read   = array( 'l', 'I', '1' );
	$easier_to_read = array( 'x', '5', 'M' );

	$key = str_replace( $hard_to_read, $easier_to_read, $password );

	return apply_filters( 'post_lock_get_key', $key );
}

/**
 * Loads the markup and assets for the post lock feature on enabled post types.
 *
 * @since 1.0
 *
 * @return void
 */
function post_lock_add_post_lock() {

	$item_id = absint( get_the_ID() );

	if ( ! post_lock_is_item_allowed_post_type( $item_id ) ) {
		return;
	}

	$dir_url = plugin_dir_url( __FILE__ );

	wp_enqueue_style( 'post-lock', $dir_url . 'post-lock.css', array( 'dashicons' ) );
	wp_enqueue_script( 'post-lock-js', $dir_url . 'post-lock.js', array( 'jquery' ) );

	$key = post_lock_get_key();
	
	$post_type      = get_post_type_object( get_post_type( get_the_ID() ) );
	$post_type_name = isset( $post_type->labels->singular_name ) ? $post_type->labels->singular_name : esc_html__( 'item', 'post-lock' );

	$action = ( 'published' == get_post_status( $item_id ) ? 'publish' : 'update' );

	wp_localize_script( 'post-lock-js', 'post_lock_l10n', array(
		'strings' => array(
			'title'  => sprintf( esc_html__( 'To %1$s, simply click the lock and then confirm your intent to %1$s.', 'post-lock' ), $action ),
			'prompt' => esc_html__( sprintf( 'Enter the following key to unlock this %s so that you can %s it: %s', strtolower( $post_type_name ), $action, $key ), 'post-lock' ),
			'key'    => $key
		)
	));
}

add_action( 'post_submitbox_start', 'post_lock_add_post_lock' );