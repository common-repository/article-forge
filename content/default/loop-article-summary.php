<?php
?>
<div class="articleforge_summary-summary">
	<div class="articleforge_summary-title">
		<a href="<?php the_permalink(); ?>"><div class="articleforge_summary-title-text"><?php the_title(); ?></div></a>
	</div>
	<div class="articleforge_summary-excerpt">
		<?php the_excerpt(); ?>
	</div>
	<div class="articleforge_summary-info">
		Posted on <?php the_date(); ?> by <a href="<?php echo site_url(articleforge()->options->arthur_slug) . '/' .get_the_author_meta('user_nicename'); ?>"><?php the_author() ?></a>
	</div>
</div>