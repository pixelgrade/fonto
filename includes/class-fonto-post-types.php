<?php
/**
 * Document for class Fonto_Post_Types
 *
 * PHP Version 5.6
 *
 * @category Class
 * @package Fonto
 * @author   PixelGrade <contact@pixelgrade.com>
 * @license  GPL v2.0 (or later) see LICENCE file or http://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://pixelgrade.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to handle custom post types: registration, taxonomies and custom fields
 *
 * @category include
 * @package  Fonto
 * @author   PixelGrade <contact@pixelgrade.com>
 * @license  GPL v2.0 (or later) see LICENCE file or http://www.gnu.org/licenses/gpl-2.0.html
 * @version  Release: .1
 * @link     https://pixelgrade.com
 * @since    Class available since Release .1
 */
class Fonto_Post_Types {
	/**
	 * The single instance of Fonto_Post_Types which will register all Custom Post Types,
	 *     add metaboxes with custom fields, and also register all taxonomies.
	 * @var     Fonto_Post_Types
	 * @access  private
	 * @since     1.0.0
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 * @var     Fonto object
	 * @access  public
	 * @since     1.0.0
	 */
	public $parent = null;

	/**
	 * Constructor function
	 *
	 * @param Object $parent Fonto Object.
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;

		// Register the Font custom post type
		$args = array(
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'query_var'           => false,
			'can_export'          => true,
			'rewrite'             => false,
			'has_archive'         => false,
			'hierarchical'        => false,
			'supports'            => array( 'title', ),
			'menu_position'       => 30,
			'menu_icon'           => 'dashicons-editor-textcolor',
		);

		// The parent class invokes Fonto_Post_Type
		$this->parent->register_post_type( 'font', __( 'Fonts', 'fonto' ), __( 'Font', 'fonto' ), __( 'Custom fonts bonanza', 'fonto' ), $args );

		// Register a custom taxonomy for fonts.
		$font_category_taxonomy = $this->parent->register_taxonomy( 'font_categories', __( 'Font Categories', 'fonto' ), __( 'Font Category', 'fonto' ), 'font' );

		// Add taxonomy filter to Font edit page.
		$font_category_taxonomy->add_filter();

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		add_action( 'cmb2_admin_init', array( $this, 'add_meta_boxes' ) );

	}

	/**
	 * Add meta boxes
	 * @return void
	 */
	function add_meta_boxes() {

		$this->font_custom_fields();
	}

	/**
	 * Supply metaboxes for Font post type edit page
	 *
	 * @return array
	 */
	function font_custom_fields() {
		// For more info about the various options available for fields
		// see this https://github.com/WebDevStudios/CMB2/wiki/Field-Parameters

		$prefix = $this->parent->_token . '_';

		$font_details = new_cmb2_box( array(
			'id'           => $prefix . 'font_details',
			'title'        => __( 'Font Details', 'cmb2' ),
			'object_types' => array( 'font', ), // Post type
			'context'    => 'normal',
			'priority'   => 'high',
			'show_names' => true, // Show field names on the left
			'cmb_styles' => true, // false to disable the CMB stylesheet
			'closed'     => false, // true to keep the metabox closed by default
			//'classes'    => 'extra-class', // Extra cmb2-wrap classes
			// 'classes_cb' => 'yourprefix_add_some_classes', // Add classes through a callback.
		) );

		//To put the description after the field label, we use the 'our_desc' key instead of the regular 'desc' one and add the 'render_row_cb' callback

		$font_details->add_field( array(
			'name'    => __( 'Font Source', 'cmb2' ),
			'our_desc'    => __( 'Select whether you are using a Custom Web Font Service (Typekit, Myfonts, Fonts.com) or you\'re self-hosting the fonts.', 'cmb2' ),
			'id'      => $prefix . 'font_source',
			'type'    => 'radio',
			'options' => array(
				'font_service' => __( 'Web Font Service', 'cmb2' ),
				'self_hosted' => __( 'Self-Hosted', 'cmb2' ),
			),
			'default' => 'font_service',
			'row_classes' => array( 'no-divider', ),
			'render_row_cb' => array( $this, 'render_field_callback_our_desc_after_label' ),
		) );

		$font_details->add_field( array(
			'name' => __( 'Fonts Loading / Embed Code', 'cmb2' ),
			'our_desc' => __( 'Insert below the embed code (JS/CSS) provided by the font service. <a href="#" target="_blank">Learn More</a>', 'cmb2' ),
			'id'   => $prefix . 'embed_code',
			'type' => 'textarea_code',
			'attributes'  => array(
				'placeholder' => 'Your embed code',
				'rows'        => 5,
			),
			'after_field' => esc_html__( 'The above code will be inserted in the <head> area of your website.', 'fonto' ),
			'row_classes' => array( 'full-width', 'title__large', 'background__dark' ),
			'render_row_cb' => array( $this, 'render_field_callback_our_desc_after_label' ),
		) );

		$font_details->add_field( array(
			'name' => __( 'Weights & Styles Matching', 'cmb2' ),
			'our_desc' => '<span class="subtitle">' . __( 'How Fonts Variations (weights & styles) are declared?', 'cmb2' ) . '</span>'
						. __( 'Based on the format that you received the font names from the font service.', 'fonto' ),
			'id'   => $prefix . 'font_name_style',
			'type' => 'radio',
			'options' => array(
				'grouped' => __( 'Fonts are grouped together in a single "Font Family" name', 'cmb2' )
				             . '<span class="option-details">' . __( 'When you have only <em>one</em> font name; we will add weights and styles via CSS.<br/>— Example: <em>"proxima-nova"</em> from Typekit or Fonts.com', 'fonto' ) . '</span>',
				'individual' => __( 'Font Names are Referenced Individually', 'cmb2' )
								. '<span class="option-details">' . __( 'If you have <em>multiple</em> font names; this means the weights and styles are bundled within each font and we shouldn\'t add them again in CSS.<br/>— Example: <em>"ProximaNW01-Regular"</em> and <em>"ProximaNW01-RegularItalic"</em> from MyFonts.com', 'fonto' ) . '</span>',
			),
			'default' => 'grouped',
			'row_classes' => array( 'full-width', 'title__large', 'background__dark', ),
			'render_row_cb' => array( $this, 'render_field_callback_our_desc_after_label' ),
		) );

		$font_details->add_field( array(
			'name'    => __( 'Font Family Name', 'cmb2' ),
			'our_desc'    => __( 'Insert the CSS font name as provided by the font service.', 'cmb2' ),
			'id'      => $prefix . 'font_family_name',
			'type'    => 'text_medium',
			'attributes'  => array(
				'placeholder' => 'Proxima Nova',
			),
			'render_row_cb' => array( $this, 'render_field_callback_our_desc_after_label' ),
		) );

		$font_details->add_field( array(
			'name'    => __( 'Available Font Variations', 'cmb2' ),
			'our_desc'    => __( 'Check the available variations for this font.', 'cmb2' )
						. '<span class="note">' . __( '*Note that the variations will be available through the Font Selectors and will not make any effect if a specific variation is checked but is not loaded by the font service.', 'fonto' ) . '</span>',
			'id'      => $prefix . 'font_variations',
			'type'    => 'multicheck',
			'select_all_button' => false,
			// 'multiple' => true, // Store values in individual rows
			'options' => array(
				'100_normal' => __( 'Thin 100', 'cmb2' ),
				'100_italic' => __( 'Thin Italic', 'cmb2' ),
				'200_normal' => __( 'Extra Light 200', 'cmb2' ),
				'200_italic' => __( 'Extra Light Italic', 'cmb2' ),
				'300_normal' => __( 'Light 300', 'cmb2' ),
				'300_italic' => __( 'Light Italic', 'cmb2' ),
				'400_normal' => __( 'Regular 400', 'cmb2' ),
				'400_italic' => __( 'Regular Italic', 'cmb2' ),
				'500_normal' => __( 'Medium 500', 'cmb2' ),
				'500_italic' => __( 'Medium Italic', 'cmb2' ),
				'600_normal' => __( 'SemiBold 600', 'cmb2' ),
				'600_italic' => __( 'SemiBold Italic', 'cmb2' ),
				'700_normal' => __( 'Bold 700', 'cmb2' ),
				'700_italic' => __( 'Bold Italic', 'cmb2' ),
				'800_normal' => __( 'ExtraBold 800', 'cmb2' ),
				'800_italic' => __( 'ExtraBold Italic', 'cmb2' ),
				'900_normal' => __( 'Black 100', 'cmb2' ),
				'900_italic' => __( 'Black Italic', 'cmb2' ),
			),
			// 'inline'  => true, // Toggles display to inline
			'row_classes' => array( 'font-variations', 'no-divider' ),
			'render_row_cb' => array( $this, 'render_field_callback_our_desc_after_label' ),
		) );

	} // End font_custom_fields()

	/**
	 * Custom field render callback that will put the description (desc) after the label (name) not after the input (the default behaviour)
	 *
	 * @param  array      $field_args Array of field parameters
	 * @param  CMB2_Field $field      Field object
	 *
	 * @return  CMB2_Field object
	 */
	public function render_field_callback_our_desc_after_label( $field_args, $field ) {

		// If field is requesting to not be shown on the front-end
		if ( ! is_admin() && ! $field->args( 'on_front' ) ) {
			return;
		}

		// If field is requesting to be conditionally shown
		if ( ! $field->should_show() ) {
			return;
		}

		$field->peform_param_callback( 'before_row' );

		printf( "<div class=\"cmb-row %s\" data-fieldtype=\"%s\">\n", $field->row_classes(), $field->type() );

		if ( ! $field->args( 'show_names' ) ) {
			echo "\n\t<div class=\"cmb-td\">\n";

			$field->peform_param_callback( 'label_cb' );

		} else {

			if ( $field->get_param_callback_result( 'label_cb' ) ) {
				echo '<div class="cmb-th">', $field->peform_param_callback( 'label_cb' ), '<p class="cmb2-metabox-our-description">', $field->peform_param_callback( 'our_desc' ), '</p></div>';
			}

			echo "\n\t<div class=\"cmb-td\">\n";
		}

		$field->peform_param_callback( 'before' );

		$field_type = new CMB2_Types( $field );
		$field_type->render();

		$field->peform_param_callback( 'after' );

		echo "\n\t</div>\n</div>";

		$field->peform_param_callback( 'after_row' );

		// For chaining
		return $field;
	}

	/**
	 * Main Fonto_Post_Types Instance
	 *
	 * Ensures only one instance of Fonto_Post_Types is loaded or can be loaded.
	 *
	 * @since  1.0.0
	 * @static
	 * @see    Fonto()
	 *
	 * @param  Object $parent Main Fonto instance.
	 *
	 * @return Fonto_Post_Types instance
	 */
	public static function instance( $parent ) {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}

		return self::$_instance;
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cheatin&#8217; huh?' ) ), esc_html( $this->parent->_version ) );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {

		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cheatin&#8217; huh?' ) ), esc_html( $this->parent->_version ) );
	} // End __wakeup()

}

