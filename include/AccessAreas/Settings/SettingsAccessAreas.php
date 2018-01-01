<?php

namespace AccessAreas\Settings;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use AccessAreas\Core;

class SettingsAccessAreas extends Settings {

	private $optionset = '';


	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		add_option( 'wpaa_default_behavior_login_redirect' , 0 );
		add_option( 'wpaa_default_behavior' , '404' );
		add_option( 'wpaa_default_behavior_status' , '404' );
/*
402 Payment Required
403 Forbidden
410 Gone
418 I'm a teapot
451 Unavailable For Legal Reasons
*/
		add_option( 'wpaa_fallback_page' , 0 );

		add_option( 'wpaa_default_caps' , array( ) );

		add_option( 'wpaa_default_post_status' , 'publish' );

		add_option( 'wpaa_enable_assign_cap' , 0 );

//		add_option( 'access_areas_setting_1' , 'Default Value' , '' , False );

		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

		parent::__construct();

	}

	/**
	 *	Add Settings page
	 *
	 *	@action admin_menu
	 */
	public function admin_menu() {
		add_options_page( __('WP Access Areas Settings' , 'wp-access-areas' ),__('WP Access Areas' , 'wp-access-areas'),'manage_options',$this->optionset, array( $this, 'settings_page' ) );
	}

	/**
	 *	Render Settings page
	 */
	public function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		?>
		<div class="wrap">
			<h2><?php _e('WP Access Areas Settings', 'wp-access-areas') ?></h2>

			<form action="options.php" method="post">
				<?php
				settings_fields(  $this->optionset );
				do_settings_sections( $this->optionset );
				submit_button( __('Save Settings' , 'wp-access-areas' ) );
				?>
			</form>
		</div><?php
	}


	/**
	 * Enqueue settings Assets
	 *
	 *	@action load-options-{$this->optionset}.php

	 */
	public function enqueue_assets() {

	}


	/**
	 *	Setup options.
	 *
	 *	@action admin_init
	 */
	public function register_settings() {

		$settings_section	= 'access_areas_settings';

		add_settings_section( $settings_section, __( 'Restricted Access Behavior',  'wp-access-areas' ), array( $this, 'post_access_section_intro' ), $this->optionset );


		$option_name		= 'wpaa_default_behavior_login_redirect';
		register_setting( $this->optionset , $option_name, array( $this , 'sanitize_http_status' ) );
		add_settings_field(
			$option_name,
			__( 'Redirect to Login',  'wp-access-areas' ),
			array( $this, 'select_login_redirect' ),
			$this->optionset,
			$settings_section,
			array(
				'option_name'			=> $option_name,
//				'label_for'		=> '',
//				'class'			=> 'wpaa-http-status-select',
			)
		);

		$option_name		= 'wpaa_default_behavior';
		register_setting( $this->optionset , $option_name, array( $this , 'sanitize_behavior' ) );
		add_settings_field(
			$option_name,
			__( 'Default Behaviour',  'wp-access-areas' ),
			array( $this, 'select_behavior' ),
			$this->optionset,
			$settings_section,
			array(
				'option_name'			=> $option_name,
//				'label_for'		=> '',
//				'class'			=> '',
			)
		);

		$option_name		= 'wpaa_default_behavior_status';
		register_setting( $this->optionset , $option_name, array( $this , 'sanitize_http_status' ) );
		add_settings_field(
			$option_name,
			__( 'HTTP Status',  'wp-access-areas' ),
			array( $this, 'select_http_status' ),
			$this->optionset,
			$settings_section,
			array(
				'option_name'			=> $option_name,
//				'label_for'		=> '',
				'class'			=> 'wpaa-http-status-select',
			)
		);


		$option_name		= 'wpaa_fallback_page';
		register_setting( $this->optionset , $option_name, array( $this , 'sanitize_fallback_page' ) );
		add_settings_field(
			$option_name,
			__( 'Fallback Page',  'wp-access-areas' ),
			array( $this, 'select_fallback_page' ),
			$this->optionset,
			$settings_section,
			array(
				'option_name'			=> $option_name,
//				'label_for'		=> '',
//				'class'			=> '',
			)
		);

	}

	/**
	 *	Print some documentation for the optionset
	 */
	public function post_access_section_intro( $args ) {
		?>
		<div class="inside">
			<p class="description"><?php
			_e('What will happen if somebody tries to view a restricted post directly.' , 'wp-access-areas' );
			?><br /><?php
			_e('You can also set these Options for each post individually.' , 'wp-access-areas' );

			?></p>
		</div>
		<?php
	}

	/**
	 *	...
	 */
	public function select_login_redirect( $args ) {

		$template = Core\Template::instance();
		echo $template->select_login_redirect( get_option( $args['option_name'] ), $args['option_name'] );

	}

	/**
	 *	...
	 */
	public function select_behavior( $args ) {

		$template = Core\Template::instance();
		echo $template->select_behavior( get_option( $args['option_name'] ), $args['option_name'] );

	}

	/**
	 *	...
	 */
	public function select_http_status( $args ) {

		$template = Core\Template::instance();
		echo $template->select_http_status( get_option( $args['option_name'] ), $args['option_name'] );

	}


	/**
	 *	...
	 */
	public function select_fallback_page( $args ) {
		$template = Core\Template::instance();
		echo $template->select_fallback_page( get_option( $args['option_name'] ), $args['option_name'] );


	}


	/**
	 * Sanitize value of setting_1
	 *
	 * @return string sanitized value
	 */
	public function sanitize_setting_1( $value ) {
		// do sanitation here!
		return $value;
	}



	/**
	 *	@inheritdoc
	 */
	public function activate() {

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

		// post behavior
		if ( get_option( 'wpaa_default_behavior' === 'login' ) ) {
			update_option( 'wpaa_default_behavior_login_redirect', true );
			update_option( 'wpaa_default_behavior', 'page' );
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
		// delete options!
	}


}
