<?php
/**
 * Document for class Fonto_Output
 *
 * @category Class
 * @package Fonto
 * @author   Pixelgrade <contact@pixelgrade.com>
 * @license  GPL v2.0 (or later) see LICENCE file or http://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://pixelgrade.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles the "end" effects of the plugin
 * Like adding code in wp_head, adding selects in the WP Editor, and so on.
 * Also it will expose various information for others to use (fonts lista and so on).
 *
 * @category include
 * @package  Fonto
 * @author   Pixelgrade <contact@pixelgrade.com>
 * @license  GPL v2.0 (or later) see LICENCE file or http://www.gnu.org/licenses/gpl-2.0.html
 * @version  Release: .1
 * @link     https://pixelgrade.com
 * @since    Class available since Release .1
 */
class Fonto_Output {

	/**
	 * The single instance of Fonto_Output.
	 * @var     Fonto_Output
	 * @access  private
	 * @since     1.0.0
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 * @var     Fonto
	 * @access  public
	 * @since     1.0.0
	 */
	public $parent = null;

	/**
	 * The prefix used for metas and options.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $prefix;

	/**
	 * The fonts variations
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $font_variations_options;

	/**
	 * Constructor for Fonto_Output class
	 *
	 * @param Object $parent Fonto Object.
	 *
	 * @return void
	 */
	public function __construct( $parent ) {

		$this->parent = $parent;

		$this->prefix = $this->parent->_token . '_';

		// These are all the variations we are using
		$this->font_variations_options = array(
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
			'900_normal' => __( 'Black 100', 'fonto' ),
			'900_italic' => __( 'Black Italic', 'fonto' ),
		);

		//add front-end embed code in the <head> area
		add_action( 'wp_head', array( $this, 'add_front_embed_code' ), 7 );

		// Add the font family and sizes selects in TinyMCE
		add_filter('mce_buttons_2', array( $this, 'add_font_family_sizes_selects' ) );
		add_filter('tiny_mce_before_init', array( $this, 'tiny_mce_before_init' ) );

		//add embed code in the WP editor
		add_filter( 'mce_external_plugins', array( $this, 'tinymce_raw_head_code_plugin' ) );

		foreach ( array('post.php','post-new.php') as $hook ) {
			//add the embed code to the WP admin page so we can use it for previews in the typography selects
			add_action( "admin_head-$hook", array( $this, 'add_admin_embed_code' ) );

			//add the embed code as a JS variable that can be used in the TinyMCE iframe
			add_action( "admin_head-$hook", array( $this, 'localize_tinymce_raw_head_code_plugin' ) );
		}

		//add custom CSS for editor
		add_action( 'admin_init', array( $this, 'add_custom_css_into_wp_editor' ) );
	}

	public function get_fonts( $args = array() ) {
		$defaults = array(
			'numberposts' => -1,
			'orderby' => 'menu_order, date',
			'order' => 'DESC',
			'post_type' => 'font',
			'post_status' => 'publish',
		);

		$final_args = wp_parse_args( $args, $defaults );

		//allow others to filter the args
		$final_args = apply_filters( $this->parent->_token . '_get_fonts_args', $final_args, $args );

		$fonts = get_posts( $final_args );

		//allow others to filter the found fonts
		return apply_filters( $this->parent->_token . '_get_fonts', $fonts, $final_args );
	}

	public function get_fonts_embed_code() {
		//initialize
		$embed_code = '';

		//get all the defined fonts (only those published)
		$fonts = $this->get_fonts();
		if ( ! empty( $fonts ) ) {
			foreach ( $fonts as $font ) {
				//first determine what kind of font source are we using (web font service or self-hosted)
				$font_source = get_post_meta( $font->ID, $this->prefix . 'font_source', true );
				if ( empty( $font_source ) ) {
					//use the default
					$font_source = 'font_service';
				}

				if ( 'font_service' == $font_source ) {
					/* ===== WEB FONT SERVICE ==== */

					//get the embed code
					$embed = trim( get_post_meta( $font->ID, $this->prefix . 'embed_code_font_service', true ) );

					if ( ! empty( $embed ) ) {

						// A little sanity check - people sometimes forget
						// For font services, we expect the embed code to be some JS either inline or external
						// If no <scrip> or <style> tags are present, wrap it in a <script>

						// Remove all spaces so we can better compare
						$temp_embed = str_replace( ' ', '', $embed );
						if ( strpos( $temp_embed, '</script>' ) === false && strpos( $temp_embed, '</style>' ) === false && strpos( $temp_embed, '<link' ) === false ) {
							$embed = '<script>' . $embed . '</script>';
						}

						$embed_code .= $embed . PHP_EOL;
					}

				} elseif ( 'self_hosted' == $font_source ) {
					/* ===== SELF-HOSTED FONT ==== */

					//get the embed code
					$embed = trim( get_post_meta( $font->ID, $this->prefix . 'embed_code_self_hosted', true ) );

					if ( ! empty( $embed ) ) {

						// A little sanity check - people sometimes forget
						// For self-hosted fonts, we expect the embed code to be some CSS either inline or external
						// If no <scrip> or <style> tags are present, wrap it in a <style>

						// Remove all spaces so we can better compare
						$temp_embed = str_replace( ' ', '', $embed );
						if ( strpos( $temp_embed, '</style>' ) === false && strpos( $temp_embed, '</script>' ) === false && strpos( $temp_embed, '<link' ) === false ) {
							$embed = '<style type="text/css">' . $embed . '</style>';
						}

						$embed_code .= $embed . PHP_EOL;
					}
				}
			}
		}

		return $embed_code;
	}

	public function add_front_embed_code() {
		// Allow others to stop us from adding the embed code in the <head> area
		if ( ! apply_filters( $this->prefix . 'add_front_embed_code', true ) ) {
			return;
		}

		echo $this->get_fonts_embed_code();
	}

	public function add_admin_embed_code() {
		// Allow others to stop us from adding the embed code in the <head> area
		if ( ! apply_filters( $this->prefix . 'add_admin_embed_code', true ) ) {
			return;
		}

		echo $this->get_fonts_embed_code();
	}

	/**
	 * Adds the Raw Head Code external TinyMCE plugin to be loaded into the TinyMCE editor.
	 *
	 * @since 1.0.0
	 *
	 * @param array $plugins  Default array of plugins to be loaded by TinyMCE.
	 * @return array $plugins Amended array of plugins to be loaded by TinyMCE.
	 */
	public function tinymce_raw_head_code_plugin( $plugins ) {
		$plugins['raw_head_code'] = esc_url( $this->parent->assets_url ) . 'js/tinymce_raw_head_code_plugin.js?ver=' . $this->parent->_version;
		return $plugins;
	}

	/**
	 * Localize the TinyMCE Raw Head Code plugin to receive the actual code to inject in the iframe's head
	 */
	function localize_tinymce_raw_head_code_plugin() {
		$embed_code = json_encode( $this->get_fonts_embed_code() );
		?>
		<!-- TinyMCE Raw Head Plugin -->
		<script type='text/javascript'>
			var tinymce_raw_head_code = {
				'code': <?php echo $embed_code; ?>,
			};
		</script>
		<!-- TinyMCE Raw Head Plugin -->
		<?php
	}

	/**
	 * Activate the TinyMCE fontsize and font selects
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	function add_font_family_sizes_selects( $options ) {
		//put the selects on the front
		array_unshift( $options, 'fontsizeselect' );
		array_unshift( $options, 'fontselect' );

		return $options;
	}

	/**
	 * Add our font families to the fonts select in TinyMCE
	 *
	 * @param array $mceInit
	 *
	 * @return array
	 */
	function tiny_mce_before_init( $mceInit ) {
		// initialize
		$custom_font_formats = '';

		//get all the defined fonts (only those published)
		$fonts = $this->get_fonts();
		if ( ! empty( $fonts ) ) {
			foreach ( $fonts as $font ) {
				//first determine how the font families are named
				$font_name_style = get_post_meta( $font->ID, $this->prefix . 'font_name_style', true );
				if ( empty( $font_name_style ) ) {
					//use the default
					$font_name_style = 'grouped';
				}

				if ( 'grouped' == $font_name_style ) {
					/* ===== Fonts are grouped together in a single "Font Family" name ==== */
					$font_family_name = trim( get_post_meta( $font->ID, $this->prefix . 'font_family_name', true ) );

					// Grab the font variations meta
					// this is a single meta holding an array
					$font_variations = get_post_meta( $font->ID, $this->prefix . 'font_variations', true );
					// if the font has some variations then we can use it
					if ( ! empty( $font_variations ) ) {
						$custom_font_formats .= esc_html( $font->post_title . '=' . $font_family_name . ';' );
					}
				} elseif ( 'individual' == $font_name_style ) {
					/* ===== Font Names are Referenced Individually ==== */
					// We need to loop through all the variations and use only those filled with a font name
					foreach ( $this->font_variations_options as $id => $display_name ) {
						$font_family_name = trim( get_post_meta( $font->ID, $this->prefix . $id . '_individual', true ) );
						if ( ! empty( $font_family_name ) ) {
							$custom_font_formats .= esc_html( $font_family_name . '=' . $font_family_name . ';' );
						}
					}
				}
			}
		}
		// prepend our custom fonts in front of the TinyMCE default font list
		// see here for more details http://archive.tinymce.com/wiki.php/Configuration:font_formats
		$mceInit['font_formats'] = $custom_font_formats . 'Andale Mono=andale mono,monospace;Arial=arial,helvetica,sans-serif;Arial Black=arial black,sans-serif;Book Antiqua=book antiqua,palatino,serif;Comic Sans MS=comic sans ms,sans-serif;Courier New=courier new,courier,monospace;Georgia=georgia,palatino,serif;Helvetica=helvetica,arial,sans-serif;Impact=impact,sans-serif;Symbol=symbol;Tahoma=tahoma,arial,helvetica,sans-serif;Terminal=terminal,monaco,monospace;Times New Roman=times new roman,times,serif;Trebuchet MS=trebuchet ms,geneva,sans-serif;Verdana=verdana,geneva,sans-serif;Webdings=webdings;Wingdings=wingdings,zapf dingbats';

		return $mceInit;
	}

	function add_custom_css_into_wp_editor() {
		// Allow others to stop us from adding the custom CSS to the WP Editor (i.e. our Customify plugin)
		if ( ! apply_filters( $this->prefix . 'add_custom_css_to_editor', true ) ) {
			return;
		}

		// We will use and AJAX call to return a "file" with the dynamic CSS
		// This way we can use the regular way of adding custom CSS to the WP editor through add_editor_style()
		// So, define our AJAX endpoints
		add_action( 'wp_ajax_fonto_editor_dynamic_css', array( $this, 'editor_dynamic_css' ) );
		add_action( 'wp_ajax_nopriv_fonto_editor_dynamic_css', array( $this, 'editor_dynamic_css' ) );

		//enqueue the editor style
		add_editor_style( admin_url('admin-ajax.php').'?action=fonto_editor_dynamic_css' );
	}

	public function editor_dynamic_css() {
		//first set the headers
		header("Content-type: text/css; charset: UTF-8");

		echo '/* Go on! CSS something nice */' . PHP_EOL;

		$fonts = $this->get_fonts();
		if ( ! empty( $fonts ) ) {
			foreach ( $fonts as $font ) {
				//first determine what kind of font source are we using (web font service or self-hosted)
				$font_source = get_post_meta( $font->ID, $this->prefix . 'font_source', true );
				if ( empty( $font_source ) ) {
					//use the default
					$font_source = 'font_service';
				}

				if ( 'font_service' == $font_source ) {
					/* ===== WEB FONT SERVICE ==== */

					//right now we do nothing

				} elseif ( 'self_hosted' == $font_source ) {
					/* ===== SELF-HOSTED FONT ==== */

					//right now we do nothing
				}
			}
		}

		//don't forget to exit
		exit;
	}

	/**
	 * Main Fonto_Output Instance
	 *
	 * Ensures only one instance of Fonto_Output is loaded or can be loaded.
	 *
	 * @since  1.0.0
	 * @static
	 * @see    Fonto()
	 *
	 * @param  Object $parent Main Fonto instance.
	 *
	 * @return Fonto_Output instance
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
