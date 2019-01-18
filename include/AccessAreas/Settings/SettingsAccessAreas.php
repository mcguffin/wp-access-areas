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

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		add_action( "load-settings_page_{$this->optionset}", array( $this, 'enqueue_assets' ) );

		add_action( "load-settings_page_{$this->optionset}", array( $this, 'add_help_tabs' ) );

		add_filter( 'pre_update_option_wpaa_enable_assign_cap', array( $this, 'process_role_cap' ), 10, 2 );
		add_action( 'update_option_wpaa_enable_assign_cap', array( $this, 'role_caps_changed' ), 10, 2 );

		parent::__construct();

	}
	/**
	 *	@filter pre_update_option_wpaa_enable_assign_cap
	 */
	public function process_role_cap( $value ) {

		if ( is_array( $_POST['grant_cap'] ) ) {
			foreach ( $_POST['grant_cap'] as $role_slug => $cap ) {
				$allow = current_user_can( 'wpaa_edit_role_caps', $role_slug )
					&& current_user_can( $cap );
				if ( $allow && ( $role = get_role( $role_slug ) ) ) {
					$role->add_cap( $cap, true );
				}
			}
		}
		if ( is_array( $_POST['revoke_cap'] ) ) {
			foreach ( $_POST['revoke_cap'] as $role_slug => $cap ) {
				$allow = current_user_can( 'wpaa_edit_role_caps', $role_slug )
					&& current_user_can( $cap );
				if ( $allow && ( $role = get_role( $role_slug ) ) ) {
					$role->remove_cap( $cap, true );
				}
			}
		}
		return $value;
	}

	/**
	 *	@action update_option_wpaa_enable_assign_cap
	 */
	public function role_caps_changed( $old_value, $value ) {
/*
What do we want?
- single: out of the box editing -> just add the 4 caps √
- network: out of the box √
  + option for network admin
  	grant/revoke wpaa_edit_role_caps
	grant/revoke wpaa_set_*_cap √

*/
		if ( $value && ! $old_value ) { // was enabled
			$admin_role = get_role( 'administrator' );
			if ( $admin_role && ! $admin_role->has_cap('wpaa_edit_role_caps') ) {

				// add default role caps
				$admin_role->add_cap('wpaa_set_view_cap');
				$admin_role->add_cap('wpaa_set_edit_cap');
				$admin_role->add_cap('wpaa_set_comment_cap');
				$admin_role->add_cap('wpaa_edit_role_caps');
				$admin_role->add_cap('edit_grant_access');
				$admin_role->add_cap('edit_revoke_access');

			}

		}
	}

	/**
	 *	@action "load-settings_page_{$this->optionset}"
	 */
	public function add_help_tabs() {
		get_current_screen()->add_help_tab( array(
		'id'		=> 'post-types',
		'title'		=> __( 'Post Types', 'wp-access-areas' ),
		'content'	=>
			'<p>' . __('Set up default access control for each post type.', 'wp-access-areas') . '</p>' .
			'<ul>' .
				'<li>' .
					'<strong>' . __('Allow Override','wp-access-areas') . '</strong>' . ' ' .
					__('Enable access control metabox on the post edit screen.', 'wp-access-areas' ) .
				'</li>' .
				'<li>' .
					'<strong>' . __('Reading','wp-access-areas') . '</strong>' . ' ' .
					__('Which WP role or access area may read this post type.', 'wp-access-areas' ) .
					__('Please note: protecting Attachments will only prevent access to the attachments page. It will not prevent the attached file from being downloaded.', 'wp-access-areas' ) .
				'</li>' .
				'<li>' .
					'<strong>' . __('Editing','wp-access-areas') . '</strong>' . ' ' .
					__('Which WP role or access area may edit this post type.', 'wp-access-areas' ) .
				'</li>' .
				'<li>' .
					'<strong>' . __('Posting Comments','wp-access-areas') . '</strong>' . ' ' .
					__('Which WP role or access area may post comments on this post type.', 'wp-access-areas' ) .
				'</li>' .
				'<li>' .
					'<strong>' . __('Default Post status','wp-access-areas') . '</strong>' . ' ' .
					__('Assign this post status when the reading Access area or role is deleted.', 'wp-access-areas' ) .
				'</li>' .
			'</ul>'
		) );

		get_current_screen()->add_help_tab( array(
		'id'		=> 'screen-content',
		'title'		=> __('Screen Content'),
		'content'	=>
			'<p>' . __('You can customize the display of this screen&#8217;s contents in a number of ways:') . '</p>' .
			'<ul>' .
				'<li>' . __('You can hide/display columns based on your needs and decide how many posts to list per screen using the Screen Options tab.') . '</li>' .
				'<li>' . __( 'You can filter the list of posts by post status using the text links above the posts list to only show posts with that status. The default view is to show all posts.' ) . '</li>' .
				'<li>' . __('You can view posts in a simple title list or with an excerpt using the Screen Options tab.') . '</li>' .
				'<li>' . __('You can refine the list to show only posts in a specific category or from a specific month by using the dropdown menus above the posts list. Click the Filter button after making your selection. You also can refine the list by clicking on the post author, category or tag in the posts list.') . '</li>' .
			'</ul>'
		) );
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
		</div>
		<?php
	}


	/**
	 *	Enqueue settings Assets
	 *
 	 *	@action "load-settings_page_{$this->optionset}"
 	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'access-areas-settings' );
		wp_enqueue_script( 'access-areas-settings' );
	}

	/**
	 *	Setup options.
	 *
	 *	@action admin_init
	 */
	public function register_settings() {

		$this->register_post_type_settings();

		if ( current_user_can('wpaa_edit_role_caps') ) {
			$this->register_role_settings();
		}

	}

	/**
	 *	@access private
	 */
	private function register_post_type_settings() {
		$sanitize = Core\Sanitize::instance();
		$settings_section	= 'wpaa_post_access_section';
		add_settings_section( $settings_section, __( 'Post Types', 'wp-access-areas' ), array( $this, 'post_type_section_intro' ), $this->optionset );


		register_setting( $this->optionset, 'wpaa_default_access_override', 'absint' ); //*

		register_setting( $this->optionset, 'wpaa_default_post_view_cap', array( $sanitize, 'capability' ) );
		register_setting( $this->optionset, 'wpaa_default_post_edit_cap', array( $sanitize, 'capability' ) );
		register_setting( $this->optionset, 'wpaa_default_post_comment_cap', array( $sanitize, 'capability' ) );

		register_setting( $this->optionset, 'wpaa_default_behavior_override', 'absint'); //*
		register_setting( $this->optionset, 'wpaa_default_behavior', array( $sanitize, 'behavior' )); //*
		register_setting( $this->optionset, 'wpaa_default_login_redirect', 'absint');
		register_setting( $this->optionset, 'wpaa_default_http_status', array( $sanitize, 'http_status' ));
		register_setting( $this->optionset, 'wpaa_default_fallback_page', 'absint');
		register_setting( $this->optionset, 'wpaa_default_default_post_status', array( $sanitize, 'post_status' ) );


		register_setting( $this->optionset, 'wpaa_post_types', array( $this , 'sanitize_post_type_settings' ) );

	}

	/**
	 *	@access private
	 */
	private function register_role_settings() {
		$settings_section	= 'wpaa_roles_section';
		add_settings_section( $settings_section, __( 'Role Capabilities', 'wp-access-areas' ), array( $this, 'role_section_intro' ), $this->optionset );

		$option_name = 'wpaa_enable_assign_cap';
		register_setting( $this->optionset, 'wpaa_enable_assign_cap', 'absint' );

	}


	/**
	 *	Print some documentation for the optionset
	 */
	public function post_type_section_intro( $args ) {
		?>
		<div class="inside">
			<p class="description"><?php

				_e( 'Default settings for post types.' , 'wp-access-areas' );

			?></p>
		</div>
		<?php

		$this->post_type_settings();
	}


	/**
	 *	Print some documentation for the optionset
	 */
	public function role_section_intro( $args ) {
		?>
		<div class="inside">
			<p class="description"><?php
				if ( absint( get_option('wpaa_enable_assign_cap') ) ) {
					_e('If you are running a role editor plugin such as <a href="https://wordpress.org/plugins/user-role-editor/">User Role editor by Vladimir Garagulya</a> or <a href="https://wordpress.org/plugins/wpfront-user-role-editor/">WPFront User Role Editor by Syam Mohan</a> you can do the same as here by assigning the custom capabilites <code>wpaa_set_view_cap</code>, <code>wpaa_set_edit_cap</code> and <code>wpaa_set_comment_cap</code>.','wp-access-areas');
					?>
					</p>
					<p class="description"><?php

					_e('By disabling the role capabilities feature you will allow everybody who can at least publish a post to edit the access properties as well.','wp-access-areas');

				} else {
					_e('By default everybody who can publish an entry can also edit the access properties such as ‘Who can view’ or ‘Who can edit’.<br /> If this is too generous for you then click on the button below.','wp-access-areas');
				}

			?></p>
		</div>
		<?php

		$this->role_settings();
	}


	/**
	 *	settings field callback
	 */
	public function post_type_settings() {
		global $wp_roles;

		$template = Core\Template::instance();

		$option_name = 'wpaa_post_types';
		$option_value = get_option( $option_name );

		$post_types = get_post_types(array(
			'show_ui' => true,
		),'objects');

		$post_types = array( 0 => get_post_type_object('post') ) + $post_types;


		//
		$post_status_labels = array();

		$post_stati = get_post_stati( array(
			'internal'	=> false,
		), false );

		foreach ( $post_stati as $status => $post_status ) {
			$post_status_labels[ $status ] = $post_status->label;
		}

		$pt_options = $this->get_default_pt_options();

		?>
		<div class="tab-group wp-clearfix">
			<div class="tab-links">
				<ul>
					<?php foreach ( $post_types as $post_type => $post_type_object ) { ?>
						<?php
							if ( $post_type === 0) {
								printf( '<li id="tab-link-wpaa-general" class="active">%s</li>',
									sprintf( '<a href="#tab-panel-general" aria-controls="tab-panel-general">%1$s</a>', __('General','wp-access-areas') )
								);
							} else {
								$id = sprintf( 'tab-panel-%s', $post_type );
								printf( '<li id="tab-link-wpaa-%s">%s</li>',
									$post_type,
									sprintf( '<a href="#%1$s" aria-controls="%1$s">%2$s</a>', $id, $post_type_object->labels->name )
								);
							}
						?>
					<?php } ?>
				</ul>
			</div>

			<div class="tab-panels">
				<?php foreach ( $post_types as $post_type => $post_type_object ) { ?>
					<?php

						if ( $post_type === 0 ) { // general settings
							$name_template = 'wpaa_default_%s';
							$id_template = 'wpaa-default-%s';
							$tab_name = __( 'General','wp-access-areas' );
							$tab_singular_name = __( 'Post', 'wp-access-areas' );

							$pt_settings = array();
							foreach ( $this->get_default_pt_option() as $key => $default ) {
								$val = get_option( 'wpaa_default_' . $key );
								if ( $val !== false ) {
									$pt_settings[$key] = $val;
								} else {
									$pt_settings[$key] = $default;
								}
							}
						} else {  // specific post type settings
							$name_template = sprintf('%s[%s][%%s]', $option_name, $post_type );
							$id_template = sprintf('%s-%s-%%s', $option_name, $post_type );

							$tab_name = $post_type_object->labels->name;
							$tab_singular_name = $post_type_object->labels->singular_name;

							if ( isset( $option_value[ $post_type ] ) ) {
								$option_val = $option_value[ $post_type ];
							} else {
								$option_val = array();
							}
							// sanitize pt settings
							$pt_settings = wp_parse_args( $option_val, $pt_options[ $post_type ] );
						}

					?>
					<div id="<?php printf( 'tab-panel-%s', $post_type === 0 ? 'general' : $post_type ); ?>" class="tab-panel-content <?php echo ( $post_type === 0 ) ? 'active' : ''; ?>">
						<h3><?php echo $tab_name; ?></h3>
						<?php /* Access Settings */ ?>
						<div class="wpaa-settings-section wpaa-settings-access">

							<h4><?php _e('Access Control','wp-access-areas') ?></h4>
							<div class="wpaa-control">
								<?php

									// add enable access control for post type
									$name = sprintf( $name_template, 'access_override' );
									$id = sprintf( $id_template, 'access-override' );

									printf( '<input type="hidden" name="%s" value="0" />', $name );
									printf(
										'<input type="checkbox" name="%s" id="%s" value="1" %s />',
										$name,
										$id,
										checked( intval( $pt_settings[ 'access_override' ] ), 1, false )
									);
									printf( '<label for="%s">%s</label>', $id, __( 'Allow Access Control Override', 'wp-access-areas' ) );

								?>
								<p class="description">
									<?php printf(
											_x('If checked you will be able to edit these settings for each %s individually.', 'post-type', 'wp-access-areas'),
											$tab_singular_name
									); ?>
								</p>

							</div>

							<?php

								echo $template->access_controls( $post_type_object, null, $pt_settings, array(
									'name_template'	=> $name_template,
									'id_template'	=> $id_template,
								) );

							?>
						</div>

						<?php if ( $post_type_object->public ) {
							?>
							<div class="wpaa-settings-section wpaa-settings-behavior">

								<h4><?php _e('Behavior','wp-access-areas') ?></h4>

								<div class="wpaa-control">
									<?php

										// add enable access control for post type
										$name = sprintf( $name_template, 'behavior_override' );
										$id = sprintf( $id_template, 'behavior-override' );

										printf( '<input type="hidden" name="%s" value="0" />', $name );
										printf(
											'<input type="checkbox" name="%s" id="%s" value="1" %s />',
											$name,
											$id,
											checked( intval( $pt_settings[ 'behavior_override' ] ), 1, false )
										);
										printf( '<label for="%s">%s</label>', $id, __( 'Allow Behavior Override', 'wp-access-areas' ) );

									?>
									<p class="description">
										<?php printf(
												_x('If checked you will be able to edit these settings for each %s individually.', 'post-type', 'wp-access-areas'),
												$tab_singular_name
										); ?>
									</p>

								</div>

								<?php

									echo $template->behavior_controls( $pt_settings, array(
										'name_template'	=> $name_template,
										'id_template'	=> $id_template,
									) );

								?>

							</div>
							<?php
						} ?>

						<div class="wpaa-settings-section wpaa-settings-advanced">
							<?php
							$name = sprintf( $name_template, 'default_post_status' );
							$id = sprintf( $id_template, 'default-post-status' );

							?>
							<h4><?php _e('Advanced','wp-access-areas') ?></h4>
							<div class="wpaa-control">
								<?php

									printf( '<label for="%s">%s</label>', $id, __( 'Fallback Post status', 'wp-access-areas' ) );

									echo $template->dropdown( $post_status_labels, $pt_settings['default_post_status'], array(
										'name'	=> $name,
										'id'	=> $id,
									));

								?>
								<p class="description">
									<?php _e('Assign this post status when the reading access area or role is deleted.', 'wp-access-areas' ); ?>
								</p>
							</div>
						</div>

					</div>
				<?php } ?>
			</div>
		</div>
		<?php
	}

	/**
	 *	@param array $args
	 */
	public function role_settings( ) {

		$option_name = 'wpaa_enable_assign_cap';
		$option_value = absint( get_option( $option_name ) );

		printf( '<input type="hidden" name="%s" value="%s" />', $option_name, $option_value );

		if ( ! $option_value ) {
			// print convert button, return
			?>
			<button name="wpaa_enable_assign_cap" value="1" type="submit" class="button-secondary">
				<?php _e('Enable Role Capabilities' , 'wp-access-areas'); ?>
			</button>
			<?php
			return;
		}
		?>
		<button name="wpaa_enable_assign_cap" value="0" type="submit" class="button-secondary">
			<?php _e('Disable Role Capabilities' , 'wp-access-areas'); ?>
		</button>
		<?php

		$roles = get_editable_roles();
		$caps = array(
			'wpaa_set_view_cap'		=> __( 'Change View Access' , 'wp-access-areas'),
			'wpaa_set_edit_cap'		=> __( 'Change Edit Access' , 'wp-access-areas'),
			'wpaa_set_comment_cap'	=> __( 'Change Comment Access' , 'wp-access-areas'),
		);

		$ad = get_role('administrator');

		foreach ( array_keys($caps) as $cap ) {
			if ( ! current_user_can( $cap ) ) {
				unset($caps[$cap]);
			}
		}
		$caps = apply_filters( 'wpaa_settings_editable_role_caps', $caps );
		?>
		<table class="widefat striped wpaa-role-caps">
			<thead>
				<th><?php _e('Role','wp-access-areas'); ?></th>
				<?php
					foreach ( $caps as $cap => $label ) {

						printf('<th data-cap="%s">%s<br /><code>%s</code></th>', esc_attr($cap), $label, $cap );

					}
				?>
			</thead>
			<tbody>
				<?php
				foreach ( $roles as $role_slug => $role_details ) {
					$role = get_role( $role_slug );

					if ( ! current_user_can( 'wpaa_edit_role_caps', $role_slug ) || ! $role->has_cap( 'edit_posts' ) && ! $role->has_cap( 'edit_pages' ) ) {
						continue;
					}

					?>
					<tr>
						<th scope="row">
							<?php echo translate_user_role( $role_details['name'] ) ?>
						</th>
						<?php
						foreach ( array_keys( $caps ) as $cap ) {

							?>
							<td data-cap="<?php echo esc_attr($cap); ?>">
								<?php
								$attr = '';
								if ( $role->has_cap( $cap ) ) {
									printf('<button type="submit" name="revoke_cap[%s]" value="%s" class="button-secondary">%s</button', $role_slug, $cap, __('Forbid','wp-access-areas' ) );
								} else {
									printf('<button type="submit" name="grant_cap[%s]" value="%s" class="button-primary">%s</button', $role_slug, $cap, __('Allow','wp-access-areas' ) );
								}
								?>
							</td>
							<?php
						}
						?>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php

	}

	/**
	 *	@param array $settings
	 *	@return array
	 */
	public function sanitize_post_type_settings( $settings ) {
		$sanitize = Core\Sanitize::instance();
		// validate var var type
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		// validate assoc keys
		$settings = wp_parse_args( $settings, $this->get_default_pt_options() );
		foreach ( array_keys( $settings ) as $post_type ) {
			// remove invalid PTs
			if ( ! post_type_exists( $post_type ) ) {
				unset( $settings[ $post_type ] );
				continue;
			}

			// fill up with default values
			$settings[ $post_type ] = wp_parse_args( $settings[ $post_type ], $this->get_default_pt_option() );

			$settings[ $post_type ][ 'http_status' ] = $sanitize->http_status( $settings[ $post_type ][ 'http_status' ] );
			$settings[ $post_type ][ 'behavior' ] = $sanitize->behavior( $settings[ $post_type ][ 'behavior' ] );
			$settings[ $post_type ][ 'default_post_status' ] = $sanitize->post_status( $settings[ $post_type ][ 'default_post_status' ] );

			$settings[ $post_type ][ 'post_view_cap' ] = $sanitize->capability( $settings[ $post_type ][ 'post_view_cap' ] );
			$settings[ $post_type ][ 'post_edit_cap' ] = $sanitize->capability( $settings[ $post_type ][ 'post_edit_cap' ] );
			$settings[ $post_type ][ 'post_comment_cap' ] = $sanitize->capability( $settings[ $post_type ][ 'post_comment_cap' ] );

			// sanitize boolean
			foreach ( array( 'access_override', 'behavior_override', 'login_redirect' ) as $prop ) {
				$settings[ $post_type ][ $prop ] = absint( $settings[ $post_type ][ $prop ] );
			}

		}

		return $settings;
	}

	/**
	 *	Settings default values
	 *	@return array
	 */
	private function get_default_pt_options() {
		$default_pt_option = array();

		$post_types = get_post_types(array(
			'show_ui' => true,
		),'objects');

		foreach ( $post_types as $post_type => $post_type_object ) {
			$default_pt_option[$post_type] = $this->get_default_pt_option();
		}

		return $default_pt_option;
	}

	/**
	 *	Post type Settings default values
	 *	@return array
	 */
	private function get_default_pt_option() {
		return array(
			'access_override'		=>  1,
			'post_view_cap'			=>  'exist',
			'post_edit_cap'			=>  'exist',
			'post_comment_cap'		=>  'exist',
			'default_status'		=>  'publish',

			'behavior_override'		=>  1,
			'behavior'			=>  '404',
			'login_redirect'		=>  0,
			'http_status'			=>  '404',
			'fallback_page'			=>  0,

			'default_post_status'	=> 'private',
		);
	}

	/**
	 *	@inheritdoc
	 */
	public function activate() {

		$defaults = $this->get_default_pt_option();

		add_option( 'wpaa_enable_assign_cap', 0, null, true );

		foreach ( $defaults as $key => $default) {
			add_option( 'wpaa_default_' . $key, $default, null, true );
		}

		add_option( 'wpaa_post_types', $this->get_default_pt_options(), null, false );

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
			$post_type_settings = get_option( 'wpaa_default_caps' );

			$fallback_page = get_option( 'wpaa_fallback_page' );
			$behavior = get_option( 'wpaa_default_behavior' ); // > 404|page|login
			$login_redirect = intval( $behavior === 'login');
			$behavior = $behavior === 'login' ? '404' : $behavior;

			foreach ( $post_type_settings as $post_type => $pt_options ) {

				$post_type_settings[$post_type]['access_override']	= 1;
				$post_type_settings[$post_type]['default_status'] 	= $default_post_status;

				$post_type_settings[$post_type]['behavior_override']	= 1;
				$post_type_settings[$post_type]['behavior']				= $behavior;
				$post_type_settings[$post_type]['login_redirect']		= $login_redirect;
				$post_type_settings[$post_type]['http_status']			= '404';
				$post_type_settings[$post_type]['fallback_page']		= $fallback_page;

			}



			update_option( 'wpaa_post_types', $post_type_settings );
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
