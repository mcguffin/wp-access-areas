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
	}
	
	// --------------------------------------------------
	// comment restrictions
	// --------------------------------------------------
	static function comments_open( $open, $post_id ) {
		if ( $open ) // opened by wp 
			return true;
		
		if ( $_post = get_post($post_id) )
			return wpaa_user_can($_post->post_comment_cap);
		
		return false;
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
		
		$cond = array( "$table_name.post_view_cap = 'exist'" );
		if ( is_user_logged_in() ) {
			// get current user's groups
			$roles = new WP_Roles();
			
			// reading
			if ( current_user_can( 'read' ) )
				$cond[] = "$table_name.post_view_cap = 'read'"; // logged in users
			
			// user's roles
			$user_roles = wpaa_user_contained_roles();
			foreach ( $user_roles as $role )
				$cond[] = "$table_name.post_view_cap = '$role'"; 
			
			// user's custom caps
			foreach( UndisclosedUserlabel::get_label_array( ) as $cap => $capname)
				if ( wpaa_user_can_accessarea( $cap ) )
					$cond[] = "$table_name.post_view_cap = '$cap'";
		}
		$where .= " AND (".implode( ' OR ' , $cond ) . ")";
		return $where;
	}

}
UndisclosedPosts::init();
endif;

?>