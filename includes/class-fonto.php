<?php
/**
 * Document for Fonto Class
 *
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
require_once 'class-fonto-init.php';
require_once 'lib/class-fonto-option.php';

/**
 * Fonto - Main plugin class
 *
 * @since    Class available since Release 1.0.0
 * @link     https://pixelgrade.com
 * @author   Pixelgrade <contact@pixelgrade.com>
 * @license  GPL v2.0 (or later) see LICENCE file or http://www.gnu.org/licenses/gpl-2.0.html
 * @version  Release: .1
 * @category Html_Tag
 * @package  Fonto
 */
class Fonto extends Fonto_Init {

	/**
	 * The single instance of Fonto.
	 * @since     1.0.0
	 * @var     Fonto
	 * @access    private
	 */
	private static $_instance = null;

	/**
	 * Post_types class object
	 * @since   1.0.0
	 * @var     Fonto_Post_Types
	 * @access  public
	 */
	public $post_types = null;

	/**
	 * Output class object
	 * @since   1.0.0
	 * @var     Fonto_Output
	 * @access  public
	 */
	public $output = null;

	/**
	 * Option class object
	 * @since   1.0.0
	 * @var     Fonto_Option
	 * @access  public
	 */
	public $option = null;

	/**
	 * Admin Api class object
	 * @since   1.0.0
	 * @var     object
	 * @access  public
	 */
	public $admin = null;

	/**
	 * The token.
	 * @since   1.0.0
	 * @var     string
	 * @access  public
	 */
	public $_token;

	/**
	 * The main plugin file.
	 * @since   1.0.0
	 * @var     string
	 * @access  public
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @since   1.0.0
	 * @var     string
	 * @access  public
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @since   1.0.0
	 * @var     string
	 * @access  public
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @since   1.0.0
	 * @var     string
	 * @access  public
	 */
	public $assets_url;

	/**
	 * Suffix for JavaScripts.
	 * @since   1.0.0
	 * @var     string
	 * @access  public
	 */
	public $script_suffix;

	/**
	 * Constructor for Fonto
	 *
	 * @param string $file    Name of main plugin file (used for determining paths).
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

		// Hook into the upload process to sanitize SVG uploads
		add_filter('wp_handle_upload_prefilter', array($this, 'sanitize_svg_upload'));
	}

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

		/* Load vendors */
		$this->load_vendors();

		/* Load integrations */
		add_action( 'init', array( $this, 'load_integrations' ) );

		// Load plugin environment variables.
		$this->dir        = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load admin JS & CSS.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		// Handle localisation.
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		// Add custom post types.
		if ( is_null( $this->post_types ) ) {
			$this->post_types = Fonto_Post_Types::instance( $this );
		}

		// Add the output - this is where things get interesting
		if ( is_null( $this->output ) ) {
			$this->output = Fonto_Output::instance( $this );
		}

		//Add our fonts mime types
		add_filter( 'upload_mimes', array( $this, 'extra_mime_types' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'update_mime_types' ), 10, 3 );

	}

	/**
	 * Wrapper function to register a new post type
	 *
	 * @param string $post_type   Post type name.
	 * @param string $plural      Post type item plural name.
	 * @param string $single      Post type item single name.
	 * @param string $description Description of post type.
	 * @param array  $options     Overide default post type arguments.
	 *
	 * @return object|null              Post type class object
	 */
	public function register_post_type( $post_type = '', $plural = '', $single = '', $description = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) {
			return null;
		}

		$post_type = new Fonto_Post_Type( $post_type, $plural, $single, $description, $options );

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy
	 *
	 * @param string $taxonomy      Taxonomy name.
	 * @param string $plural        Taxonomy single name.
	 * @param string $single        Taxonomy plural name.
	 * @param array  $post_types    Post types to which this taxonomy applies.
	 * @param array  $taxonomy_args Overide default taxonomy arguments.
	 *
	 * @return object|null             Taxonomy class object.
	 */
	public function register_taxonomy( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) {
			return null;
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
			return;
		}

		// Only use minified files if SCRIPT_DEBUG is off
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$rtl = is_rtl() ? '-rtl' : '';

		// Filter required styles and register stylesheet
		$styles = apply_filters( $this->_token . '_admin_style_dependencies', array() );
		wp_register_style( $this->_token . '-admin-styles', esc_url( $this->assets_url ) . "css/admin{$rtl}{$min}.css", $styles, $this->_version );

		wp_enqueue_style( $this->_token . '-admin-styles' );
	}

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

	}

	/**
	 * Load vendors
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_vendors() {

		// Load everything about CMB2 - if that is the case.
		if ( file_exists( dirname( __FILE__ ) . '/vendor/CMB2/init.php' ) ) {
			require_once dirname( __FILE__ ) . '/vendor/CMB2/init.php';
		}
		add_filter( 'cmb2_script_dependencies', array( $this, 'cmb2_requires_wp_media' ) );

		// The CMB2 conditional display of fields
		if ( file_exists( dirname( __FILE__ ) . '/vendor/cmb2-conditionals/cmb2-conditionals.php' ) ) {
			require_once dirname( __FILE__ ) . '/vendor/cmb2-conditionals/cmb2-conditionals.php';
		}
	}

	/**
	 * Load various integrations with other plugins
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_integrations() {

		/**
		 * Load Customify compatibility file.
		 * @see https://wordpress.org/plugins/customify/
		 */
		if ( class_exists( 'PixCustomifyPlugin' ) ) {
			require_once dirname( __FILE__ ) . '/integrations/customify.php';
		}

		/**
		 * Load Style Manager compatibility file.
		 * @see https://wordpress.org/plugins/style-manager/
		 */
		if ( defined( '\Pixelgrade\StyleManager\VERSION' ) ) {
			require_once dirname( __FILE__ ) . '/integrations/style-manager.php';
		}
	}

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation() {
		//for the plugin
		$this->l10ni18n();

	}

	/**
	 * Registers Fonto text domain path
	 * @since  1.0.0
	 */
	public function l10ni18n() {

		$loaded = load_plugin_textdomain( 'fonto', false, '/languages/' );

		if ( ! $loaded ) {
			$loaded = load_muplugin_textdomain( 'fonto', '/languages/' );
		}

		if ( ! $loaded ) {
			$loaded = load_theme_textdomain( 'fonto', get_stylesheet_directory() . '/languages/' );
		}

		if ( ! $loaded ) {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'fonto' );
			$mofile = dirname( __DIR__ ) . '/languages/fonto-' . $locale . '.mo';
			load_textdomain( 'fonto', $mofile );
		}

	}

	/**
	 * Add mime types specific to font files
	 *
	 * @param array $mimes
	 *
	 * @return array
	 */
	function extra_mime_types( $mimes ) {
		$mimes['woff']  = 'application/x-font-woff';
		$mimes['woff2'] = 'application/x-font-woff2';
		$mimes['ttf']   = 'application/x-font-ttf';
		$mimes['svg']   = 'image/svg+xml';
		$mimes['eot']   = 'application/vnd.ms-fontobject';
		$mimes['otf']   = 'font/otf';

		return $mimes;
	}

	/**
	 * Correct the mime-types and extension for the font types.
	 *
	 * @param array  $defaults File data array containing 'ext', 'type', and
	 *                                          'proper_filename' keys.
	 * @param string $file                      Full path to the file.
	 * @param string $filename                  The name of the file (may differ from $file due to
	 *                                          $file being in a tmp directory).
	 * @return Array File data array containing 'ext', 'type', and
	 */
	public function update_mime_types( $defaults, $file, $filename ) {
		if ( 'ttf' === pathinfo( $filename, PATHINFO_EXTENSION ) ) {
			$defaults['type'] = 'application/x-font-ttf';
			$defaults['ext']  = 'ttf';
		}

		if ( 'otf' === pathinfo( $filename, PATHINFO_EXTENSION ) ) {
			$defaults['type'] = 'application/x-font-otf';
			$defaults['ext']  = 'otf';
		}

		return $defaults;
	}

	function cmb2_requires_wp_media( $dependencies ) {
		$dependencies['media-editor'] = 'media-editor';

		return $dependencies;
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
	 * @see    Fonto()
	 *
	 * @param string $version Version.
	 *
	 * @param string $file    File.
	 *
	 * @return Fonto Main Fonto instance
	 */
	public static function instance( $file = '', $version = '1.0.0' ) {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cheatin&#8217; huh?' ) ), esc_html( $this->_version ) );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {

		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cheatin&#8217; huh?' ) ), esc_html( $this->_version ) );
	}

	/**
	 * Handles the SVG upload process by sanitizing the file content,
	 * generating a random filename, and preserving the original upload path.
	 *
	 * @param array $file The uploaded file information.
	 * @return array The modified file information with sanitized content and updated filename.
	 */
	public function sanitize_svg_upload($file) {
		// Check if the uploaded file is an SVG
		if ($file['type'] === 'image/svg+xml') {
			// Step 1: Read the original SVG content from the temporary file location
			$svg_content = file_get_contents($file['tmp_name']);
			
			// Step 2: Sanitize the SVG content to remove any potentially unsafe elements
			$clean_svg = $this->sanitize_svg_content($svg_content);

			// Step 3: Extract the original file name (without extension) for use in the new name
			$original_name = pathinfo($file['name'], PATHINFO_FILENAME);

			// Step 4: Sanitize the original name to ensure no special characters remain
			$sanitized_name = sanitize_file_name($original_name);

			// Step 5: Generate a unique filename by appending a random suffix to the sanitized name
			$random_suffix = wp_generate_password(6, false);
			$new_filename = "{$sanitized_name}-{$random_suffix}.svg";

			// Step 6: Overwrite the temporary file with the sanitized SVG content
			file_put_contents($file['tmp_name'], $clean_svg);

			// Step 7: Update the file's displayed name, leaving tmp_name intact for WordPress to handle
			$file['name'] = $new_filename;
		}
		
		// Return the updated file information back to WordPress
		return $file;
	}

	/**
	 * Sanitizes SVG content by removing <script> elements and any unsafe attributes.
	 * This helps mitigate potential XSS vulnerabilities from SVG uploads.
	 *
	 * @param string $svg_content The raw SVG content to be sanitized.
	 * @return string The sanitized SVG content.
	 */
	private function sanitize_svg_content($svg_content) {
		// Create a new DOMDocument instance to parse the SVG XML content
		$dom = new DOMDocument();
		
		// Suppress XML parsing errors for invalid SVG formats
		libxml_use_internal_errors(true);
		
		// Load the SVG content into the DOMDocument for manipulation
		$dom->loadXML($svg_content, LIBXML_NOENT | LIBXML_DTDLOAD);
		
		// Clear any XML parsing errors
		libxml_clear_errors();
		
		// Step 1: Remove any <script> elements, which can execute JavaScript
		$scripts = $dom->getElementsByTagName('script');
		while ($scripts->length > 0) {
			$scripts->item(0)->parentNode->removeChild($scripts->item(0));
		}

		// Step 2: Remove unsafe attributes that could contain JavaScript (like onclick, onload)
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//@*') as $attr) {
			if (stripos($attr->name, 'on') === 0 || stripos($attr->value, 'javascript:') === 0) {
				$attr->parentNode->removeAttribute($attr->name);
			}
		}

		// Return the sanitized SVG content as XML
		return $dom->saveXML();
	}
}
