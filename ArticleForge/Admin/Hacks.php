<?php
namespace ArticleForge\Admin;

/**
 * Article Forge Admin Hacks
 *
 * This class provides hacks to fix issues with new Article sections
 *
 * $Id$
 *
 * @package ArticleForge
 *
 */


// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

import('ArticleForge.Constants');

/**
 *
 * Admin Hacks Class
 *
 */

final class Hacks {

	public function __construct() {
		$this->setup_actions();
	}

	public function setup_actions() {
		add_action( 'wp_insert_post',        array( $this, 'insert_post' ),         0     );
		add_action( 'auto-draft_to_draft',   array( $this, 'save_new_post'),        0     );
	}

	public static function insert_post($post_id) {
		// ArticleForge doesn't populate post_name for draft articles which prevents unauthorized viewing of these articles.
		// Populate post_name to allow previewing of draft articles.
		global $wpdb;
		$post_type = get_post_type( $post_id );
		if (!is_post_type_namespace(af_namespace(), $post_type)) return;
		if ( (strcmp($post_type, af_namespace() . '_' . \ArticleForge\Constants::SummaryPostType) == 0) ||
			 (strcmp($post_type, af_namespace() . '_' . \ArticleForge\Constants::ContentPostType) == 0) ) {
			$article = get_post($post_id);
			if ( empty($article->post_name) && in_array( $article->post_status, array( 'draft' ) ) ) {
				$article->post_name = sanitize_title($article->post_title, $article->ID);
				$wpdb->update( $wpdb->posts, array( 'post_name' => $article->post_name ), array( 'ID' => $article->ID ) );
			}
		}
	}

	public static function save_new_post($post) {
		// Menu order doesn't get saved during the initial update of the post from auto-draft to draft.
		// Populate menu_order initially to prevent erroneous section data and improper updating of
		// menu order on content save.
		global $wpdb;
		if (!is_post_type_namespace(af_namespace(), $post->post_type)) return;
		if (strcmp($post->post_type, af_namespace() . '_' . \ArticleForge\Constants::ContentPostType) == 0) {
			if ($post->menu_order == 0) {
				$menu_order = $wpdb->get_var($wpdb->prepare("SELECT IFNULL(MAX(menu_order), 0) + 1 AS menu_order FROM $wpdb->posts WHERE post_parent = %d AND post_type = %s", $post->post_parent, af_namespace() . '_' . \ArticleForge\Constants::ContentPostType));
				$post->menu_order = $menu_order;
				$wpdb->update( $wpdb->posts, array( 'menu_order' => $post->menu_order ), array( 'ID' => $post->ID ) );
			}
		}
	}

}
