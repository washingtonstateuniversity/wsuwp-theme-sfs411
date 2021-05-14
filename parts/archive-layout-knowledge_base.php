<section class="row side-right gutter pad-ends">

	<div class="column one">

		<?php

		if ( is_category() ) {
			?>
			<h1>Knowledge base: <?php single_cat_title( '', true ); ?></h1>
			<?php
		} else {
			?>
			<h1>Knowledge base</h1>
			<?php
		}

		?>
		<input id="kb-filter-archive" type="text" placeholder="Type to filter" />
		<?php

		while ( have_posts() ) : the_post(); ?>

		<article id="post-<?php the_ID(); ?>" <?php post_class( 'kb-item' ); ?> data-title="<?php echo esc_attr( strtolower( get_the_title() ) ); ?>">
			<header class="article-header">
				<hgroup>
					<h2 class="article-title">
						<a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a>
					</h2>
				</hgroup>
				<hgroup class="source">
					Last updated <time class="article-date" datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php echo esc_html( get_the_modified_date() ); ?></time>
				</hgroup>
			</header>
		</article>

		<?php endwhile; ?>

	</div><!--/column-->

	<div class="column two">

		<?php get_sidebar(); ?>

	</div><!--/column two-->

</section>
