<?php
/**
* @package WP_AccessAreas
* @version 1.0.0
*/ 

// ----------------------------------------
//	Frontend Post Filters
// ----------------------------------------

if ( ! class_exists('UndisclosedPosts') ):
class UndisclosedPosts {
	
	static function init() {

		// viewing restrictions on posts lists
		add_action( 'get_pages' , array( __CLASS__ , 'skip_undisclosed_items' ) , 10 , 1 ); // needed by nav menus
		add_filter( "posts_where" , array( __CLASS__ , "get_posts_where" ) , 10 , 2 );
		add_filter( "posts_join" , array( __CLASS__ , "get_posts_join" ) , 10 , 2 );

		add_filter( "get_next_post_where" , array( __CLASS__ , "get_adjacent_post_where" ) , 10 , 3 );
		add_filter( "get_previous_post_where" , array( __CLASS__ , "get_adjacent_post_where" ) , 10 , 3 );
		add_filter( "get_next_post_join" , array( __CLASS__ , "get_adjacent_post_join" ) , 10 , 3 );
		add_filter( "get_previous_post_join" , array( __CLASS__ , "get_adjacent_post_join" ) , 10 , 3 );
		
		// behavior
		add_action('template_redirect',array(__CLASS__,'template_redirect')); // or wp
		
		// comment restrictions
		add_filter( 'comments_open', array(__CLASS__,'comments_open') , 10 , 2 );
		
		//misc
		add_filter( 'edit_post_link' , array(__CLASS__,'edit_post_link') , 10 , 2 );
		add_filter( 'post_class' , array( __CLASS__ , 'post_class' ) , 10 , 3 );

		// caps
		add_filter( 'map_meta_cap' , array( __CLASS__ , 'map_meta_cap' ) , 10 , 4 );
		add_filter( 'user_has_cap', array( __CLASS__ , 'user_has_cap' ) , 10 , 3  );
	}
	static function user_has_cap( $allcaps, $caps, $args ){
		$user_id = $args[1]; // user id
		$user_caps = get_user_meta($user_id , WPUND_GLOBAL_USERMETA_KEY , true );
		if ( $user_caps )
			$allcaps += array_combine( $user_caps , array_fill(0,count($user_caps),true));
		return $allcaps;
	}
	
	// --------------------------------------------------
	// comment restrictions
	// --------------------------------------------------
	static function template_redirect() {
		if ( is_singular() && $restricted_post = get_post() ) {
			if ( ! wpaa_user_can( $restricted_post->post_view_cap ) ) {
				do_action( 'wpaa_view_restricted_post' , $restricted_post->ID , $restricted_post );
				$redirect			= false;
				$behavior 			= get_post_meta($restricted_post->ID,'_wpaa_post_behavior',true);
				$fallback_page_id	= get_post_meta($restricted_post->ID,'_wpaa_fallback_page',true);
				
				// no behavior? take default value
				if ( ! $behavior )
					$behavior = get_option( 'wpaa_default_behavior' );

				
				if ( $behavior == 'page' || is_user_logged_in() ) {
					
					// no fallback? take default value
					if ( ! $fallback_page_id )
						$fallback_page_id = get_option( 'wpaa_fallback_page' );
					
					$fallback_page = get_post( $fallback_page_id );
					
					if ( wpaa_user_can( $fallback_page->post_view_cap ) && $fallback_page->post_status == 'publish' ) {
						// if accessable take user to the fallback page
						$redirect = get_permalink( $fallback_page_id );
					} else {
						// last resort: send him home
						$redirect = home_url();
					}
				} else if ( $behavior == 'login' ) {
					// get user to login and return him to the requested page.
					$redirect = wp_login_url( get_permalink() );
				} else if ( $behavior == '404' ) { // 404
					global $wp_query;
					$wp_query->set_404();
					status_header(404);
				}
				$redirect = apply_filters( 'wpaa_restricted_post_redirect' , $redirect , $restricted_post->ID , $restricted_post );
				if ( $redirect ) {
					wp_redirect( $redirect );
					exit();
				}
			}
		}
	}
	
	
	// --------------------------------------------------
	// comment restrictions
	// --------------------------------------------------
	static function comments_open( $open, $post_id ) {
		if ( $post = get_post($post_id) ) {
			if ( $post->post_comment_cap != 'exist' )
				$open = wpaa_user_can( $post->post_comment_cap ) && wpaa_user_can( $post->post_view_cap );
		}
		return $open;
	}
	
	
	// --------------------------------------------------
	// edit link
	// --------------------------------------------------
	static function edit_post_link( $link , $post_ID ) {
		// we only get a post id, se we better rely on map_meta_cap, where the post object is loaded
		if ( current_user_can('edit_post',$post_ID ) )
			return $link;
		return '';
	}
	
	// --------------------------------------------------
	// Post class
	// --------------------------------------------------
	static function post_class( $classes , $class , $post_ID ) {
		$post = get_post( $post_ID );
		
		if ( $post->post_view_cap != 'exist' && wpaa_user_can( $post->post_view_cap ) ) {
			$classes[] = 'wpaa-view-restricted';
			$classes[] = "wpaa-view-{$post->post_view_cap}";
		}
		if ( $post->post_edit_cap != 'exist' && current_user_can('edit_post',$post_ID ) ) {
			$classes[] = 'wpaa-edit-restricted';
			$classes[] = "wpaa-edit-{$post->post_edit_cap}";
		}
		if ( $post->post_comment_cap != 'exist' && wpaa_user_can( $post->post_comment_cap ) && wpaa_user_can( $post->post_view_cap ) ) {
			$classes[] = 'wpaa-comment-restricted';
			$classes[] = "wpaa-comment-{$post->post_comment_cap}";
		}
		return array_unique($classes);
	}
	
	// --------------------------------------------------
	// editing caps
	// --------------------------------------------------
	static function map_meta_cap($caps, $cap, $user_id, $args ) {
		switch ( $cap ) {
			case 'edit_post':
			case 'delete_post':
			case 'edit_page':
			case 'delete_page':
				if ( count($args[0]) ) {
					$post_ID = $args[0];
					// if he not can like specfied, ;
					$post = get_post( $post_ID );
					$edit_cap = $post->post_edit_cap;
					$view_cap = $post->post_view_cap;
					if ( ! $edit_cap )
						break;
					if ( ! wpaa_user_can( $edit_cap ) || ! wpaa_user_can( $view_cap ) )
						$caps[] = 'do_not_allow';
				}
				break;
		}
		return $caps;
	}
	
	// --------------------------------------------------
	// viewing restrictions
	// --------------------------------------------------
	static function skip_undisclosed_items( $items ) {
		// everything's fine - return.
		if ( current_user_can( 'administrator' ) )
			return $items;
		
		// remove undisclosed posts
		$ret = array();
		foreach ( $items as $i => $item ) {
			if ( wpaa_user_can( $item->post_view_cap ) )
				$ret[] = $item;
		}
		return $ret;
	}
	
	static function get_posts_where( $where , &$wp_query ) {
		global $wpdb;
		$where = self::_get_where( $where , $wpdb->posts );
		return $where;
	}
	static function get_posts_join( $join , &$wp_query ) {
		global $wpdb;
// 		if ( is_singular() )
// 			$join .= " LEFT JOIN $wpdb->postmeta AS wpaa_postmeta ON wpaa_postmeta.meta_key = '_wpaa_post_behavior' AND wpaa_postmeta.meta_value IS NOT NULL";
		return $join;
	}
	
	static function get_adjacent_post_where( $where , $in_same_cat, $excluded_categories ) {
		return self::_get_where($where);
	}
	static function get_adjacent_post_join( $join , $in_same_term, $excluded_terms ) {
		global $wpdb;
		$join .= " LEFT JOIN $wpdb->postmeta AS wpaa_postmeta ON wpaa_postmeta.meta_key = '_wpaa_post_behavior' AND wpaa_postmeta.meta_value IS NOT NULL";
		return $join;
	}


	private static function _get_where( $where , $table_name = 'p' ) {
		// not true on multisite
		if ( is_singular() || ( ! is_multisite() && current_user_can('administrator') ) )
			return $where;
		
		$caps = array('exist');
		if ( is_user_logged_in() ) {
			// get current user's groups
			$roles = new WP_Roles();
			
			// reading
			if ( current_user_can( 'read' ) )
				$caps[] = 'read';
			
			// user's roles
			$user_roles = wpaa_user_contained_roles();
			foreach ( $user_roles as $role )
				$caps[] = $role;
			
			// user's custom caps
			foreach( UndisclosedUserlabel::get_label_array( ) as $cap => $capname)
				if ( wpaa_user_can_accessarea( $cap ) )
					$caps[] = $cap;
		}
		$add_where = " $table_name.post_view_cap IN ('".implode( "','" , $caps ) . "')";
		if ( is_single() )
			$add_where .= " OR (wpaa_postmeta.meta_value IS NOT NULL)";

		$add_where = " AND ( $add_where ) ";
		return $where . $add_where;
	}

}
UndisclosedPosts::init();
endif;
