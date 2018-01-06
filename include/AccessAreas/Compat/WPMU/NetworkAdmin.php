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
			add_filter( 'access_areas_current_blog_id',array($this,'return_zero_string'));

		}
	}
	/**
	 *	@filter access_areas_current_blog_id 
	 */
	public function return_zero_string() {
		return '0';
	}

	public function add_uninstaller() {
		add_options_page( __( 'Access Areas Uninstall' , 'wp-access-areas' ), __( 'Uninstall Access Areas' , 'wp-access-areas' ), 'manage_network', 'access_areas-uninstall', array( $this, 'render_uninstaller_page' ) );

	}

	/**
	 * 	Add Admin page to menu
	 */
	function render_page() {
		?><div class="wrap">
			<h2><?php _e( 'Access Areas Uninstaller' , 'wp-access-areas' ); ?></h2>
			<?php _e('Uninstall Instructions ...', 'wp-access-areas' ); ?>
			<ol>
				<?php if ( get_site_option( 'wpaa_uninstall_active' ) ) { ?>
					<li><?php _e('Enable uninstall mode first', 'wp-access-areas' ); ?></li>
				<?php } ?>
				<li><?php printf(
					__('Visit the %s page', 'wp-access-areas' ),
					sprintf( '<a href="%s">%s</a>',
						network_admin_url('upgrade.php'),
						__('Upgrade Network','wp-access-areas') )
				); ?></li>
			</ol>
			<button>
		</div><?php
	}


}
