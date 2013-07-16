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

		if ( ! is_admin() ) {
			// viewing restrictions
			add_action( 'get_pages' , array( __CLASS__ , 'skip_undisclosed_items' ) , 10 , 1 );
			add_filter( "posts_where" , array( __CLASS__ , "get_posts_where" ) , 10, 2 );

			add_filter( "get_next_post_where" , array( __CLASS__ , "get_adjacent_post_where" ) , 10, 3 );
			add_filter( "get_previous_post_where" , array( __CLASS__ , "get_adjacent_post_where" ) , 10, 3 );
			
			
			// comment restrictions
			add_filter( 'comments_open', array(__CLASS__,'comments_open') , 10 , 2 );
		}
		// implement editing restrictions
	}
	
	// --------------------------------------------------
	// comment restrictions
	// --------------------------------------------------
	static function comments_open( $open, $post_id ) {
		if ( $open )
			return $open;

		$_post = get_post($post_id);
		return self::_user_can($_post->post_comment_cap);
	}
	
	
	
	// --------------------------------------------------
	// viewing restrictions
	// --------------------------------------------------
	static function undisclosed_content( $content ) {
		if ( current_user_can( 'administrator' ) )
			return $content;
		if ( self::_user_can( get_post()->post_view_cap ) )
			return $content;
		return sprintf(__('Please <a href="%s">log in</a> to see this content!' , 'wpundisclosed'),wp_login_url( get_permalink() ));
	}
	
	static function skip_undisclosed_items( $items ) {
		// everything's fine - return.
		if ( current_user_can( 'administrator' ) )
			return $items;
		
		// remove undisclosed posts
		$ret = array();
		foreach ( $items as $i => $item ) {
			if ( self::_user_can( $item->post_view_cap ) )
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
		if ( current_user_can( 'administrator' ) )
			return $where;
		if ( is_user_logged_in() ) {
			// get current user's groups
			$roles = new WP_Roles();
			$cond = array( "$table_name.post_view_cap = 'exist'");
			foreach( array_keys( array_merge( UndisclosedUserlabel::get_label_array( ) , $roles->get_names() )) as $cap)
				if ( current_user_can($cap) )
					$cond[] = "$table_name.post_view_cap = '$cap'";
			
			return $where . " AND (".implode( ' OR ' , $cond ) . ")";
		}
		$where .= " AND ($table_name.post_view_cap = 'exist') ";

		return $where;
	}
	
	// --------------------------------------------------
	// private - retrieving user capabilities
	// --------------------------------------------------
	static function _user_can_role( $role , $user_role_caps ) {
		$roles = new WP_Roles();
		if ($roles->is_role($role))
			return 0 == count(array_diff_assoc(  $roles->get_role( $role )->capabilities , $user_role_caps));
		return false;
	}
	static function _user_can($cap) {
		if ( !$cap || 'exist' == $cap || 'read' == $cap && is_user_logged_in() )
			return true;
		return current_user_can( $cap );
	}
}
UndisclosedPosts::init();
endif;

?>