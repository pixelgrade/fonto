<?php
/**
 * Document for class Fonto_Post_Types
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
 * Class to handle the : registration, taxonomies and custom fields
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

		//Add the metaboxes for the post types
		add_action( 'cmb2_admin_init', array( $this, 'add_meta_boxes' ) );

		//Enqueue the static assets for the metaboxes in the admin area
		// Load admin JS & CSS.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		//change the upload directory for our font CPT
		add_filter( 'upload_dir', array( $this, 'custom_upload_directory' ) );

		//Add AJAX actions
		add_action( 'wp_ajax_sample_font_url_path', array( $this, 'wp_ajax_sample_font_url_path' ), 1 );
	}

	/**
	 * Ajax handler to retrieve the sample Font URL path to where the font files are uploaded
	 *
	 * @since 3.1.0
	 */
	function wp_ajax_sample_font_url_path() {
		check_ajax_referer( 'samplepermalink', 'samplepermalinknonce' );

		//get the current URL for the uploads directory
		$uploads = wp_upload_dir();

		wp_die( $uploads['url'] );
	}

	/**
	 * Change Upload Directory for Custom Post-Type
	 *
	 * This will change the upload directory for a custom post-type. Attachments will
	 * now be uploaded to an "uploads" directory within the folder of your plugin. Make
	 * sure you swap out "post-type" in the if-statement with the appropriate value...
	 */
	public function custom_upload_directory( $path ) {
		// Check if uploading from inside a post/page/cpt - if not, default Upload folder is used
		$use_default_dir = ( isset($_REQUEST['post_id'] ) && $_REQUEST['post_id'] == 0 ) ? true : false;
		if( ! empty( $path['error'] ) || $use_default_dir )
			return $path;

		// Check if correct post type
		$the_post_type = get_post_type( $_REQUEST['post_id'] );
		if( 'font' != $the_post_type ) {
			return $path;
		}

		//generate a unique hashid for the post_id to use a directory to upload the font files
		$hashid = $this->parent->hash_encode_ID( $_REQUEST['post_id'] );

		$customdir = '/fonts/' . $hashid;

		//remove default subdir (year/month) and add custom dir INSIDE THE DEFAULT UPLOAD DIR
		$path['path']    = str_replace( $path['subdir'], $customdir, $path['path']);
		$path['url']     = str_replace( $path['subdir'], $customdir, $path['url']);

		$path['subdir']  = $customdir;

		return $path;
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
			'title'        => __( 'Font Details', 'fonto' ),
			'object_types' => array( 'font', ), // Post type
			'context'    => 'normal',
			'priority'   => 'high',
			'show_names' => true, // Show field names on the left
			'cmb_styles' => false, // false to disable the CMB stylesheet - we are loding our own clean CSS
			'closed'     => false, // true to keep the metabox closed by default
			//'classes'    => 'extra-class', // Extra cmb2-wrap classes
			// 'classes_cb' => 'yourprefix_add_some_classes', // Add classes through a callback.
		) );

		//To put the description after the field label, we use the 'our_desc' key instead of the regular 'desc' one and add the 'render_row_cb' callback

		$font_details->add_field( array(
			'name'    => __( 'Font Source', 'fonto' ),
			'our_desc'    => __( 'Select whether you are using a Custom Web Font Service (Typekit, Myfonts, Fonts.com) or you\'re self-hosting the fonts.', 'fonto' ),
			'id'      => $prefix . 'font_source',
			'type'    => 'radio',
			'options' => array(
				'font_service' => __( 'Web Font Service', 'fonto' ),
				'self_hosted' => __( 'Self-Hosted', 'fonto' ),
			),
			'default' => 'font_service',
			'row_classes' => array( 'no-divider', ),
			'render_row_cb' => array( $this, 'render_field_callback_our_desc_after_label' ),
		) );

		$font_details->add_field( array(
			'name' => __( 'Fonts Loading / Embed Code', 'fonto' ),
			'id'   => $prefix . 'fonts_loading_embed_code',
			'type' => 'title',
			'row_classes' => array( 'full-width', 'background__dark' ),
			'before_row' => '<div class="font-loading-section">',
		) );

		$font_details->add_field( array(
			'name'         => __( 'Font Files', 'fonto' ),
			'our_desc'         => __( 'Upload all the files received from the font service/generator.', 'fonto' ),
			'id'           => $prefix . 'font_files',
			'type'         => 'file_list',
			'preview_size' => false, // Default: array( 50, 50 )
			'attributes'  => array(
				// Shown for Self-Hosted fonts
				'data-conditional-id' => $prefix . 'font_source',
				'data-conditional-value' => 'self_hosted',
			),
			'row_classes' => array( 'background__dark' ),
			'render_row_cb' => array( $this, 'render_field_callback_our_desc_after_label' ),
		) );

		$font_details->add_field( array(
			'name'        => __( 'URL Path to the font files', 'fonto' ),
			'our_desc'    => __( 'This is the URL path to be used for the uploaded files.', 'fonto' ),
			'id'          => $prefix . 'url_path',
			'type'        => 'text',
			'attributes'  => array(
				// Shown for Self-Hosted fonts
				'data-conditional-id' => $prefix . 'font_source',
				'data-conditional-value' => 'self_hosted',
				'readonly' => 'readonly',
			),
			'row_classes' => array( 'background__dark' ),
			'render_row_cb' => array( $this, 'render_field_callback_our_desc_after_label' ),
		) );

		$font_details->add_field( array(
			'name' => __( 'Embed Code', 'fonto' ),
			'show_names' => false,
			'id'   => $prefix . 'embed_code_font_service',
			'type' => 'textarea_code',
			'attributes'  => array(
				'placeholder' => 'Your embed code',
				'rows'        => 5,
				// Shown for Web Fonts services
				'data-conditional-id' => $prefix . 'font_source',
				'data-conditional-value' => 'font_service',
			),
			'before_field' => __( 'Insert below the embed code (JS/CSS) provided by the font service. <a href="#" target="_blank">Learn More</a>', 'fonto' ),
			'after_field' => esc_html__( 'The above code will be inserted in the <head> area of your website.', 'fonto' ),
			'row_classes' => array( 'full-width', 'title__large', 'background__dark' ),
		) );

		$font_details->add_field( array(
			'name' => __( 'Embed Code', 'fonto' ),
			'show_names' => true,
			'id'   => $prefix . 'embed_code_self_hosted',
			'type' => 'textarea_code',
			'attributes'  => array(
				'placeholder' => 'Your embed code',
				'rows'        => 5,
				// Shown for Self-Hosted fonts
				'data-conditional-id' => $prefix . 'font_source',
				'data-conditional-value' => 'self_hosted',
			),
			'before_field' => __( 'Insert below the CSS code. <a href="#" target="_blank">Learn More</a>', 'fonto' ),
			'after_field' => esc_html__( 'The above code will be inserted in the <head> area of your website.', 'fonto' ),
			'row_classes' => array( 'full-width', 'title__large', 'background__dark' ),
			'after_row' => '</div><!-- .font-loading-section -->'
		) );

		$font_details->add_field( array(
			'name' => __( 'Weights & Styles Matching', 'fonto' ),
			'id'   => $prefix . 'weights_styles_matching',
			'type' => 'title',
			'row_classes' => array( 'full-width', ),
		) );

		$font_details->add_field( array(
			'name' => __( 'How Fonts Variations (weights & styles) are declared?', 'fonto' ),
			'our_desc' => __( 'Based on the format that you received the font names from the font service.', 'fonto' ),
			'id'   => $prefix . 'font_name_style',
			'type' => 'radio',
			'options' => array(
				'grouped' => __( 'Fonts are grouped together in a single "Font Family" name', 'fonto' )
				             . '<span class="option-details">' . __( 'When you have only <em>one</em> font name; we will add weights and styles via CSS.<br/>— Example: <em>"proxima-nova"</em> from Typekit or Fonts.com', 'fonto' ) . '</span>',
				'individual' => __( 'Font Names are Referenced Individually', 'fonto' )
								. '<span class="option-details">' . __( 'If you have <em>multiple</em> font names; this means the weights and styles are bundled within each font and we shouldn\'t add them again in CSS.<br/>— Example: <em>"ProximaNW01-Regular"</em> and <em>"ProximaNW01-RegularItalic"</em> from MyFonts.com', 'fonto' ) . '</span>',
			),
			'default' => 'grouped',
			'row_classes' => array( 'full-width', ),
			'render_row_cb' => array( $this, 'render_field_callback_our_desc_after_label' ),
		) );

		$font_details->add_field( array(
			'name'    => __( 'Font Family Name', 'fonto' ),
			'our_desc'    => __( 'Insert the CSS font name as provided by the font service.', 'fonto' ),
			'id'      => $prefix . 'font_family_name',
			'type'    => 'text',
			'attributes'  => array(
				'placeholder' => 'Proxima Nova',
				// Shown when using a single font family name
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'grouped',
			),
			'render_row_cb' => array( $this, 'render_field_callback_our_desc_after_label' ),
		) );

		$font_details->add_field( array(
			'name'    => __( 'Available Font Variations', 'fonto' ),
			'our_desc'    => __( 'Check the available variations for this font.', 'fonto' )
						. '<span class="note">' . __( '*Note that the variations will be available through the Font Selectors and will not make any effect if a specific variation is checked but is not loaded by the font service.', 'fonto' ) . '</span>',
			'id'      => $prefix . 'font_variations',
			'type'    => 'multicheck',
			'select_all_button' => false,
			// 'multiple' => true, // Store values in individual rows
			'options' => array(
				'100_normal' => __( 'Thin 100', 'fonto' ),
				'100_italic' => __( 'Thin Italic', 'fonto' ),
				'200_normal' => __( 'Extra Light 200', 'fonto' ),
				'200_italic' => __( 'Extra Light Italic', 'fonto' ),
				'300_normal' => __( 'Light 300', 'fonto' ),
				'300_italic' => __( 'Light Italic', 'fonto' ),
				'400_normal' => __( 'Regular 400', 'fonto' ),
				'400_italic' => __( 'Regular Italic', 'fonto' ),
				'500_normal' => __( 'Medium 500', 'fonto' ),
				'500_italic' => __( 'Medium Italic', 'fonto' ),
				'600_normal' => __( 'SemiBold 600', 'fonto' ),
				'600_italic' => __( 'SemiBold Italic', 'fonto' ),
				'700_normal' => __( 'Bold 700', 'fonto' ),
				'700_italic' => __( 'Bold Italic', 'fonto' ),
				'800_normal' => __( 'ExtraBold 800', 'fonto' ),
				'800_italic' => __( 'ExtraBold Italic', 'fonto' ),
				'900_normal' => __( 'Black 900', 'fonto' ),
				'900_italic' => __( 'Black Italic', 'fonto' ),
			),
			'attributes'  => array(
				// Shown when using a single font family name
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'grouped',
			),
			// 'inline'  => true, // Toggles display to inline
			'row_classes' => array( 'font-variations', 'no-divider' ),
			'render_row_cb' => array( $this, 'render_field_callback_our_desc_after_label' ),
		) );

		$font_details->add_field( array(
			'name' => __( 'Font Weights & Styles Variations', 'fonto' ),
			'desc' => __( 'Pair the provided fonts references names with their matching weights and styles.', 'fonto' ),
			'id'   => $prefix . 'font_weight_style_variations',
			'type' => 'title',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'full-width', 'title__small', ),
		) );

		$font_details->add_field( array(
			'name'        => __( 'Thin 100', 'fonto' ),
			'id'          => $prefix . '100_normal_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
			'before_row' => '<div class="matching-fields-section">',
		) );

		$font_details->add_field( array(
			'name'        => __( 'Thin Italic', 'fonto' ),
			'id'          => $prefix . '100_italic_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => __( 'Extra Light 200', 'fonto' ),
			'id'          => $prefix . '200_normal_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => __( 'Extra Light Italic', 'fonto' ),
			'id'          => $prefix . '200_italic_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => __( 'Light 300', 'fonto' ),
			'id'          => $prefix . '300_normal_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => __( 'Light Italic', 'fonto' ),
			'id'          => $prefix . '300_italic_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => __( 'Regular 400', 'fonto' ),
			'id'          => $prefix . '400_normal_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => __( 'Regular Italic', 'fonto' ),
			'id'          => $prefix . '400_italic_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => __( 'Medium 500', 'fonto' ),
			'id'          => $prefix . '500_normal_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => __( 'Medium Italic', 'fonto' ),
			'id'          => $prefix . '500_italic_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => __( 'Semi Bold 600', 'fonto' ),
			'id'          => $prefix . '600_normal_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => __( 'Semi Bold Italic', 'fonto' ),
			'id'          => $prefix . '600_italic_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => __( 'Bold 700', 'fonto' ),
			'id'          => $prefix . '700_normal_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => __( 'Bold Italic', 'fonto' ),
			'id'          => $prefix . '700_italic_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => __( 'Extra Bold 800', 'fonto' ),
			'id'          => $prefix . '800_normal_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => __( 'Extra Bold Italic', 'fonto' ),
			'id'          => $prefix . '800_italic_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => __( 'Black 900', 'fonto' ),
			'id'          => $prefix . '900_normal_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => __( 'Black Italic', 'fonto' ),
			'id'          => $prefix . '900_italic_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id' =>  $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
			'after_row'   => '</div><!-- .matching-fields-section -->',
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
	 * Load admin CSS - specific for post types
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles() {
		global $post;

		// If the post we're editing isn't a font type, exit this function
		if ( ! $post || 'font' != $post->post_type ) {
			return;
		}

		//Allow others to stop us in enqueueing the CSS
		if ( ! apply_filters( $this->parent->_token . '_cmb2_enqueue_css', true ) ) {
			return false;
		}

		// Only use minified files if SCRIPT_DEBUG is off
		$min   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$rtl   = is_rtl() ? '-rtl' : '';

		// Filter required styles and register stylesheet
		$styles = apply_filters( $this->parent->_token . '_cmb2_style_dependencies', array() );
		wp_register_style( $this->parent->_token . '-cmb2-styles', esc_url( $this->parent->assets_url ) . "css/cmb2/cmb2{$rtl}{$min}.css", $styles, $this->parent->_version );

		wp_enqueue_style( $this->parent->_token . '-cmb2-styles' );
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript - specific for post types
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts() {
		global $post;

		// If the post we're editing isn't a font type, exit this function
		if ( ! $post || 'font' != $post->post_type ) {
			return;
		}

		//Allow others to stop us in enqueueing the JS
		if ( ! apply_filters( $this->parent->_token . '_cmb2_enqueue_js', true ) ) {
			return false;
		}

		// Register our cmb custom JS
		wp_register_script( $this->parent->_token . '-cmb2', esc_url( $this->parent->assets_url ) . 'js/cmb2' . $this->parent->script_suffix . '.js', array(
			'jquery',
		), $this->parent->_version );

		wp_enqueue_script( $this->parent->_token . '-cmb2' );

	} // End admin_enqueue_scripts ()


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
