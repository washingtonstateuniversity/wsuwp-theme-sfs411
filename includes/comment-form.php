<?php
/**
 * Handling for customizations to the comment form.
 *
 * @package sfs411
 */

namespace SFS411\Comment\Form;

add_action( 'after_setup_theme', __NAMESPACE__ . '\register_html5_support' );
add_filter( 'comment_form_fields', __NAMESPACE__ . '\filter_comment_form_fields', 10 );
add_filter( 'preprocess_comment', __NAMESPACE__ . '\preprocess_comment_data', 10 );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_comment_reply_script' );

/**
 * Registers HTML5 support for the comment form and comments output.
 */
function register_html5_support() {
	add_theme_support( 'html5', array(
		'comment-form',
		'comment-list',
	) );
}

/**
 * Add a checkbox to the comment form for flagging posts with bad content.
 *
 * @param array $comment_fields A list of comment fields to output when capturing a comment.
 * @return array $comment_fields A modified list of comment fields.
 */
function filter_comment_form_fields( $comment_fields ) {
	if ( 'knowledge_base' === get_post_type() && is_user_logged_in() ) {

		// Add a checkbox for flagging that the comment is about bad content on the post.
		$flag_checkbox = '<p class="comment-form-flag"><input type="checkbox" id="content-flag" name="content_flag" /><label for="content-flag">This post has old, missing, or incorrect content</label></p>';

		$comment_fields['comment'] = $comment_fields['comment'] . $flag_checkbox;
	}

	return $comment_fields;
}

/**
 * If the comment is about bad content, save it as a `flagged_content` type.
 *
 * @param array $commentdata Submitted comment data.
 * @return array $commentdata Modified comment data.
 */
function preprocess_comment_data( $commentdata ) {

	// Nonce verification is not necessary for the comment form.
	if ( is_user_logged_in() && isset( $_POST['content_flag'] ) ) { // phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification
		$commentdata['comment_type'] = 'flagged_content';
	}

	return $commentdata;
}

/**
 * Enqueues the comment reply script if appropriate.
 */
function enqueue_comment_reply_script() {
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
