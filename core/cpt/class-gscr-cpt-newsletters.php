<?php
/**
 * Class CPT_GSCR_Newsletters
 *
 * Creates the post type.
 *
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class CPT_GSCR_Newsletters extends RBM_CPT {

	public $post_type = 'newsletter';
	public $label_singular = null;
	public $label_plural = null;
	public $labels = array();
	public $icon = 'media-document';
	public $post_args = array(
		'hierarchical' => true,
		'supports' => array( 'title', 'editor', 'author', 'thumbnail' ),
		'has_archive' => true,
		'rewrite' => array(
			'slug' => 'newsletter',
			'with_front' => false,
			'feeds' => false,
			'pages' => true
		),
		'menu_position' => 11,
		//'capability_type' => 'newsletter',
	);

	/**
	 * CPT_GSCR_Newsletters constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		// This allows us to Localize the Labels
		$this->label_singular = __( 'Newsletter', 'gscr-cpt-newsletters' );
		$this->label_plural = __( 'Newsletters', 'gscr-cpt-newsletters' );

		$this->labels = array(
			'menu_name' => __( 'Newsletters', 'gscr-cpt-newsletters' ),
			'all_items' => __( 'All Newsletters', 'gscr-cpt-newsletters' ),
		);

		parent::__construct();
		
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		
		add_filter( 'manage_' . $this->post_type . '_posts_columns', array( $this, 'admin_column_add' ) );
		
		add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'admin_column_display' ), 10, 2 );
		
		add_filter( 'manage_edit-' . $this->post_type . '_sortable_columns', array( $this, 'admin_columns_sortable' ) );
		
		add_action( 'pre_get_posts', array( $this, 'admin_columns_sorting' ) );
		
		add_filter( 'get_sample_permalink_html', array( $this, 'alter_permalink_html' ), 10, 5 );
		
		add_filter( 'the_permalink', array( $this, 'the_permalink' ) );
		
		add_filter( 'post_type_link', array( $this, 'get_permalink' ), 10, 4 );
		
		add_filter( 'template_include', array( $this, 'redirect_to_pdf' ) );
		
	}
	
	/**
	 * Add Meta Box
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function add_meta_boxes() {
		
		add_meta_box(
			'newsletter-pdf-url',
			sprintf( _x( '%s Meta', 'Metabox Title', 'gscr-cpt-newsletters' ), $this->label_singular ),
			array( $this, 'metabox_content' ),
			$this->post_type,
			'side'
		);
		
	}
	
	/**
	 * Add Meta Field
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function metabox_content() {
		
		rbm_do_field_media(
			'newsletter_pdf_url',
			_x( 'Newsletter PDF', 'Newsletter PDF Label', 'gscr-cpt-newsletters' ),
			false,
			array(
				'type' => 'application/pdf',
				'description' => __( 'If a PDF is uploaded here, this Newsletter will link to the PDF directly.', 'gscr-cpt-newsletters' ),
			)
		);
		
	}
	
	/**
	 * Adds an Admin Column
	 * 
	 * @param		array $columns Array of Admin Columns
	 *                                       
	 * @access		public
	 * @since		1.0.0
	 * @return		array Modified Admin Column Array
	 */
	public function admin_column_add( $columns ) {
		
		$columns['newsletter_pdf_url'] = _x( 'Attached PDF?', 'Attached PDF Admin Column Label', 'gscr-cpt-newsletters' );
		
		return $columns;
		
	}
	
	/**
	 * Displays data within Admin Columns
	 * 
	 * @param		string  $column  Admin Column ID
	 * @param		integer $post_id Post ID
	 *                               
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function admin_column_display( $column, $post_id ) {
		
		switch ( $column ) {
				
			case 'newsletter_pdf_url' :
				if ( rbm_get_field( $column, $post_id ) ) {
					echo __( 'Yes', 'gscr-cpt-newsletters' );
				}
				break;
			default : 
				echo rbm_field( $column, $post_id );
				break;
				
		}
		
	}
	
	/**
	 * Modify the Sortable Admin Columns
	 * 
	 * @param		array $sortable_columns Sortable Admin Columns
	 *                                                
	 * @access		public
	 * @since		1.0.0
	 * @return		array Sortable Admin Columns
	 */
	public function admin_columns_sortable( $sortable_columns ) {
		
		$sortable_columns[ 'newsletter_pdf_url' ] = '_rbm_newsletter_pdf_url';
		
		return $sortable_columns;
		
	}
	
	/**
	 * Allow PDF Attached Newsletters to be sorted by whether the PDF exists or not
	 * This technically also runs on the Frontend, but it isn't important. The condition should never be true anyway in normal use.
	 * 
	 * @param		object $query WP_Query
	 *                       
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function admin_columns_sorting( $query ) {
		
		$orderby = $query->get( 'orderby' );
		
		if ( $orderby == '_rbm_newsletter_pdf_url' ) {
			
			$query->set( 'meta_query', array(
				array(
					'key' => '_rbm_newsletter_pdf_url',
					'compare' => 'EXISTS'
				),
			) );
			
			$query->set( 'orderby', 'meta_value_num' );
			
		}
		
	}
	
	/**
	 * Show the PDF URL as the Permalink Sample if this Newsletter has one set
	 * 
	 * @param		string  $return    Sample HTML Markup
	 * @param		integer $post_id   Post ID
	 * @param		string  $new_title New Sample Permalink Title
	 * @param		string  $new_slug  New Sample Permalnk Slug
	 * @param		object  $post      WP Post Object
	 *                   
	 * @access		public
	 * @since		1.0.0
	 * @return		string  Modified HTML Markup
	 */
	public function alter_permalink_html( $return, $post_id, $new_title, $new_slug, $post ) {

		// No sense in a database query if it isn't the correct Post Type
		if ( $post->post_type == 'newsletter' ) {

			if ( $pdf = rbm_get_field( 'newsletter_pdf_url', $post_id ) ) {
				
				$pdf = wp_get_attachment_url( $pdf );
				
				$return = preg_replace( '/<a.*<\/a>/', '<a href="' . $pdf . '">' . $pdf . '</a>', $return );
				$return = str_replace( '<span id="edit-slug-buttons"><button type="button" class="edit-slug button button-small hide-if-no-js" aria-label="Edit permalink">Edit</button></span>', '', $return );
				
			}

		}

		return $return;

	}
	
	/**
	 * Replace the_permalink() calls on the Frontend with the PDF URL
	 * 
	 * @param		string $url The Post URL
	 *                
	 * @access		public
	 * @since		1.0.0
	 * @return		string Modified URL
	 */
	public function the_permalink( $url ) {

		if ( get_post_type() == 'newsletter' ) {

			if ( $pdf = rbm_get_field( 'newsletter_pdf_url', $post_id ) ) {
				
				$pdf = wp_get_attachment_url( $pdf );

				$url = $pdf;

			}

		}

		return $url;

	}

	/**
	 * Replace get_peramlink() calls on the Frontend with the PDF URL
	 * 
	 * @param		string  $url       The Post URL
	 * @param		object  $post      WP Post Object
	 * @param		boolean $leavename Whether to leave the Post Name
	 * @param		boolean $sample    Is it a sample permalink?
	 *     
	 * @access		public
	 * @since		1.0.0
	 * @return		string  Modified URL
	 */
	public function get_permalink( $url, $post, $leavename = false, $sample = false ) {

		if ( $post->post_type == 'newsletter' ) {

			if ( $pdf = rbm_get_field( 'newsletter_pdf_url', $post_id ) ) {
				
				$pdf = wp_get_attachment_url( $pdf );

				$url = $pdf;

			}

		}

		return $url;

	}
	
	/**
	 * Force a redirect to the PDF if one exists
	 * 
	 * @param       string $template Path to Template File
	 *                                                
	 * @since       1.0.0
	 * @return      string Modified Template File Path
 	 */
	public function redirect_to_pdf( $template ) {
		
		global $wp_query;
		global $post;
		
		if ( is_single() && get_post_type() == 'newsletter' ) {
			
			if ( $pdf = rbm_get_field( 'newsletter_pdf_url', $post->ID ) ) {
				
				$pdf = wp_get_attachment_url( $pdf );
				
				header( "Location: $pdf", true, 301 );
			
			}
		}

		return $template;
	
	}
	
}