<?php

namespace AccessAreas\Compat\WPMU;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use AccessAreas\Core;
use AccessAreas\Admin;


class NetworkAdmin extends Core\Singleton {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		// is_network_admin() => true
		if ( current_user_can( 'manage_network_users' ) ) {
			$admin = \AccessAreas\Admin\AdminPageAccessAreas::instance();
			add_action( 'network_admin_menu', array( $admin, 'add_admin_page' ));
			add_action( 'network_admin_menu', array( $this, 'add_uninstaller' ));
			add_filter( 'access_areas_current_blog_id', array( $this, 'return_zero_string' ) );
		}
	}


	/**
	 *	@filter access_areas_current_blog_id
	 */
	public function return_zero_string() {
		return '0';
	}

	/**
	 *	@action network_admin_menu
	 */
	public function add_uninstaller() {
		add_submenu_page(
			'plugins.php',
			__( 'Access Areas Uninstall' , 'wp-access-areas' ),
			__( 'Uninstall Access Areas' , 'wp-access-areas' ),
			'install_plugins', 'access-areas-uninstall',
			array( $this, 'render_uninstaller_page' )
		);
	}

	/**
	 *	Network admin page callback
	 */
	public function render_uninstaller_page() {

		$action = 'wpaa-activate-uninstall';
		$param_name = 'wpaa_uninstall_active';
//var_dump(isset( $_POST[ $param_name ] ),check_admin_referer( $action ));
		if ( isset( $_POST[ $param_name ] ) && check_admin_referer( $action ) ) {
			update_site_option( $param_name, intval( $_POST[ $param_name ] ) );
		}
		$is_active = boolval( get_site_option( $param_name ) );

		?><div class="wrap">
			<h2><?php _e( 'Access Areas Uninstaller' , 'wp-access-areas' ); ?></h2>
			<div class="card">
				<h3 class="title"><?php _e('Uninstall Instructions', 'wp-access-areas' ); ?></h3>
				<p class="description"><?php
					_e('Use the uninstall Mode to wipe out every Data that Access Areas has generated.','wp-access-areas');
				?></p>
				<ol>
					<li><?php _e('Enable uninstall mode in the Box below', 'wp-access-areas' ); ?></li>
					<li><?php
						printf( '<a href="%s">%s</a>',
							add_query_arg( array(
								'action' => 'upgrade',
							), network_admin_url('upgrade.php')),
							__('Upgrade Network','wp-access-areas')
					); ?></li>
					<li><?php printf(
						__('Deactivate the plugin from the %s page', 'wp-access-areas' ),
						sprintf( '<a href="%s">%s</a>',
							network_admin_url('plugins.php'),
							__('Plugins','wp-access-areas') )
					); ?></li>
					<li><?php _e('Delete the plugin.', 'wp-access-areas' ); ?></li>
				</ol>
			</div>
			<div class="card">
				<form method="post">
					<?php wp_nonce_field( $action ); ?>
					<p><?php printf( __('Uninstall mode is currently %s','wp-access-areas' ),
						$is_active ? __('enabled','wp-access-areas') : __('disabled','wp-access-areas')
					); ?></p>
					<?php
						printf('<button type="submit" class="button button-%s" name="%s" value="%d">%s</button>',
							$is_active ? 'secondary' : 'primary',
							$param_name,
							intval( ! $is_active ),
							$is_active ? __('Disable Uninstall Mode','wp-access-areas') : __('Enable Uninstall Mode','wp-access-areas')
						);
					?>
				</form>
			</div>
		</div><?php
	}


}
