<?php
/**
* @package WP_AccessAreas
* @version 1.0.0
*/ 

// ----------------------------------------
//	Data model for Userlabels.
// ----------------------------------------

if ( ! class_exists('UndisclosedUserlabel') ):
class UndisclosedUserlabel {
	
	private static $_what_went_wrong=0;
	
	static function get_count_available_userlabels( ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . WPUND_USERLABEL_TABLE;
		
		$query = "SELECT COUNT(*) FROM $table_name WHERE blog_id=0 ";
		if ( ! is_network_admin() ) {
			$blog_id = get_current_blog_id();
			$query .= "OR blog_id=$blog_id";
		}
		return $wpdb->get_var($query);
	}
	static function get_available_userlabels( $limit = 0 , $order = 'blog_id DESC,cap_title ASC'  ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . WPUND_USERLABEL_TABLE;
		
		$query = 'SELECT * FROM '.$table_name.' WHERE blog_id=0 ';
		$query_param = array();
		if ( ! is_network_admin() ) {
			$query_param[] = get_current_blog_id();
			$query .= 'OR blog_id=%d';
		}
		if ( $sql_orderby = sanitize_sql_orderby($order) ) {
			$query .= " ORDER BY $sql_orderby";
		}
		if ( $limit )
			$query .= " LIMIT $limit" ;
		if ( count($query_param) ) {
			array_unshift($query_param,$query);
			$query = call_user_func_array( array($wpdb,'prepare') , $query_param);
		}
		return $wpdb->get_results($query);
	}
	static function get_blog_userlabels( $blog_id = 0 , $order_by = 'cap_title' , $order = 'ASC' ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . WPUND_USERLABEL_TABLE;
		if ( ! $blog_id ) 
			$blog_id = get_current_blog_id();
		$query = 'SELECT * FROM '.$table_name.' WHERE blog_id=%s ';
		if ( $sql_orderby = sanitize_sql_orderby("$order_by $order") )
			$query .= " ORDER BY $sql_orderby";
		
		return $wpdb->get_results( $wpdb->prepare( $query , $blog_id ) );
	}
	static function get_network_userlabels(  $order_by = 'cap_title' , $order = 'ASC' ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . WPUND_USERLABEL_TABLE;
		$query = 'SELECT * FROM '.$table_name.' WHERE blog_id=0 ';
		if ( $sql_orderby = sanitize_sql_orderby("$order_by $order") )
			$query .= " ORDER BY $sql_orderby";
		
		return $wpdb->get_results( $query );
	}
	static function get_label_array( ) {
		$labels = self::get_available_userlabels( );
		$label_map = array();
		foreach ( $labels as $item ) {
			$label_map[$item->capability] = $item->cap_title;
			if ( is_multisite() && ! $item->blog_id ) 
				$label_map[$item->capability] .= ' '.__('(Network)','wpundisclosed');
		}
		return $label_map;
	}
	
	static function delete_userlabel( $id ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . WPUND_USERLABEL_TABLE;
		
		$userlabel = self::get_userlabel( $id );
		if ( ! $userlabel ) {
			self::$_what_went_wrong = 5;
			return false;
		}
		if ( is_multisite() ) {
			if ( ! $userlabel->blog_id ) { // network wide !
				$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			} else {
				$blogids = array( $userlabel->blog_id );
			}
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				self::_delete_userlabel_from_blog( $userlabel );
			}
			restore_current_blog();
			
			// remove global capabilities
			$query =  "SELECT * FROM $wpdb->usermeta WHERE meta_key = '".WPUND_GLOBAL_USERMETA_KEY."' AND meta_value LIKE '%\"".WPUND_USERLABEL_PREFIX."%'" ;
			$usermeta = $wpdb->get_results($query);

			foreach ( $usermeta as $meta) {
				$caps = maybe_unserialize($meta->meta_value);
				$caps = array_filter( $caps , array( __CLASS__ , 'is_not_custom_cap' ) );
				$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->usermeta SET meta_value=%s WHERE umeta_id=%d",
					serialize( $caps ), 
					$meta->umeta_id
				) );
			}
		} else {
			self::_delete_userlabel_from_blog( $userlabel );
		}
		return $wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE ID=%d",$id ) );
	}
	
	private static function _delete_userlabel_from_blog( &$userlabel ) {
		global $wpdb;
		$post_status_sql = '';
		$default_post_status = get_option('wpaa_default_post_status');
		if ( $default_post_status  && in_array( $default_post_status , UndisclosedSettings::get_post_stati( ) ) )
			$post_status_sql = $wpdb->prepare(" , post_status=%s " , $default_post_status );
			
		// delete everything from posts and restore usefull default values
		$query = $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET post_view_cap='exist' $post_status_sql WHERE post_view_cap=%s" , $userlabel->capability ) );
		$query = $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET post_edit_cap='exist' WHERE post_edit_cap=%s" , $userlabel->capability ) ); // back to default
		$query = $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET post_comment_cap='exist',comment_status='closed' WHERE post_comment_cap=%s" , $userlabel->capability ) ); // back to default
		
		if ( is_multisite() )
			$current_blog_id = get_current_blog_id();

		
		// remove all caps from users
		$users = get_users( );
		foreach( $users as $user ) {
			if ( is_multisite() )
				$user->for_blog( $current_blog_id );
			$user->remove_cap( $userlabel->capability );
		}
	}
	private static function is_not_custom_cap( $capname ) {
		return strpos( $capname , WPUND_USERLABEL_PREFIX ) !== 0;
	}
	static function get_userlabel( $id ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . WPUND_USERLABEL_TABLE;

		return $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE ID = %d",$id) );
	}
	static function what_went_wrong( ) {
		$ret = self::$_what_went_wrong;
		self::$_what_went_wrong = 0;
		return $ret;
	}
	static function create_userlabel( $data ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . WPUND_USERLABEL_TABLE;

		extract( $data , EXTR_SKIP ); // cap_title, blog_id
		
		if ( self::title_exists( $cap_title , $blog_id ) ) {
			self::$_what_went_wrong = 4;
			return false;
		}
		
		
		$capability = $blog_id ? wpaa_get_local_prefix($blog_id) : WPUND_USERLABEL_PREFIX;
		$capability .= sanitize_title($cap_title);

		$query = $wpdb->prepare(
			"INSERT INTO $table_name (`cap_title`,`capability`,`blog_id`) VALUES (%s,%s,%d)",
			$cap_title,
			$capability,
			$blog_id
		);
		$wpdb->query( $query );
		return $wpdb->insert_id;
	}
	static function update_userlabel( $data ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . WPUND_USERLABEL_TABLE;

		extract( $data , EXTR_SKIP );

		if ( self::title_exists( $cap_title , $blog_id ) ) {
			self::$_what_went_wrong = 4;
			return false;
		}

		$query = $wpdb->prepare(
			"UPDATE $table_name SET cap_title=%s,blog_id=%d WHERE ID=%d",
			$cap_title,
			$blog_id,
			$id
		);
		$wpdb->query( $query );
		return $id;
	}
	static function title_exists( $cap_title , $blog_id ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . WPUND_USERLABEL_TABLE;
		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE cap_title=%s AND blog_id=%d" , $cap_title , $blog_id) );
	}
	static function capability_exists( $cap ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . WPUND_USERLABEL_TABLE;
		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE capability=%s" , $cap) );
	}
}
endif;

