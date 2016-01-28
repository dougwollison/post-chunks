<?php
/*
Plugin Name: Post Chunks
Plugin URI: https://github.com/dougwollison/post-chunks
Description: Mark separations in WordPress post content; the resulting "chunks" can then be inserted into different parts of a page template.
Version: 1.0.0
Author: Doug Wollison
Author URI: http://dougw.me
Tags: post chunks, post sections, post parts
License: GPL2
Text Domain: post-chunks
*/

/**
 * Utility for splitting the content into chunks.
 *
 * @since 1.0.0
 *
 * @param string $content   The content to split.
 * @param string $separator The separator to split at.
 *
 * @return array The resulting chunks.
 */
function get_content_chunks( $content, $separator ) {
	// Escape the separator to make sure it works in a regex
	$separator_quoted = preg_quote( $separator, '/' );

	// Move closing tags after a separator to before it, prevents broken code
	$content = preg_replace( '/(' . $separator_quoted . ')((?:\s*<\/\w+>\s*)+)/', '$2$1', $content );

	// Create the chunks
	$chunks = explode( $separator, $content );

	return $chunks;
}

/**
 * Adds new property to $post object with chopped up version of the post.
 *
 * @since 1.0.0
 *
 * @param object $post The post to be chopped up.
 */
function postchunks_process_post( $post ) {
	// Abort if $post isn't an object or if the chunks have already been handled
	if ( ! is_object( $post ) || property_exists( $post, 'chunks' ) ) {
		return;
	}

	// The default separator is the more tag
	$sep = '<!--more-->';

	/**
	 * Filter the chunk separator string.
	 *
	 * @since 1.8.0
	 *
	 * @param string  $sep  The separator to filter.
	 * @param WP_Post $post The post object being used.
	 */
	$sep = apply_filters( 'postchunks_separator', $sep, $post );

	// Get the chunks
	$post->chunks = get_content_chunks( $post->post_content, $sep );

	// Store the default chunk number for looping
	$post->chunk = 1;
}
add_action( 'the_post', 'postchunks_process_post' );

/**
 * Return a specified chunk
 *
 * @since 1.0.0
 *
 * @global WP_Post $post The current post object.
 *
 * @param int  $i      Optional The number of the chunk, 1-indexed.
 * @param bool $filter Optional Wether or not to apply filters to the chunk.
 *
 * @return string The content chunk.
 */
function get_chunk( $i = null, $filter = 'the_content' ) {
	global $post;

	if ( is_null( $i ) ) {
		$i = $post->chunk;
		$post->chunk++;
	}

	$chunk = $post->chunks[ $i - 1 ];

	if ( $filter ) {
		$chunk = apply_filters( $filter, $chunk ) ;
	}

	return $chunk;
}

/**
 * Prints out a specified chunk.
 *
 * @since 1.0.0
 *
 * @global WP_Post $post The current post object.
 *
 * @param int $i Optional The number of the chunk, 1-indexed.
 *
 * @return string The processed content chunk.
 */
function the_chunk( $i = null ) {
	global $post;
	echo get_chunk( $i );
}

/**
 * Return wether or not there are still chunks to retrieve.
 *
 * @since 1.0.0
 *
 * @global WP_Post $post The current post object.
 *
 * @return bool Wether or not there are still chunks to retrieve.
 */
function have_chunks() {
	global $post;
	return $post->chunk <= count( $post->chunks );
}
