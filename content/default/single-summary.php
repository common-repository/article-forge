<?php

/**
 * Single Article Main Content
 *
 * @package articleforge
 *
 */

import('ArticleForge.Constants');
import('ArticleForge.Content');

$section = get_query_var(articleforge()->options->section_slug);
$preview = array_key_exists('preview', $_GET) && ($_GET['preview'] == 'true') &&
	is_user_logged_in() && current_user_can('read', $post->ID);

// Let's default to showing the first section if no section or invalid section
// if section is specified, follow article options unless showtocalways is false and showallsections is true
the_post();

$has_section = $section ? true : false;
$always_show_toc = get_post_meta( $post->ID, af_namespace() . '_always_show_toc', 'false' ) == 'true' ? true : false;
$show_all_sections = (get_post_meta( $post->ID, af_namespace() . '_show_all_sections', 'false' ) == 'true') ||
	(array_key_exists('show_all_sections', $_GET) && ($_GET['show_all_sections'] == 'true'));
$show_toc = (!$has_section && $show_all_sections && $always_show_toc) ||
(!$has_section && !$show_all_sections) || 
($has_section && $always_show_toc);
$show_all_sections = !$has_section && $show_all_sections;

function af_check_preview($post_id = 0) {
	if ( isset($_GET['preview_id']) && isset($_GET['preview_nonce']) ) {
		$id = (int) $_GET['preview_id'];
		if ($id != $post_id) return false;

		if ( false == wp_verify_nonce( $_GET['preview_nonce'], 'post_preview_' . $id ) )
			wp_die( __('You do not have permission to preview drafts.') );
		return true;
	}
	return false;
}

function af_section_url($content, $preview) {
	$url = get_permalink() . articleforge()->options->section_slug . "/" . $content->post_name;
	if ($preview) {
		$url = add_query_arg(array( 'preview' => 'true' ), $url);
	}
	return $url;
}

$content = null;
global $content;
?>

<div id="content" role="main" class="articleforge_section">
	<div class="articleforge-heading">
		<div class="articleforge-heading-info">
		<?php the_date(); ?> by <a href="<?php echo site_url(articleforge()->options->arthur_slug) . '/' . get_the_author_meta('user_nicename'); ?>"><?php the_author() ?></a>
		</div>
		<div class="articleforge-heading-title">
		<?php the_title(); ?>
		</div>
		<div class="articleforge-heading-summary">
		<p><?php the_content(); /*echo apply_filters('the_content', $post->post_content)*/?></p>
		</div>
	</div>
<?php
	$statuses = array('publish');
	if ($preview) array_push($statuses, 'draft');
	// Don't tromp on WordPress globals
	$contents = new \WP_Query(
		array(
			'post_parent' => $post->ID,
			'post_type'   => af_namespace() . '_' . \ArticleForge\Constants::ContentPostType,
			'post_status' => $statuses,
			'posts_per_page' => -1,
			'orderby'     => 'menu_order',
			'order'       => 'ASC'
		)
	);
	if ($show_toc && ($contents->found_posts != 0)) {
?>
<div class="articleforge-toc">
	<div class="articleforge-toc-header">Table of Contents</div>
	<ul>
<?php
	while ( $contents->have_posts() ) {
		$content = $contents->next_post();
?>
	<div class="articleforge-toc-title">
		<li><a href="<?php echo $show_all_sections ? '#' . $content->post_name :  af_section_url($content, $preview) ?>"><?php echo $content->post_title; ?></a></li>
	</div>
<?php
	}
?>
	</ul>
</div>
<?php
	}

	$show_first_section = !$show_all_sections && !$has_section;
	$contents->rewind_posts(); $content = null; $last_content;
	while ( $contents->have_posts() ) {
		$last_content = $content;
		$content = $contents->next_post(); //$content = $contents->post;
		if ($show_first_section) {
			\ArticleForge\Content::load('article', 'section');
			break;
		} else if ($show_all_sections) {
			\ArticleForge\Content::load('article', 'section');
		} else if ($has_section) {
			if ($content->post_name == $section) {
				?><input type="hidden" id="section_name" name="section_name" value="<?php echo $section; ?>"/><?php
					if ($preview &&
					((($content->post_status == 'publish') && af_check_preview($content->ID)) || ($content->status == 'draft')))
						$content = _set_preview($content);
				\ArticleForge\Content::load('article', 'section');
				break;
			}
		}
	}
	// output navigation (if necessary)
	if (!$show_all_sections) {
?>
	<div class="articleforge-nav">
<?php
		if (isset($last_content)) {
			$content = $last_content;
?>
		<div class="articleforge-nav-previous">
			<a href="<?php echo af_section_url($content, $preview); ?>"><h3><< <?php echo $content->post_title; ?></h3></a>
		</div>
<?php
		}
	// move forward one
		if ($contents->have_posts()) {
			$content = $contents->next_post();
?>
		<div class="articleforge-nav-next">
			<a href="<?php echo af_section_url($content, $preview); ?>"><h3><?php echo $content->post_title; ?> >></h3></a>
		</div>
<?php
		}
?>
	</div>
<?php
	}
?>
<?php comments_template(); ?>
</div><!-- #content -->
<?php
?>
