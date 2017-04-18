<?php
/**
 * Plugin Name: Fonto
 * Version: 1.0.2
 * Plugin URI: https://pixelgrade.com
 * Description: Use your premium web fonts directly in the Editor or with the Customify plugin. Works with Typekit, MyFonts, Fonts.com, self-hosted fonts, and others.
 * Author: Pixelgrade
 * Author URI: https://pixelgrade.com
 * Requires at least: 4.0
 * Tested up to: 4.7.3
 *
 * Text Domain: fonto
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Pixelgrade
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the main instance of Fonto to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Fonto
 */
function fonto() {

	require_once( 'includes/class-fonto.php' );
	$instance = Fonto::instance( __FILE__, '1.0.2' );

	return $instance;
}

$instance = fonto();
