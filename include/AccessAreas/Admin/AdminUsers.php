<?php

namespace AccessAreas\Admin;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use AccessAreas\Core;
use AccessAreas\Helper;
use AccessAreas\Model;

class AdminUsers extends Core\PluginComponent {

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

			add_filter( 'views_users', array( $this, 'list_table_views' ) );

//			add_filter( 'user_row_actions', array( $this, 'user_row_actions' ), 10, 2 );
		}
	}

	/**
	 *	Filter users by granted Access Areas
	 *
	 *	@filter views_users
	 */
	public function list_table_views( $views ) {
		$model = Model\ModelAccessAreas::instance();
		$access_areas = $model->fetch_by( 'blog_id', get_current_blog_id() );

		if ( ! $access_areas ) {
			return $views;
		}

		foreach ( array_values( $access_areas ) as $i => $access_area ) {
			$before = '';
			if ( $i === 0 ) {
				$before = sprintf('<span class="wpaa-label">%s</span>',__('Access Areas:','wp-access-areas'));
			}

			$views[ $access_area->capability ] = sprintf('%s <a href="%s">%s</a>',
					$before,
					add_query_arg( 'role', $access_area->capability, admin_url( 'users.php' ) ),
					$access_area->title
				);
		}
		return $views;
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
					<td><?php echo $this->get_access_areas_ui( $profileuser->ID ) ?></td>
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
		return $this->get_access_areas_ui( $user_id );
	}

	/**
	 *	@param int|WP_User
	 *	@return string
	 */
	public function get_access_areas_ui( $user_id ) {

		$user		= new \WP_User( $user_id );
		$template	= Helper\Template::instance();
		$output		= '';
		$output .= sprintf('<div class="assign-access-areas" data-wpaa-user="%d">', $user_id );
		if ( apply_filters('wpaa_user_is_admin', $user->has_cap('administrator'), $user ) ) {
			//
			$output .= $template->user_access_area( (object) array(
				'id'			=> 0,
				'title' 		=> __( 'Everywhere', 'wp-access-area' ),
				'capability'	=> 'global',
				'blog_id'		=> '0',
			), $user_id );
		} else {
			$is_self = $user_id === get_current_user_id();
			$granted	= array();
			$model		= Model\ModelAccessAreas::instance();
			// !!!
			$available	= apply_filters('wpaa_assignable_access_areas_user', $model->fetch_list( ), $user );

			foreach ( $available as $aa ) {
				if ( $user->has_cap( $aa->capability ) ) {
					$granted[] = $aa;
				}
			}
			foreach ( $granted as $aa ) {
				if ( $is_self || ! current_user_can( 'wpaa_revoke_access', $aa ) ) {
					$output .= $template->post_access_area( $aa );
				} else {
					$output .= $template->user_access_area( $aa, $user_id );
				}
			}
			if ( ! $is_self && current_user_can('wpaa_grant_access') ) {
				$output .= $template->user_add_access_area( $user_id );
			}

		}
		$output .= '</div>';
		return $output;
	}

	/**
	 *	@inheritdoc
	 */
	public function activate() {
		// single blog activation: add all caps to administrator
		$admin_role = get_role( 'administrator' );

		if ( $admin_role ) {
			// add default role caps
			$admin_caps = array(
				// modify roles
				'wpaa_edit_role_caps',

				// modify users
				'wpaa_set_view_cap', 'wpaa_set_edit_cap','wpaa_set_comment_cap',

				// modify posts
				'wpaa_grant_access', 'wpaa_revoke_access',

				// manage aa
				'wpaa_manage_access_areas',
			);
			$admin_caps = apply_filters( 'wpaa_default_admin_caps', $admin_caps );
			foreach ( $admin_caps as $cap ) {
				$admin_role->add_cap( $cap );
			}
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
	public function upgrade( $new_version, $old_version ) {

	}

	/**
	 *	@inheritdoc
	 */
	public function uninstall() {
		// revoke all access from all users
		$model = Model\ModelAccessAreas::instance();
		$access_areas = $model->fetch_list();
		$users = get_users();
		foreach ( $users as $user ) {
			foreach ( $access_areas as $access_area ) {
				// much faster than $user->remove_cap( $access_area->capability )
				if ( isset( $user->caps[ $access_area->capability ] ) ) {
					unset( $user->caps[ $access_area->capability ] );
				}
			}
			update_user_meta( $user->ID, $user->cap_key, $user->caps );
		}

	}


}
