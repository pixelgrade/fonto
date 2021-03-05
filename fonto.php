<?php
/**
 * Plugin Name: Fonto - Web Fonts Manager
 * Version: 1.1.1
 * Plugin URI: https://pixelgrade.com
 * Description: Use your premium web fonts directly in the Editor or with the Customify plugin. Works with Typekit, MyFonts, Fonts.com, self-hosted fonts, and others.
 * Author: Pixelgrade
 * Author URI: https://pixelgrade.com
 * Requires at least: 4.9
 * Tested up to: 5.7.0
 * Text Domain: fonto
 * Domain Path: /lang/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the main instance of Fonto to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return Fonto
 */
function fonto() {

	require_once( 'includes/class-fonto.php' );
	$instance = Fonto::instance( __FILE__, '1.1.0' );

	return $instance;
}

$instance = fonto();
