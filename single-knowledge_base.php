<?php
/**
 * Template for displaying a Knowledge Base post's layout.
 *
 * @package sfs411
 */

get_header();

do_action( 'spine_theme_template_before_main', 'single-knowledge-base.php' );

?>

<main id="wsuwp-main">

	<?php

	do_action( 'spine_theme_template_before_headers', 'single-knowledge-base.php' );

	wsuwp_spine_get_template_part( 'single-knowledge-base.php', 'parts/headers' );

	do_action( 'spine_theme_template_after_headers', 'single-knowledge-base.php' );

	do_action( 'spine_theme_template_before_content', 'single-knowledge-base.php' );

	if ( spine_has_featured_image() && 'page' !== get_post_type() ) {
		$featured_image_src = spine_get_featured_image_src();
		$featured_image_position = get_post_meta( get_the_ID(), '_featured_image_position', true );

		if ( ! $featured_image_position || sanitize_html_class( $featured_image_position ) !== $featured_image_position ) {
			$featured_image_position = '';
		}
		?><figure class="featured-image <?php echo sanitize_html_class( $featured_image_position ); ?>" style="background-image: url('<?php echo esc_url( $featured_image_src ); ?>');"><?php spine_the_featured_image(); ?></figure><?php
	}

	?>

	<section class="row side-right gutter pad-ends">

		<div class="column one">

			<?php while ( have_posts() ) : the_post(); ?>

				<?php get_template_part( 'articles/post', get_post_type() ) ?>

			<?php endwhile; ?>

		</div><!--/column-->

		<div class="column two"></div><!--/column two-->

	</section>

	<footer class="main-footer">
		<section class="row halves pager prevnext gutter pad-ends">
			<div class="column one">
				<?php previous_post_link(); ?>
			</div>
			<div class="column two">
				<?php next_post_link(); ?>
			</div>
		</section><!--pager-->
	</footer>

	<?php

	do_action( 'spine_theme_template_after_content', 'single-knowledge-base.php' );

	do_action( 'spine_theme_template_before_footer', 'single-knowledge-base.php' );

	wsuwp_spine_get_template_part( 'single-knowledge-base.php', 'parts/footers' );

	do_action( 'spine_theme_template_after_footer', 'single-knowledge-base.php' );

	?>

</main><!--/#page-->

<?php

do_action( 'spine_theme_template_after_main', 'single-knowledge-base.php' );

get_footer();
