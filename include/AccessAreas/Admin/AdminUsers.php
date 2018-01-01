<?php

namespace AccessAreas\Admin;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use AccessAreas\Core;
use AccessAreas\Model;

class AdminUsers extends Core\Singleton {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		parent::__construct();

		add_action( 'admin_init' , array( $this, 'admin_init' ) );

	}

	/**
	 *	Admin init
	 *	@action admin_init
	 */
	public function admin_init() {
		if ( current_user_can( 'promote_users' ) ) {
			add_filter( 'bulk_actions-users', array( $this, 'bulk_actions_users' ) );

			add_filter( 'manage_users_columns' , array( $this, 'add_column') );
			add_filter( 'manage_users_custom_column' , array( $this, 'manage_column'), 10, 3 );
			add_action( 'load-users.php', array( $this, 'enqueue_assets' ) );
			add_action( 'load-user-edit.php', array( $this, 'enqueue_assets' ) );
			add_action( 'load-profile.php', array( $this, 'enqueue_assets' ) );
			add_filter( 'additional_capabilities_display' , '__return_false' );

			add_action( 'edit_user_profile' , array( $this, 'personal_options' ) );
			add_action( 'show_user_profile' , array( $this, 'personal_options' ) );

//			add_filter( 'user_row_actions', array( $this, 'user_row_actions' ), 10, 2 );
		}
	}
	/**
	*	@action edit_user_profile
	*	@action show_user_profile
	 */
	public function personal_options( $profileuser ) {
		?>
			<h2><?php _e( 'Access Areas', 'wp-access-areas' ); ?></h2>
			<table class="form-table">
				<tbody>
					<th><?php _e('Granted Access', 'wp-access-areas'); ?></th>
					<td><?php echo $this->manage_column( '', 'access', $profileuser->ID ) ?></td>
				</tbody>
			</table>
		<?php
	}


	/**
	 *	@action load-users.php
	 */
	public function enqueue_assets() {
		wp_enqueue_media();
		wp_enqueue_script( 'access-areas-admin');
		wp_enqueue_style( 'access-areas-admin');
	}


	/**
	 *	@action user_row_actions
	 */
	public function user_row_actions( $actions, $user_object ) {
		if ( current_user_can( 'promote_users' ) ) {
			$actions['wpaa-grant'] = sprintf('<button class="button-link" data-wpaa-action="wpaa-grant" data-wpaa-user="%d">%s</button>', $user_object->ID, __('Grant Access','wp-access-areas') );
			$actions['wpaa-revoke'] = sprintf('<button class="button-link" data-wpaa-action="wpaa-revoke" data-wpaa-user="%d">%s</button>', $user_object->ID, __('Revoke Access','wp-access-areas') );
		}
		return $actions;
	}
	/**
	 *	@filter bulk_actions-users
	 */
	public function bulk_actions_users( $actions ) {
		if ( current_user_can( 'promote_users' ) ) {
			$actions['wpaa-grant'] = __('Grant Access','wp-access-areas');
			$actions['wpaa-revoke'] = __('Revoke Access','wp-access-areas');
		}
		return $actions;
	}

	/**
	 *	@filter manage_users_columns
	 */
	public function add_column($columns) {

		$columns['access'] = __( 'Access', 'wp-access-areas' );
		return $columns;
	}

	/**
	 *	@filter manage_users_custom_column
	 */
	public function manage_column( $column_content, $column, $user_id ) {
		if ( $column != 'access') {
			return $column_content;
		}
		$user		= new \WP_User( $user_id );
		$template	= Core\Template::instance();
		$output		= '';
		$output .= sprintf('<div class="assign-access-areas" data-wpaa-user="%d">', $user_id );
		if ( apply_filters('wpaa_user_is_admin', $user->has_cap('administrator') ) ) {
			$output .= $template->user_access_area( (object) array(
				'id'			=> 0,
				'title' 		=> __( 'Everywhere', 'wp-access-area' ),
				'capability'	=> 'global',
				'blog_id'		=> '0',
			), $user_id );
		} else {
			$granted	= array();
			$model		= Model\ModelAccessAreas::instance();
			$available	= apply_filters('wpaa_assignable_access_areas_user', $model->fetch_available( 'user' ), $user );

			foreach ( $available as $aa ) {
				if ( $user->has_cap( $aa->capability ) ) {
					$granted[] = $aa;
				}
			}
			foreach ( $granted as $aa ) {
				$output .= $template->user_access_area( $aa, $user_id );
			}
			$output .= $template->user_add_access_area( $user_id );

		}
		$output .= '</div>';
		return $output;
	}

}
