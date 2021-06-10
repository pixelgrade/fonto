<?php
/**
 * Document for class Fonto_Taxonomy
 *
 * PHP Version 5.6
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
 * Class to create taxonomies for custom post types
 *
 * @category library
 * @package  Fonto
 * @author   Pixelgrade <contact@pixelgrade.com>
 * @license  GPL v2.0 (or later) see LICENCE file or http://www.gnu.org/licenses/gpl-2.0.html
 * @version  Release: .1
 * @link     https://pixelgrade.com
 * @since    Class available since Release .1
 */
class Fonto_Taxonomy {

	/**
	 * The name for the taxonomy.
	 * @var    string
	 * @access  public
	 * @since    1.0.0
	 */
	public $taxonomy;

	/**
	 * The plural name for the taxonomy terms.
	 * @var    string
	 * @access  public
	 * @since    1.0.0
	 */
	public $plural;

	/**
	 * The singular name for the taxonomy terms.
	 * @var    string
	 * @access  public
	 * @since    1.0.0
	 */
	public $single;

	/**
	 * The array of post types to which this taxonomy applies.
	 * @var    array
	 * @access  public
	 * @since    1.0.0
	 */
	public $post_types;

	/**
	 * The array of taxonomy arguments
	 * @var    array
	 * @access  public
	 * @since    1.0.0
	 */
	public $taxonomy_args;

	/**
	 * Constructor for Fonto_Taxonomy class
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param string $plural Plural taxonomy name.
	 * @param string $single Singular taxonomy name.
	 * @param array $post_types Post types to apply this taxonomy to.
	 * @param array $tax_args Arguments to overided default taxonomy options.
	 *
	 * @return void
	 */
	public function __construct( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $tax_args = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) {
			return;
		}

		// Post type name and labels.
		$this->taxonomy = $taxonomy;
		$this->plural   = $plural;
		$this->single   = $single;
		if ( ! is_array( $post_types ) ) {
			$post_types = array( $post_types );
		}
		$this->post_types    = $post_types;
		$this->taxonomy_args = $tax_args;

		// Register taxonomy.
		add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	/**
	 * Register new taxonomy
	 * @return void
	 */
	public function register_taxonomy() {

		$labels = array(
			'name'                       => $this->plural,
			'singular_name'              => $this->single,
			'menu_name'                  => $this->plural,
			'all_items'                  => sprintf( esc_html__( 'All %s', 'fonto' ), $this->plural ),
			'edit_item'                  => sprintf( esc_html__( 'Edit %s', 'fonto' ), $this->single ),
			'view_item'                  => sprintf( esc_html__( 'View %s', 'fonto' ), $this->single ),
			'update_item'                => sprintf( esc_html__( 'Update %s', 'fonto' ), $this->single ),
			'add_new_item'               => sprintf( esc_html__( 'Add New %s', 'fonto' ), $this->single ),
			'new_item_name'              => sprintf( esc_html__( 'New %s Name', 'fonto' ), $this->single ),
			'parent_item'                => sprintf( esc_html__( 'Parent %s', 'fonto' ), $this->single ),
			'parent_item_colon'          => sprintf( esc_html__( 'Parent %s:', 'fonto' ), $this->single ),
			'search_items'               => sprintf( esc_html__( 'Search %s', 'fonto' ), $this->plural ),
			'popular_items'              => sprintf( esc_html__( 'Popular %s', 'fonto' ), $this->plural ),
			'separate_items_with_commas' => sprintf( esc_html__( 'Separate %s with commas', 'fonto' ), $this->plural ),
			'add_or_remove_items'        => sprintf( esc_html__( 'Add or remove %s', 'fonto' ), $this->plural ),
			'choose_from_most_used'      => sprintf( esc_html__( 'Choose from the most used %s', 'fonto' ), $this->plural ),
			'not_found'                  => sprintf( esc_html__( 'No %s found', 'fonto' ), $this->plural ),
		);

		$args = array(
			'label'                 => $this->plural,
			'labels'                => apply_filters( $this->taxonomy . '_labels', $labels ),
			'hierarchical'          => true,
			'public'                => true,
			'show_ui'               => true,
			'show_in_nav_menus'     => true,
			'show_tagcloud'         => true,
			'meta_box_cb'           => null,
			'show_admin_column'     => true,
			'update_count_callback' => '',
			'query_var'             => $this->taxonomy,
			'rewrite'               => true,
			'sort'                  => '',
		);

		$args = array_merge( $args, $this->taxonomy_args );

		register_taxonomy( $this->taxonomy, $this->post_types, apply_filters( $this->taxonomy . '_register_args', $args, $this->taxonomy, $this->post_types ) );
	}

	/**
	 * Add taxonomy filter to post type edit page
	 * @return void
	 */
	public function add_filter() {

		if ( in_array( filter_input( INPUT_GET, 'post_type', FILTER_SANITIZE_STRING ), $this->post_types ) ) {
			add_action( 'restrict_manage_posts', array( $this, 'display_filter' ) );
		}
	}


	/**
	 * Creates HTML for taxonomy filter on post type edit page
	 * @return void
	 */
	public function display_filter() {

		$tax_obj  = get_taxonomy( $this->taxonomy );
		$tax_name = $tax_obj->labels->name;
		$terms    = get_terms( $this->taxonomy );

		if ( count( $terms ) > 0 ) {
			echo "<select name='" . esc_attr( $this->taxonomy ) . "' id='" . esc_attr( $this->taxonomy ) . "' class='postform'>";
			echo "<option value=''>". sprintf( esc_html__( 'Show All %s', 'fonto' ), esc_html( $tax_name ) ) . '</option>';

			foreach ( $terms as $term ) {
				echo '<option value=' . $term->slug, filter_input( INPUT_GET, $this->taxonomy, FILTER_SANITIZE_STRING ) === esc_attr( $term->slug ) ? ' selected="selected"' : '', '>' . esc_attr( $term->name ) . ' (' . esc_attr( $term->count ) . ')</option>';
			}

			echo '</select>';
		}

	}
}
