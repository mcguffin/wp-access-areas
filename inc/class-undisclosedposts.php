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

		// viewing restrictions
		add_action( 'get_pages' , array( __CLASS__ , 'skip_undisclosed_items' ) , 10 , 1 ); // needed by nav menus
		add_filter( "posts_where" , array( __CLASS__ , "get_posts_where" ) , 10, 2 );

		add_filter( "get_next_post_where" , array( __CLASS__ , "get_adjacent_post_where" ) , 10, 3 );
		add_filter( "get_previous_post_where" , array( __CLASS__ , "get_adjacent_post_where" ) , 10, 3 );
		
		
		// comment restrictions
		add_filter( 'comments_open', array(__CLASS__,'comments_open') , 10 , 2 );
		add_filter('edit_post_link',array(__CLASS__,'edit_post_link'),10,2);
		add_filter('map_meta_cap', array( __CLASS__ , 'map_meta_cap' ) ,10,4);

		add_filter( 'user_has_cap', array( __CLASS__ , 'user_has_cap' ) , 10 , 3  );
	}
	function user_has_cap( $allcaps, $caps, $args ){
		$user_id = $args[1]; // user id
		$user_caps = get_user_meta($user_id , WPUND_GLOBAL_USERMETA_KEY , true );
		if ( $user_caps )
			$allcaps += array_combine( $user_caps , array_fill(0,count($user_caps),true));
		return $allcaps;
	}
	
	// --------------------------------------------------
	// comment restrictions
	// --------------------------------------------------
	static function comments_open( $open, $post_id ) {
		if ( $post = get_post($post_id) ) {
			if ( $post->post_comment_cap != 'exist' )
				$open = wpaa_user_can( $post->post_comment_cap );
		}
		return $open;
	}
	
	
	// --------------------------------------------------
	// edit link
	// --------------------------------------------------
	static function edit_post_link( $link , $post_ID ) {
		if ( current_user_can('edit_post',$post_ID ) )
			return $link;
		return '';
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
	
	static function get_adjacent_post_where( $where , $in_same_cat, $excluded_categories ) {
		return self::_get_where($where);
	}


	private static function _get_where( $where , $table_name = 'p' ) {
		// not true on multisite
		if ( ! is_multisite() && current_user_can('administrator') )
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
		$where .= " AND $table_name.post_view_cap IN ('".implode( "','" , $caps ) . "')";
		return $where;
	}

}
UndisclosedPosts::init();
endif;

?>