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

		if ( is_network_admin() ) {
			add_filter( 'wpaa_grant_options', array( $this, 'get_global_grant' ) );
		} else {
			add_filter( 'wpaa_grant_options', array( $this, 'add_global_grant' ) );
		}

		// edit post - assignable access
		add_filter( 'wpaa_access_options', array( $this, 'add_global_access' ) );

		add_filter( 'wpaa_assignable_access_areas_user', array( $this, 'merge_global_access_areas' ), 10, 2 );

		// override super user privileges
		add_filter( 'wpaa_current_user_is_admin', 'is_super_admin', 10, 0 );

		add_filter( 'wpaa_allow_grant_access', array( $this, 'allow_grant_access'), 10, 2 );

		add_action( 'wpmu_upgrade_site', array( $this, 'maybe_upgrade' ) );

		add_action( 'wpmu_upgrade_site', array( $this, 'maybe_uninstall' ) );

		add_filter( 'user_has_cap', array( $this, 'user_has_cap' ) , 10 , 4  );

		if ( is_admin() ) {
			AdminUsers::instance();
		}

		if ( is_network_admin() ) {
			NetworkAdmin::instance();
		}
	}



	/**
	 *	@filter user_has_cap;
	 */
	static function user_has_cap( $allcaps, $caps, $args, $user ) {

		if ( $user_caps = Users::instance()->get_global_caps( $user ) ) {

			$allcaps += $user_caps; //array_combine(  , array_fill( 0, count( $user_caps ), true ) );

		}

		return $allcaps;
	}


	/**
	 *	@filter wpaa_available_access_areas_post
	 *	@filter wpaa_available_access_areas_user
	 */
	public function add_global_access( $access_areas ) {

		$return = $access_areas;
		$model = Model\ModelAccessAreas::instance();
		$global = $model->fetch_by('blog_id','0');
		foreach ( array_keys($global) as $idx ) {
			$global[$idx]->id = $global[$idx]->capability;
		}
		$local_key = __( 'Local Access Areas', 'wp_access_areas');

		if ( count( $global ) ) {
			if ( ! isset( $access_areas[ $local_key ] ) ) {
				$return = array();
				$return[ $local_key ] = $access_areas;
			}
			$return[ __( 'Network Access Areas', 'wp_access_areas') ] = $global;
		}

		return $return;
	}

	public function get_global_grant() {
		$model = Model\ModelAccessAreas::instance();

		return $model->fetch_by( 'blog_id', 0 );
	}
	public function add_global_grant( $a ) {
		vaR_dump($a);
		return $a + $this->get_global_grant();
	}

	/**
	 *	@filter wpaa_assignable_access_areas_user
	 */
	public function merge_global_access_areas( $access_areas, $user ) {
		$model = Model\ModelAccessAreas::instance();
		$model->fetch_by( 'blog_id', 0 );
		return array_merge( $access_areas, $model->fetch_by( 'blog_id', 0 ) );
	}

	/**
	 *	@return string
	 */
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

		if ( is_object( $access_area ) && intval( $access_area->blog_id ) === 0 ) {

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
		 global $wpdb;
		 $wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key = 'wpaa_global_access_areas';");
	}

	public function maybe_upgrade( $site_id ) {
		error_log("maybe upgrade $site_id");
		switch_to_blog( $site_id );
		Core\Core::instance()->activate();
		Core\Core::instance()->maybe_upgrade();
		restore_current_blog();
	}

	/**
	 *	erase wpaa data on blog
	 *	@action wpmu_upgrade_site
	 */
	public function maybe_uninstall( $blog_id ) {
		if ( get_site_option( 'wpaa_uninstall_active' ) ) {
			// uninstall posts, settings
			switch_to_blog( $blog_id );

			//Settings\SettingsAccessAreas::instance()->uninstall();
			Model\ModelUser::instance()->uninstall(); // remove all caps
			Model\ModelPost::instance()->uninstall(); // remove post cols
			Model\ModelAccessAreas::instance()->uninstall(); // remove main table
			Settings\SettingsAccessAreas::instance()->uninstall(); // remove settings
			restore_current_blog();
		}
	}

	/**
 	 *	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
		AdminUsers::instance()->upgrade( $new_version, $old_version );

	}

}
