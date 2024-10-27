<?php

/**
 * The Article Forge Plugin Main class
 *
 * This file provides the main articleForge class which contains the
 * functionality necessary to set-up the plugin.
 *
 * $Id$
 *
 * @package ArticleForge
 *
 */


// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'ArticleForge' ) ) :
/** Load classes **/
// Core
import('ArticleForge.Constants');
import('ArticleForge.Options');
import('ArticleForge.Admin');

// Actions and Hooks
import('ArticleForge.ActionMap');

// Filters
//import('ArticleForge.Templates');
import('ArticleForge.Content');

/**
 *
 * Main Class
 *
 */

final class ArticleForge {
	private static $instance;  // plugin instance
	private $internal;         // internal working variables

	/**
	 * Main ArticleForge Instance
	 *
	 * Insures that only one instance of the plugin exists in memory.
	 *
	 * @return ArticleForge instance
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new ArticleForge;
			self::$instance->setup_fields();
			self::$instance->setup_actions();
			self::$instance->setup_filters();
		}
		return self::$instance;
	}

	/**
	 * Prevent ArticleForge from being loaded more than once.
	 *
	 */
	private function __construct() { /* Do nothing here */ }

	/**
	 * Prevent ArticleForge from being cloned
	 *
	 */
	public function __clone() { _doing_it_wrong( __FUNCTION__, __( 'Clone not allowed', 'articleforge' ), '2.1' ); }

	/**
	 * Prevent ArticleForge from being unserialized
	 *
	 */
	public function __wakeup() { _doing_it_wrong( __FUNCTION__, __( 'Wakeup not allowed', 'articleforge' ), '2.1' ); }

	/**
	 * Magic method for checking the existence of a custom field
	 *
	 */
	public function __isset( $key ) { return isset( $this->internal[$key] ); }

	/**
	 * Magic method for getting custom varible
	 *
	 */
	public function __get( $key ) {
		# add methodology to verify is a valid key
		return isset( $this->internal[$key] ) ? $this->internal[$key] : null;
	}

	/**
	 * Magic method for setting custom varible
	 *
	 */
	public function __set( $key, $value ) {
		# add methodology to verify is a valid key
		$this->internal[$key] = $value;
	}

	/**
	 * Magic method to prevent notices and errors from invalid method calls
	 *
	 */
	public function __call( $name = '', $args = array() ) {
		unset( $name, $args ); return null;
	}

	/**
	 *
	 * Set-up runtime values
	 *
	 */
	private function setup_fields() {

		/** Versioning **/
		$this->version    = af_version();
		$this->prefix     = af_namespace();

		/** Paths **/
		$this->file       = __FILE__;//WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . plugin_basename(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'ArticleForge.php';
		$this->basename   = plugin_basename( $this->file );
		$this->plugin_dir = plugin_dir_path( $this->file );
		$this->plugin_url = plugin_dir_url( $this->file );

		if (is_admin()) {
			$this->admin = new ArticleForge\Admin;
		} else {
			$this->content = new ArticleForge\Content;
		}

	}

	/**
	 *
	 * Setup the default hooks and actions
	 *
	 */
	private function setup_actions() {
		$actionMap = new ArticleForge\ActionMap;
		add_action( af_namespace() . '_upgrade',           array('ArticleForge\Install', 'upgrade' ),  0 );
		add_action( af_namespace() . '_init',              array($this, 'initialize'),                 1 );
		add_action( af_namespace() . '_register',          array($this, 'register_post_types'),        1 );
		add_action( af_namespace() . '_register',          array($this, 'register_post_statuses'),     2 );
//		add_action( $this->prefix() . '_register', array($this, 'register_taxonomies'),    3 );
//		add_action( $this->prefix() . '_register', array($this, 'register_views'),         4 );
//		add_action( $this->prefix() . '_register', array($this, 'register_shortcodes'),    5 );
		add_action( af_namespace() . '_add_rewrite_tags',  array($this, 'add_rewrite_tags'),           1 );
		add_action( af_namespace() . '_add_rewrite_rules',  array($this, 'add_rewrite_rules'),         1 );
		add_action( 'wp_loaded', array('ArticleForge\Install', 'update_rewrite_rules'),                0 );
	}

	public function setup_filters() {
//		add_filter( 'request', array( $this, 'validate_request'), 10, 1 );
	}

	public function validate_request($wp_query) {
		$query = $wp_query->query;
		$post_type = array_key_exists('post_type', $query) ? $query['post_type'] : '';
		if ( is_post_type_namespace(af_namespace(), $post_type) ) {
			if ($post_type == af_namespace() . '_' . ArticleForge\Constants::ContentPostType) {
				// Check if we have enough info to determine which Section it is
				// look for p, then look for name, otherwise 404
				if (array_key_exists('p', $query)) {
					$section = get_post($query['p']);
				} else if (array_key_exists('name', $query)) {
					$cquery = new \WP_Query(array(
							'post_type' => $post_type,
							'name'      => $query['name'],
							'post_status' => array('publish', 'draft')
					));
					if ($cquery->found_posts == 1) {
						$cquery->next_post();
						if ($cquery->post->post_status == 'draft') {
							if (is_user_logged_in() && 
								current_user_can('read', $cquery->post->ID)
								&& (array_key_exists('preview', $_GET) && ($_GET['preview'] == 'true'))) {
								$section = $cquery->post;
							}
						} else {
							$section = $cquery->post;
						}
					}
				}
				if (isset($section)) {
					$article = get_post($section->post_parent);
					if (isset($article)) {
						$url = site_url('/' . $this->options->single_slug . '/' . $article->post_name . '/' . $this->options->section_slug . '/' . $section->post_name);
						foreach ($_GET as $key => $value) {
							if (!in_array($key, array('p', 'post_type', 'name'))) {
								$url = add_query_arg(array($key => $value), $url);
							}
						}
						wp_safe_redirect($url);
						exit();
					}
				}
				$wp_query->set_404();
			} else if ($post_type == af_namespace() . '_' . ArticleForge\Constants::SummaryPostType) {
				if (array_key_exists($this->options->section_slug, $query)) {
					// get post id
					if (array_key_exists('p', $query)) {
						$post_id = $query['p'];
						$post_post = get_post($post_id);
					} else if (array_key_exists('name', $query)) {
						$pquery = new \WP_Query(array(
							'post_type' => $post_type,
							'name'      => $query['name']
						));
						if ($pquery->found_posts == 1) {
							$pquery->next_post();
							$post_post = $pquery->post;
							//$post_id = $pquery->post->ID;
						}
					}
					// if $post_post is set, we have a valid article
					if (isset($post_post)) {
						$statuses = array('publish');
						if (is_user_logged_in() && current_user_can('read', $post_post->ID)
							&& array_key_exists('preview', $_GET) && ($_GET['preview'] == 'true')) array_push($statuses, 'draft');
						// use author id and _GET to determine if we should allow draft to $statuses
						// get section from post id and section name
						$cquery = new \WP_Query(array(
							'post_type' => af_namespace() . '_' . ArticleForge\Constants::ContentPostType,
							'name'      => $query[$this->options->section_slug],
							'post_status' => $statuses,
							'parent_id' => $post_post->ID//id
						));
						// if no such section, 404
						if ($cquery->found_posts != 1) {
							$wp_query->set_404();
						}
					} else {
						$wp_query->set_404();
					}
				}
			}
		}
		if ($wp_query->is_404()) {
			remove_filter('template_redirect', 'redirect_canonical');
		}
		return !$wp_query->is_404();
	}

	/**
	 * Use the WordPress action cascade to setup the Article Forge instance
	 *
	 */
	public function initialize() {
		$this->options = ArticleForge\Options::load();
	}

	/**
	 * Setup the post types for articleForge
	 *
	 */
	public function register_post_types() {
		$post_type = array();

		/** Article Summary **
		 *
		 *  This is the main anchor for an article
		 *
		 */

		// Article labels
		$post_type['labels'] = array(
			'name'               => __( 'Articles',                   'articleforge' ),
			'menu_name'          => __( 'Articles',                   'articleforge' ),
			'singular_name'      => __( 'Article',                    'articleforge' ),
			'all_items'          => __( 'All Articles',               'articleforge' ),
			'add_new'            => __( 'New Article',                'articleforge' ),
			'add_new_item'       => __( 'Create New Article',         'articleforge' ),
			'edit'               => __( 'Edit',                       'articleforge' ),
			'edit_item'          => __( 'Edit Article',               'articleforge' ),
			'new_item'           => __( 'New Article',                'articleforge' ),
			'view'               => __( 'View Article',               'articleforge' ),
			'view_item'          => __( 'View Article',               'articleforge' ),
			'search_items'       => __( 'Search Articles',            'articleforge' ),
			'not_found'          => __( 'No articles found',          'articleforge' ),
			'not_found_in_trash' => __( 'No articles found in Trash', 'articleforge' ),
			'parent_item_colon'  => __( 'Parent Article:',            'articleforge' )
		);


		// Article rewrite
		$post_type['rewrite'] = array(
			'slug'       => $this->options->single_slug,
			'with_front' => false
		);

		// Article supports
		$post_type['supports'] = array(
			'title',
			'editor',
			'comments',
		);
		if ($this->options->enable_revisions == 'true') array_push($post_type['supports'], 'revisions');

		register_post_type(
			af_namespace() . '_' . ArticleForge\Constants::SummaryPostType,
			array(
				'labels'              => $post_type['labels'],
				'public'              => true,
				'rewrite'             => $post_type['rewrite'],
				'supports'            => $post_type['supports'],
				'taxonomies'          => array('category', 'post_tag'),
//				'capability_type'     => array( 'article', 'articles' ),
//				'map_meta_cap'        => true,
				'description'         => __( 'Article Forge Article', 'articleforge' ),
				'has_archive'         => $this->options->list_slug,
/*				'capabilities'        => //TODO: fix me
				'capability_type'     => array( 'article', 'articles' ),
				'map_meta_cap'        => true,
//				'menu_position'       => 555555,
				'exclude_from_search' => false,
				'show_in_menu'   => true,
				'public'              => true,
				'show_ui'             => true,
				'can_export'          => true,
				'hierarchical'        => false,
				'query_var'           => true,
//				'menu_icon'           => ''*/
			)
		);

		/** Content **
		 *
		 *  This is the article content for each section
		 *
		 */

		// Content labels
		$post_type['labels'] = array(
			'name'               => __( 'Contents',                   'articleforge' ),
			'menu_name'          => __( 'Contents',                   'articleforge' ),
			'singular_name'      => __( 'Section',                    'articleforge' ),
			'all_items'          => __( 'All Contents',               'articleforge' ),
			'add_new'            => __( 'New Section',                'articleforge' ),
			'add_new_item'       => __( 'Create New Section',         'articleforge' ),
			'edit'               => __( 'Edit',                       'articleforge' ),
			'edit_item'          => __( 'Edit Section',               'articleforge' ),
			'new_item'           => __( 'New Section',                'articleforge' ),
			'view'               => __( 'View',                       'articleforge' ),
			'view_item'          => __( 'View Section',               'articleforge' ),
			'search_items'       => __( 'Search Contents',            'articleforge' ),
			'not_found'          => __( 'No contents found',          'articleforge' ),
			'not_found_in_trash' => __( 'No contents found in Trash', 'articleforge' ),
			'parent_item_colon'  => __( 'Section:',                   'articleforge' )
		);

		// Content rewrite
//		$post_type['rewrite'] = false;
		$post_type['rewrite'] = array(
			'slug'       => $this->options->section_slug,
			'with_front' => false
		);

		// Content supports
		$post_type['supports'] = array(
			'title',
			'editor',
		);
		if ($this->options->enable_revisions == 'true') array_push($post_type['supports'], 'revisions');


		// Register Topic content type
		register_post_type(
			af_namespace() . '_' . ArticleForge\Constants::ContentPostType,
			array(
				'labels'              => $post_type['labels'],
				'rewrite'             => $post_type['rewrite'],
				'supports'            => $post_type['supports'],
				'description'         => __( 'Article Contents', 'articleforge' ),
//				'capabilities'        => //TODO: 
//				'capability_type'     => array( 'content', 'contents' ),
//				'map_meta_cap'        => true,
				'menu_position'       => 555555,
				'has_archive'         => false,
				'exclude_from_search' => false,
				'show_in_nav_menus'   => false,
				'show_in_menu'        => false,
				'public'              => true,
				'show_ui'             => is_admin(),
				'can_export'          => true,
				'hierarchical'        => false,
				'query_var'           => false,
				'menu_icon'           => ''
			)
		 );

		/** Comments **
		 *
		 *  This is the article comments
		 *
		 */
/*
		// Comment labels
		$post_type['labels'] = array(
			'name'               => __( 'Comments',                   'articleforge' ),
			'menu_name'          => __( 'Comments',                   'articleforge' ),
			'singular_name'      => __( 'Comment',                    'articleforge' ),
			'all_items'          => __( 'All Comments',               'articleforge' ),
			'add_new'            => __( 'New Comment',                'articleforge' ),
			'add_new_item'       => __( 'Create New Comment',         'articleforge' ),
			'edit'               => __( 'Edit',                       'articleforge' ),
			'edit_item'          => __( 'Edit Comment',               'articleforge' ),
			'new_item'           => __( 'New Comment',                'articleforge' ),
			'view'               => __( 'View Comment',               'articleforge' ),
			'view_item'          => __( 'View Comment',               'articleforge' ),
			'search_items'       => __( 'Search Comments',            'articleforge' ),
			'not_found'          => __( 'No comments found',          'articleforge' ),
			'not_found_in_trash' => __( 'No comments found in Trash', 'articleforge' ),
			'parent_item_colon'  => __( 'Comment:',                   'articleforge' )
		);

		// Comment rewrite
		$post_type['rewrite'] = array(
			'slug'       => 'comments',
			'with_front' => false
		);

		// Reply supports
		$post_type['supports'] = array(
			'title',
			'editor',
			'revisions'
		);

		// Register reply content type
		register_post_type(
			af_namespace() . '_' . ArticleForge\Constants::CommentPostType,
			array(
				'labels'              => $post_type['labels'],
				'rewrite'             => $post_type['rewrite'],
				'supports'            => $post_type['supports'],
				'description'         => __( 'ArticleForge Comments', 'articleforge' ),
//				'capabilities'        => , \\FIXME: 
				'capability_type'     => array( 'comment', 'comments' ),
				'map_meta_cap'        => true,
				'menu_position'       => 555555,
				'exclude_from_search' => false,
				'has_archive'         => false,
				'show_in_nav_menus'   => false,
				'public'              => true,
				'show_ui'             => is_admin(),
				'can_export'          => true,
				'hierarchical'        => true,
				'query_var'           => true,
				'menu_icon'           => ''
			)
		);
*/
	}

	public static function register_post_statuses() {
		// TODO:
	}


	public function add_rewrite_tags() {
		add_rewrite_tag('%' . $this->options->section_slug . '%', '([^/]+)');//'([^&]+)');
		add_rewrite_rule('^article/([^/]*)/' . $this->options->section_slug . '/([^/]*)/?','index.php?articleforge_summary=$matches[1]&' . $this->options->section_slug . '=$matches[2]','top');
	}

	public static function add_rewrite_rules() {
	}
}

endif; // class_exists check
