<?php


namespace AccessAreas\Model;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use AccessAreas\Core;

class ModelPost extends Core\PluginComponent {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		$this->user = ModelUser::instance();

		// make sure out filters are applied
		add_action( 'pre_get_posts' , array( $this, 'wp_query_allow_filters' ) );

		// object filter
		add_action( 'get_pages' , array( $this, 'filter_viewable_posts' ) , 10 , 1 ); // needed by nav menus

		//
		// query filters (read)
		//
		// WHERE
		add_filter( 'posts_where' , array( $this, 'get_posts_where' ) , 10 , 2 );
		add_filter( 'getarchives_where' , array( $this, 'get_archiveposts_where' ) , 10 , 2 );
		add_filter( 'get_next_post_where' , array( $this, 'get_adjacent_post_where' ) , 10 , 3 );
		add_filter( 'get_previous_post_where' , array( $this, 'get_adjacent_post_where' ) , 10 , 3 );
		/*
		// see calendar wiget issue: https://core.trac.wordpress.org/ticket/29319
		add_filter( 'getcalendar_where' , array( __CLASS__ , 'get_archiveposts_where' ) , 10 , 1 );
		add_filter( 'getcalendar_next_where' , array( __CLASS__ , 'get_archiveposts_where' ) , 10 , 1 );
		add_filter( 'getcalendar_previous_where' , array( __CLASS__ , 'get_archiveposts_where' ) , 10 , 1 );
		//*/

		// JOIN
		add_filter( 'posts_join' , array( $this, 'get_posts_join' ) , 10 , 2 );
		add_filter( 'get_next_post_join' , array( $this, 'get_adjacent_post_join' ) , 10 , 3 );
		add_filter( 'get_previous_post_join' , array( $this, 'get_adjacent_post_join' ) , 10 , 3 );


		// behavior
//		add_action('template_redirect',array(__CLASS__,'template_redirect')); // or wp


		//	misc
	//	add_filter( 'edit_post_link' , array( $this,'edit_post_link') , 10 , 2 );
			// >> shouldn't this work through map_meta_cap...?

		add_filter( 'post_class' , array( $this, 'post_class' ) , 10 , 3 );


		// caps
//		add_filter( 'map_meta_cap' , array( __CLASS__ , 'map_meta_cap' ) , 10 , 4 );
//		add_filter( 'user_has_cap', array( __CLASS__ , 'user_has_cap' ) , 10 , 3  );

	}

	/**
	 *	@filter post_class
	 */
	public function post_class( $classes, $class, $post_ID ) {
		if ( $post_ID ) {
			$post = get_post( $post_ID );

			if ( $post->post_view_cap != 'exist' ) {
				$classes[] = 'wpaa-view-restricted';
				$classes[] = "wpaa-view-{$post->post_view_cap}";
			}
			if ( $post->post_edit_cap != 'exist' && $this->user->can( 'edit_post', $post_ID ) ) {
				$classes[] = 'wpaa-edit-restricted';
				$classes[] = "wpaa-edit-{$post->post_edit_cap}";
			}
			if ( $post->post_comment_cap != 'exist' && $this->user->can( $post->post_comment_cap ) ) {
				$classes[] = 'wpaa-comment-restricted';
				$classes[] = "wpaa-comment-{$post->post_comment_cap}";
			}
		}
		return array_unique( $classes );
	}


	// public function edit_post_link( $link , $post_ID ) {
	// 	// we only get a post id, so we better rely on map_meta_cap, where the post object is loaded
	// 	if ( current_user_can( 'edit_post', $post_ID ) )
	// 		return $link;
	// 	return '';
	// }
	/**
	 *	@action pre_get_posts
	 */
	public function wp_query_allow_filters( $wp_query ) {
		$wp_query->set('suppress_filters',false);
	}


	/**
	 *	@filter get_pages
	 */
	public function filter_viewable_posts( $posts ) {
		// everything's fine - return.
		return $this->filter_posts($posts, 'view' );
	}

	/**
	 *	Filter posts by allowd action
	 *	@param array $posts
	 *	@param string $action view|comment|edit
	 */
	protected function filter_posts( $posts, $action = 'view' ) {
		if ( current_user_can( 'administrator' ) ) {
			return $posts;
		}
		$prop = "post_{$action}_cap";

		// remove undisclosed posts
		$filtered_posts = array();
		foreach ( $posts as $post ) {
			if ( wpaa_user_can( $post->$prop ) ) {
				$filtered_posts[] = $post;
			}
		}
		return $filtered_posts;
	}



	/**
	 *	@filter getarchives_where
	 */
	public function get_archiveposts_where( $where , $args = null ) {
		$where = $this->build_where( $where , '' );
		return $where;
	}

	/**
	 *	@filter posts_where
	 */
	public function get_posts_where( $where , $wp_query ) {
		global $wpdb;
		$where = $this->build_where( $where , $wpdb->posts );
		return $where;
	}

	/**
	 *	@filter posts_join
	 */
	public function get_posts_join( $join , $wp_query ) {
// 		global $wpdb;
		return $join;
	}

	/**
	 *	@filter get_previous_post_where
	 *	@filter get_mext_post_where
	 */
	public function get_adjacent_post_where( $where , $in_same_cat, $excluded_categories ) {
		return $this->build_where($where);
	}

	/**
	 *	@filter get_previous_post_join
	 *	@filter get_mext_post_join
	 */
	public function get_adjacent_post_join( $join , $in_same_term, $excluded_terms ) {
		global $wpdb;
		$join .= " LEFT JOIN $wpdb->postmeta AS wpaa_postmeta ON wpaa_postmeta.meta_key = '_wpaa_post_behavior' AND wpaa_postmeta.meta_value IS NOT NULL";
		return $join;
	}

	/**
	 *	Build where clause
	 *	@param string $where
	 *	@param string $table_name
	 *	@return string
	 */
	public function build_where( $where , $table_name = 'p' ) {
		global $wpdb, $wp_query;

		// disable filtering: on queries for single posts/pages and for single blog administrators
		if ( ( isset( $wp_query ) && is_singular() && preg_match( "/{$wpdb->posts}.(post_name|ID)\s?=/" , $where ) ) || ( ! is_multisite() && current_user_can('administrator') ) ) {
			return $where;
		}

		if ( $table_name && substr($table_name,-1) !== '.' )
			$table_name .= '.';

		$caps = array('exist');

		if ( is_user_logged_in() ) {

			// reading
			if ( current_user_can( 'read' ) ) {
				$caps[] = 'read';
			}

			$caps = array_merge( $caps, $this->user->get_contained_roles() );

			$caps = array_merge( $caps, $this->user->get_access_area_caps() );

		}
		$add_where = " {$table_name}post_view_cap IN ('".implode( "','" , $caps ) . "')";
//		if ( is_single() ) // why did I do this....?
//			$add_where .= " OR (wpaa_postmeta.meta_value IS NOT NULL)";

		$add_where = " AND ( $add_where ) ";
		return $where . $add_where;
	}



	/**
	 *	@inheritdoc
	 */
	public function activate() {
		$this->install_posts_table();
	}

	/**
	 *	Add permission olumns to posts table
	 */
	private function install_posts_table( ) {

		global $wpdb;

		$cols = array( 'comment_cap'=>'post_comment_cap' , 'edit_cap'=>'post_edit_cap' , 'view_cap'=>'post_view_cap' );

		foreach ( $cols as $idx => $col ) {

			$c = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM $wpdb->posts LIKE %s", $col ) );

			if ( empty( $c ) ) {
				$wpdb->query("ALTER TABLE $wpdb->posts ADD COLUMN $col varchar(128) NOT NULL DEFAULT 'exist' AFTER `post_status`;");
			}

			$i = $wpdb->get_results( $wpdb->prepare( "SHOW INDEX FROM $wpdb->posts WHERE Key_name = %s", $idx ) );

			if ( empty( $i ) ) {
				$wpdb->query("ALTER TABLE $wpdb->posts ADD INDEX `$idx` (`$col`);");
			}
		}
	}

	/**
	 *	remove permission olumns from posts table
	 */
	private function uninstall_posts_table( ) {
		global $wpdb;
		// , 'edit_cap'=>'post_edit_cap' will be used later.
		$cols = array( 'comment_cap'=>'post_comment_cap' , 'edit_cap'=>'post_edit_cap' , 'view_cap'=>'post_view_cap' );
		foreach ( $cols as $idx => $col ) {

			$c = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM $wpdb->posts LIKE %s", $col ) );

			if ( ! empty( $c ) ) {
				$wpdb->query("ALTER TABLE $wpdb->posts DROP COLUMN $col;");
			}

			$i = $wpdb->get_results( $wpdb->prepare( "SHOW INDEX FROM $wpdb->posts WHERE Key_name = %s", $idx ) );

			if ( ! empty( $i ) ) {
				$wpdb->query("ALTER TABLE $wpdb->posts DROP INDEX ('$idx');");
			}
		}
	}

	/**
	 *	@inheritdoc
	 */
	public function deactivate() {
	}

	/**
	 *	@inheritdoc
	 */
	public function uninstall() {
		// drop table
		$this->uninstall_posts_table();

		global $wpdb;
		$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE %s", $wpdb->esc_like('_wpaa_').'%') );
	}

	/**
	 *	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
		if ( version_compare( $old_version, '2.0.0', '<' ) ) {
			$this->upgrade_1x();
		}

	}

	/**
	 *	Upgrade from version 1.x
	 */
	private function upgrade_1x() {
		global $wpdb;
		$posts = $wpdb->get_results($wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key=%s AND meta_value=%s" , '_wpaa_post_behavior', 'login' ));
		// post behavior
		foreach ( $posts as $p ) {
			update_post_meta( $p->post_id, '_wpaa_post_behavior', 'page' );
			update_post_meta( $p->post_id, '_wpaa_post_behavior_login_redirect', true );
		}

	}

}
