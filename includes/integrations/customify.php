<?php
/**
 * A set of functionality that helps with the integration with our Customify plugin - https://wordpress.org/plugins/customify/
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
 * @param WP_Post|int $font WP_Post instance or post id.
 *
 * @return array
 */
function fonto_construct_customify_font_details( $font ) {
	/** @var Fonto $local_fonto */
	$local_fonto = fonto();

	$font_details = array();

	$font = get_post( $font );
	if ( empty( $font ) ) {
		return $font_details;
	}

	// First determine how the font families are named.
	$font_name_style = get_post_meta( $font->ID, $local_fonto->output->prefix . 'font_name_style', true );
	if ( empty( $font_name_style ) ) {
		//use the default
		$font_name_style = 'grouped';
	}

	if ( 'grouped' == $font_name_style ) {
		/* ===== Fonts are grouped together in a single "Font Family" name ==== */

		$font_family_name = trim( get_post_meta( $font->ID, $local_fonto->output->prefix . 'font_family_name', true ) );

		// Grab the font variations meta
		// this is a single meta holding an array
		$font_variations = get_post_meta( $font->ID, $local_fonto->output->prefix . 'font_variations', true );
		// if the font has some variations then we can use it
		if ( ! empty( $font_variations ) ) {
			$font_details['font_family'] = esc_html( $font_family_name );
			//we will use the font post title for the font family display
			$font_details['font_family_display'] = esc_html( $font->post_title );

			$font_details['variants'] = array();
			//loop through all the checked variations
			foreach ( $font_variations as $variation_value ) {
				//the value is like this 400_regular, so we will split it by '_' to get weight and style
				list( $variation_weight, $variation_style ) = explode( '_', $variation_value );

				//no need to pass the normal/regular style - it is assumed
				if ( 'normal' == $variation_style ) {
					$variation_style = '';
				}
				$font_details['variants'][] = $variation_weight . $variation_style;
			}
		}
	} elseif ( 'individual' == $font_name_style ) {
		/* ===== Font Names are Referenced Individually ==== */

		//we assume the worst
		$valid_font_family = false;

		// We need to loop through all the variations and use only those filled with a font name
		foreach ( $local_fonto->output->font_variations_options as $id => $display_name ) {
			$font_family_name = trim( get_post_meta( $font->ID, $local_fonto->output->prefix . $id . '_individual', true ) );
			if ( ! empty( $font_family_name ) ) {
				//we have found a valid variation; this makes the font family usable
				$valid_font_family = true;

				//the $id is like this 400_regular, so we will split it by '_' to get weight and style
				list( $variation_weight, $variation_style ) = explode( '_', $id );

				//we need to store for each variant the whole details, including family name
				$font_details['variants'][] = array(
					'font-family' => esc_html( $font_family_name ),
					'font-weight' => $variation_weight,
					'font-style' => $variation_style,
				);
			}
		}

		if ( true === $valid_font_family ) {
			//we will use the font post title for the font family display
			$font_details['font_family'] = $font_details['font_family_display'] = esc_html( $font->post_title );
		}
	}

	return $font_details;
}

/**
 * Add the Fonto Group in the old Customify Typography fields.
 *
 * @param $active_font_family
 */
function fonto_add_custom_fonts_to_customify_typography_select( $active_font_family ) {
	if ( ! class_exists('Pix_Customize_Typography_Control') ) {
		return;
	}

	// Get all the published custom fonts.
	$fonts = fonto_get_fonts();
	if ( ! empty( $fonts ) ) {
		echo '<optgroup label="' . __( 'Custom Fonts', 'fonto' ) . '">';

		foreach ( $fonts as $font ) {
			$font_details = fonto_construct_customify_font_details( $font );
			if ( ! empty( $font_details ) && ! empty( $font_details['font_family'] ) ) {
				//display the select option's HTML
				Pix_Customize_Typography_Control::output_font_option( $font_details['font_family'], $active_font_family, $font_details, 'custom_' . $font_name_style );
			}
		}
		echo "</optgroup>";
	}
}
add_action( 'customify_typography_font_family_before_options', 'fonto_add_custom_fonts_to_customify_typography_select' );

/**
 * Add the Fonto Group in the new Customify Font Select, pre-Customify 2.11.
 *
 * @param $active_font_family
 */
function fonto_add_custom_fonts_to_customify_font_select( $active_font_family ) {
	if ( ! class_exists('Pix_Customize_Font_Control') ) {
		return;
	}

	// Get all the published custom fonts.
	$fonts = fonto_get_fonts();

	if ( ! empty( $fonts ) ) {
		echo '<optgroup label="' . __( 'Custom Fonts', 'fonto' ) . '">';

		foreach ( $fonts as $font ) {
			$font_details = fonto_construct_customify_font_details( $font );
			if ( ! empty( $font_details ) && ! empty( $font_details['font_family'] ) ) {
				//display the select option's HTML
				Pix_Customize_Font_Control::output_font_family_option( $font_details['font_family'], $active_font_family );
			}
		}
		echo "</optgroup>";
	}
}
add_action( 'customify_font_family_before_options', 'fonto_add_custom_fonts_to_customify_font_select' );

/**
 * Add our custom fonts details to the new Customify v2.11+ third-party font list.
 *
 * @param array $font_list
 *
 * @return array
 */
function fonto_add_fonts_to_customify_third_party_list( $font_list ) {
	// Get all the published custom fonts.
	$fonts = fonto_get_fonts();
	if ( empty( $fonts ) ) {
		return $font_list;
	}

	foreach ( $fonts as $font ) {
		$font_details = fonto_construct_customify_font_details( $font );
		if ( ! empty( $font_details ) && ! empty( $font_details['font_family'] ) ) {
			$font_list[ $font_details['font_family'] ] = $font_details;
		}
	}

	return $font_list;
}
add_filter( 'customify_third_party_fonts', 'fonto_add_fonts_to_customify_third_party_list' );

/**
 * Change the font group label for Customify v2.11+ third-party font list.
 *
 * @param string $group_label
 */
add_filter( 'customify_third_party_font_group_label', function( $group_label ) {
	return esc_html__( 'Custom Fonts', 'fonto' );
}, 10, 1 );
