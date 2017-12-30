<?php

namespace AccessAreas\Core;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


class Core extends Plugin {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		add_action( 'plugins_loaded' , array( $this , 'load_textdomain' ) );
		add_action( 'plugins_loaded' , array( $this , 'init_compat' ), 0 );
		add_action( 'init' , array( $this , 'init' ) );
		add_action( 'wp_enqueue_scripts' , array( $this , 'wp_enqueue_style' ) );
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}


		parent::__construct();
	}

	/**
	 *	Load frontend styles and scripts
	 *
	 *	@action wp_enqueue_scripts
	 */
	public function wp_enqueue_style() {
	}


	/**
	 *	Load Compatibility classes
	 *
	 *  @action plugins_loaded
	 */
	public function init_compat() {
		if ( is_multisite() && is_plugin_active_for_network( ACCESS_AREAS_PLUGIN ) ) {
			Compat\WPMU\WPMU::instance();
		}
	}


	/**
	 *	Load text domain
	 *
	 *  @action plugins_loaded
	 */
	public function load_textdomain() {
		$path = pathinfo( dirname( ACCESS_AREAS_FILE ), PATHINFO_FILENAME );
		load_plugin_textdomain( 'wp-access-areas' , false, $path . '/languages' );
	}

	/**
	 *	Init hook.
	 *
	 *  @action init
	 */
	public function init() {
	}

	/**
	 *	Get asset url for this plugin
	 *
	 *	@param	string	$asset	URL part relative to plugin class
	 *	@return wp_enqueue_editor
	 */
	public function get_asset_url( $asset ) {
		return plugins_url( $asset, ACCESS_AREAS_FILE );
	}



}
