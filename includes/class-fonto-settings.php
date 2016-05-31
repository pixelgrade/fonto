<?php
/**
 * Document for class Fonto_Settings
 *
 * PHP Version 5.6
 *
 * @category Class
 * @package Fonto
 * @author   PixelGrade <peter@geotonics.com>
 * @license  GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 * @link     https://pixelgrade.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to create settings
 *
 * @category include
 * @package  Fonto
 * @author   PixelGrade <peter@geotonics.com>
 * @license  GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 * @version  Release: .1
 * @link     http://geotonics.com
 * @since    Class available since Release .1
 */
class Fonto_Settings
{

	/**
	 * The single instance of Fonto_Settings.
	 * @var     object
	 * @access  private
	 * @since     1.0.0
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 * @var     object
	 * @access  public
	 * @since     1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	/**
	 * Constructor for Fonto_Settings class
	 * @param Object $parent Fonto Object.
	 * @return void
	 */
	public function __construct( $parent ) {

		$this->parent = $parent;

		$this->base = 'wpt_';

		// Initialise settings.
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register plugin settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add settings page to menu.
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page.
		add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file ), array( $this, 'add_settings_link' ) );

		add_action( 'admin_menu', array( $this, 'settings_assets' ) );

		// Add links to users page.
		add_filter( 'manage_users_columns', array( $this, 'new_users_column' ), 10, 1 );
		add_filter( 'manage_users_custom_column', array( $this, 'new_users_column_data' ), 10, 3 );
	}

	/**
	 * Initialise settings
	 * @return void
	 */
	public function init_settings() {

		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page to admin menu
	 * @return void
	 */
	public function add_menu_item() {

		$page = add_options_page( __( 'Fonto Settings', 'fonto' ), __( 'Fonto', 'fonto' ), 'manage_options', $this->parent->_token . '_settings',  array( $this, 'settings_page' ) );
	}

	/**
	 * Load settings JS & CSS
	 * @return void
	 */
	public function settings_assets() {

		// We're including the farbtastic script & styles here because they're needed for the colour picker.
		// If you're not including a colour picker field then you can leave these calls out as well as the farbtastic dependency for the wpt-admin-js script below.
		wp_enqueue_style( 'farbtastic' );
		wp_enqueue_script( 'farbtastic' );

		wp_register_script( $this->parent->_token . '-settings-js', $this->parent->assets_url . 'js/settings' . $this->parent->script_suffix . '.js', array( 'farbtastic', 'jquery' ), '1.0.0' );
		wp_enqueue_script( $this->parent->_token . '-settings-js' );
	}

	/**
	 * Add settings link to plugin list table
	 * @param  array $links Existing links.
	 * @return array         Modified links
	 */
	public function add_settings_link( $links ) {

		$settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __( 'Settings', 'fonto' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {

		$settings['standard'] = array(
		'title'                    => __( 'Standard', 'fonto' ),
		'description'            => __( 'These are fairly standard form input fields.', 'fonto' ),
		'fields'                => array(
		array(
		'id'             => 'text_field',
		'label'            => __( 'Some Text', 'fonto' ),
		'description'    => __( 'This is a standard text field.', 'fonto' ),
		'type'            => 'text',
		'default'        => '',
		'placeholder'    => __( 'Placeholder text', 'fonto' ),
		),
		array(
		'id'             => 'password_field',
		'label'            => __( 'A Password', 'fonto' ),
		'description'    => __( 'This is a standard password field.', 'fonto' ),
		'type'            => 'password',
		'default'        => '',
		'placeholder'    => __( 'Placeholder text', 'fonto' ),
		),
		array(
		'id'             => 'secret_text_field',
		'label'            => __( 'Some Secret Text', 'fonto' ),
		'description'    => __( 'This is a secret text field - any data saved here will not be displayed after the page has reloaded, but it will be saved.', 'fonto' ),
		'type'            => 'text_secret',
		'default'        => '',
		'placeholder'    => __( 'Placeholder text', 'fonto' ),
		),
		array(
		'id'             => 'text_block',
		'label'            => __( 'A Text Block', 'fonto' ),
		'description'    => __( 'This is a standard text area.', 'fonto' ),
		'type'            => 'textarea',
		'default'        => '',
		'placeholder'    => __( 'Placeholder text for this textarea', 'fonto' ),
		),
		array(
		'id'             => 'single_checkbox',
		'label'            => __( 'An Option', 'fonto' ),
		'description'    => __( 'A standard checkbox - if you save this option as checked then it will store the option as \'on\', otherwise it will be an empty string.', 'fonto' ),
		'type'            => 'checkbox',
		'default'        => '',
		),
		array(
		'id'             => 'select_box',
		'label'            => __( 'A Select Box', 'fonto' ),
		'description'    => __( 'A standard select box.', 'fonto' ),
		'type'            => 'select',
		'options'        => array( 'drupal' => 'Drupal', 'joomla' => 'Joomla', 'wordpress' => 'WordPress' ),
		'default'        => 'wordpress',
		),
		array(
		'id'             => 'radio_buttons',
		'label'            => __( 'Some Options', 'fonto' ),
		'description'    => __( 'A standard set of radio buttons.', 'fonto' ),
		'type'            => 'radio',
		'options'        => array( 'superman' => 'Superman', 'batman' => 'Batman', 'ironman' => 'Iron Man' ),
		'default'        => 'batman',
		),
		array(
		'id'             => 'multiple_checkboxes',
		'label'            => __( 'Some Items', 'fonto' ),
		'description'    => __( 'You can select multiple items and they will be stored as an array.', 'fonto' ),
		'type'            => 'checkbox_multi',
		'options'        => array( 'square' => 'Square', 'circle' => 'Circle', 'rectangle' => 'Rectangle', 'triangle' => 'Triangle' ),
		'default'        => array( 'circle', 'triangle' ),
		),
		),
		);

		$settings['extra'] = array(
		'title'                    => __( 'Extra', 'fonto' ),
		'description'            => __( 'These are some extra input fields that maybe aren\'t as common as the others.', 'fonto' ),
		'fields'                => array(
		array(
					'id'             => 'number_field',
					'label'            => __( 'A Number', 'fonto' ),
					'description'    => __( 'This is a standard number field - if this field contains anything other than numbers then the form will not be submitted.', 'fonto' ),
					'type'            => 'number',
					'default'        => '',
					'placeholder'    => __( '42', 'fonto' ),
		),
		array(
		'id'             => 'colour_picker',
		'label'            => __( 'Pick a colour', 'fonto' ),
		'description'    => __( 'This uses WordPress\' built-in colour picker - the option is stored as the colour\'s hex code.', 'fonto' ),
		'type'            => 'color',
		'default'        => '#21759B',
		),
		array(
		'id'             => 'an_image',
		'label'            => __( 'An Image', 'fonto' ),
		'description'    => __( 'This will upload an image to your media library and store the attachment ID in the option field. Once you have uploaded an imge the thumbnail will display above these buttons.', 'fonto' ),
		'type'            => 'image',
		'default'        => '',
		'placeholder'    => '',
		),
		array(
		'id'             => 'multi_select_box',
		'label'            => __( 'A Multi-Select Box', 'fonto' ),
		'description'    => __( 'A standard multi-select box - the saved data is stored as an array.', 'fonto' ),
		'type'            => 'select_multi',
		'options'        => array( 'linux' => 'Linux', 'mac' => 'Mac', 'windows' => 'Windows' ),
		'default'        => array( 'linux' ),
		),
		),
		);

		$settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register plugin settings
	 * @return void
	 */
	public function register_settings() {

		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab.
			$current_section = '';
			$tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );

			if ( isset( $tab ) && $tab ) {
				$current_section = $tab;
			} else {
				if ( isset( $tab ) && $tab ) {
					$current_section = $tab;
				}
			}

			foreach ( $this->settings as $section => $data ) {

				if ( $current_section && $current_section !== $section ) { continue;
				}

				// Add section to page.
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), $this->parent->_token . '_settings' );

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field.
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field.
					$option_name = $this->base . $field['id'];
					register_setting( $this->parent->_token . '_settings', $option_name, $validation );

					// Add field to page.
					add_settings_field( $field['id'], $field['label'], array( $this->parent->admin, 'display_field' ), $this->parent->_token . '_settings', $section, array( 'field' => $field, 'prefix' => $this->base ) );
				}

				if ( ! $current_section ) {
					break;
				}
			}
		}
	}

	/**
	 * Display settings section
	 *
	 * @param array $section  Section data.
	 * @return void
	 */
	public function settings_section( $section ) {

		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		$allowed = array(
		    'p' => array(),
		);
		echo  wp_kses( $html, $allowed );
	}

	/**
	 * Load settings page content
	 * @return void
	 */
	public function settings_page() {

		// Build page HTML.
		echo '<div class="wrap" id="' . esc_attr( $this->parent->_token ) . '_settings">
			<h2>' . esc_attr( __( 'Fonto', 'fonto' ) ) . '</h2>';

		$tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );

		// Show page tabs.
		if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

			echo '<h2 class="nav-tab-wrapper">';

			$c = 0;
			foreach ( $this->settings as $section => $data ) {

				// Set tab class.
				$class = 'nav-tab';

				if ( ! isset( $tab ) ) {

					if ( 0 === $c ) {
						$class .= ' nav-tab-active';
					}
				} else {

					if ( isset( $tab ) && $section === $tab ) {
						$class .= ' nav-tab-active';
					}
				}

				// Set tab link.
				$tab_link = add_query_arg( array( 'tab' => $section ) );
				$settings_updated = filter_input( INPUT_GET, 'settings-updated', FILTER_SANITIZE_STRING );

				if ( isset( $settings_updated ) ) {
					$tab_link = remove_query_arg( 'settings-updated', $tab_link );
				}

				// Output tab.
				echo '<a href="' . esc_attr( $tab_link ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";
				++$c;
			}

			echo '</h2>';
		}

		echo '<form method="post" action="options.php" enctype="multipart/form-data">';

		// Get settings fields.
		settings_fields( $this->parent->_token . '_settings' );
		do_settings_sections( $this->parent->_token . '_settings' );

		echo '<p class="submit">
			<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />
			<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings', 'fonto' ) ) . '" />
			</p>
			</form>
		</div>';
	}

	/**
	 * Adds column Fonto data.
	 *
	 * @param string $cols  Name of new column.
	 * @return html Text with links.
	 */
	public function new_users_column( $cols ) {

		$cols['fonto'] = 'Fonto';
		return $cols;
	}

	/**
	 * Provided data for column with links to Fonto data.
	 *
	 * @param string $existing_string    Name of existing columns.
	 * @param string $col_name string    Name of new column.
	 * @param string $user_object_userID User id.
	 * @return html Text with links.
	 */
	public function new_users_column_data( $existing_string, $col_name, $user_object_userID ) {

		if ( 'fonto' !== $col_name ) {
			return $existing_string;
			$preview = get_author_posts_url( $user_object_userID );
		}

		$name = get_the_author_meta( 'user_login', $user_object_userID );
		return '<a href="'.admin_url().'plugins.php?page=fonto_menu_page&author='.$user_object_userID.'">Fonto</a>';
	}


	/**
	 * Main Fonto_Settings Instance
	 *
	 * Ensures only one instance of Fonto_Settings is loaded or can be loaded.
	 *
	 * @since  1.0.0
	 * @static
	 * @see    Fonto()
	 * @param  Object $parent Main Fonto instance.
	 * @return Main Fonto_Settings instance
	 */
	public static function instance( $parent ) {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}
		return self::$_instance;
	} // End instance().

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cheatin&#8217; huh?' ) ), esc_html( $this->parent->_version ) );
	} // End __clone().

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {

		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cheatin&#8217; huh?' ) ), esc_html( $this->parent->_version ) );
	} // End __wakeup().

}
