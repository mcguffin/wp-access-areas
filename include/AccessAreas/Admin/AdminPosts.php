<?php

namespace AccessAreas\Admin;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use AccessAreas\Core;


class AdminPosts extends Core\Singleton {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		// add meta boxes
		$post_type_settings = get_option( 'wpaa_post_types' );

		foreach ( $post_type_settings as $post_type => $settings ) {
			if ( intval( $settings['access_override'] ) ) {
				add_action( "add_meta_boxes_{$post_type}", array( $this, 'add_meta_boxes_access') );

				// add post type column
				if ( $post_type === 'attachment' ) {
					add_filter( "manage_media_columns", array( $this, 'post_columns') );
					add_filter( "manage_media_custom_column", array( $this, 'post_column'), 10, 2 );
				} else {
					add_filter( "manage_{$post_type}_posts_columns", array( $this, 'post_columns') );
					add_filter( "manage_{$post_type}_posts_custom_column", array( $this, 'post_column'), 10, 2 );
				}

			} else {
				// add action @ save post...
			}
			if ( intval( $settings['behavior_override'] ) ) {
				add_action( "add_meta_boxes_{$post_type}", array( $this, 'add_meta_boxes_behavior') );
			} else {
				// add action @ save post...
			}
		}
		add_filter( 'wp_insert_attachment_data', array( $this, 'insert_post_data') , 10 , 2 );
		add_filter( 'wp_insert_post_data', array( $this, 'insert_post_data') , 10 , 2 );
		add_action( 'save_post', array( $this, 'save_post') , 10 , 3 );

		add_action('load-post.php', array( $this, 'enqueue_assets' ));
		add_action('load-post-new.php', array( $this, 'enqueue_assets' ));

		add_filter('pre_post_view_cap',function($a){
			error_log($a);
			return $a;
		});
	}
	/**
	 *	@param $columns
	 *	@filter manage_{$post_type}_posts_columns
	 */
	public function post_columns( $columns ) {
		$columns['wpaa'] = __( 'Access', 'wp-access-areas' );
		return $columns;
	}
	public function post_column( $column, $post_id ) {
		if ('wpaa' === $column ) {
			$names = 
			printf();
			vaR_dump(get_post($post_id));

		}
	}
	/**
	*	@action load-post.php
	*	@action load-post-new.php
	 */
	public function enqueue_assets() {
		wp_enqueue_style('access-areas-posts');
	}

	/**
	 *	@filter wp_insert_post_data
	 */
	public function insert_post_data( $data, $postarr ) {

		$post_type = $data["post_type"];
		$post_type_object 	= get_post_type_object( $post_type );
		$post_type_settings = get_option( 'wpaa_post_types' );

		// set default caps for post type
		$default_caps = array_intersect_key( $post_type_settings[ $post_type ], array(
			'post_view_cap' => 'exist',
			'post_edit_cap' => 'exist',
			'post_comment_cap' => 'exist',
		) );
		$data = wp_parse_args( $data, $default_caps );

		if ( $data['post_status'] == 'auto-draft' ) {
			return $data;
		}

		// process user input
		if ( isset($postarr['post_view_cap'])
			&& $postarr['post_view_cap']
			&& current_user_can( 'wpaa_set_view_cap', $postarr['ID'] ) ) {

			$data['post_view_cap'] = $postarr['post_view_cap'];
		}

		if ( isset($postarr['post_edit_cap'])
			&& $postarr['post_edit_cap']
			&& current_user_can( 'wpaa_set_edit_cap', $postarr['ID'] ) ) {

			$data['post_edit_cap'] = $postarr['post_edit_cap'];
		}

		if ( isset($postarr['post_comment_cap'])
			&& $postarr['post_comment_cap']
			&& current_user_can( 'wpaa_set_comment_cap', $postarr['ID'] ) ) {

			$data['post_comment_cap'] = $postarr['post_comment_cap'];
		}

		return $data;
	}

	/**
	 *	@action save_post
	 */
	public function save_post() {
		// update behavior postmeta
		// '_wpaa_behavior'
		// '_wpaa_login_redirect'
		// '_wpaa_http_status'
		// '_wpaa_fallback_page'
	}

	/**
	 *	@action "add_meta_boxes_{$post_type}"
	 */
	public function add_meta_boxes_access( $post ) {
		add_meta_box('wpaa-access',
			'<span class="dashicons dashicons-lock"></span>' . __('Access Control','wp-access-areas'),
			array( $this, 'meta_box_access' ),
			null,
			'side',
			'high'
		);
	}
	/**
	 *	@action "add_meta_boxes_{$post_type}"
	 */
	public function add_meta_boxes_behavior( $post ) {
		add_meta_box('wpaa-behavior',
			__('Access Behavior','wp-access-areas'),
			array( $this, 'meta_box_behavior' ),
			null,
			'side',
			'high'
		);
	}

	/**
	 *	Meta Box callback
	 */
	public function meta_box_access( $post, $metabox ) {

		$template = Core\Template::instance();
		$post_type_object = get_post_type_object( $post->post_type );
		$values = array(
			'post_view_cap'		=> $post->post_view_cap,
			'post_edit_cap'		=> $post->post_edit_cap,
			'post_comment_cap'	=> $post->post_comment_cap,
		);

		echo $template->access_controls( $post_type_object, $values );

	}

	/**
	 *	Meta Box callback
	 */
	public function meta_box_behavior( $post, $metabox ) {
		$template = Core\Template::instance();
		$post_type_object = get_post_type_object( $post->post_type );
		$values = array(
			'post_behavior'		=> get_post_meta( $post->ID, '_wpaa_post_behavior', true ),
			'login_redirect'	=> get_post_meta( $post->ID, '_wpaa_login_redirect', true ),
			'http_status'		=> get_post_meta( $post->ID, '_wpaa_http_status', true ),
			'fallback_page'		=> get_post_meta( $post->ID, '_wpaa_fallback_page', true ),
		);

		echo $template->behavior_controls( $post_type_object, $values );

	}


}
