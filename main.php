<?php

/**
 * Plugin Name: Article Forge
 * Plugin URI:  http://wordpress.org/plugins/article-forge/
 * Description: Aritcle Forge provides a creative and conducive environment for writing and publishing various heirarchical texts
 * Author:      Anthony Wells
 * Author URI:  http://www.bytewisemcu.org/profile/awells
 * Version:     1.1.4
 * Text Domain: articleforge
 * Domain Path: /languages/
 */

/**
 * The ArticleForge Plugin
 *
 * This file provides the environmental set-up and main method to introduce
 * the plugin into the WordPress system.
 *
 * $Id$
 *
 * @package ArticleForge
 *
 */


// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// Set-up class_path superglobal variable using php includ_path as basis
$GLOBALS['class_path'] = array();
foreach (explode(PATH_SEPARATOR, get_include_path()) as $path) {
	if ($path == '.') { // realpath wrongly reports '.'in some cases, use this file as the basis for '.' instead
		$path = __DIR__;
	}
	array_push( $GLOBALS['class_path'], realpath($path) );
}

function import($package = '') {
	if ($package == '') {
		trigger_error("Package argument must be specified.", E_USER_ERROR);
	}
	$package_bits = explode('.', $package);
	// use explode to make this more robust
	// also check for '*' for directory imports
	//$package_path =  str_replace('.', DIRECTORY_SEPARATOR, $package) . '.php';
	$package_path = implode(DIRECTORY_SEPARATOR, $package_bits) . '.php';
	foreach ($GLOBALS['class_path'] as $path) {
		$file = $path . DIRECTORY_SEPARATOR . $package_path;//combinepath($path, $package_path))
		if (file_exists($file)) {
 			require_once($file);
 			if (!class_exists(implode('\\', $package_bits))) {
 				trigger_error("Class '" . $package . "' not found in file '" . $package_path . "'.", E_USER_ERROR);
 			}
			return;
		}
	}
	trigger_error("'" . $package . "' not found.", E_USER_ERROR);
}

// Common
require( 'functions.php' );
//update_option(af_namespace() . '_options', array());
import('ArticleForge.Install');
import('ArticleForge');

// Do activate/deactivate/uninstall here
register_activation_hook(   __FILE__, array( 'ArticleForge\Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ArticleForge\Install', 'deactivate' ) );
register_uninstall_hook(    __FILE__, array( 'ArticleForge\Install', 'uninstall' ) );

function articleforge() {
	return ArticleForge::instance();
}

// Let's do it!
articleforge();
