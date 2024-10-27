<?php
namespace ArticleForge;

/**
 * The Article Forge Install 'class'
 *
 * This class provides the functions for activating, deactivating,
 * uninstalling, and updating the plugin.  It is non instatiatable
 * and should be used in a static context.
 *
 * $Id$
 *
 * @package ArticleForge
 * @subpackage Install
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

import('ArticleForge.Options');

final class Install {

	/**
	 * Prevent Install from being instatiated ever
	 *
	 */
	private function __construct() { trigger_error("Attempting to instantiate a static class", E_USER_ERROR); }

	/**
	 * Prevent Install from being cloned
	 *
	 */
	public function __clone() { trigger_error("Attempting to clone a static class", E_USER_ERROR); }


	/** Uses update instead of add to prevent overriding existing options **/
	public static function activate() {
		// Validate PHP version, TODO: validate WP version
		// TODO: Clean this up
		if ( version_compare(PHP_VERSION, '5.3.0', '<') ) {
			deactivate_plugins( plugin_basename(dirname(dirname(__FILE__))) . '/main.php', true );
		    wp_die("Article Forge requires at least PHP v5.3.0; you're version: " . PHP_VERSION . "   " .
		    	"<a href='" . admin_url('plugins.php') . "'>Return to plugins page</a>");
		}
	}

	public static function deactivate() {
		// Needs to be a better system in place to handle this
		// Delete the 'blogs' rewrite rules, so a rebuild happens on next page load
		delete_option( 'rewrite_rules' );
		delete_option(af_namespace() . '_rewrite_rules');
	}

	public static function uninstall() {
		Options::remove();
		delete_option(af_namespace() . '_ver');
		if (get_option(af_namespace() . '_rewrite_rules')) {
			// Needs to be a better system in place to handle this
			// Delete the 'blogs' rewrite rules, so a rebuild happens on next page load
			delete_option( 'rewrite_rules' );
			delete_option(af_namespace() . '_rewrite_rules');
		}
	}

	public static function upgrade() {
		$db_version = get_option(af_namespace() . '_ver', '');
		if (strcmp($db_version, af_version()) == 0) {
			return;
		} //else if (strcmp($dbversion, 'm.n.a') > 0) {...
		$options = Options::defaults();
		$current_options = get_option(af_namespace() . '_options');
		if (!is_array($current_options)) $current_options = array ( );
		foreach ($options->names() as $key) {
			if (array_key_exists($key, $current_options)) {
				$options->$key = $current_options[$key];
			}
		}
		$options->update();
		update_option(af_namespace() . '_ver', af_version());
		// updates are not common, assume the rewrite rules need to be flushed
		// mark for flush rewrite rules on next load by deleting option
		delete_option(af_namespace() . '_rewrite_rules');
	}

	public static function update_rewrite_rules() {
		if (!get_option(af_namespace() . '_rewrite_rules')) {
			flush_rewrite_rules();
			add_option(af_namespace() . '_rewrite_rules', 'current');
		}
	}
}




