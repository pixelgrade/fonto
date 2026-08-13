<?php
/**
 * Plugin Name: Fonto - Custom Web Fonts Manager
 * Version: 1.2.4
 * Plugin URI: https://wordpress.org/plugins/fonto
 * Description: Use your premium web fonts directly in the Editor or with the Customify and Style Manager plugins. Works with Typekit, MyFonts, Fonts.com, self-hosted fonts, and others.
 * Author: Pixelgrade
 * Author URI: https://pixelgrade.com
 * Requires at least: 5.9.0
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fonto
 * Domain Path: /languages/
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
	$instance = Fonto::instance( __FILE__, '1.2.4' );

	return $instance;
}

$fonto_instance = fonto();
