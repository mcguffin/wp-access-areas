<?php

namespace AccessAreas\Settings;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use AccessAreas\Core;
use AccessAreas\Model;

class SettingsAccessAreas extends Settings {

	private $optionset = 'access-areas';


	/**
	 *	@inheritdoc
	 */
	protected function __construct() {


//		add_option( 'access_areas_setting_1' , 'Default Value' , '' , False );

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		add_action( 'admin_init', array( $this, 'register_assets' ) );

		add_action( "load-settings_page_{$this->optionset}", array( $this, 'enqueue_assets' ) );

		parent::__construct();

	}

	/**
	 *	Add Settings page
	 *
	 *	@action admin_menu
	 */
	public function admin_menu() {
		add_options_page( __('WP Access Areas Settings' , 'wp-access-areas' ),__('WP Access Areas' , 'wp-access-areas'),'manage_options', $this->optionset, array( $this, 'settings_page' ) );
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
	public function register_assets() {
		$core = Core\Core::instance();
		wp_register_style( 'access-areas-settings', $core->get_asset_url( '/css/admin/settings.css' ) );

	}

	/**
	 * Enqueue settings Assets
	 *
	 *	@action load-settings_page_access-areas
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'access-areas-settings' );
	}

	/**
	 *	Setup options.
	 *
	 *	@action admin_init
	 */
	public function register_settings() {

		$this->register_behaviour_settings();

		$this->register_post_type_settings();

		$this->register_role_settings();

	}

	/**
	 *	@access private
	 */
	private function register_behaviour_settings() {
		$settings_section	= 'access_areas_settings';

		add_settings_section( $settings_section, __( 'Restricted Access Behavior',  'wp-access-areas' ), array( $this, 'behaviour_section_intro' ), $this->optionset );


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
	 *	@access private
	 */
	private function register_post_type_settings() {

		$settings_section	= 'wpaa_post_access_section';
		add_settings_section( $settings_section, __( 'Post Types', 'wp-access-areas' ), '__return_null', $this->optionset );

		$option_name		= 'wpaa_post_types'; //
		register_setting( $this->optionset, $option_name, array( $this , 'sanitize_post_type_settings' ) );

		add_settings_field(
			$option_name,
			__( 'Post Type Settings',  'wp-access-areas' ),
			array( $this, 'post_type_settings' ),
			$this->optionset,
			$settings_section,
			array(
				'option_name'			=> $option_name,
//				'label_for'		=> '',
				'class'			=> 'wpaa-post-type-settings',
			)
		);





	}

	/**
	 *	@access private
	 */
	private function register_role_settings() {
	}

	/**
	 *	Print some documentation for the optionset
	 */
	public function behaviour_section_intro( $args ) {
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
	 *	Print some documentation for the optionset
	 */
	public function post_type_section_intro( $args ) {
		?>
		<div class="inside">
			<p class="description"><?php

				_e('Default settings for newly created posts.' , 'wp-access-areas' );

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
		$option_name = 'wpaa_default_behavior';
		$option_value = get_option( $option_name );

		$template = Core\Template::instance();
		echo $template->select_behavior( $option_value, $option_name );

	}

	/**
	 *	...
	 */
	public function select_http_status( $args ) {
		$option_name = 'wpaa_default_behavior_status';
		$option_value = get_option( $option_name );

		/*
		402 Payment Required
		403 Forbidden
		410 Gone
		418 I'm a teapot
		451 Unavailable For Legal Reasons
		*/

		$template = Core\Template::instance();
		echo $template->select_http_status( $option_value, $option_name );

	}


	/**
	 *	...
	 */
	public function select_fallback_page( $args ) {
		$option_name = 'wpaa_fallback_page';
		$option_value = get_option( $option_name );
		$template = Core\Template::instance();
		echo $template->select_fallback_page(  $option_value, $option_name );
	}



	public function post_type_settings( $args ) {
		global $wp_roles;

		$template = Core\Template::instance();
		$model = Model\ModelAccessAreas::instance();



		// aa fitting to current blog
			// maybe added global here...

		$option_value = get_option( $args['option_name'] );
		$roles = wp_roles()->get_names();

		$post_types = get_post_types(array(
			'show_ui' => true,
		));


		$roles_key =__( 'WordPress Roles', 'wp_access_areas');
		$wpaa_key = __( 'Local', 'wp_access_areas');

		$access_areas_view = array();
		$access_areas_view[ 'exist' ] = __( 'WordPress default', 'wp-access-areas' );
		$access_areas_view[ 'read' ] = __( 'Logged in Users', 'wp-access-areas' );
		$access_areas_view[ $roles_key ] = $roles;
		$access_areas_view[ $wpaa_key ] = $model->fetch_available();
		$access_areas_view = apply_filters( "wpaa_access_areas_dropdown_post", $access_areas_view );

		$access_areas_edit = array();
		$access_areas_edit[ 'exist' ] = __( 'WordPress default', 'wp-access-areas' );
		$access_areas_edit[ $roles_key ] = array();
		$access_areas_edit[ $wpaa_key ] = $model->fetch_available();
		$access_areas_edit = apply_filters( "wpaa_access_areas_dropdown_post", $access_areas_edit );

		?>
		<table class="widefat striped">
			<thead>
				<th class="check-column"></th>
				<th><?php _e( 'Post Type', 'wp-access-areas' ); ?></th>
				<th><?php _e( 'Reading', 'wp-access-areas' ); ?></th>
				<th><?php _e( 'Editing', 'wp-access-areas' ); ?></th>
				<th><?php _e( 'Post Comment', 'wp-access-areas' ); ?></th>
				<th><?php _e( 'Default Post status', 'wp-access-areas' ); ?></th>
			</thead>
			<tbody>
				<?php foreach ( $post_types as $post_type ) { ?>
					<?php

					if ( ! isset( $option_value[ $post_type ] ) ) {
						$option_value[ $post_type ] = array();
					}

					// sanitize pt settings
					$pt_settings = wp_parse_args( $option_value[ $post_type ], array(
						'enabled'			=> 1,
						'post_view_cap'		=> 'exist',
						'post_edit_cap'		=> 'exist',
						'post_comment_cap'	=> 'exist',
						'default_status'	=> 'private',
					) );

					$post_type_object = get_post_type_object( $post_type );

					// gather roles that may edit this post type
					$editing_cap = $post_type_object->cap->edit_posts;
					$edit_rolenames = array();
					foreach ( $roles as $role => $rolename ) {
						if ( get_role( $role )->has_cap( $editing_cap ) ) {
							$edit_rolenames[$role] = $rolename;
						}
					}
					$name_prefix = sprintf('%s[%s]', $args['option_name'], $post_type );
					?>
					<tr>
						<th class="check-column"><?php
							// enable checkbox
							$name = sprintf('%s[enabled]', $name_prefix );
							$id = sprintf('%s-%s-enabled', $args['option_name'], $post_type );
							printf( '<input type="hidden" name="%s" value="0" />', $name );
							printf(
								'<input type="checkbox" name="%s" id="%s" value="1" %s />',
								$name, $id, checked( intval($pt_settings['enabled']), 1, false )
							);
						?></th>
						<th>
							<?php printf('<label for="%s">%s</label>', $id, $post_type_object->labels->name ); ?>
						</th>
						<td><?php
							// reading access
							$name = sprintf('%s[post_view_cap]', $name_prefix );
							if ( $post_type !== 'attachment' && ( $post_type_object->public || $post_type_object->show_ui ) ) {
								echo $template->access_areas_dropdown( $access_areas_view, 'post', array(
									'name'	=> $name,
								));
							}
						?></td>
						<td><?php
							// writing access
							$name = sprintf('%s[post_edit_cap]', $name_prefix );
							$access_areas_edit[ $roles_key ] = array();

							foreach ( $roles as $role_slug => $role_name ) {
								if ( get_role( $role_slug )->has_cap( $post_type_object->cap->edit_posts ) ) {
									$access_areas_edit[ $roles_key ][ $role_slug ] = $role_name;
								}
							}

							echo $template->access_areas_dropdown( $access_areas_edit, 'post', array(
								'name'	=> $name,
							));

						?></td>
						<td><?php
							// comment access
							$name = sprintf('%s[post_comment_cap]', $name_prefix );
							echo $template->access_areas_dropdown( $access_areas_view, 'post', array(
								'name'	=> $name,
							));
						?></td>
						<td><?php
							// Default Post Status
							$name = sprintf('%s[default_status]', $name_prefix );
							// select status
						?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		<?php
	}




	public function sanitize_post_type_settings($settings) {
		return $settings;
	}

	/**
	 *	@inheritdoc
	 */
	public function activate() {

		add_option( 'wpaa_default_behavior_login_redirect' , 0 );

		add_option( 'wpaa_default_behavior' , '404' );

		add_option( 'wpaa_default_behavior_status' , '404' );

		add_option( 'wpaa_fallback_page' , 0 );


		add_option( 'wpaa_default_caps' , array( ) );

		add_option( 'wpaa_default_post_status' , 'publish' );

		add_option( 'wpaa_enable_assign_cap' , 0 );

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

		// post type settings
		if ( ! get_option( 'wpaa_post_types' ) ) {
			$default_post_status = get_option('wpaa_default_post_status');
			$post_type_settings = get_option( 'wpaa_dafault_caps' );
			foreach ( $post_type_settings as $post_type => $pt_options ) {
				$post_type_settings[$post_type]['enabled'] = true;
				$post_type_settings[$post_type]['default_status'] = $default_post_status;
			}
			upate_option( 'wpaa_post_types', $post_type_settings );
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
		// delete all options
		global $wpdb;
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'wpaa_%'" );
	}


}
