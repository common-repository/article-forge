<?php
namespace ArticleForge;

/**
 * The Article Forge Options class
 *
 * All option names and default values for runtime operation of the
 * Article Forge plugin are defined here.
 *
 * $Id$
 *
 * @package ArticleForge
 * @subpackage Options
 */


// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'ArticleForge\Options' ) ) :
/**
 *
 * Options Class
 *
 */

final class Options {

	private $hash = array(
		'list_slug' => 'articles',
		'single_slug'  => 'article',
		'section_slug' => 'section',
		'arthur_slug' => 'author',
		//'comments_slug' => 'comments',
		//'comment_slug' => 'comment',
		'prefix_list_slug' => 'true',
		'content_dir' => 'default',
		//'comments_chunk_size'
		//'[allow]_nested/heirarchical_comments',
		'summaries_per_page' => 5,
		'enable_revisions' => 'true',
		'show_on_home' => 'false'
		//'anonymous_comments'
	);

	public function __construct() { }

	public function __get( $key ) {
		if (!array_key_exists($key, $this->hash)) {
			trigger_error("Field $key does not exist for this class", E_USER_ERROR);
		}
		return $this->hash[$key];
	}

	public function __set( $key, $value ) {
		if (!array_key_exists($key, $this->hash)) {
			trigger_error("Field $key does not exist for this class", E_USER_ERROR);
		}

		$this->hash[$key] = $value;
	}

	public function names() {
		return array_keys($this->hash);
	}

	public static function defaults() {
		return new Options;
	}

	public static function load() {
		$options = new Options;
		$t_hash = get_option(af_namespace() . '_options');
		foreach ($options->hash as $key => $value) {
			if (!array_key_exists($key, $t_hash)) {
				trigger_error("Missing option ($key) in saved Plugin options", E_USER_ERROR);
			}
			$options->hash[$key] = $t_hash[$key];
		}
		return $options;
	}

	public function store() {
		add_option(af_namespace() . '_options', $this->hash);
	}

	public function update() {
		update_option(af_namespace() . '_options', $this->hash);
	}

	public function upgrade() {
		$t_hash = get_option(af_namespace() . '_options');
	}

	public static function remove() {
		delete_option(af_namespace() . '_options');
	}

}

endif; // class_exists check
