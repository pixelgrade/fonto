<?php
/**
 * A set of functions that expose information to others to use
 *
 * @package  Fonto
 * @author   Pixelgrade <contact@pixelgrade.com>
 * @license  GPL v2.0 (or later) see LICENCE file or http://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://pixelgrade.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param array $args WP_Query args to use to get posts.
 *
 * @return WP_Post[]|int[] Array of post objects or post IDs.
 */
function fonto_get_fonts( $args = array() ) {
	/** @var Fonto $local_fonto */
	$local_fonto = fonto();

	return $local_fonto->output->get_fonts( $args );
}
