<?php

/**
 * Article List Part
 *
 * @package ArticleForge
 *
 */

import('ArticleForge.Content');

?>
<div id="content" role="main" class="articleforge_summary">

<div id="<?php echo af_namespace() . '_articles' ?>" class="articleforge-summary">

	<?php //do_action( af_namespace() . '_query_articles' ); ?>

	<?php if ( have_posts() ) : ?>

		<?php while ( have_posts() ) : the_post(); ?>

			<?php \Articleforge\Content::load( 'loop',     'article-summary'    ); ?>
			<div>
				<span style="padding: 0px 0px 0px 20px; float: left;"><?php previous_posts_link(); ?></span>
				<span style="padding: 0px 20px 0px 0px; float: right;"><?php next_posts_link(); ?></span>
			</div>

		<?php endwhile; ?>

	<?php endif; ?>

</div>

</div><!-- #content -->
