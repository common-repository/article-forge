<?php
namespace ArticleForge\Content;

/**
 * Article Forge Content Action Map
 *
 * This class sets up the mapping between WordPress Content actions and ArticleForge
 * Content actions.
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
 * Content ActionMap Class
 *
 */

final class ActionMap {

	public function __construct() {
		$this->setup_map();
	}

	public function setup_map() {
		add_action( 'pre_get_posts',      array( $this, 'pre_get_posts'   ),   0   );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ),   0   );
	}

	public static function pre_get_posts( $wp_query ) {
		if ( is_admin() || ! $wp_query->is_main_query() || $wp_query->get( 'suppress_filters' ) )
			return;

		if ( is_home() ) {
			if ( articleforge()->options->show_on_home == 'true' )
				$wp_query->set('post_type', af_namespace() . '_' . \ArticleForge\Constants::SummaryPostType);
		} elseif( is_category() || is_tag() ) {
		    	$post_type = get_query_var('post_type');
				if(!$post_type) {
		    		$post_type = $wp_query->get('post_type');
		    		if (is_array($post_type)) {
		    			array_push($post_type, af_namespace() . '_' . \ArticleForge\Constants::SummaryPostType);
		    		} else {
		    			$post_type = array($post_type, af_namespace() . '_' . \ArticleForge\Constants::SummaryPostType);
		    		}
		    		$wp_query->set('post_type', $post_type);
		    	}
		    	return;
		}
		// should just do is_post_type_namespace(af_namespace())
		if ( is_post_type_namespace(af_namespace()) ) {
			if (!articleforge()->validate_request($wp_query)) return;
			do_action( af_namespace() . '_pre_get_posts', $wp_query );
		}
	}

	public static function enqueue_scripts() {
		do_action( af_namespace() . '_enqueue_scripts' );
	}

	public static function request( $query ) {
		do_a( af_namespace() . '_request', $query );
	}
}
