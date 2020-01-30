<?php
/**
 * The template file for displaying the comments and comment form.
 *
 * Copied from the Twenty Twenty theme.
 *
 * @package sfs411
 */

/*
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
*/
if ( post_password_required() ) {
	return;
}

if ( $comments ) {
	$comments_number = absint( get_comments_number() );
	?>

	<div class="comments" id="comments">

		<div class="comments-header">

			<h2 class="comment-reply-title">
			<?php
			if ( ! have_comments() ) {
				esc_html_e( 'Leave a comment', 'sfs411' );
			} elseif ( '1' === $comments_number ) {
				/* translators: %s: post title */
				printf( esc_html_x( 'One reply on &ldquo;%s&rdquo;', 'comments title', 'sfs411' ), esc_html( get_the_title() ) );
			} else {
				echo esc_html(
					sprintf(
						/* translators: 1: number of comments, 2: post title */
						_nx(
							'%1$s reply on &ldquo;%2$s&rdquo;',
							'%1$s replies on &ldquo;%2$s&rdquo;',
							$comments_number,
							'comments title',
							'sfs411'
						),
						esc_html( number_format_i18n( $comments_number ) ),
						esc_html( get_the_title() )
					)
				);
			}

			?>
			</h2><!-- .comments-title -->

		</div><!-- .comments-header -->

		<div class="comments-inner section-inner thin max-percentage">

			<?php
			wp_list_comments(
				array(
					'avatar_size' => 0,
					'style'       => 'div',
					'page'        => get_the_ID(),
				)
			);

			$comment_pagination = paginate_comments_links(
				array(
					'echo'      => false,
					'end_size'  => 0,
					'mid_size'  => 0,
					'next_text' => __( 'Newer Comments', 'sfs411' ) . ' <span aria-hidden="true">&rarr;</span>',
					'prev_text' => '<span aria-hidden="true">&larr;</span> ' . __( 'Older Comments', 'sfs411' ),
				)
			);

			if ( $comment_pagination ) {
				$pagination_classes = '';

				// If we're only showing the "Next" link, add a class indicating so.
				if ( false === strpos( $comment_pagination, 'prev page-numbers' ) ) {
					$pagination_classes = ' only-next';
				}
				?>

				<nav class="comments-pagination pagination<?php echo esc_attr( $pagination_classes ); ?>" aria-label="<?php esc_attr_e( 'Comments', 'sfs411' ); ?>">
					<?php echo wp_kses_post( $comment_pagination ); ?>
				</nav>

				<?php
			}
			?>

		</div><!-- .comments-inner -->

	</div><!-- comments -->

	<?php
}

if ( comments_open() ) {

	comment_form();

} elseif ( is_single() ) {

	?>

	<div class="comment-respond" id="respond">

		<p class="comments-closed"><?php esc_html_e( 'Comments are closed.', 'sfs411' ); ?></p>

	</div><!-- #respond -->

	<?php
}
