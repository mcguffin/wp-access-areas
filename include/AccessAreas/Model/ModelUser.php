<?php


namespace AccessAreas\Model;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use AccessAreas\Core;

class ModelUser extends Core\PluginComponent {

	/**
	 *	@var array
	 */
	private $contained_roles;

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wpaa_before_delete', array( $this, 'revoke_from_all') );

		add_filter( 'map_meta_cap' , array( $this, 'map_meta_cap' ) , 10 , 4 );


		$this->contained_roles = $this->get_role_hierarchy();
	}
	public function current_user_is_admin() {
		$is_admin = current_user_can( 'administrator' );
		return apply_filters( 'wpaa_current_user_is_admin', $is_admin );
	}

	public function get_current_user_access_caps() {
		// anonymous users
		$caps = array( 'exist' );

		if ( ! is_user_logged_in() ) {
			return $caps;
		}

		if ( current_user_can( 'read' ) ) {
			$caps[] = 'read';
		}
		$user = wp_get_current_user();
		$user_caps = $user->caps;
		foreach ( $user->roles as $role_slug ) {
			if ( isset( $user_caps[ $role_slug ] ) ) {
				unset( $user_caps[ $role_slug ] );
			}
			if ( isset( $this->contained_roles[ $role_slug ] ) ) {
				$caps = array_merge( $caps, $this->contained_roles[ $role_slug ] );
			}
		}
		$caps = array_merge( $caps, array_keys( array_filter( $user_caps ) ) );
		return array_unique($caps);
	}

	public function get_access_area_caps( $user = null ) {
		//
		return array();
	}

	public function get_contained_roles( $user = null ) {
		// all user roles
		
		return array();
	}


	/**
	 *	@filter map_meta_cap
	 */
	public function map_meta_cap( $caps, $cap, $user_id, $args ) {
		switch ( $cap ) {
			case 'edit_post':
			case 'delete_post':
			case 'edit_page':
			case 'delete_page':
				if ( count($args[0]) ) {
					$post_ID = $args[0];
					// if he not can like specfied, ;
					$post = get_post( $post_ID );

					if ( ! $post->post_edit_cap ) {
						break;
					}

					if ( ! $this->can( $post->post_edit_cap ) || ! $this->can( $post->post_view_cap ) ) {
						$caps[] = 'do_not_allow';
					}
				}
				break;

			case 'edit_comment':
				if ( count($args[0]) ) {

					$comment_ID = $args[0];
					$comment = get_comment( $comment_ID );

					if ( $comment && $comment->comment_post_ID  ) {

						$post = get_post( $comment->comment_post_ID );

						if ( ! $this->can( $post->post_view_cap ) ) {
							$caps[] = 'do_not_allow';
						}
					}
				}
				break;
		}
		return $caps;
	}


	/**
	 *	@action init
	 */
	public function init() {
		$wp_roles = wp_roles();
		add_action( "update_option_{$wp_roles->role_key}", array( $this, 'purge_role_hierachy_cache' ) );

	}

	/**
	 *	Setup an assoc indicating the role hierarchy.
	 */
	private function get_role_hierarchy() {
		$wp_roles = wp_roles();
		$transient_key = 'wpaa_role_hierarchy';

		if ( $contained_roles = get_transient( $transient_key ) ) {
			return $contained_roles;
		}
		$contained_roles = array();

		$rolenames = $wp_roles->get_names();

		foreach ( array_keys( $rolenames ) as $container_role ) {

			$contained_roles[ $container_role ] = array();

			foreach ( array_keys( $rolenames ) as $contained_role ) {
				$is_contained = $container_role === $contained_role
					||
					0 === count(array_diff_assoc(
						$wp_roles->get_role( $contained_role )->capabilities,
						$wp_roles->get_role( $container_role )->capabilities
					));
				if ( $is_contained ) {
					$contained_roles[ $container_role ][] = $contained_role;
				}
			}
		}

		set_transient( $transient_key, $contained_roles );

		return $contained_roles;
	}

	/**
	 *
	 */
	public function purge_role_hierachy_cache() {
		delete_transient( 'wpaa_role_hierarchy' );
	}

	/**
	 *	Whether current user has capability or role.
	 *
	 *	@param string $capability Capability or role name.
	 *	@param array $args Arguments such as Post IDs. (will be passed to WP current_user_can() if applicable)
	 *	@return bool
	 *	@return bool
	 */
	public function can( $capability, $args = array() ) {
		global $wp_roles;
		// exist always true. read always true for logged in users.
		if ( 'exist' == $capability || ('read' == $capability && is_user_logged_in() ) ) {
			return true;
		}

		// true for role
		if ( $wp_roles->is_role( $capability ) ) {
			return $this->can_role( $capability );
		}

		$model = ModelAccessAreas::instance();

		if ( $access_area = $model->fetch_one_by( 'capability', $capability ) ) {
			return $this->can_access_area( $capability, $args );
		}

		return current_user_can( $capability, $args );
	}

	/**
	 * Check if a user is allowed in a specific Access Area.
	 *
	 * @param string $capability Access Area Capability name
	 * @param array $args Arguments such as Post IDs. (will be passed to PW current_user_can() if applicable)
	 * @return boolean
	 */
	public function can_access_area( $capability, $args = array() ) {

		// always true for administrators on local caps
		if ( $this->current_user_is_admin() ) {
			$can = true;
		} else {
			$can = current_user_can( $capability, $args );
		}
		return apply_filters( 'wpaa_user_can_access_area', $can, $capability, $args );
	}

	/**
	 * Check if a user is allowed in a specific Access Area.
	 *
	 * @param string $capability Access Area Capability name
	 * @param array $args Arguments such as Post IDs. (will be passed to PW current_user_can() if applicable)
	 * @return boolean
	 */
	public function can_role( $rolename, $args = array() ) {
		if ( is_null( $user_role_caps ) ) {
			$user_role_caps = wpaa_get_user_role_caps();
		}
		if ( $wp_roles->is_role($role) ) {
			$can = 0 == count(array_diff_assoc(  $wp_roles->get_role( $role )->capabilities , $user_role_caps ) );

		}
		$can ;

		// always true for administrators on local caps
		return apply_filters( 'wpaa_user_can_access_area', current_user_can( $capability, $args ), $capability, $args );
	}
	/**
	 *	Revoke Access area from all users
	 */
	public function revoke_from_all( $access_area_id ) {
		$model = ModelAccessAreas::instance();
		$access_area = $model->fetch_one_by( 'id', $access_area_id );
		$users = get_users();
		foreach ( $users as $user ) {
			$this->revoke_user_access( $user, $access_area );
		}
	}

	/**
	 *	Grant Access
	 *	@param WP_User $user
	 *	@param object $access_area
	 *	@return bool
	 */
	public function grant_user_access( $user, $access_area ) {
		if ( ! $user->has_cap( $access_area->capability ) ) {
			$user->add_cap( $access_area->capability , true );
			do_action( 'wpaa_grant_access', $user, $access_area->capability, $access_area );
			do_action( "wpaa_grant_{$access_area->capability}", $user );
			return true;
		}
		return false;
	}

	/**
	 *	Revoke Access
	 *	@param WP_User $user
	 *	@param object $access_area
	 *	@return bool
	 */
	public function revoke_user_access( $user, $access_area ) {
		if ( $user->has_cap( $access_area->capability ) ) {
			$user->remove_cap( $access_area->capability, true );
			do_action( 'wpaa_grant_access', $user, $access_area->capability, $access_area );
			do_action( "wpaa_grant_{$access_area->capability}", $user );
			return true;
		}
		return false;
	}


	/**
	 *	@inheritdoc
	 */
	public function activate() {
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
		$model = Model\ModelAccessAreas::instance();
		$access_areas = $model->fetch_available();
		$users = get_users();
		foreach ( $users as $user ) {
			foreach ( $access_areas as $access_area ) {
				// much faster than $user->remove_cap( $access_area->capability )
				if ( isset( $user->caps[ $access_area->capability ] ) ) {
					unset( $user->caps[ $access_area->capability ] );
				}
			}
			update_user_meta( $user->ID, $user->cap_key, $user->caps );
		}
	}

	/**
	*	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
	}

}
