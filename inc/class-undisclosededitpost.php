<?php
/**
* @package WP_AccessAreas
* @version 1.0.0
*/ 

// ----------------------------------------
//	This class provides an UI for assining 
//	WP-Roles and user-labels to posts.
// ----------------------------------------

if ( ! class_exists('UndisclosedEditPost') ) :
class UndisclosedEditPost {

	static function init() {
		if ( is_admin() ) {
			// edit post
			add_filter('wp_insert_post_data', array(__CLASS__ , 'edit_post') , 10 , 2 );
			add_filter('save_post', array(__CLASS__ , 'set_post_behavior') , 10 , 3 );
			add_action('edit_attachment',array(__CLASS__ , 'edit_attachment') );
			add_action('add_attachment',array(__CLASS__ , 'edit_attachment') );
			
			add_action('add_meta_boxes' , array( __CLASS__ , 'add_meta_boxes' ) );

			
			
			add_action('bulk_edit_custom_box' , array(__CLASS__,'bulk_edit_fields') , 10 , 2 );
			add_action('quick_edit_custom_box' , array(__CLASS__,'quick_edit_fields') , 10 , 2 );

			add_action( 'wp_ajax_get_accessarea_values', array( __CLASS__ , 'ajax_get_accessarea_values' ) );
			
			add_action( 'admin_init' , array( __CLASS__ , 'add_post_type_columns' ) );
		}
		add_action( 'load-edit.php' , array( __CLASS__ , 'enqueue_script_style' ) );
		add_action( 'load-edit.php' , array( __CLASS__ , 'enqueue_style' ) );
		
		add_action( 'load-post.php' , array( __CLASS__ , 'enqueue_script_style' ) );
		add_action( 'load-post-new.php' , array( __CLASS__ , 'enqueue_script_style' ) );
		
	}
	
	static function add_post_type_columns() {
		
		$can_edit_edit_cap		=  self::can_edit_edit_cap();

		// posts
		if ( post_type_supports( 'post' , 'comments' ) )
			add_filter('manage_posts_columns' , array( __CLASS__ , 'add_disclosure_columns') );
		else 
			add_filter('manage_posts_columns' , array( __CLASS__ , 'add_disclosure_column_view') );
		add_action('manage_posts_custom_column' , array( __CLASS__ , 'manage_disclosure_column') , 10 ,2 );

		// pages
		if ( post_type_supports( 'page' , 'comments' ) )
			add_filter('manage_pages_columns' , array( __CLASS__ , 'add_disclosure_columns') );
		else 
			add_filter('manage_pages_columns' , array( __CLASS__ , 'add_disclosure_column_view') );
		add_action('manage_pages_custom_column' , array( __CLASS__ , 'manage_disclosure_column') , 10 ,2 );

		// media library
		if ( post_type_supports( 'attachment' , 'comments' ) )
			add_filter('manage_media_columns' , array( __CLASS__ , 'add_disclosure_column_comment'));
		add_action('manage_media_custom_column' , array( __CLASS__ , 'manage_disclosure_column') , 10 ,2 );

		if ( $can_edit_edit_cap ) {
			add_filter( 'manage_posts_columns' , array( __CLASS__ , 'add_disclosure_column_edit'));
			add_filter( 'manage_pages_columns' , array( __CLASS__ , 'add_disclosure_column_edit'));
			add_filter( 'manage_media_columns' , array( __CLASS__ , 'add_disclosure_column_edit'));
		}

		$post_types = get_post_types(array(
			'show_ui' => true,
			'_builtin' => false,
		));
		$post_types['attachment'] = 'attachment';
		
		// custom post types
		foreach ( $post_types as $post_type ) {
			$post_type_object = get_post_type_object( $post_type );
			$view_col 		= self::can_edit_view_cap( $post_type , $post_type_object );
			$comment_col	= self::can_edit_comment_cap( $post_type );

			if ( $view_col && $comment_col ) 
				add_filter( "manage_{$post_type}_posts_columns" , array( __CLASS__ , 'add_disclosure_columns'));
			else if ( $view_col && ! $comment_col )
				add_filter( "manage_{$post_type}_posts_columns" , array( __CLASS__ , 'add_disclosure_column_view'));
			else if ( ! $view_col &&  $comment_col )
				add_filter( "manage_{$post_type}_posts_columns" , array( __CLASS__ , 'add_disclosure_column_comment'));

			if ( $can_edit_edit_cap )
				add_filter( "manage_{$post_type}_posts_columns" , array( __CLASS__ , 'add_disclosure_column_edit'));
		}
	}
	
	
	static function ajax_get_accessarea_values() {
		$result = false;
		if ( isset( $_POST['ajax_nonce'] ) && wp_verify_nonce( $_POST['ajax_nonce'], 'get_accessarea_values' ) ) {
			if ( isset( $_POST['post_ID'] ) && current_user_can( 'edit_post' , $_POST['post_ID'] ) ) {
				header('Content-Type: application/json');
				$result = wp_parse_args( get_post($_POST['post_ID'] , ARRAY_A ) , array(
					'post_view_cap'		=> 'exist',
					'post_edit_cap'		=> 'exist',
					'post_comment_cap'	=> 'exist',
				));
			}
		}
		echo json_encode( $result );
		die;
	}
	
	static function enqueue_script_style() {
		self::enqueue_style();
		self::enqueue_script();
	}
	static function enqueue_style() {
		add_action('admin_enqueue_scripts' , array(__CLASS__,'load_style'));
	}
	static function enqueue_script() {
		add_action('admin_enqueue_scripts' , array(__CLASS__,'load_edit_script'));
	}

	static function load_edit_script() {
		wp_enqueue_script( 'disclosure-edit' );
		wp_enqueue_script( 'disclosure-quick-edit' );
	} 
	static function load_style() {
		wp_enqueue_style( 'disclosure-admin' );
	}
	
	// --------------------------------------------------
	// add meta boxes to all post content
	// --------------------------------------------------
	static function add_meta_boxes() {
		global $wp_post_types;
		if ( ! get_option( 'wpaa_enable_assign_cap' ) || current_user_can( 'wpaa_set_view_cap' ) || current_user_can( 'wpaa_set_edit_cap' ) || current_user_can( 'wpaa_set_comment_cap' ) ) {
			foreach ( array_keys($wp_post_types) as $post_type ) {
				add_meta_box( 'post-disclosure' , __('Access','wp-access-areas') , array(__CLASS__,'disclosure_box_info') , $post_type , 'side' , 'high' );
				if ( self::can_edit_view_cap( $post_type ) )
					add_meta_box( 'post-disclosure-behavior' , __('Behaviour','wp-access-areas') , array(__CLASS__,'disclosure_box_behavior') , $post_type , 'side' , 'high' );
			}
		}
	}
	// --------------------------------------------------
	// saving posts
	// --------------------------------------------------
	static function edit_post( $data, $postarr ) {
	
		$post_type = $data["post_type"];
		$post_type_object 	= get_post_type_object( $post_type );
		
		// set default values
		if ( ! $postarr['ID'] ) {
			$caps = array( 
				'post_view_cap' => 'exist',
				'post_edit_cap' => 'exist',
				'post_comment_cap' => 'exist',
			);
			
			if ( ( $default_caps = get_option( 'wpaa_default_caps' )) && isset( $default_caps[$data["post_type"]] ) )
				$caps = wp_parse_args( $default_caps[$data["post_type"]] , $caps );
			$data = wp_parse_args( $data , $caps );
		}
		if ( $data['post_status'] == 'auto-draft' )
			return $data;

		
		// process user input. 
		if ( self::can_edit_view_cap( $post_type , $post_type_object ) && isset($postarr['post_view_cap']) && $postarr['post_view_cap'] )
			$data['post_view_cap']	= wpaa_sanitize_access_cap(  $postarr['post_view_cap'] );
		
		if ( self::can_edit_edit_cap() && isset($postarr['post_edit_cap']) && $postarr['post_edit_cap']) 
			$data['post_edit_cap']	= wpaa_sanitize_access_cap( $postarr['post_edit_cap'] );
	
		if (  self::can_edit_comment_cap( $post_type ) && isset($postarr['post_comment_cap']) && $postarr['post_comment_cap'] )
			$data['post_comment_cap']	= wpaa_sanitize_access_cap( $postarr['post_comment_cap'] );
		
		return $data;
	}
	
	// --------------------------------------------------
	// saving posts, 
	// --------------------------------------------------
	static function set_post_behavior(  $post_ID , $post , $update ) {
		// should only happen if edit_view_cap is true
		if ( self::can_edit_view_cap( $post->post_type ) &&  isset( $_POST['_wpaa_enable_custom_behaviour'] ) ) {
			if ( (bool) $_POST['_wpaa_enable_custom_behaviour'] ) {
				if ( isset( $_POST['_wpaa_fallback_page'] ) )
					update_post_meta( $post_ID , '_wpaa_fallback_page' , intval( $_POST['_wpaa_fallback_page'] ) );
	
				if ( isset( $_POST['_wpaa_post_behavior'] ) ) {
					$meta = $_POST['_wpaa_post_behavior'];
	
					if ( $meta === '' ) {
						delete_post_meta( $post_ID , '_wpaa_post_behavior' );
					} else if ( in_array( $meta , array( '404' , 'page' , 'login' ) ) ) {
						update_post_meta( $post_ID , '_wpaa_post_behavior' , $meta );
					}
				}
			} else {
				delete_post_meta( $post_ID , '_wpaa_post_behavior' );
				delete_post_meta( $post_ID , '_wpaa_fallback_page' );
			}
		}
	}
	
	static function edit_attachment( $attachment_ID ) {
		$attachment			= get_post($attachment_ID);
		$post_edit_cap 		= isset($_POST['post_edit_cap']) ? wpaa_sanitize_access_cap( $_POST['post_edit_cap'] ) : $attachment->post_edit_cap;
		$post_comment_cap	= isset($_POST['post_comment_cap']) ? wpaa_sanitize_access_cap( $_POST['post_comment_cap'] ) : $attachment->post_comment_cap;
	
		if ( $attachment && $post_edit_cap != $attachment->post_edit_cap || $post_comment_cap != $attachment->post_comment_cap ) {
			// use $wpdb instead of wp_update_post to avoid inifinite do_action
			global $wpdb;
			$data = array(
				'post_edit_cap' => $post_edit_cap,
				'post_comment_cap' => $post_comment_cap,
			);
			$wpdb->update( $wpdb->posts , $data , array( 'ID' => $attachment_ID ) , array('%s','%s') , array( '%d' ) );
		}
	}
	
	// --------------------------------------------------
	// edit post - the meta box
	// --------------------------------------------------
	static function disclosure_box_info() {
		global $wp_roles;
		$post 				= get_post(get_the_ID());

		$post_type_object 	= get_post_type_object($post->post_type);
		$editing_cap 		= $post_type_object->cap->edit_posts;
		
		$post_behavior	 	= get_post_meta( $post->ID , '_wpaa_post_behavior' , true );
		$post_fallback_page	= get_post_meta( $post->ID , '_wpaa_fallback_page' , true );

		// <select> with - Evereybody, Logged-in only, list WP-Roles, list discosure-groups
		$roles	 			= $wp_roles->get_names();
		$groups 			= UndisclosedUserlabel::get_label_array( );
		$user_role_caps 	= wpaa_get_user_role_caps();

		$rolenames 			= array();
		$edit_rolenames		= array();
		foreach ( $roles as $role => $rolename ) {
			if ( wpaa_user_can_role( $role , $user_role_caps ) ) {
				$rolenames[$role] = $rolename;
				if ( get_role( $role )->has_cap( $editing_cap ) ) {
					$edit_rolenames[$role] = $rolename;
				}
			}
		}
		
		if ( self::can_edit_view_cap( $post->post_type , $post_type_object ) ) { 
			?><div class="disclosure-view-select misc-pub-section"><?php
				?><label for="post_view_cap-select"><strong><?php _e( 'Who can read:' , 'wp-access-areas') ?></strong></label><br /><?php
				self::access_area_dropdown( $rolenames , $groups , $post->post_view_cap , 'post_view_cap' );
			?></div><?php
		}
		if ( self::can_edit_edit_cap() ) {
			?><div class="disclosure-edit-select misc-pub-section"><?php
				?><label for="post_edit_cap-select"><strong><?php _e( 'Who can edit:' , 'wp-access-areas') ?></strong></label><br /><?php 
				self::access_area_dropdown( $edit_rolenames , $groups , $post->post_edit_cap , 'post_edit_cap' );
			?></div><?php
		}
		if ( self::can_edit_comment_cap( $post->post_type ) && wpaa_user_can( $post->post_comment_cap ) ) {
			?><div class="disclosure-comment-select misc-pub-section"><?php
				?><label for="post_comment_cap-select"><strong><?php _e( 'Who can comment:' , 'wp-access-areas') ?></strong></label><br /><?php
				self::access_area_dropdown( $rolenames , $groups , $post->post_comment_cap , 'post_comment_cap' );
			?></div><?php
		}
	}
	
	private static function can_edit_view_cap( $post_type , $post_type_object = null ) {
		if ( is_null( $post_type_object ) )
			$post_type_object = get_post_type_object( $post_type );
		return ( ! get_option( 'wpaa_enable_assign_cap' ) || current_user_can( 'wpaa_set_view_cap' ) ) && ( $post_type_object->public || $post_type_object->show_ui ) && $post_type != 'attachment';
	}
	private static function can_edit_edit_cap() {
		return ( ! get_option( 'wpaa_enable_assign_cap' ) || current_user_can( 'wpaa_set_edit_cap' ) );
	}
	private static function can_edit_comment_cap( $post_type ) {
		 return ( ! get_option( 'wpaa_enable_assign_cap' ) || current_user_can( 'wpaa_set_comment_cap' ) ) && post_type_supports( $post_type , 'comments' );
	}
	
	static function disclosure_box_behavior( ) {
		$post 			= get_post(get_the_ID());
		$is_custom_behaviour = true;
		$is_custom_fallback  = true;

		$post_behavior 	= get_post_meta( $post->ID , '_wpaa_post_behavior' , true );
		if ( ! $post_behavior ) {
			$post_behavior       = get_option('wpaa_default_behavior');
			$is_custom_behaviour = false;
		}
		
		$post_fallback_page	= get_post_meta( $post->ID , '_wpaa_fallback_page' , true );
		if ( ! $post_fallback_page ) {
			$post_fallback_page = get_option('wpaa_fallback_page');
			$is_custom_fallback = false;
		}
		
		$is_custom = $is_custom_fallback && $is_custom_behaviour;
		
		?><div class="wpaa-select-behaviour<?php echo $is_custom ? ' custom' : '' ; ?>"><?php
			?><div class="misc-pub-section"><?php
				?><label for="wpaa_enable_custom_behaviour"><?php
					?><input name="_wpaa_enable_custom_behaviour" value="0" type="hidden" /><?php
					?><input name="_wpaa_enable_custom_behaviour" value="1" type="checkbox" id="wpaa_enable_custom_behaviour" <?php checked( $is_custom ); ?> /><?php
					_e( 'Custom Behaviour' ,  'wp-access-areas' );
				?></label><?php
			?></div><?php
			?><div class="wpaa-behaviour-controls"><?php
				?><div class="disclosure-view-select misc-pub-section"><?php
					?><p class="description"><?php _e('If somebody tries to view a restricted post directly:' , 'wp-access-areas' ); ?></p><?php
		
					self::behavior_select( $post_behavior );
		
				?></div><?php
	
				?><div class="disclosure-view-select misc-pub-section"><?php
					?><label for="_wpaa_fallback_page"><?php
					_e('Fallback Page','wp-access-areas');
					?></label><?php
					// only offer non-restricted pages
		
					self::fallback_page_dropdown( $post_fallback_page );
				?></div><?php
			
			?></div><?php
		?></div><?php

	}
	
	
	// --------------------------------------------------
	// edit post - Access Area dropdown menu
	// --------------------------------------------------
	static function access_area_dropdown( $roles , $groups , $selected_cap , $fieldname , $first_item_value = null , $first_item_label = ''  ) {
		if ( ! $selected_cap )
			$selected_cap = 'exist';
		?><select id="<?php echo sanitize_title($fieldname) ?>-select" name="<?php echo $fieldname ?>"><?php
			if ( ! is_null( $first_item_value ) && ! is_null( $first_item_label ) ) {
				?><option value="<?php $first_item_value ?>"><?php echo $first_item_label ?></option><?php
			}
		
			?><option value="exist" <?php selected($selected_cap , 'exist') ?>><?php _e( 'WordPress default' , 'wp-access-areas' ) ?></option><?php
			if ( strpos( $fieldname , 'post_edit_cap' ) === false ) {
				?><option value="read" <?php selected($selected_cap , 'read') ?>><?php _e( 'Logged in Users' , 'wp-access-areas' ) ?></option><?php
			}
			
			?><optgroup label="<?php _e( 'WordPress roles' , 'wp-access-areas') ?>"><?php
			foreach ($roles as $role => $rolename) {
				if ( ! wpaa_user_can_role( $role ) )
					continue;
				?><option value="<?php echo $role ?>" <?php selected($selected_cap , $role) ?>><?php _ex( $rolename, 'User role' ) ?></option><?php
			} 
			?></optgroup><?php
			if ( count($groups) ) { 
				?><optgroup label="<?php _e( 'Users with Access to' , 'wp-access-areas') ?>"><?php
				foreach ($groups as $group=>$groupname) { 
					if ( ! wpaa_user_can_accessarea($group) )
						continue;
					?><option value="<?php echo $group ?>" <?php selected($selected_cap , $group) ?>><?php _e( $groupname , 'wp-access-areas' ) ?></option><?php
				 } /* foreach( $groups ) */ 
				?></optgroup><?php 
			}  /* if count( $groups ) */ 
		?></select><?php
	}
	// --------------------------------------------------
	// edit post - Fallback page dropdown menu
	// --------------------------------------------------
	static function fallback_page_dropdown( $post_fallback_page = false , $fieldname = '_wpaa_fallback_page' ) {
		global $wpdb;
		if ( ! wpaa_is_post_public( $post_fallback_page ) )
			$post_fallback_page = 0;
		
		// if not fallback page, use global fallback page
		$restricted_pages = $wpdb->get_col($wpdb->prepare("SELECT id 
			FROM $wpdb->posts 
			WHERE 
				post_type=%s AND 
				post_status=%s AND
				post_view_cap!=%s" , 'page','publish','exist' ));
		wp_dropdown_pages(array(
			'selected' 	=> $post_fallback_page,
			'name'		=> $fieldname,
			'exclude'	=> $restricted_pages,
			'show_option_none' => __('Front page'),
			'option_none_value' => 0,
		));
	}
	static function behavior_select( $post_behavior = '' , $fieldname = '_wpaa_post_behavior' ) {
		$behaviors = array(
			array( 
				'value'	=> '404',
				'label' => __( 'Show 404' , 'wp-access-areas'),
			),
			array(
				'value'	=> 'page',
				'label' => __( 'Redirect to the fallback page.' , 'wp-access-areas'),
			),
			array(
				'value'	=> 'login',
				'label' => __( 'If not logged in, redirect to login. Otherwise redirect to the fallback page.' , 'wp-access-areas'),
			),
		);

		foreach ( $behaviors as $item ) {
			extract( $item );
			?><label for="disclosure-view-post-behavior-<?php echo $value ?>"><?php
				?><input name="<?php echo $fieldname ?>" <?php checked( $value , $post_behavior ); ?> class="wpaa-post-behavior" id="disclosure-view-post-behavior-<?php echo $value ?>" value="<?php echo $value ?>"  type="radio" /><?php
				echo $label 
			?><br /></label><?php
		}
	}
	
	// --------------------------------------------------
	// Quick Edit hook callback
	// --------------------------------------------------
	static function quick_edit_fields( $column_name, $post_type ) {
		global $post;
		// enqueue
		self::_edit_fields( $column_name, $post_type , $post , null );
	}
	
	// --------------------------------------------------
	// Bulk Edit hook callback
	// --------------------------------------------------
	static function bulk_edit_fields( $column_name, $post_type ) {
		self::_edit_fields( $column_name, $post_type );
	}
	
	// --------------------------------------------------
	// Quick/Bulk Edit html
	// --------------------------------------------------
	private static function _edit_fields( $column_name, $post_type , $post = null , $first_item_value = -1 ) {
		global $wp_roles;
		if ($column_name == 'view_cap') {

			$view_cap = ! is_null( $post ) ? $post->post_view_cap : false;
			$edit_cap = ! is_null( $post ) ? $post->post_edit_cap : false;
			$comment_cap = ! is_null( $post ) ? $post->post_comment_cap : false;

			$post_type_object	= get_post_type_object($post_type);

			$can_edit_view = self::can_edit_view_cap( $post_type , $post_type_object );
			$can_edit_edit = self::can_edit_edit_cap( );
			$can_edit_comment = self::can_edit_comment_cap( $post_type );
			$can_edit = $can_edit_view || $can_edit_edit || $can_edit_comment;
			
			if ( $can_edit ) {
				$editing_cap 		= $post_type_object->cap->edit_posts;
				$current_user 		= wp_get_current_user();
				$roles	 			= $wp_roles->get_names();
				$groups 			= UndisclosedUserlabel::get_label_array( );
		
				$user_role_caps 	= wpaa_get_user_role_caps();
			
				$rolenames 			= array();
				$edit_rolenames		= array();
			
				foreach ( $roles as $role => $rolename ) {
					if ( wpaa_user_can_role( $role , $user_role_caps ) ) {
						$rolenames[$role] = $rolename;
						if ( get_role( $role )->has_cap( $editing_cap ) ) {
							$edit_rolenames[$role] = $rolename;
						}
					}
				}
				?><fieldset class="inline-edit-col-access-areas inline-edit-col-left"><?php
					?><h3><?php _e('Access','wp-access-areas') ?></h3><?php
					?><div class="inline-edit-col"><?php
						if ( $can_edit_view ) {
							?><div class="inline-edit-group"><?php
								?><label><?php
									?><span class="title"><?php _e( 'Read:' , 'wp-access-areas') ?></span><?php
									self::access_area_dropdown( $rolenames , $groups , $view_cap , 'post_view_cap' , $first_item_value , __( '&mdash; No Change &mdash;' ) );
								?></label><?php
							?></div><?php
						}
						if ( $can_edit_edit ) {
							?><div class="inline-edit-group"><?php
								?><label><?php
									?><span class="title"><?php _e( 'Edit:' , 'wp-access-areas') ?></span><?php
									self::access_area_dropdown( $edit_rolenames , $groups , $edit_cap , 'post_edit_cap'  , $first_item_value , __( '&mdash; No Change &mdash;' )  );
								?></label><?php
							?></div><?php
						}
						if ( $can_edit_comment ) {
							?><div class="inline-edit-group"><?php
								?><label><?php
									?><span class="title"><?php _e( 'Comment:' , 'wp-access-areas') ?></span><?php
									self::access_area_dropdown( $rolenames , $groups , $comment_cap , 'post_comment_cap'  , $first_item_value , __( '&mdash; No Change &mdash;' ) );
								?></label><?php
							?></div><?php
						}
					?></div><?php
				?></fieldset><?php
			}
		}
	}
		
	
	
	// --------------------------------------------------
	// admin list views
	// --------------------------------------------------
	static function add_disclosure_columns($columns) {
		$cols = array();
		$afters = array('author','title','cb');
		foreach ( $afters as $after )
			if ( isset($columns[$after] ) )
				break;

		foreach ($columns as $k=>$v) {
			$cols[$k] = $v;
			if ($k == $after ) {
				$cols['view_cap'] = __('Visible to','wp-access-areas');
				$cols['comment_cap'] = __('Commentable to','wp-access-areas');
			}
		}
		return $cols;
	}
	// --------------------------------------------------
	// admin list views
	// --------------------------------------------------
	static function add_disclosure_column_view( $columns ) {
		$cols = array();
		$afters = array('author','title','cb');
		foreach ( $afters as $after )
			if ( isset($columns[$after]) )
				break;

		foreach ($columns as $k=>$v) {
			$cols[$k] = $v;
			if ( $k == $after ) {
				$cols['view_cap'] = __('Visible to','wp-access-areas');
			}
		}
		return $cols;
	}
	// --------------------------------------------------
	// admin list views
	// --------------------------------------------------
	static function add_disclosure_column_comment($columns) {
		$cols = array();
		$afters = array('view_cap','author','title','cb');
		foreach ( $afters as $after )
			if ( isset($columns[$after]) )
				break;

		foreach ($columns as $k=>$v) {
			$cols[$k] = $v;
			if ( $k == $after ) {
				$cols['comment_cap'] = __('Commentable to','wp-access-areas');
			}
		}
		return $cols;
	}
	// --------------------------------------------------
	// admin list views
	// --------------------------------------------------
	static function add_disclosure_column_edit( $columns ) {
		$cols = array();
		$afters = array('comment_cap','view_cap','author','title','cb');
		foreach ( $afters as $after )
			if ( isset($columns[$after]) )
				break;

		foreach ($columns as $k=>$v) {
			$cols[$k] = $v;
			if ( $k == $after ) {
				$cols['edit_cap'] = __('Editable for','wp-access-areas');
			}
		}
		return $cols;
	}

	// --------------------------------------------------
	// admin list view column
	// --------------------------------------------------
	static function manage_disclosure_column($column, $post_ID) {
		global $wp_roles;
// 		var_dump($column,current_filter());
		switch ( $column ) {
			case 'view_cap':
				$names = array_merge(array('exist' => __( 'Everybody' , 'wp-access-areas' ), 'read' => __( 'Blog users' , 'wp-access-areas' )) , UndisclosedUserlabel::get_label_array( ), $wp_roles->get_names());
				$names[''] = $names['exist'];
				$val = get_post($post_ID)->post_view_cap;
				_e($names[$val]);
				break;
			case 'comment_cap':
				$names = array_merge(array('exist' => __( 'Everybody' , 'wp-access-areas' ), 'read' => __( 'Blog users' , 'wp-access-areas' )) , UndisclosedUserlabel::get_label_array( ), $wp_roles->get_names());
				$names[''] = $names['exist'];
				$val = get_post($post_ID)->post_comment_cap;
				_e($names[$val]);
				break;
			case 'edit_cap':
				$names = array_merge(array('exist' => __( 'Everybody' , 'wp-access-areas' ), 'read' => __( 'Blog users' , 'wp-access-areas' )) , UndisclosedUserlabel::get_label_array( ), $wp_roles->get_names());
				$names[''] = $names['exist'];
				$val = get_post($post_ID)->post_edit_cap;
				_e($names[$val]);
				break;
		}
	}

}
endif;
