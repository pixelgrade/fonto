<?php
/**
 * Document for class Fonto_Post_Types
 *
 * @link     https://pixelgrade.com
 * @package  Fonto
 * @author   Pixelgrade <contact@pixelgrade.com>
 * @license  GPL v2.0 (or later) see LICENCE file or http://www.gnu.org/licenses/gpl-2.0.html
 * @category Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to handle the : registration, taxonomies and custom fields
 *
 * @since    Class available since Release .1
 * @link     https://pixelgrade.com
 * @author   Pixelgrade <contact@pixelgrade.com>
 * @license  GPL v2.0 (or later) see LICENCE file or http://www.gnu.org/licenses/gpl-2.0.html
 * @version  Release: .1
 * @category include
 * @package  Fonto
 */
class Fonto_Post_Types {
	/**
	 * The single instance of Fonto_Post_Types which will register all Custom Post Types,
	 *     add metaboxes with custom fields, and also register all taxonomies.
	 * @since     1.0.0
	 * @var     Fonto_Post_Types
	 * @access    private
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 * @since     1.0.0
	 * @var     Fonto object
	 * @access    public
	 */
	public $parent = null;

	/**
	 * Constructor function
	 *
	 * @param Fonto $parent Fonto Object.
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;

		// Register the Font custom post type
		$args = array(
			'public'        => true,
			'show_ui'       => true,
			'show_in_menu'  => true,
			'show_in_rest'  => true,
			'query_var'     => false,
			'can_export'    => true,
			'rewrite'       => false,
			'has_archive'   => false,
			'hierarchical'  => false,
			'supports'      => array( 'title', ),
			'menu_position' => 30,
			'menu_icon'     => 'dashicons-editor-textcolor',
		);

		// The parent class invokes Fonto_Post_Type
		$this->parent->register_post_type( 'font', esc_html__( 'Fonts', 'fonto' ), esc_html__( 'Font', 'fonto' ), esc_html__( 'Custom fonts bonanza', 'fonto' ), $args );

		// Register a custom taxonomy for fonts.
		//$font_category_taxonomy = $this->parent->register_taxonomy( 'font_categories', __( 'Font Categories', 'fonto' ), __( 'Font Category', 'fonto' ), 'font' );

		// Add taxonomy filter to Font edit page.
		//$font_category_taxonomy->add_filter();

		//Add the metaboxes for the post types
		add_action( 'cmb2_admin_init', array( $this, 'add_meta_boxes' ) );
		// Make sure that no CMB2 core styles are enqueued
		add_filter( 'cmb2_enqueue_css', array( $this, 'prevent_cmb2_core_styles' ) );

		// Enqueue the static assets for the metaboxes in the admin area.
		// Load admin JS & CSS.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		// Change the upload directory for our font CPT.
		add_filter( 'upload_dir', array( $this, 'custom_upload_directory' ) );

		// Handle the self-hosted file list fiels arguments.
		add_filter( 'cmb2_input_attributes', array( $this, 'handle_file_list_attributes' ), 10, 3 );

		// Add AJAX actions.
		add_action( 'wp_ajax_sample_font_url_path', array( $this, 'wp_ajax_sample_font_url_path' ), 1 );
	}

	/**
	 * Change Upload Directory for the font post type.
	 *
	 * This will change the upload directory for a custom post-type. Attachments will
	 * now be uploaded to an "uploads" directory within the folder of your plugin. Make
	 * sure you swap out "post-type" in the if-statement with the appropriate value...
	 */
	public function custom_upload_directory( $path ) {
		$post_ID = 0;
		// We need to account for various keys.
		if ( ! empty( $_REQUEST['post'] ) ) {
			$post_ID = absint( $_REQUEST['post'] );
		} elseif ( ! empty( $_REQUEST['post_id'] ) ) {
			$post_ID = absint( $_REQUEST['post_id'] );
		} elseif ( ! empty( $_REQUEST['post_ID'] ) ) {
			$post_ID = absint( $_REQUEST['post_ID'] );
		}
		// Check if uploading from inside a post/page/cpt - if not, default Upload folder is used
		$use_default_dir = empty( $post_ID ) ? true : false;
		if ( empty( $post_ID ) || ! empty( $path['error'] ) || $use_default_dir ) {
			return $path;
		}

		// Check if correct post type
		$the_post_type = get_post_type( $post_ID );
		if ( 'font' != $the_post_type ) {
			return $path;
		}

		// Append the post ID (that is unique) to the path.
		$customdir = '/fonts/' . $post_ID;

		// Remove default subdir (year/month) and add custom dir INSIDE THE DEFAULT UPLOAD DIR.
		$path['path'] = str_replace( $path['subdir'], $customdir, $path['path'] );
		$path['url']  = str_replace( $path['subdir'], $customdir, $path['url'] );

		$path['subdir'] = $customdir;

		return $path;
	}

	/**
	 * @param array  $args              The array of attribute arguments.
	 * @param array  $type_defaults     The array of default values.
	 * @param CMB2_Field  $field             The `CMB2_Field` object.
	 *
	 * @return mixed
	 */
	public function handle_file_list_attributes( $args, $type_defaults, $field ) {
		if ( 'file_list' !== $field->type() && $this->parent->_token . '_font_files' !== $field->id() ) {
			return $args;
		}

		$args['data-objectid'] = $field->object_id;
		$args['data-objecttype'] = $field->object_type;

		return $args;
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
	 */
	function font_custom_fields() {
		// For more info about the various options available for fields
		// see this https://github.com/WebDevStudios/CMB2/wiki/Field-Parameters

		$prefix = $this->parent->_token . '_';

		$font_details = new_cmb2_box( array(
			'id'           => $prefix . 'font_details',
			'title'        => esc_html__( 'Font Details', 'fonto' ),
			'object_types' => array( 'font', ), // Post type
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true,  // Show field names on the left
			'cmb_styles'   => false, // false to disable the CMB stylesheet - we are loading our own clean CSS
			'closed'       => false, // true to keep the metabox closed by default
			//'classes'    => 'extra-class', // Extra cmb2-wrap classes
			// 'classes_cb' => 'yourprefix_add_some_classes', // Add classes through a callback.
		) );

		//To put the description after the field label, we use the 'our_desc' key instead of the regular 'desc' one and add the 'render_row_cb' callback

		$font_details->add_field( array(
			'name'        => esc_html__( 'Font Source', 'fonto' ),
			'desc'        => esc_html__( 'Select whether you are using a Custom Web Font Service (Typekit, Myfonts, Fonts.com) or you\'re self-hosting the fonts.', 'fonto' ),
			'id'          => $prefix . 'font_source',
			'type'        => 'radio',
			'options'     => array(
				'font_service' => esc_html__( 'Web Font Service', 'fonto' ),
				'self_hosted'  => esc_html__( 'Self-Hosted', 'fonto' ),
			),
			'default'     => 'font_service',
			'row_classes' => array( 'no-divider', ),
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Fonts Loading / Embed Code', 'fonto' ),
			'id'          => $prefix . 'fonts_loading_embed_code',
			'type'        => 'title',
			'row_classes' => array( 'full-width', 'background__dark' ),
			'before_row'  => '<div class="font-loading-section">',
		) );

		$font_details->add_field( array(
			'name'         => esc_html__( 'Font Files', 'fonto' ),
			'desc'         => esc_html__( 'Upload all the files received from the font service/generator.', 'fonto' ),
			'id'           => $prefix . 'font_files',
			'type'         => 'file_list',
			'preview_size' => '', // Default: array( 50, 50 )
			'query_args' => array(
				// We need the current post ID but we need to set it before the field render, not here.
			),
			'attributes'   => array(
				// Shown for Self-Hosted fonts
				'data-conditional-id'    => $prefix . 'font_source',
				'data-conditional-value' => 'self_hosted',
			),
			'row_classes'  => array( 'background__dark' ),
		) );

		$font_details->add_field( array(
			'name'            => esc_html__( 'URL Path to the font files', 'fonto' ),
			'desc'            => wp_kses_post( __( 'This is the URL path to be used for the uploaded font files. Please use it in the font\'s CSS rules (i.e. replace <code>Fonts/something.woff</code> with <code>http://yourdomain.com/wp-content/uploads/fonts/123/something.woff</code> ).<span class="note">*Note: You need to give a title to this font first and this field will be autofilled!</span>', 'fonto' ) ),
			'id'              => $prefix . 'url_path',
			'type'            => 'text',
			'attributes'      => array(
				// Shown for Self-Hosted fonts
				'data-conditional-id'    => $prefix . 'font_source',
				'data-conditional-value' => 'self_hosted',
				'readonly'               => 'readonly',
			),
			'row_classes'     => array( 'background__dark' ),
			'sanitization_cb' => array( $this, 'sanitize_url_path_not_empty' ),
		) );

		$font_details->add_field( array(
			'name'         => esc_html__( 'Embed Code', 'fonto' ),
			'show_names'   => false,
			'id'           => $prefix . 'embed_code_font_service',
			'type'         => 'textarea_code',
			'attributes'   => array(
				'placeholder'            => esc_html__( 'Your embed code', 'fonto' ),
				'rows'                   => 5,
				// Shown for Web Fonts services
				'data-conditional-id'    => $prefix . 'font_source',
				'data-conditional-value' => 'font_service',
			),
			'before_field' => sprintf( wp_kses_post( __( 'Insert below the embed code (JS/CSS) provided by the font service. <a href="%s" target="_blank">Learn more</a>', 'fonto' ) ), 'https://pixelgrade.com/docs/advanced-customizations/fonto-premium-fonts/' ),
			'after_field'  => wp_kses_post( __( 'The above code will be inserted in the <code>&lt;head&gt;</code> area of your website.', 'fonto' ) ),
			'row_classes'  => array( 'full-width', 'title__large', 'background__dark' ),
		) );

		$font_details->add_field( array(
			'name'         => esc_html__( 'Embed Code', 'fonto' ),
			'show_names'   => true,
			'id'           => $prefix . 'embed_code_self_hosted',
			'type'         => 'textarea_code',
			'attributes'   => array(
				'placeholder'            => esc_html__( 'Your embed code', 'fonto' ),
				'rows'                   => 5,
				// Shown for Self-Hosted fonts
				'data-conditional-id'    => $prefix . 'font_source',
				'data-conditional-value' => 'self_hosted',
			),
			'before_field' => wp_kses_post( __( 'Insert below the CSS code. <a href="#" target="_blank">Learn More</a>', 'fonto' ) ),
			'after_field'  => wp_kses_post( __( 'The above code will be inserted in the <code>&lt;head&gt;</code> area of your website.', 'fonto' ) ),
			'row_classes'  => array( 'full-width', 'title__large', 'background__dark' ),
			'after_row'    => '</div><!-- .font-loading-section -->',
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Weights & Styles Matching', 'fonto' ),
			'id'          => $prefix . 'weights_styles_matching',
			'type'        => 'title',
			'row_classes' => array( 'full-width', ),
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'How Fonts Variations (weights & styles) are declared?', 'fonto' ),
			'desc'        => esc_html__( 'Based on the format that you received the font names from the font service.', 'fonto' ),
			'id'          => $prefix . 'font_name_style',
			'type'        => 'radio',
			'options'     => array(
				'grouped'    => esc_html__( 'Fonts are grouped together in a single "Font Family" name', 'fonto' )
				                . '<span class="option-details">' . wp_kses_post( __( 'When you have only <em>one</em> font name; we will add weights and styles via CSS.<br/>— Example: <em>"proxima-nova"</em> from Typekit or Fonts.com', 'fonto' ) ) . '</span>',
				'individual' => esc_html__( 'Font Names are Referenced Individually', 'fonto' )
				                . '<span class="option-details">' . wp_kses_post( __( 'If you have <em>multiple</em> font names; this means the weights and styles are bundled within each font and we shouldn\'t add them again in CSS.<br/>— Example: <em>"ProximaNW01-Regular"</em> and <em>"ProximaNW01-RegularItalic"</em> from MyFonts.com', 'fonto' ) ) . '</span>',
			),
			'default'     => 'grouped',
			'row_classes' => array( 'full-width', ),
		) );

		$font_details->add_field( array(
			'name'       => esc_html__( 'Font Family Name', 'fonto' ),
			'desc'       => esc_html__( 'Insert the CSS font name as provided by the font service.', 'fonto' ),
			'id'         => $prefix . 'font_family_name',
			'type'       => 'text',
			'attributes' => array(
				'placeholder'            => 'Proxima Nova',
				// Shown when using a single font family name
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'grouped',
			),
		) );

		$font_details->add_field( array(
			'name'              => esc_html__( 'Available Font Variations', 'fonto' ),
			'desc'              => esc_html__( 'Check the available variations for this font.', 'fonto' )
			                       . '<span class="note">' . esc_html__( '*Note that the variations will be available through the Font Selectors and will not make any effect if a specific variation is checked but is not loaded by the font service.', 'fonto' ) . '</span>',
			'id'                => $prefix . 'font_variations',
			'type'              => 'multicheck',
			'select_all_button' => false,
			// 'multiple' => true, // Store values in individual rows
			'options'           => array(
				'100_normal' => esc_html__( 'Thin 100', 'fonto' ),
				'100_italic' => esc_html__( 'Thin Italic', 'fonto' ),
				'200_normal' => esc_html__( 'Extra Light 200', 'fonto' ),
				'200_italic' => esc_html__( 'Extra Light Italic', 'fonto' ),
				'300_normal' => esc_html__( 'Light 300', 'fonto' ),
				'300_italic' => esc_html__( 'Light Italic', 'fonto' ),
				'400_normal' => esc_html__( 'Regular 400', 'fonto' ),
				'400_italic' => esc_html__( 'Regular Italic', 'fonto' ),
				'500_normal' => esc_html__( 'Medium 500', 'fonto' ),
				'500_italic' => esc_html__( 'Medium Italic', 'fonto' ),
				'600_normal' => esc_html__( 'SemiBold 600', 'fonto' ),
				'600_italic' => esc_html__( 'SemiBold Italic', 'fonto' ),
				'700_normal' => esc_html__( 'Bold 700', 'fonto' ),
				'700_italic' => esc_html__( 'Bold Italic', 'fonto' ),
				'800_normal' => esc_html__( 'ExtraBold 800', 'fonto' ),
				'800_italic' => esc_html__( 'ExtraBold Italic', 'fonto' ),
				'900_normal' => esc_html__( 'Black 900', 'fonto' ),
				'900_italic' => esc_html__( 'Black Italic', 'fonto' ),
			),
			'attributes'        => array(
				// Shown when using a single font family name
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'grouped',
			),
			// 'inline'  => true, // Toggles display to inline
			'row_classes'       => array( 'font-variations', 'no-divider' ),
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Font Weights & Styles Variations', 'fonto' ),
			'desc'        => esc_html__( 'Pair the provided fonts references names with their matching weights and styles.', 'fonto' ),
			'id'          => $prefix . 'font_weight_style_variations',
			'type'        => 'title',
			'attributes'  => array(
				// Shown when using a font names are referenced individually
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'full-width', 'title__small', ),
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Thin 100', 'fonto' ),
			'id'          => $prefix . '100_normal_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individually
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
			'before_row'  => '<div class="matching-fields-section">',
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Thin Italic', 'fonto' ),
			'id'          => $prefix . '100_italic_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Extra Light 200', 'fonto' ),
			'id'          => $prefix . '200_normal_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Extra Light Italic', 'fonto' ),
			'id'          => $prefix . '200_italic_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Light 300', 'fonto' ),
			'id'          => $prefix . '300_normal_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Light Italic', 'fonto' ),
			'id'          => $prefix . '300_italic_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Regular 400', 'fonto' ),
			'id'          => $prefix . '400_normal_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Regular Italic', 'fonto' ),
			'id'          => $prefix . '400_italic_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Medium 500', 'fonto' ),
			'id'          => $prefix . '500_normal_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Medium Italic', 'fonto' ),
			'id'          => $prefix . '500_italic_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Semi Bold 600', 'fonto' ),
			'id'          => $prefix . '600_normal_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Semi Bold Italic', 'fonto' ),
			'id'          => $prefix . '600_italic_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Bold 700', 'fonto' ),
			'id'          => $prefix . '700_normal_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Bold Italic', 'fonto' ),
			'id'          => $prefix . '700_italic_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Extra Bold 800', 'fonto' ),
			'id'          => $prefix . '800_normal_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Extra Bold Italic', 'fonto' ),
			'id'          => $prefix . '800_italic_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Black 900', 'fonto' ),
			'id'          => $prefix . '900_normal_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
		) );

		$font_details->add_field( array(
			'name'        => esc_html__( 'Black Italic', 'fonto' ),
			'id'          => $prefix . '900_italic_individual',
			'type'        => 'text_small',
			'attributes'  => array(
				// Shown when using a font names are referenced individualy
				'data-conditional-id'    => $prefix . 'font_name_style',
				'data-conditional-value' => 'individual',
			),
			'row_classes' => array( 'grouped-input', 'half-width', ),
			'after_row'   => '</div><!-- .matching-fields-section -->',
		) );

	}

	public function prevent_cmb2_core_styles( $enqueue ) {
		global $post;

		if ( $post && 'font' === $post->post_type ) {
			return false;
		}

		return $enqueue;
	}

	/**
	 * Ajax handler to retrieve the sample Font URL path to where the font files are uploaded
	 *
	 * @since 1.0.0
	 */
	function wp_ajax_sample_font_url_path() {
		check_ajax_referer( 'samplepermalink', 'samplepermalinknonce' );

		// Get the current URL for the uploads directory.
		$uploads = wp_upload_dir();

		wp_die( trailingslashit( $uploads['url'] ) );
	}

	/**
	 * Make sure that the Font URL Path field is not saved empty (maybe the AJAX that was supposed to retrieve it failed)
	 * @since 1.0.0
	 *
	 * @param mixed      $value      The unsanitized value from the form.
	 * @param array      $field_args Array of field arguments.
	 * @param CMB2_Field $field      The field object
	 *
	 * @return mixed                  Sanitized value to be stored.
	 */
	function sanitize_url_path_not_empty( $value, $field_args, $field ) {
		$sanitized_value = $value;

		if ( empty( $value ) ) {
			//get the current URL for the uploads directory
			$uploads = wp_upload_dir();

			$sanitized_value = trailingslashit( $uploads['url'] );
		}

		return $sanitized_value;
	}

	/**
	 * Load admin CSS - specific for post types
	 * @access  public
	 * @since   1.0.0
	 * @return  bool
	 */
	public function admin_enqueue_styles() {
		global $post;

		// If the post we're editing isn't a font type, exit this function
		if ( ! $post || 'font' != $post->post_type ) {
			return false;
		}

		//Allow others to stop us in enqueueing the CSS
		if ( ! apply_filters( $this->parent->_token . '_cmb2_enqueue_css', true ) ) {
			return false;
		}

		// Only use minified files if SCRIPT_DEBUG is off
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$rtl = is_rtl() ? '-rtl' : '';

		// Filter required styles and register stylesheet
		$styles = apply_filters( $this->parent->_token . '_cmb2_style_dependencies', array() );
		wp_register_style( $this->parent->_token . '-cmb2-styles', esc_url( $this->parent->assets_url ) . "css/cmb2/cmb2{$rtl}{$min}.css", $styles, $this->parent->_version );

		wp_enqueue_style( $this->parent->_token . '-cmb2-styles' );

		return true;
	}

	/**
	 * Load admin Javascript - specific for post types
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  bool
	 */
	public function admin_enqueue_scripts() {
		global $post;

		// If the post we're editing isn't a font type, exit this function
		if ( ! $post || 'font' != $post->post_type ) {
			return false;
		}

		// Make sure that the current post is passed to the media-editor.
		wp_enqueue_media( array( 'post' => $post ) );

		//Allow others to stop us in enqueueing the JS
		if ( ! apply_filters( $this->parent->_token . '_cmb2_enqueue_js', true ) ) {
			return false;
		}

		// Register our cmb custom JS
		wp_register_script( $this->parent->_token . '-cmb2', esc_url( $this->parent->assets_url ) . 'js/cmb2' . $this->parent->script_suffix . '.js', array(
			'jquery',
		), $this->parent->_version );

		wp_enqueue_script( $this->parent->_token . '-cmb2' );

		return true;

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
	 * @param Fonto $parent Main Fonto instance.
	 *
	 * @return Fonto_Post_Types instance
	 */
	public static function instance( $parent ) {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cheatin&#8217; huh?' ) ), esc_html( $this->parent->_version ) );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {

		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cheatin&#8217; huh?' ) ), esc_html( $this->parent->_version ) );
	}

}