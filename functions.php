<?php

/**
 * Article Forge Common Functions
 *
 * Common functions to all Article Forge plugin members
 * Provides the prefix(namespace) for all WordPress actions, hooks, and tags
 *
 * $Id$
 *
 * @package ArticleForge
 *
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

import('ArticleForge.Constants');

/** articleforge_namespace **/
function af_namespace() {
	return \ArticleForge\Constants::PackagePrefix;
}

function af_version() {
	return \ArticleForge\Constants::Version;
}

function articleforge_debug($tag = '', $detail = '', $args = array()) {
	if (WP_DEBUG == true) {
		$msg = $tag . ': ';
		if( is_array( $detail ) || is_object( $detail ) ){
			$msg .= print_r( $detail, true );
			if (array_key_exists('singleline', $args) && $args['singleline']) {
				// TODO: remove \r, \n from $msg
			}
		} else {
			$msg .= $detail;
		}
		error_log($msg);
	}
}

function regwptk_get_content() {
		\ArticleForge\Content::generate();
}

function regwptk_get_context() {
	if (is_search()) return 'search';
	else if (is_single()) return 'single';
	else if (is_post_type_archive()) return 'archive';
	else if (is_home()) return 'home';
	else if (is_category()) return 'category';
}

function is_post_type_namespace( $namespace = '', $post_type = '' ) {
	global $wp_query;
	if (!$post_type) {
		if ( ! isset( $wp_query ) ) {
			_doing_it_wrong( __FUNCTION__, __( 'Conditional query tags do not work before the query is run. Before then, they always return false.' ), '3.1' );
			return false;
		}
		$post_type = $wp_query->get('post_type');
	}
	if ($pos = strpos($post_type, "_")) {
		return (strcmp(substr($post_type, 0, $pos), $namespace) == 0);
	} else {
		return false;
	}
}
