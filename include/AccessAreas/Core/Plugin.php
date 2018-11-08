<?php

namespace AccessAreas\Core;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use AccessAreas\PostType;
use AccessAreas\Compat;

class Plugin extends Singleton {

	/**
	 *	@var array plugin component classes
	 */
	private static $components = array(
		'AccessAreas\Admin\AdminUsers',
		'AccessAreas\Model\ModelAccessAreas',
		'AccessAreas\Model\ModelPost',
		'AccessAreas\Settings\SettingsAccessAreas',
	);

	/**
	 *	@var array plugin metadata
	 */
	private $meta = null;

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		register_activation_hook( ACCESS_AREAS_FILE, array( __CLASS__ , 'activate' ) );
		register_deactivation_hook( ACCESS_AREAS_FILE, array( __CLASS__ , 'deactivate' ) );
		register_uninstall_hook( ACCESS_AREAS_FILE, array( __CLASS__ , 'uninstall' ) );

		add_action( 'admin_init', array( $this, 'maybe_upgrade' ) );

		parent::__construct();
	}

	/**
	 *	@action plugins_loaded
	 */
	public function maybe_upgrade() {
		// trigger upgrade

		$new_version = $this->get_version();
		$old_version = get_option( 'access_areas_version' );

		// call upgrade
		if ( version_compare( $new_version, $old_version, '>' ) ) {

			$this->upgrade( $new_version, $old_version );

			update_option( 'access_areas_version', $new_version );

		}

	}

	private function get_version() {
		if ( is_null( $this->meta ) ) {
			$this->meta = get_plugin_data( ACCESS_AREAS_FILE );
		}
		return $this->meta['Version'];
	}

	/**
	 *	Fired on plugin activation
	 */
	public static function activate() {

		$new_version = self::instance()->get_version();

		update_site_option( '_version', $new_version );

		foreach ( self::$components as $component ) {
			$comp = $component::instance();
			$comp->activate();
		}


	}


	/**
	 *	Fired on plugin updgrade
	 *
	 *	@param string $nev_version
	 *	@param string $old_version
	 *	@return array(
	 *		'success' => bool,
	 *		'messages' => array,
	 * )
	 */
	public function upgrade( $new_version, $old_version ) {

		$result = array(
			'success'	=> true,
			'messages'	=> array(),
		);

		foreach ( self::$components as $component ) {
			$comp = $component::instance();
			$upgrade_result = $comp->upgrade( $new_version, $old_version );
			$result['success'] 		&= $upgrade_result['success'];
			$result['messages'][]	=  $upgrade_result['message'];
		}

		return $result;
	}

	/**
	 *	Fired on plugin deactivation
	 */
	public static function deactivate() {
		foreach ( self::$components as $component ) {
			$comp = $component::instance();
			$comp->deactivate();
		}
	}

	/**
	 *	Fired on plugin deinstallation
	 */
	public static function uninstall() {
		foreach ( self::$components as $component ) {
			$comp = $component::instance();
			$comp->uninstall();
		}
	}

}
