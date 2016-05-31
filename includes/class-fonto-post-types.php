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

	}

	/**
	 * Add meta boxes
	 * @return void
	 */
	function add_meta_boxes() {

		global $post;

		if ( method_exists( $this, $post->post_type . '_custom_fields' ) ) {
			//make the custom fields for each post type filterable
			add_filter( $post->post_type . '_custom_fields', array(
				$this,
				$post->post_type . '_custom_fields'
			), 10, 2 );
			$this->parent->admin->add_meta_box( 'standard', __( 'Standard', 'fonto' ), array( 'font' ) );
			$this->parent->admin->add_meta_box( 'extra', __( 'Extra', 'fonto' ), array( 'font' ) );
		}
	}

	/**
	 * Supply metaboxes for Font post type edit page
	 *
	 * @return array
	 */
	function font_custom_fields() {

		$fields                                    = array();
		$fields['standard']['tabs']['Text Fields'] = array(
			array(
				'id'          => 'text_field',
				'label'       => __( 'Some Text', 'fonto' ),
				'description' => __( 'This is a standard text field.', 'fonto' ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => __( 'Placeholder text', 'fonto' ),
			),
			array(
				'id'          => 'password_field',
				'label'       => __( 'A Password', 'fonto' ),
				'description' => __( 'This is a standard password field.', 'fonto' ),
				'type'        => 'password',
				'default'     => '',
				'placeholder' => __( 'Placeholder text', 'fonto' ),
			),
			array(
				'id'          => 'secret_text_field',
				'label'       => __( 'Some Secret Text', 'fonto' ),
				'description' => __( 'This is a secret text field - any data saved here will not be displayed after the page has reloaded, but it will be saved.', 'fonto' ),
				'type'        => 'text_secret',
				'default'     => '',
				'placeholder' => __( 'Placeholder text', 'fonto' ),
			),
		);

		$fields['standard']['tabs']['Option Fields'] = array(
			array(
				'id'          => 'single_checkbox',
				'label'       => __( 'An Option', 'fonto' ),
				'description' => __( 'A standard checkbox - if you save this option as checked then it will store the option as \'on\', otherwise it will be an empty string.', 'fonto' ),
				'type'        => 'checkbox',
				'default'     => '',
			),
			array(
				'id'          => 'select_box',
				'label'       => __( 'A Select Box', 'fonto' ),
				'description' => __( 'A standard select box.', 'fonto' ),
				'type'        => 'select',
				'options'     => array( 'drupal' => 'Drupal', 'joomla' => 'Joomla', 'wordpress' => 'WordPress' ),
				'default'     => 'wordpress',
			),
			array(
				'id'          => 'radio_buttons',
				'label'       => __( 'Some Options', 'fonto' ),
				'description' => __( 'A standard set of radio buttons.', 'fonto' ),
				'type'        => 'radio',
				'options'     => array( 'superman' => 'Superman', 'batman' => 'Batman', 'ironman' => 'Iron Man' ),
				'default'     => 'batman',
			),
			array(
				'id'          => 'multiple_checkboxes',
				'label'       => __( 'Some Items', 'fonto' ),
				'description' => __( 'You can select multiple items and they will be stored as an array.', 'fonto' ),
				'type'        => 'checkbox_multi',
				'options'     => array(
					'square'    => 'Square',
					'circle'    => 'Circle',
					'rectangle' => 'Rectangle',
					'triangle'  => 'Triangle'
				),
				'default'     => array( 'circle', 'triangle' ),
			),
		);
		$fields['standard']['fields'][]              =
			array(
				'id'          => 'text_block',
				'label'       => __( 'A Text Block', 'fonto' ),
				'description' => __( 'This is a standard text area.', 'fonto' ),
				'type'        => 'textarea',
				'default'     => '',
				'placeholder' => __( 'Placeholder text for this textarea', 'fonto' ),
			);
		$fields['extra']                             = array(
			array(
				'id'          => 'number_field',
				'label'       => __( 'A Number', 'fonto' ),
				'description' => __( 'This is a standard number field - if this field contains anything other than numbers then the form will not be submitted.', 'fonto' ),
				'type'        => 'number',
				'default'     => '',
				'placeholder' => __( '42', 'fonto' ),
			),
			array(
				'id'          => 'colour_picker',
				'label'       => __( 'Pick a colour', 'fonto' ),
				'description' => __( 'This uses WordPress\' built-in colour picker - the option is stored as the colour\'s hex code.', 'fonto' ),
				'type'        => 'color',
				'default'     => '#21759B',
			),
			array(
				'id'          => 'an_image',
				'label'       => __( 'An Image', 'fonto' ),
				'description' => __( 'This will upload an image to your media library and store the attachment ID in the option field. Once you have uploaded an imge the thumbnail will display above these buttons.', 'fonto' ),
				'type'        => 'image',
				'default'     => '',
				'placeholder' => '',
			),
			array(
				'id'          => 'multi_select_box',
				'label'       => __( 'A Multi-Select Box', 'fonto' ),
				'description' => __( 'A standard multi-select box - the saved data is stored as an array.', 'fonto' ),
				'type'        => 'select_multi',
				'options'     => array( 'linux' => 'Linux', 'mac' => 'Mac', 'windows' => 'Windows' ),
				'default'     => array( 'linux' ),
			),

			array(
				'id'          => 'date_picker_field',
				'label'       => __( 'A Date Picker Field', 'fonto' ),
				'description' => __( 'A standard date picker field.', 'fonto' ),
				'type'        => 'date_picker',
				'placeholder' => '2015-10-01',
			),
			array(
				'id'          => 'datetime_picker_field',
				'label'       => __( 'A Date Time Picker Field', 'fonto' ),
				'description' => __( 'A standard date time picker field.', 'fonto' ),
				'type'        => 'datetime_picker',
				'placeholder' => '2015-10-29 12:14 am',
			),

		);

		return $fields;
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

