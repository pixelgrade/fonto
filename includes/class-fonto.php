<?php
/**
 * Document for Fonto
 *
 * PHP Version 5.6
 *
 * @category Class
 * @package  Fonto
 * @author   PixelGrade <contact@pixelgrade.com>
 * @license  GPL v2.0 (or later) see LICENCE file or http://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://pixelgrade.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once 'class-fonto-init.php';
require_once 'lib/class-fonto-option.php';

/**
 * Fonto - Main plugin class
 *
 * @category Html_Tag
 * @package  Fonto
 * @author   PixelGrade <contact@pixelgrade.com>
 * @license  GPL v2.0 (or later) see LICENCE file or http://www.gnu.org/licenses/gpl-2.0.html
 * @version  Release: .1
 * @link     https://pixelgrade.com
 * @since    Class available since Release 1.0.0
 */
class Fonto extends Fonto_Init {

	/**
	 * The single instance of Fonto.
	 * @var     object
	 * @access  private
	 * @since     1.0.0
	 */
	private static $_instance = null;

	/**
	 * Settings class object
	 * @var     Fonto_Settings
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * Post_types class object
	 * @var     Fonto_Post_Types
	 * @access  public
	 * @since   1.0.0
	 */
	public $post_types = null;

	/**
	 * Option class object
	 * @var     Fonto_Option
	 * @access  public
	 * @since   1.0.0
	 */
	public $option = null;

	/**
	 * Admin Api class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $admin = null;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for JavaScripts.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor for Fonto
	 *
	 * @param string $file Name of main plugin file (used for determining paths).
	 * @param string $version Version number of this plugin.
	 */
	public function __construct( $file = '', $version = '1.0.0' ) {

		$this->_version = $version;
		$this->file     = $file;
		$this->_token   = 'fonto';

		// Add options API.
		if ( is_null( $this->option ) ) {
			$this->option = Fonto_Option::instance( $this );
		}

		parent::__construct();

		if ( $this->php_version_check() ) {
			// Only load and run the init function if we know PHP version can parse it.
			$this->upgrade();
			$this->init();
		}

	} // End __construct ().

	/**
	 * Initialze plugin
	 */
	private function init() {

		// Load plugin class files.
		include_once 'class-fonto-settings.php';
		include_once 'class-fonto-post-types.php';

		// Load plugin libraries.
		include_once 'lib/class-fonto-admin-api.php';
		include_once 'lib/class-fonto-post-type.php';
		include_once 'lib/class-fonto-taxonomy.php';

		// Load vendors
		if ( file_exists( dirname( __FILE__ ) . '/vendor/cmb2/init.php' ) ) {
			require_once dirname( __FILE__ ) . '/vendor/cmb2/init.php';
		}

		// Load plugin environment variables.
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$this->script_suffix = '';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load frontend JS & CSS.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		// Load API for generic admin functions.
		if ( is_admin() ) {
			$this->admin = new Fonto_Admin_API( $this );
		}

		// Handle localisation.
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		// Add admin settings.
		if ( is_null( $this->settings ) ) {
			$this->settings = Fonto_Settings::instance( $this );
		}

		// Add custom post types.
		if ( is_null( $this->post_types ) ) {
			$this->post_types = Fonto_Post_Types::instance( $this );
		}

	}

	/**
	 * Wrapper function to register a new post type
	 *
	 * @param  string $post_type Post type name.
	 * @param  string $plural Post type item plural name.
	 * @param  string $single Post type item single name.
	 * @param  string $description Description of post type.
	 * @param  array $options Overide default post type arguments.
	 *
	 * @return object              Post type class object
	 */
	public function register_post_type( $post_type = '', $plural = '', $single = '', $description = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) {
			return;
		}

		$post_type = new Fonto_Post_Type( $post_type, $plural, $single, $description, $options );

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy
	 *
	 * @param  string $taxonomy Taxonomy name.
	 * @param  string $plural Taxonomy single name.
	 * @param  string $single Taxonomy plural name.
	 * @param  array $post_types Post types to which this taxonomy applies.
	 * @param  array $taxonomy_args Overide default taxonomy arguments.
	 *
	 * @return object             Taxonomy class object.
	 */
	public function register_taxonomy( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) {
			return;
		}

		$taxonomy = new Fonto_Taxonomy( $taxonomy, $plural, $single, $post_types, $taxonomy_args );

		return $taxonomy;
	}

	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles() {

		wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-frontend' );

	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts() {

		wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-frontend' );
	} // End enqueue_scripts ()

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles() {

		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );

		wp_register_style( 'jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css' );
		wp_enqueue_style( 'jquery-ui' );
		wp_enqueue_style(
			$this->_token . ' datetime-picker-style',
			esc_url( $this->assets_url ) . 'css/jquery-ui-timepicker-addon.css'
		);
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts() {

		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array(
			'jquery',
			'jquery-ui-tabs'
		), $this->_version );
		wp_enqueue_script( $this->_token . '-admin' );

		wp_enqueue_script(
			$this->_token . 'jquery-datetimepicker',
			esc_url( $this->assets_url ) . 'js/jquery-ui-timepicker-addon.js',
			array( 'jquery', 'jquery-ui-datepicker' )
		);

		// We're including the WP media scripts here because they're needed for the image upload field.
		// If you're not including an image upload then you can leave this function call out.
		wp_enqueue_media();

	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation() {

		load_plugin_textdomain( 'fonto', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain() {

		$domain = 'fonto';

		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	}

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install() {

		$this->log_version_number();
	} // End install ()

	/**
	 * Main Fonto Instance
	 *
	 * Ensures only one instance of Fonto is loaded or can be loaded.
	 *
	 * @since  1.0.0
	 * @static
	 *
	 * @param string $file File.
	 * @param string $version Version.
	 *
	 * @see    Fonto()
	 * @return Main Fonto instance
	 */
	public static function instance( $file = '', $version = '1.0.0' ) {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}

		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cheatin&#8217; huh?' ) ), esc_html( $this->_version ) );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {

		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cheatin&#8217; huh?' ) ), esc_html( $this->_version ) );
	} // End __wakeup ()
}
