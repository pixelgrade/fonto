<?php
/**
 * Document for Fonto Class
 *
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
	 * @var     Fonto
	 * @access  private
	 * @since     1.0.0
	 */
	private static $_instance = null;

	/**
	 * The single instance of Hashids.
	 * @var     Hashids
	 * @access  private
	 * @since     1.0.0
	 */
	protected static $hashids = null;

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
	 * Output class object
	 * @var     Fonto_Output
	 * @access  public
	 * @since   1.0.0
	 */
	public $output = null;

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
		include_once 'class-fonto-post-types.php';
		include_once 'class-fonto-output.php';

		// Load plugin libraries.
		include_once 'lib/class-fonto-post-type.php';
		include_once 'lib/class-fonto-taxonomy.php';

		// Load extras
		include_once 'extras.php';

		// Initialize hashids used for upload directories
		$this->initialize_hashids();

		/* Load vendors */
		// The main CMB2
		if ( file_exists( dirname( __FILE__ ) . '/vendor/cmb2/init.php' ) ) {
			require_once dirname( __FILE__ ) . '/vendor/cmb2/init.php';
		}

		//The CMB2 conditional display of fields
		if ( file_exists( dirname( __FILE__ ) . '/vendor/cmb2-conditionals/cmb2-conditionals.php' ) ) {
			require_once dirname( __FILE__ ) . '/vendor/cmb2-conditionals/cmb2-conditionals.php';
		}

		/* Load integrations */
		add_action( 'init', array( $this, 'load_integrations' ) );

		// Load plugin environment variables.
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$this->script_suffix = '';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load admin JS & CSS.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		// Handle localisation.
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
		
		// Add custom post types.
		if ( is_null( $this->post_types ) ) {
			$this->post_types = Fonto_Post_Types::instance( $this );
		}

		// Add the output - this is where things get interesting
		if ( is_null( $this->output) ) {
			$this->output = Fonto_Output::instance( $this );
		}

		//Add our fonts mime types
		add_filter( 'upload_mimes', array( $this, 'extra_mime_types' ) );

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
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles() {
		//Allow others to stop us in enqueueing the CSS
		if ( ! apply_filters( $this->_token . '_admin_enqueue_css', true ) ) {
			return false;
		}

		// Only use minified files if SCRIPT_DEBUG is off
		$min   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$rtl   = is_rtl() ? '-rtl' : '';

		// Filter required styles and register stylesheet
		$styles = apply_filters( $this->_token . '_admin_style_dependencies', array() );
		wp_register_style( $this->_token . '-admin-styles', esc_url( $this->assets_url ) . "css/admin{$rtl}{$min}.css", $styles, $this->_version );

		wp_enqueue_style( $this->_token . '-admin-styles' );
		
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts() {

		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array(), $this->_version );
		wp_enqueue_script( $this->_token . '-admin' );

	} // End admin_enqueue_scripts ()

	/**
	 * Load various integrations with other plugins
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_integrations() {

		/**
		 * Load Customify compatibility file.
		 * https://wordpress.org/plugins/customify/
		 */
		if ( class_exists( 'PixCustomifyPlugin' ) ) {
			require_once dirname( __FILE__ ) . '/integrations/customify.php';
		}

	} // End load_integrations ()

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
	 * Add mime types specific to font files
	 *
	 * @param array $mimes
	 *
	 * @return array
	 */
	function extra_mime_types( $mimes ) {
		$mimes['eot'] = 'application/vnd.ms-fontobject';
		$mimes['otf|ttf'] = 'application/font-sfnt';
		$mimes['woff'] = 'application/font-woff';
		//people are not quite sure yet on the mime-type, so it's best to use them both
		$mimes['woff2'] = 'application/font-woff2';
		$mimes['woff2'] = 'font/woff2';

		$mimes['svg'] = 'image/svg+xml';

		return $mimes;
	}

	/**
	 * Initialize the Hashids library with our own salt and settings
	 */
	protected function initialize_hashids() {
		//Used for generating post_ID hashes without collisions
		require_once( dirname( __FILE__ ) . '/vendor/hashids/Hashids.php');

		//do not change this salt - never ever
		//if you do change the salt any previous hashed IDs will no longer work

		//the minimum hash length is 8
		self::$hashids = new Hashids('youshouldgetuniquefontdirectories', 8);
	}

	/**
	 * Given an positive integer ID return a hash string
	 *
	 * @param integer $id
	 *
	 * @return string
	 */
	public function hash_encode_ID( $id ) {
		return self::$hashids->encode( $id );
	}

	/**
	 * Given an hash string return the decoded ID (positive integer)
	 *
	 * @param string $hash_ID
	 *
	 * @return int|bool
	 */
	public function hash_decode_ID( $hash_ID ) {
		$data = self::$hashids->decode( $hash_ID );

		if ( empty( $data ) ) {
			return false;
		}

		if ( is_array( $data ) ) {
			return array_shift( $data );
		}

		return $data;
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
