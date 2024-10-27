<?php
namespace ArticleForge;

/**
 * The Article Forge Action Map
 *
 * This class sets up the mapping between WordPress actions and ArticleForge
 * actions.
 *
 * $Id$
 *
 * @package ArticleForge
 * 
 */


// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 *
 * ActionMap Class
 *
 */

final class ActionMap {

	/**
	 * 
	 *
	 */
	public function __construct() {
		$this->setup_map();
	}

	public function setup_map() {
		add_action( 'init', array( $this, 'init' ),                     0     );
	}

	public static function init() {
		do_action( af_namespace() . '_upgrade'  );
		do_action( af_namespace() . '_init'     );
		do_action( af_namespace() . '_register' );
		do_action( af_namespace() . '_add_rewrite_tags'  );
//		do_action( af_namespace() . '_add_rewrite_rules' );
//		do_action( af_namespace() . '_ready'    );
	}

}
