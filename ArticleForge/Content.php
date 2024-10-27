<?php
namespace ArticleForge;

import('ArticleForge.Constants');
import('ArticleForge.Content.ActionMap');

/**
 * Article Forge Content Class
 *
 * This class is responsible for producing and controlling content specific
 * to the ArticleForge plugin
 *
 * $Id$
 *
 * @package ArticleForge
 *
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

final class Content {

	public function __construct() {
		$this->setup_actions();
//		$this->setup_filters();
	}

	public function setup_actions() {
		new \ArticleForge\Content\ActionMap;
		add_action( af_namespace() . '_pre_get_posts',     array( $this, 'pre_get_posts'    ),     1 );
		add_action( af_namespace() . '_enqueue_scripts',   array( $this, 'enqueue_scripts'  ),     1 );
	}

	public function pre_get_posts( $query = '' ) {
		// When previewing sections, WP does not validate the post id but blindly assumes it's for the main query
		// Turn it off here but reactivate when rendering sections
 		if ( is_preview() && $query->get('section') ) {
			//if (( $query->get('post_type') == af_namespace() . '_' . \ArticleForge\Constants::SummyPostType) &&
			$query->is_preview = 0;
		}
		/*if ( is_home() ) {
			$query->set( 'posts_per_page', 1 );
		}*/
		if ( is_post_type_archive() ) {
			$query->set( 'posts_per_page', articleforge()->options->summaries_per_page );
		}
	}

	private function get_files($directory = '', $extension = '') {
		$files = array();

		if ( ! is_dir( $directory ) )
			return;
		if ( $dir = @ opendir( $directory ) ) {
			while ( ( $file = readdir( $dir ) ) !== false ) {
				if ( substr( $file, -strlen($extension) ) == $extension )
					array_push($files, $file);
			}
		} else return;
		return $files;
	}

	public function enqueue_scripts() {
		$css_files = $this->get_files(articleforge()->plugin_dir . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . articleforge()->options->content_dir . DIRECTORY_SEPARATOR . 'css', '.css');
		if (isset($css_files))
		foreach ($css_files as $index => $file) {
			wp_enqueue_style( af_namespace() . '_style_' . $index, plugins_url( '/content/' . articleforge()->options->content_dir . '/css/' . $file, articleforge()->file ));
		}
		$js_files = $this->get_files(articleforge()->plugin_dir . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . articleforge()->options->content_dir . DIRECTORY_SEPARATOR . 'js', '.js');
		if (isset($js_files))
		foreach ($js_files as $index => $file) {
			wp_enqueue_script( af_namespace() . '_script_' . $index, plugins_url( '/content/' . articleforge()->options->content_dir . '/js/' . $file, articleforge()->file ));
		}
	}

	public static function load( $target = '', $type = '' ) {
		$content_dir = articleforge()->options->content_dir;
		load_template(articleforge()->plugin_dir . implode(DIRECTORY_SEPARATOR, array('content', $content_dir, $target . '-' . $type . '.php')), false);
	}

	public static function generate() {
		//global $register;
		$post_type = get_post_type();
		$context = regwptk_get_context();
		$exp_post_type = explode('_', get_post_type());
		$namespace = array_shift($exp_post_type);
		//if $typeHandler = $register->getTypeHandler(get_post_type()) or $register->getType/ContentGenerator(...)
		if (strcmp($namespace, af_namespace()) == 0) {
			\ArticleForge\Content::load($context, implode('_', $exp_post_type));
			return;
		}
		// fall back to the wordpress theme if post_type is not articleforge or not registered
		get_template_part($context, $post_type);
	}

}
