<?php
namespace ArticleForge\Admin;

/**
 * The Article Forge Admin Action Map
 *
 * This class sets up the mapping between WordPress Admin actions and ArticleForge
 * Admin actions.
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
 * Admin ActionMap Class
 *
 */

final class ActionMap {

	public function __construct() {
		$this->setup_map();
	}

	public function setup_map() {
		add_action( 'admin_init',            array( $this, 'admin_init' ),          0     );
		add_action( 'admin_menu',            array( $this, 'admin_menu' ),          0     );
		add_action( 'add_meta_boxes',        array( $this, 'add_meta_boxes' ),      0     );
		add_action( 'save_post',             array( $this, 'save_post' ),           0     );
		add_action( 'trashed_post',          array( $this, 'trashed_post'),         0     );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ),     0    );
		add_action( 'load-post-new.php',     array( $this, 'new_post' ),            0     );
		add_action( 'admin_notices',         array( $this, 'admin_notices'),        0     );
	}

	public static function admin_init() {
		do_action( af_namespace() . '_admin_init' );
	}

	public static function admin_menu() {
		do_action( af_namespace() . '_admin_menu' );
	}

	public static function admin_notices() {
		do_action( af_namespace() . '_admin_notices' );
	}

	public static function add_meta_boxes() {
		do_action( af_namespace() . '_add_meta_boxes' );
	}

	public static function new_post() {
		if ( array_key_exists('post_type', $_GET) ) {
			$post_type = $_GET['post_type'];
			if ( is_post_type_namespace(af_namespace(), $post_type) ) {
				if (strcmp($post_type, af_namespace() . '_' . \ArticleForge\Constants::SummaryPostType) == 0) {
					do_action( af_namespace() . '_new_article' );
				} else if (strcmp($post_type, af_namespace() . '_' . \ArticleForge\Constants::ContentPostType) == 0) {
					do_action( af_namespace() . '_new_content' );
				} else if (strcmp($post_type, af_namespace() . '_' . \ArticleForge\Constants::CommentPostType) == 0) {
					do_action( af_namespace() . '_new_comment' );
				}
			}
		}
	}

	public static function save_post($post_id) {
		// Distinguish between actual post updates and post creation
		// Consider $this->new_post($post_id)
		if (!isset($_POST['post_type'])) return;
		$post_type = get_post_type( $post_id );

		if (!is_post_type_namespace(af_namespace(), $post_type)) return;

		if (strcmp($post_type, af_namespace() . '_' . \ArticleForge\Constants::SummaryPostType) == 0) {
			do_action( af_namespace() . '_updated_article', $post_id );
		} else if (strcmp($post_type, af_namespace() . '_' . \ArticleForge\Constants::ContentPostType) == 0) {
			do_action( af_namespace() . '_updated_content', $post_id );
		} else if (strcmp($post_type, af_namespace() . '_' . \ArticleForge\Constants::CommentPostType) == 0) {
			do_action( af_namespace() . '_save_comment', $post_id );
		}
/*		// $contentType = articleforge()->getRegisteredType(substr($post_type, strlen(af_namespace()));
		$contentType = ContentType.getContent($post_id);
		if (!$contentType) return;
		$contentType->save();*/
	}

	public static function trashed_post($post_id) {
		$post_type = get_post_type( $post_id );

		if (!is_post_type_namespace(af_namespace(), $post_type)) return;

		if (strcmp($post_type, af_namespace() . '_' . \ArticleForge\Constants::SummaryPostType) == 0) {
			do_action( af_namespace() . '_trashed_article', $post_id );
		} else if (strcmp($post_type, af_namespace() . '_' . \ArticleForge\Constants::ContentPostType) == 0) {
			do_action( af_namespace() . '_trashed_content', $post_id );
		} else if (strcmp($post_type, af_namespace() . '_' . \ArticleForge\Constants::CommentPostType) == 0) {
			do_action( af_namespace() . '_trashed_comment', $post_id );
		}
/*		$global $registry;
		$contentHandler = $registry->getContentHandler($namespace, $type);
		if (!$contentHandlder) raise new UnregisteredTypeException($type);
		$contentHandler->delete($contentHandler->retrieve($post_id)
		$registry->getContentHandler($id); or
		$registry->getContentData($id);*/
	}

	public static function admin_enqueue_scripts($hook) {
		global $post;
//		if (strncmp($post->post_type, af_namespace(), strlen(af_namespace())) == 0) {
			if (($hook == 'post.php') && ($_GET['action'] == 'edit')) {
				$hook = 'edit_page'; // no need to add namespace
			} else if ($hook == 'post-new.php') {
				$hook = 'new_page';
			} else if ($hook == 'edit.php') {
				$hook = 'all_page'; // manage_page, list_page
			}
//		}
		do_action( af_namespace() . '_admin_enqueue_scripts', $hook );
	}

}
