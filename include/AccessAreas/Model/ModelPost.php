<?php


namespace AccessAreas\Model;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use AccessAreas\Core;
use AccessAreas\Model;

class ModelPost extends Core\PluginComponent {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

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
		add_action( 'template_redirect', array( $this, 'template_redirect' ) ); // or wp


		//	misc
	//	add_filter( 'edit_post_link' , array( $this,'edit_post_link') , 10 , 2 );
			// >> shouldn't this work through map_meta_cap...?

		add_filter( 'post_class' , array( $this, 'post_class' ) , 10 , 3 );


		// caps
		add_filter( 'map_meta_cap' , array( $this, 'map_meta_cap' ) , 10 , 4 );
//		add_filter( 'user_has_cap', array( __CLASS__ , 'user_has_cap' ) , 10 , 3  );

	}

	/**
	 *	@action template_redirect
	 */
	static function template_redirect() {
		global $wp_query;

		if ( isset( $wp_query ) && is_singular() && $restricted_post = get_post() ) {
			if ( $restricted_post->ID !== 0 && ! wpaa_user_can( $restricted_post->post_view_cap ) ) {
				do_action( 'wpaa_view_restricted_post' , $restricted_post->ID , $restricted_post );


				if ( $this->get_post_type_setting( 'behavior_override', $restricted_post->post_type ) ) {
					$behavior 			= $this->get_post_setting( 'behavior', $restricted_post );
					$fallback_page_id	= $this->get_post_setting( 'fallback_page', $restricted_post );
					$login_redirect		= $this->get_post_setting( 'login_redirect', $restricted_post );
					$http_status		= $this->get_post_setting( 'http_status', $restricted_post );

				} else {
					$behavior 			= $this->get_post_type_setting( 'behavior', $restricted_post->post_type );
					$fallback_page_id	= $this->get_post_type_setting( 'fallback_page', $restricted_post->post_type );
					$login_redirect		= $this->get_post_type_setting( 'login_redirect', $restricted_post->post_type );
					$http_status		= $this->get_post_type_setting( 'http_status', $restricted_post->post_type );
				}

				$do_redirect = false;

				if ( $behavior === '404' ) {
					$redirect = get_permalink( $post->ID );
					$wp_query->set_404();

				} else if ( $behavior === 'page' ) {
					$redirect = get_permalink( $fallback_page_id );
					$do_redirect = true;

				} else if ( $behavior === 'status' ) {
					$redirect = get_permalink( $restricted_post->ID );
					$wp_query->query( array(
						'post__in'		=> array($fallback_page_id),
						'post_status'	=> 'publish',
						'post_type'		=> 'any',
					) );
					status_header( $http_status );
				}

				if ( $login_redirect && ! is_user_logged_in() ) {
					$redirect = wp_login_url( $redirect );
					$do_redirect = true;
				}

				$redirect = apply_filters( 'wpaa_restricted_post_redirect', $redirect, $restricted_post->ID, $restricted_post );

				if ( $do_redirect ) {
					wp_redirect( $redirect );
					exit();
				}
				return;

				if ( $behavior == 'login' && ! is_user_logged_in() ) {
					// get user to login and return him to the requested page.
					$redirect = wp_login_url( get_permalink( $restricted_post->ID ) );
				} else {
					if ( $behavior == 'page' || ( $behavior == 'login' && is_user_logged_in())) {

						if ( $fallback_page_id && wpaa_is_post_public( $fallback_page_id ) ) {
							// if accessable take user to the fallback page
							$redirect = get_permalink( $fallback_page_id );
						} else {
							// last resort: send him home
							$redirect = home_url();
						}
					} else { // assume 404
						$wp_query->set_404();
						status_header(404);
						return;
					}
				}
			}
		}
	}

	/**
	 *	@param string $key The setting to get. Keys: behaviour | fallback_page | login_redirect | http_status
	 *	@param WP_Post $post The post
	 */
	private function get_post_setting( $key, $post ) {
		if ( $value = get_post_meta( $post->ID, '_wpaa_' . $key, true ) ) {
			return $value;
		}
		return $this->get_post_type_setting( $key, $post->post_type );

	}

	/**
	 *	@param string $key The setting to get. Keys: behaviour | fallback_page | login_redirect | http_status
	 *	@param string $post_type The post type
	 */
	private function get_post_type_setting( $key, $post_type ) {
		$pt_options = get_option('wpaa_post_types');
		if ( isset( $pt_options[ $post_type ], $pt_options[ $post_type ][ $key ] ) ) {
			return $pt_options[ $post_type ][ $key ];
		}
		//
		return get_option( 'wpaa_default_' . $key );
	}


	/**
	 *	@filter map_meta_cap
	 */
	public function map_meta_cap( $caps, $cap, $user_id, $args ) {
		switch ( $cap ) {
			case 'edit_post': // belongs to post!
			case 'delete_post':
			case 'edit_page':
			case 'delete_page':
				if ( count( $args ) ) {
					$post_ID = $args[0];
					// if he not can like specfied, ;
					$post = get_post( $post_ID );

					if ( ! $post->post_edit_cap ) {
						break;
					}
					//*
					$caps[] = $post->post_edit_cap;
					$caps[] = $post->post_view_cap;
					/*/
					if ( ! ( $this->can( $post->post_edit_cap ) && $this->can( $post->post_view_cap ) ) ) {
						$caps[] = 'do_not_allow';
					}
					//*/
				}
				break;

			case 'edit_comment':
				if ( count( $args ) ) {

					$comment_ID = $args[0];
					$comment = get_comment( $comment_ID );

					if ( $comment && $comment->comment_post_ID  ) {

						$post = get_post( $comment->comment_post_ID );
						//*
						$caps[] = $post->post_comment_cap;
						/*/
						if ( ! $this->can( $post->post_comment_cap ) ) {
							$caps[] = 'do_not_allow';
						}
						//*/
					}
				}
				break;
		}
		return $caps;
	}

	/**
	 *	@param $post int|WP_Post
	 *	@return boolean
	 */
	public function post_is_public( $post ) {
		if ( ! is_object( $post ) ) {
			$post = get_post( $post );
		}
		if ( $post ) {
			return $post->post_status === 'publish' && $post->post_view_cap === 'exist';
		}
	}
	/**
	 *	@filter post_class
	 */
	public function post_class( $classes, $class, $post_ID ) {
		$user = ModelUser::instance();
		if ( $post_ID ) {
			$post = get_post( $post_ID );

			if ( $post->post_view_cap != 'exist' ) {
				$classes[] = 'wpaa-view-restricted';
				$classes[] = "wpaa-view-{$post->post_view_cap}";
			}
			if ( $post->post_edit_cap != 'exist' && $user->can( 'edit_post', $post_ID ) ) {
				$classes[] = 'wpaa-edit-restricted';
				$classes[] = "wpaa-edit-{$post->post_edit_cap}";
			}
			if ( $post->post_comment_cap != 'exist' && $user->can( $post->post_comment_cap ) ) {
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
		$user = ModelUser::instance();
		if ( $user->current_user_is_admin( 'administrator' ) ) {
			return $posts;
		}
		$prop = "post_{$action}_cap";

		// remove undisclosed posts
		$filtered_posts = array();
		foreach ( $posts as $post ) {
			if ( current_user_can( $post->$prop ) ) {
				$filtered_posts[] = $post;
			}
		}
		return $filtered_posts;
	}



	/**
	 *	@filter getarchives_where
	 */
	public function get_archiveposts_where( $where, $args = null ) {
		$where = $this->build_where( $where , '' );
		return $where;
	}

	/**
	 *	@filter posts_where
	 */
	public function get_posts_where( $where, $wp_query ) {
		global $wpdb;
		$where = $this->build_where( $where , $wpdb->posts );
		return $where;
	}

	/**
	 *	@filter posts_join
	 */
	public function get_posts_join( $join, $wp_query ) {
// 		global $wpdb;
		return $join;
	}

	/**
	 *	@filter get_previous_post_where
	 *	@filter get_mext_post_where
	 */
	public function get_adjacent_post_where( $where, $in_same_cat, $excluded_categories ) {
		return $this->build_where( $where );
	}

	/**
	 *	@filter get_previous_post_join
	 *	@filter get_mext_post_join
	 */
	public function get_adjacent_post_join( $join, $in_same_term, $excluded_terms ) {
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

		$user = ModelUser::instance();

		// single post/page views are handled bof @action template_redirect!
		$is_single_post = isset( $wp_query ) && is_singular() && preg_match( "/{$wpdb->posts}.(post_name|ID)\s?=/" , $where );

		if ( $user->current_user_is_admin() || $is_single_post ) {
			return $where;
		}

		if ( $table_name && substr($table_name,-1) !== '.' ) {
			$table_name .= '.';
		}

		$caps = $user->get_current_user_access_caps();

		$add_where = " {$table_name}post_view_cap IN ('".implode( "','" , $caps ) . "')";

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
		// update post behavior settings - login redirect is now a separate setting!
		foreach ( $posts as $p ) {
			update_post_meta( $p->post_id, '_wpaa_post_behavior', 'page' );
			update_post_meta( $p->post_id, '_wpaa_post_behavior_login_redirect', true );
		}

	}

}
