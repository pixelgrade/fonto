<?php
/**
 * Document for class Fonto_Template
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
 * Class to create a virtual template
 *
 * @category include
 * @package  Fonto
 * @author   PixelGrade <peter@geotonics.com>
 * @license  GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 * @version  Release: .1
 * @link     http://geotonics.com
 * @since    Class available since Release .1
 */
class Fonto_Template {

	/**
	 * A reference to an instance of this class.
	 * @var string
	 */
	private static $_instance;

	/**
	 * The array of templates that this plugin tracks.
	 * @var array
	 */
	protected $templates;

	/**
	 * Initializes templates by setting filters and administration functions.
	 *
	 * @param string $parent Parent class.
	 *
	 * @return void
	 */
	private function __construct( $parent ) {
		$this->parent = $parent;
		$this->templates = array();
		// Add a filter to the attributes metabox to inject template into the cache.
		add_filter(
			'page_attributes_dropdown_pages_args',
			array( $this, 'register_project_templates' )
		);
		// Add a filter to the save post to inject out template into the page cache.
		add_filter(
			'wp_insert_post_data',
			array( $this, 'register_project_templates' )
		);

		/*
		 * Add a filter to the template include to determine if the page has our
		 * template assigned and return it's path
		 */
		add_filter(
			'template_include',
			array( $this, 'view_project_template' )
		);
		// Add your templates to this array.
		$this->templates = array(
				'edit-custom-posts.php'     => 'Edit Custom Posts',
		);

		add_action( 'wp_ajax_update_post_form', array( $this, 'update_post_form' ) );
		add_action( 'wp_ajax_save_post', array( $this, 'save_post' ) );

	}
	/**
	 * Adds our template to the pages cache in order to trick WordPress
	 * into thinking the template file exists where it doens't really exist.
	 *
	 * @param array $atts Register project attributes.
	 */
	public function register_project_templates( $atts ) {
		// Create the key used for the themes cache.
		$cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );
		// Retrieve the cache list.
		// If it doesn't exist, or it's empty prepare an array.
		$templates = wp_get_theme()->get_page_templates();

		if ( empty( $templates ) ) {
				$templates = array();
		}

		// New cache, therefore remove the old one.
		wp_cache_delete( $cache_key , 'themes' );

		/*
		 *Now add our template to the list of templates by merging our templates
		 *with the existing templates array from the cache.
		*/
		$templates = array_merge( $templates, $this->templates );

		/*
		 * Add the modified cache to allow WordPress to pick it up for listing
		 * available templates
		*/
		wp_cache_add( $cache_key, $templates, 'themes', 1800 );
		return $atts;
	}
	/**
	 * Checks if the template is assigned to the page
	 * @param string $template Template file name.
	 */
	public function view_project_template( $template ) {

		global $post;
		if ( ! isset($this->templates[ get_post_meta(
			$post->ID, '_wp_page_template', true
		) ] ) ) {

				return $template;

		}

		$file = plugin_dir_path( $this->parent->file ). 'includes/templates/'.get_post_meta(
			$post->ID, '_wp_page_template', true
		);

		// Just to be safe, we check if the file exists first.
		if ( file_exists( $file ) ) {
				return $file;
		} else {
			echo 'Fonto template file is missing:'.esc_html( $file );
		}

		return $template;
	}

	/**
	 * Form for saving data for plugin template.
	 */
	public function update_post_form() {
		$post_id = filter_input( INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT );
		$orig_post = get_post( $post_id );
		echo '<h3>Edit '.esc_html( $orig_post->post_title ).'</h3>';
		$meta = get_post_meta( $post_id );
		$post_type = $orig_post->post_type;
		$fields = $this->parent->admin->get_fields( $post_type );

		echo '<div>
			<a id="redx" class="popupCloser pointer geoJsLink">
				<img width="19" height="19" alt="Close" src="'.esc_url( $this->parent->assets_url ).'/images/redx.png">
			</a>
		</div>
		<div class="popup_content">';

		echo '<form id="edit_post_form" method="post">
        	<div>
        		<h4>Post Title</h4>
        		<input type="text" value="'.esc_html( $orig_post->post_title ).'" name="post-post_title" id="post_title">
        		<h4>Text Block</h4>';
				$this->parent->admin->display_field( array( 'prefix' => 'meta-', 'field' => $fields['text_block'] ), $orig_post );
				echo '<input type="hidden" value="save_post" name="action">
	        	<input type="hidden" value="'.esc_html( $post_id ).'" name="post_id" id="post_id">
	        	<input type="submit" value="Save Post">';
	    wp_nonce_field( 'fonto_save_post'.esc_html( $post_id ), 'fonto_save_post'.esc_html( $post_id ).'_nonce' );
		echo '</div>
	        </form>';
		echo '<div>';
		die();
	}

	/**
	 * Save data for plugin template.
	 */
	public function save_post() {
		$post_id = filter_input( INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT );
		if ( isset( $post_id ) ) {
			$nonce_id = 'fonto_save_post'.$post_id;
			$nonce = filter_input( INPUT_POST, $nonce_id.'_nonce', FILTER_SANITIZE_STRING );
			if ( $nonce ) {

				if ( ! wp_verify_nonce( $nonce, $nonce_id ) ) {
					return;
				}
			} else {
				return;
			}

			$orig_post = get_post( $post_id );
			$post_type = $orig_post->post_type;
			$fields = $this->parent->admin->get_fields( $post_type );

			$update_post = $update_meta = array();

			foreach ( filter_input_array( INPUT_POST )  as $name => $value ) {
				$namearr = explode( '-',$name );

				switch ( $namearr[0] ) {
					case 'meta':
						// It must be a meta value, update it.
						$update_meta[ $namearr[1] ] = $value;
						break;
					case 'post':
						$update_post[ $namearr[1] ] = $value;
						continue;
						break;
				}
			}

			if ( $update_meta ) {
				$update_post['ID'] = $post_id;
					$result = $this->parent->admin->update_post_metas( $post_id , $update_meta );
			}

			if ( $update_post ) {
				$update_post['ID'] = $post_id;
				// Update the post into the database.
				$result['post_result'] = wp_update_post( $update_post );
			}
		}

		echo wp_json_encode( $result );
		die();
	}

	/**
	 * Main Fonto_Template Instance
	 *
	 * Ensures only one instance of Fonto_Template is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static.
	 *
	 * @param string $parent Name of parent class.
	 * @return Main Fonto_Template instance
	 */
	public static function instance( $parent ) {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}

		return self::$_instance;
	} // End instance()
}
