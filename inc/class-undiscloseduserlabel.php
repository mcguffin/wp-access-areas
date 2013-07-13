<?php

// ----------------------------------------
//	Data model for Userlabels.
// ----------------------------------------

if ( ! class_exists('UndisclosedUserlabel') ):
class UndisclosedUserlabel {
	
	static function get_available_userlabels() {
		global $wpdb; //This is used only if making any database queries
		$table_name = $wpdb->base_prefix . WPUND_USERLABEL_TABLE;

		$query = $wpdb->prepare( 'SELECT * FROM '.$table_name.' WHERE blog_id=0 OR blog_id=%s ORDER BY cap_title ASC' ,
			$wpdb->blogid
		);
		return $wpdb->get_results($query);
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

		if ( is_multisite() ) {
			if ( ! $userlabel->blog_id ) { // network wide !
				$blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
			} else {
				$blogids = array( $userlabel->blog_id );
			}
			foreach ( $blogids as $blogid ) {
				switch_to_blog( $userlabel->blog_id );
				self::_delete_userlabel_from_blog( );
			}
			restore_current_blog();
		} else {
			self::_delete_userlabel_from_blog( );
		}
		$wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE ID=%d",$id ) );
	}
	
	private static function _delete_userlabel_from_blog( ) {
		// delete everything from posts and restore usefull default values
		$query = $wpdb->prepare("UPDATE $wpdb->posts SET post_view_cap='exists',post_status='private' WHERE post_view_cap=%s" , $default_cap , $userlabel->capability );
		$query = $wpdb->prepare("UPDATE $wpdb->posts SET post_edit_cap='exists' WHERE post_edit_cap=%s" , $default_cap , $userlabel->capability ); // back to default
		$query = $wpdb->prepare("UPDATE $wpdb->posts SET post_comment_cap='exists',comment_status='closed' WHERE post_comment_cap=%s" , $default_cap , $userlabel->capability ); // back to default

		// remove all caps from users
		$users = get_users( ); 
		foreach( $users as $user )
			$user->remove_cap( $userlabel->capability );
	}
	
	static function get_userlabel( $id ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . WPUND_USERLABEL_TABLE;

		return $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE ID = %d",$id) );
	}
	static function create_userlabel( $data ) {
		global $wpdb;
		$table_name = $wpdb->base_prefix . WPUND_USERLABEL_TABLE;

		extract( $data , EXTR_SKIP );

		$capability = WPUND_USERLABEL_PREFIX;
		if ( $blog_id ) 
			$capability .= "{$blog_id}_";
		$capability .= sanitize_title_with_dashes($cap_title);

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
		$query = $wpdb->prepare(
			"UPDATE $table_name SET cap_title=%s,blog_id=%d WHERE ID=%d",
			$cap_title,
			$blog_id,
			$id
		);
		$wpdb->query( $query );
		return $id;
	}
}
endif;

?>