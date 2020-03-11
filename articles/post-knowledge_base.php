<?php
/**
 * Template part for displaying a Knowledge Base post's content.
 *
 * @package sfs411
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<header class="article-header">
		<hgroup>
		<?php
		if ( is_single() ) :

			get_template_part( 'parts/share-tools' );

			if ( true === spine_get_option( 'articletitle_show' ) ) :
				?>
				<h1 class="article-title"><?php the_title(); ?></h1>
				<?php
			endif;
		else :
			?>
			<h2 class="article-title">
				<a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a>
			</h2>
			<?php
		endif;
		?>
		</hgroup>
		<hgroup class="source">
			Last updated <time class="article-date" datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php echo esc_html( get_the_modified_date() ); ?></time>
			<cite class="article-author">
				<?php
				if ( '1' === spine_get_option( 'show_author_page' ) ) {
					the_author_posts_link();
				} else {
					echo esc_html( get_the_author() );
				}
				?>
			</cite>
		</hgroup>
	</header>

	<?php if ( ! is_singular() ) : ?>
		<div class="article-summary">
			<?php

			if ( spine_has_thumbnail_image() ) {
				?><figure class="article-thumbnail"><a href="<?php the_permalink(); ?>"><?php spine_the_thumbnail_image(); ?></a></figure><?php
			} elseif ( spine_has_featured_image() ) {
				?><figure class="article-thumbnail"><a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'spine-thumbnail_size' ); ?></a></figure><?php
			}

			// If a manual excerpt is available, default to that. If `<!--more-->` exists in content, default
			// to that. If an option is set specifically to display excerpts, default to that. Otherwise show
			// full content.
			if ( $post->post_excerpt ) {
				echo wp_kses_post( get_the_excerpt() ) . ' <a href="' . esc_url( get_permalink() ) . '"><span class="excerpt-more-default">&raquo; More ...</span></a>';
			} elseif ( strstr( $post->post_content, '<!--more-->' ) ) {
				the_content( '<span class="content-more-default">&raquo; More ...</span>' );
			} elseif ( 'excerpt' === spine_get_option( 'archive_content_display' ) ) {
				the_excerpt();
			} else {
				the_content();
			}

			?>
		</div><!-- .article-summary -->
	<?php else : ?>
		<div class="article-body">
			<?php

			the_content();

			wp_link_pages(
				array(
					'before' => '<div class="page-links">' . __( 'Pages:', 'spine' ),
					'after' => '</div>',
				)
			);

			?>
		</div>
	<?php endif; ?>

	<?php comments_template(); ?>

	<footer class="article-footer">

		<?php

		// Display site level categories attached to the post.
		if ( has_category() ) {
			echo '<dl class="categorized">';
			echo '<dt><span class="categorized-default">Categorized</span></dt>';
			foreach ( get_the_category() as $category ) {
				echo '<dd><a href="' . esc_url( get_category_link( $category->cat_ID ) ) . '">' . esc_html( $category->cat_name ) . '</a></dd>';
			}
			echo '</dl>';
		}

		// Display University tags attached to the post.
		if ( has_tag() ) {
			echo '<dl class="tagged">';
			echo '<dt><span class="tagged-default">Tagged</span></dt>';
			foreach ( get_the_tags() as $post_tag ) {
				echo '<dd><a href="' . esc_url( get_tag_link( $post_tag->term_id ) ) . '">' . esc_html( $post_tag->name ) . '</a></dd>';
			}
			echo '</dl>';
		}

		?>

	</footer><!-- .entry-meta -->

</article>
