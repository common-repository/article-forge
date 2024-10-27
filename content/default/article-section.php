<?php
	global $content;
?>
<div id="<?php echo $content->post_name; ?>" class="articleforge-section">
	<div class="articleforge-section-title">
		<?php echo $content->post_title; ?>
	</div>
	<div class="articleforge-section-content">
		<?php echo apply_filters('the_content', $content->post_content); ?>
	</div>
</div>
