<?php
/**
 * Plugin Name: Fonto
 * Version: 1.0.0
 * Plugin URI: https://pixelgrade.com
 * Description: This is your starter template for your next WordPress plugin.
 * Author: PixelGrade
 * Author URI: https://pixelgrade.com
 * Requires at least: 4.0
 * Tested up to: 4.5.2
 *
 * Text Domain: fonto
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author PixelGrade
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
	$instance = Fonto::instance( __FILE__, '1.0.0' );

	return $instance;
}

$instance = fonto();
