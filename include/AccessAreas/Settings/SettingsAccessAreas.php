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

		parent::__construct();

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

	//	$this->register_behavior_settings();

		$this->register_role_settings();

	}

	/**
	 *	@access private
	 */
	private function register_behavior_settings() {
		$settings_section	= 'access_areas_settings';

		add_settings_section( $settings_section, __( 'Behavior',  'wp-access-areas' ), array( $this, 'behavior_section_intro' ), $this->optionset );


		$option_name		= 'wpaa_default_behavior';
		register_setting( $this->optionset , $option_name, array( $this , 'sanitize_behavior' ) );
		add_settings_field(
			$option_name,
			__( 'Default behavior',  'wp-access-areas' ),
			array( $this, 'select_behavior' ),
			$this->optionset,
			$settings_section,
			array(
				'option_name'			=> $option_name,
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
			)
		);

	}
	/**
	 *	@access private
	 */
	private function register_post_type_settings() {

		$settings_section	= 'wpaa_post_access_section';
		add_settings_section( $settings_section, __( 'Post Types', 'wp-access-areas' ), array( $this, 'post_type_section_intro' ), $this->optionset );

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
	public function post_type_section_intro( $args ) {
		?>
		<div class="inside">
			<p class="description"><?php

				_e( 'Default settings for post types.' , 'wp-access-areas' );

			?></p>
		</div>
		<?php
	}


	/**
	 *	settings field callback
	 */
	public function post_type_settings( $args ) {
		global $wp_roles;

		$template = Core\Template::instance();

		$option_value = get_option( $args['option_name'] );

		$post_types = get_post_types(array(
			'show_ui' => true,
		),'objects');

		$pt_keys = array_keys( $post_types );
		$first_pt = array_shift( $pt_keys );


		//
		$post_status_labels = array();
		$post_stati = get_post_stati(array(
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
							$id = sprintf( 'tab-panel-%s', $post_type );
							printf( '<li id="tab-link-wpaa-%s" class="%s">%s</li>',
								$post_type,
								( $first_pt == $post_type ) ? 'active' : '',
								sprintf( '<a href="#%1$s" aria-controls="%1$s">%2$s</a>', $id, $post_type_object->labels->name )
							);
						?>
					<?php } ?>
				</ul>
			</div>

			<div class="tab-panels">
				<?php foreach ( $post_types as $post_type => $post_type_object ) { ?>
					<?php


						$name_prefix = sprintf('%s[%s]', $args['option_name'], $post_type );
						$id_prefix = sprintf('%s-%s', $args['option_name'], $post_type );

						// sanitize pt settings
						$pt_settings = wp_parse_args( $option_value[ $post_type ], $pt_options[$post_type] );

					?>
					<div id="<?php printf( 'tab-panel-%s', $post_type ); ?>" class="tab-panel-content <?php echo ( $first_pt == $post_type ) ? 'active' : ''; ?>">
						<h3><?php echo $post_type_object->labels->name; ?></h3>
						<?php /* Access Settings */ ?>
						<div class="wpaa-settings-section wpaa-settings-access">

							<h4><?php _e('Access Control','wp-access-areas') ?></h4>
							<div class="wpaa-control">
								<?php

									// add enable access control for post type
									$name = sprintf('%s[access_override]', $name_prefix );
									$id = sprintf('%s-%s-access-override', $args['option_name'], $post_type );
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
											$post_type_object->labels->singular_name
									); ?>
								</p>

							</div>

							<?php

								echo $template->access_controls( $post_type_object, $pt_settings, array(
									'name_template'	=> $name_prefix . '[%s]',
									'id_template'	=> $id_prefix . '-%s',
								) );

							?>
						</div>


						<div class="wpaa-settings-section wpaa-settings-behavior">

							<h4><?php _e('Behavior','wp-access-areas') ?></h4>

							<div class="wpaa-control">
								<?php

									// add enable access control for post type
									$name = sprintf('%s[behavior_override]', $name_prefix );
									$id = sprintf('%s-%s-behavior-override', $args['option_name'], $post_type );
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
											$post_type_object->labels->singular_name
									); ?>
								</p>

							</div>

							<?php

								echo $template->behavior_controls( $post_type_object, $pt_settings, array(
									'name_template'	=> $name_prefix . '[%s]',
									'id_template'	=> $id_prefix . '-%s',
								) );

							?>

						</div>

						<div class="wpaa-settings-section wpaa-settings-advanced">
							<?php
							$id = $id_prefix . '-default-post-status';

							?>
							<h4><?php _e('Advanced','wp-access-areas') ?></h4>
							<div class="wpaa-control">
								<?php
									printf( '<label for="%s">%s</label>', $id, __( 'Fallback Post status', 'wp-access-areas' ) );

									echo $template->dropdown( $post_status_labels, $pt_settings['default_post_status'], array(
										'name'	=> sprintf('%s[default_post_status]',$name_prefix ),
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
	 *
	 */
	public function sanitize_post_type_settings( $settings ) {

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
			$settings[ $post_type ] = wp_parse_args( $settings[ $post_type ], $this->get_default_pt_option() );

			// sanitize boolean
			foreach ( array( 'access_override', 'behavior_override', 'login_redirect' ) as $prop ) {
				$settings[ $post_type ][ $prop ] = intval( $settings[ $post_type ] );
			}

			// sanitize http status

			// sanitize post default_status

			// sanitize assignable capability

			// post_behavior



			// check the rest
			/*
			√access_override	intval
			post_view_cap	is capability && current_user_can( capability ) ... is_grantable
			post_edit_cap	(same)
			post_comment_cap (same)
			default_status	valid post status

			√behavior_override	intval
			post_behavior		string 404|page|status
			√login_redirect		intval
			http_status			string http status
			fallback_page		unprotected page id
			*/
		}
		return $settings;
	}

	/**
	 *	@inheritdoc
	 */
	public function activate() {

		add_option( 'wpaa_post_types', $this->get_default_pt_options() );

	}

	/**
	 *	Settings default values
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

	private function get_default_pt_option() {
		return array(
			'access_override'	=>  1,
			'post_view_cap'		=>  'exist',
			'post_edit_cap'		=>  'exist',
			'post_comment_cap'	=>  'exist',
			'default_status'	=>  'publish',
			'behavior_override'	=>  1,
			'post_behavior'		=>  '404',
			'login_redirect'	=>  0,
			'http_status'		=>  '404',
			'fallback_page'		=>  0,
		);
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
				$post_type_settings[$post_type]['post_behavior']		= $behavior;
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
