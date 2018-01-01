<?php

namespace AccessAreas\Compat\WPMU;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use AccessAreas\Core;
use AccessAreas\Model;
use AccessAreas\Settings;


class WPMU extends Core\PluginComponent {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		// add_action('wpmu_new_blog' , array( $this, 'set_network_roles_for_blog' ) , 10 , 1 );
		// add_action('wpmu_upgrade_site' , array( $this, 'set_network_roles_for_blog' ) , 10 ,1 );
		add_filter( 'wpaa_allow_create_accessarea', array( $this, 'allow_create_access_area'), 10, 2 );
		add_filter( 'wpaa_allow_delete_accessarea', array( $this, 'allow_edit_access_area'), 10, 2 );
		add_filter( 'wpaa_allow_edit_accessarea', array( $this, 'allow_edit_access_area'), 10, 2 );

		add_filter( 'wpaa_create_capability_prefix', array( $this, 'capability_prefix' ), 10, 2 );
		add_filter( 'wpaa_access_areas_dropdown_user', array( $this, 'add_global_access_areas' ) );
		add_filter( 'wpaa_access_areas_dropdown_post', array( $this, 'add_global_access_areas' ) );
		add_filter( 'wpaa_assignable_access_areas_user', array( $this, 'merge_global_access_areas' ), 10, 2 );

		add_filter( 'wpaa_allow_grant_access', array( $this, 'allow_grant_access'), 10, 2 );

		add_action( 'after_mu_upgrade', array( Core\Core::instance(), 'maybe_upgrade' ) );

		add_action( 'after_mu_upgrade', array( $this, 'maybe_uninstall' ) );

		if ( is_network_admin() ) {
			NetworkAdmin::instance();
		}
	}

	/**
	 *	@filter wpaa_available_access_areas_post
	 *	@filter wpaa_available_access_areas_user
	 */
	public function add_global_access_areas( $access_areas ) {

		$model = Model\ModelAccessAreas::instance();

		$return = $access_areas;
		$global = $model->fetch_by( 'blog_id', 0 );

		if ( count( $global ) ) {
			$return = array();
			$return[ __( 'Local', 'wp_access_areas') ] = $access_areas;
			$return[ __( 'Network', 'wp_access_areas') ] = $global;
		}

		return $return;
	}

	/**
	 *	@filter wpaa_assignable_access_areas_user
	 */
	public function merge_global_access_areas( $access_areas, $user ) {
		$model = Model\ModelAccessAreas::instance();
		$model->fetch_by( 'blog_id', 0 );
		return array_merge( $access_areas, $model->fetch_by( 'blog_id', 0 ) );
	}

	public function capability_prefix( $prefix, $blog_id ) {
		if ( $blog_id === 0 ) {
			return Core\Core::instance()->get_prefix();
		}
		return $prefix;
	}

	/**
	 *	@filter wpaa_allow_grant_access
	 */
	public function allow_grant_access( $allowed, $access_area ) {
		if ( $access_area->blog_id === 0 ) {
			return $allowed && current_user_can( $access_area->capability );
		}
		return $allowed;
	}

	/**
	 *	@filter wpaa_allow_create_accessarea
	 */
	public function allow_create_access_area( $allowed, $params ) {
		if ( isset( $params['blog_id'] ) && $params['blog_id'] === 0 ) {
			return current_user_can('manage_network_users');
		}
		return $allowed;
	}

	/**
	 *	@filter wpaa_allow_edit_accessarea
	 *	@filter wpaa_allow_delete_accessarea
	 */
	public function allow_edit_access_area( $allowed, $access_area_id ) {
		if ( $allowed ) {
			$model = Model\ModelAccessAreas::instance();
			$access_area = $model->fetch_one_by( 'id', $access_area_id );
			return $this->allow_create_access_area( $allowed, get_object_vars( $access_area ) );
		}
		return $allowed;
	}

	/**
	 *	@inheritdoc
	 */
	public function activate() {
	 	// iterate over the blog
//		Core\Plugin::activate();
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
		 // iterate blogs, alter posts table
	}

	/**
	 *	erase wpaa data on blog
	 */
	public function maybe_uninstall() {
		if ( get_site_option( 'wpaa_uninstall_active' ) ) {
			// uninstall posts, settings
			Settings\SettingsAccessAreas::instance()->uninstall();
			Model\ModelPost::instance()->uninstall();
		}
	}

	/**
 	 *	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
	}

}
