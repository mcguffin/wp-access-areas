<?php

namespace AccessAreas\Helper;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use AccessAreas\Core;
use AccessAreas\Model;

class Template extends Core\Singleton {


	/**
	 *	Access Area
	 *	@param object	$access_area
	 *	@param int $user_id
	 *	@return string
	 */
	public function user_access_area( $access_area, $user_id ) {
		if ( $user_id instanceOf \WP_User ) {
			$user_id = $user_id->ID;
		}

		$after = '';
		if ( current_user_can( 'wpaa_revoke_access', $user_id, $access_area ) ) {
			$after = sprintf('<a %s><span class="screen-reader-text">%s</span></a>',
				$this->mk_attr(array(
					'class'					=> 'button-link dashicons dashicons-dismiss',
					'data-wpaa-action'		=> 'revoke',
					'data-wpaa-access-area'	=> $access_area->id,
					'data-wpaa-user'		=> $user_id,
				)), __('Revoke Access','wp-access-areas') );
		}
		return $this->post_access_area( $access_area, $after );
	}

	/**
	 *	Access Area Badge
	 *	@param object	$access_area
	 *	@param int $user_id
	 *	@return string
	 */
	public function post_access_area( $access_area, $after = '', $type = 'none' ) {

		$output = sprintf( '<label class="wpaa-access-type-%s wpaa-access-area wpaa-%s" data-wpaa-scope="%s" title="%s">', $type, $access_area->capability, $access_area->blog_id, esc_attr($access_area->title) );
		$output .= '<span>' . $access_area->title . '</span>';
		$output .= $after;
		$output .= '</label>';
		return $output;
	}

	/**
	 *	Access Area Badge
	 *	@param object	$access_area
	 *	@param int $user_id
	 *	@return string
	 */
	public function post_access_role( $rolename, $type = 'none' ) {

		if ( ! isset( wp_roles()->roles[ $rolename ] )) {
			return '';
		}
		$rile_title = wp_roles()->roles[ $rolename ]['name'];
		$output = sprintf( '<label class="wpaa-access-type-%s wpaa-access-area wpaa-role-access wpaa-%s" title="%s">', $type, $rolename, esc_attr( $rile_title ) );
		$output .= '<span>' . $rile_title . '</span>';
		$output .= '</label>';
		return $output;
	}

	/**
	 *	Access Badge
	 *	@param object	$access_area
	 *	@param int $user_id
	 *	@return string
	 */
	public function post_access( $capability, $type = 'view' ) {
		if ( wp_roles()->is_role( $capability ) ) {
			return $this->post_access_role( $capability, $type );
		}
		if ( $wpaa = wpaa_get_access_area( $capability ) ) {
			return $this->post_access_area( $wpaa, '', $type );
		}
		if ( $capability === 'read' ) {
			$label = __( 'Logged in Users', 'wp-access-areas' );
			$output = sprintf( '<label class="wpaa-access-type-%s wpaa-access-area wpaa-base-access wpaa-%s" title="%s">', $type, $capability, esc_attr( $label ) );
			$output .= '<span>' . $label . '</span>';
			$output .= '</label>';
			return $output;
		}
		return '';

	}

	/**
	 *	Add access area button in users admin.
	 *	@param int $user_id
	 */
	public function user_add_access_area( $user_id ) {
		if ( ! current_user_can( 'wpaa_grant_access', $user_id ) ) {
		}
		$output = sprintf('<a %s><span class="screen-reader-text">%s</span></a>',
			$this->mk_attr(array(
				'class'					=> 'button dashicons dashicons-plus-alt',
				'data-wpaa-action'		=> 'grant',
				'data-wpaa-user'		=> $user_id,
			)), __('Grant Access','wp-access-areas') );
		return $output;
	}










	/**
	 *	Access controls html
	 *
	 *	@param WP_Post $post
	 *	@param object $post_type_object
	 *	@param array $values array( 'post_view_cap' => 'exists', 'post_edit_cap' => 'exists', 'post_comment_cap' => 'exist' )
	 *	@param array $options array( 'name_template' => '%s', 'id_template' => '%s',  )
	 *	@return string access control html
	 */
	public function access_controls( $post_type_object, $post = null, $values = array(), $options = array() ) {

		$output = '';

		if ( is_null( $post ) ) {
			$can_assign_view = current_user_can( 'wpaa_set_view_cap' );
			$can_assign_edit = current_user_can( 'wpaa_set_edit_cap' );
			$can_assign_comment = current_user_can( 'wpaa_set_comment_cap' );
		} else {
			$can_assign_view = current_user_can( 'wpaa_set_view_cap', $post->ID );
			$can_assign_edit = current_user_can( 'wpaa_set_edit_cap', $post->ID );
			$can_assign_comment = current_user_can( 'wpaa_set_comment_cap', $post->ID );
		}
		if ( ! $can_assign_view && ! $can_assign_view && ! $can_assign_comment ) {
			return $output;
		}

		// parse input
		$values = wp_parse_args( $values, array(
			'post_view_cap'		=> 'exist',
			'post_edit_cap'		=> 'exist',
			'post_comment_cap'	=> 'exist',
		));

		$options = wp_parse_args( $options, array(
			'name_template'	=> '%s',
			'id_template'	=> '%s',
		));

		$model_wpaa = Model\ModelAccessAreas::instance();

		$roles = wp_roles()->get_names();

		$roles_key =__( 'WordPress Roles', 'wp_access_areas');

		$access_options = $access_options_edit = $model_wpaa->get_access_options();

		unset( $access_options_edit['read'] );

		// generate output

		$output .= '<div class="wpaa-access-controls">';



		// View cap
		if ( $can_assign_view && ( $post_type_object->public || $post_type_object->show_ui ) ) {
			$output .=	'<div class="wpaa-control wpaa-access-control wpaa-access-view wp-clearfix">';

			$name	= sprintf( $options['name_template'], 'post_view_cap' );
			$id		= sprintf( $options['id_template'], 'view' );


			if ( 'attachment' === $post_type_object->name ) {

				$output .=	'<div class="inline notice notice-warning">';
				$output .=		'<p>';
				$output .=			'<strong>' . __( 'Security Notice', 'wp-access-areas' ) . ': </strong>';
				$output .=			__( 'Only the attachment page will be protected. The media file can still be downloaded.', 'wp-access-areas' );
				$output .=		'</p>';
				$output .=	'</div>';

			}
			$output .=		sprintf( '<label for="%s">%s</label>', $id, __( 'Who can read', 'wp-access-areas' ) );

			$output .=		$this->assign_access_select( $access_options, array(
				'name'		=> $name,
				'id'		=> $id,
				'selected'	=> $values['post_view_cap'],
			), 'view' );

			$output	.=	'</div>';
		} else {
//			$output .=	sprintf( '<input type="hidden" name="%s" value="exist" />', $name );
		}


		// Edit cap
		if ( $can_assign_edit ) {
			$output .= 		'<div class="wpaa-control wpaa-access-control wpaa-access-edit wp-clearfix">';

			$name	= sprintf( $options['name_template'], 'post_edit_cap' );
			$id		= sprintf( $options['id_template'], 'edit' );

			$output .=			sprintf( '<label for="%s">%s</label>', $id, __( 'Who can edit:', 'wp-access-areas' ) );

			$access_options_edit[ $roles_key ] = array();
			$edit_roles = array();
			foreach ( $roles as $role_slug => $role_name ) {
				if ( get_role( $role_slug )->has_cap( $post_type_object->cap->edit_posts ) ) {
					$access_options_edit[ $roles_key ][ $role_slug ] = $role_name;
				}
			}

			$output .=			$this->assign_access_select( $access_options_edit, array(
				'name'		=> $name,
				'id'		=> $id,
				'selected'	=> $values['post_edit_cap'],
			), 'edit' );
			$output	.=		'</div>';
		}


		// Comment cap
		if ( $can_assign_comment && post_type_supports( $post_type_object->name, 'comments' ) ) {
			// comment access
			$output .= 	'<div class="wpaa-control wpaa-access-control wpaa-access-edit wp-clearfix">';

			$name	= sprintf( $options['name_template'], 'post_comment_cap' );
			$id		= sprintf( $options['id_template'], 'comment' );

			$output .=			sprintf( '<label for="%s">%s</label>', $id, __( 'Who can comment', 'wp-access-areas' ) );

			$output .=		$this->assign_access_select( $access_options, array(
				'name'		=> $name,
				'id'		=> $id,
				'selected'	=> $values['post_comment_cap'],
			), 'comment' );
			$output	.=	'</div>';
		} else {
//			printf( '<input type="hidden" name="%s" value="exist" />', $name );
		}

		$output	.=	'</div>';

		return $output;

	}

	/**
	 *	Access controls html
	 *
	 *	@param object $post_type_object
	 *	@param array $values array( 'behavior' => '404', 'login_redirect' => 0, 'http_status' => '404', 'fallback_page' => 0 )
	 *	@param array $options array( 'name_template' => '%s', 'id_template' => '%s',  )
	 *	@return string access control html
	 */
	public function behavior_controls( $values = array(), $options = array() ) {
		// parse input

		$values = wp_parse_args( $values, array(
			'behavior'			=> '404',
			'login_redirect'	=> 0,
			'http_status'		=> '404',
			'fallback_page'		=> 0,
		));

		$options = wp_parse_args( $options, array(
			'name_template'	=> '_wpaa_%s',
			'id_template'	=> 'wpaa-behavior-%s',
		));
		$output = '';

		$output .= 	'<div class="wpaa-behavior-controls">';

		// behavior
		$name = sprintf($options['name_template'],'behavior');
		$id = sprintf( $options['id_template'], 'behavior' );

		$output .=		sprintf('<div class="wpaa-control wpaa-behavior-control wpaa-behavior" data-value="%2$s">', $id, $values['behavior'] );

		$output .= $this->select_behavior(
			$values['behavior'], $name, $id
		);
		$output	.=		'</div>';


		// login redirect
		$output .=		'<div class="wpaa-control wpaa-behavior-control wpaa-behavior-login-redirect">';

		$name = sprintf($options['name_template'],'login_redirect');
		$id = sprintf( $options['id_template'], 'login-redirect' );

		$output .= $this->select_login_redirect(
			$values['login_redirect'], $name, $id
		);
		$output	.=		'</div>';


		$output .=		'<div class="wpaa-control wpaa-behavior-control wpaa-behavior-http-status wp-clearfix">';

		$name = sprintf($options['name_template'],'http_status');
		$id = sprintf( $options['id_template'], 'http-status' );

		$output .=			sprintf( '<label for="%s">%s</label>', $id, __( 'HTTP Status', 'wp-access-areas' ) );

		$output .= $this->select_http_status(
			$values['http_status'], $name, $id
		);
		$output	.=		'</div>';


		$output .=		'<div class="wpaa-control wpaa-behavior-control wpaa-behavior-fallback-page wp-clearfix">';

		$name = sprintf( $options['name_template'], 'fallback_page' );
		$id = sprintf( $options['id_template'], 'fallback-page' );

		$output .=			sprintf( '<label for="%s">%s</label>', $id, __( 'Fallback Page', 'wp-access-areas' ) );

		$output .= $this->select_fallback_page(
			$values['fallback_page'], $name, $id
		);

		$output	.=		'</div>';

		$output	.=	'</div>';

		return $output;

	}


	/**
	 *	Assign Access Dropdown
	 *
	 *	@param assoc $access_areas
	 *	@param assoc $dropdown_attr
	 *	@return string
	 */
	public function assign_access_select( $access_areas, $dropdown_attr = array(), $assign_type = 'view' ) {

		$access_areas = $this->filter_assignable( $access_areas, $assign_type );

		if ( empty( $access_areas ) ) {
			return '';
		}

		$dropdown_attr = wp_parse_args( $dropdown_attr, array(
			'name'			=> 'wpaa-assign-access',
			'id'			=> 'wpaa-assign-access',
			'selected'		=> null,
		));

		$selected = null;

		if ( isset( $dropdown_attr['selected'] ) ) {
			$selected = $dropdown_attr['selected'];
			unset( $dropdown_attr['selected'] );
		}

		return $this->dropdown( $access_areas, $selected, $dropdown_attr );
	}

	/**
	 *	Grant Access Dropdown
	 *
	 *	@param assoc $access_areas
	 *	@param assoc $dropdown_attr
	 *	@return string
	 */
	public function grant_access_select( $access_areas, $dropdown_attr = array() ) {

		$access_areas = $this->filter_grantable( $access_areas );

		if ( empty( $access_areas ) ) {
			return '';
		}

		$dropdown_attr = wp_parse_args( $dropdown_attr, array(
			'name'			=> 'wpaa-grant-access',
			'id'			=> 'wpaa-grant-access',
			'selected'		=> null,
		));
		$selected = null;
		if ( isset( $dropdown_attr['selected'] ) ) {
			$selected = $dropdown_attr['selected'];
			unset($dropdown_attr['selected']);
		}

		$dropdown_attr['placeholder'] = __( '—Select—', 'wp-access-areas' );


		return $this->dropdown( $access_areas, $selected, $dropdown_attr );
	}

	/**
	 *	Recursively Filter array with Access Areas the current user may grant
	 *	to other users.
	 *
	 *	@param array $access_areas
	 *	@return array Grantable Access Areas
	 */
	public function filter_grantable( $access_areas ) {

		$sanitize = Sanitize::instance();


		$grantable = array();

		// shortcut
		if ( ! current_user_can( 'wpaa_grant_access' ) ) {
			return $grantable;
		}

		foreach ( $access_areas as $i => $access_area ) {
			if ( is_array( $access_area ) ) {
				$grantable[$i] = $this->filter_grantable( $access_area );
			} else if ( is_object( $access_area ) ) {
				$capability = $access_area->capability;
				if ( $sanitize->post_cap_grantable( $capability ) ) {
					$grantable[$i] = $access_area;
				}
			}

		}
		return $grantable;
	}

	/**
	 *	Recursively Filter array with Access Areas the current may assign to posts
	 *
	 *	@param array $access_areas
	 *	@return array
	 */
	public function filter_assignable( $access_areas, $assign_type = 'view' ) {
		$sanitize = Sanitize::instance();

		$assignable = array();

		// shortcut
		if ( ! current_user_can( sprintf( 'wpaa_set_%s_cap', $assign_type ) ) ) {
			return $assignable;
		}


		foreach ( $access_areas as $capability => $access_area ) {

			if ( is_array( $access_area ) ) {
				$assignable[ $capability ] = $this->filter_assignable( $access_area, $assign_type );
			} else {

				if ( $sanitize->post_cap_assignable( $capability, $assign_type ) ) {
					$assignable[$capability] = $access_area;
				}
			}

		}
		return $assignable;
	}


	/**
	 *	Select behavior for restricted posts.
	 *	@param string $selected_behavior
	 *	@param string $fieldname
	 */
	public function select_behavior( $selected_behavior = '404', $fieldname = '_wpaa_behavior', $id_prefix = 'wpaa-behavior' ) {

		$sanitize = Sanitize::instance();

		$behaviors = $sanitize->get_behaviors();

		$output = '';
		$output .= 		'<p class="description">';
		$output .=			__('What will happen if somebody tries to view a restricted post directly.' , 'wp-access-areas' );
		$output .=		'</p>';

		foreach ( $behaviors as $item ) {
			extract( $item );
			$id = $id_prefix . '-' . $value;
			$output .= sprintf( '<label for="%s">', $id );
			$output .= sprintf( '<input type="radio" name="%s" id="%s" value="%s" %s />', $fieldname, $id, $value, checked( $value , $selected_behavior, false ) );
			$output .= $label;
			$output .= '</label>';
		}


		return $output;
	}

	/**
	 *	Select HTTP-Status for restricted posts.
	 *	@param string $selected_behavior
	 *	@param string $fieldname
	 */
	public function select_login_redirect( $login_redirect = 0, $fieldname = '_wpaa_login_redirect', $id = 'wpaa-post-behavior-login-redirect' ) {
		$output = '';

		$output .= sprintf(
			'<input type="hidden" name="%s" value="0" />',
			$fieldname
		);
		$output .= sprintf(
			'<input type="checkbox" name="%s" value="1" id="%s" %s />',
			$fieldname,
			$id,
			checked( $login_redirect, 1, false )
		);
		$output .= sprintf(
			'<label for="%s">%s</label>',
			$id,
			__('Redirect to login first if not logged in.', 'wp-access-areas' )
		);

		return $output;

	}

	/**
	 *	Select HTTP-Status for restricted posts.
	 *	@param string $selected_behavior
	 *	@param string $fieldname
	 */
	public function select_http_status( $selected_status = '', $fieldname = '_wpaa_http_status', $id = 'wpaa-http-status-select' ) {

		$sanitize = Sanitize::instance();

		$http_stati = $sanitize->get_http_stati();

		return $this->dropdown( $http_stati, $selected_status, array(
			'name'			=> $fieldname,
			'id'			=> $id,
			'placeholder'	=> __( '—Select—', 'wp-access-areas' ),
		) );
	}

	/**
	 *
	 */
	public function select_fallback_page( $post_fallback_page = 0, $fieldname = '_wpaa_fallback_page', $id = 'wpaa-fallback-page' ) {

		global $wpdb;

		$postModel = Model\ModelPost::instance();

		if ( ! $postModel->post_is_public( $post_fallback_page ) ) {
			$post_fallback_page = 0;
		}

		//
		$sql = $wpdb->prepare("SELECT id
			FROM $wpdb->posts
			WHERE
				post_type=%s AND
				post_status=%s AND
				post_view_cap!=%s", 'page','publish','exist' );
		$restricted_pages = $wpdb->get_col( $sql );

		return wp_dropdown_pages(array(
			'selected' 			=> $post_fallback_page,
			'name'				=> $fieldname,
			'exclude'			=> $restricted_pages,
			'show_option_none'	=> false,//__( 'Home page', 'wp-access-areas' ),
			'option_none_value'	=> 0,
			'echo'				=> false,
			'id'				=> $id,
		));
	}

	/**
	 *	Generic dropdown.
	 */
	public function dropdown( $choices, $selected, $attr = array() ) {
		if ( isset($attr['placeholder']) && $attr['placeholder'] ) {
			$choices = array( '' => $attr['placeholder'] ) + $choices;
			unset($attr['placeholder']);
		}

		$output = sprintf('<select %s>', $this->mk_attr( $attr ));
		$output .= $this->select_options( $choices, $selected );
		$output .= '</select>';
		return $output;
	}

	/**
	 *	Generic optigroup. Used by dropdown()
	 */
	protected function select_options( $choices, $selected, $identifier = 'id' ) {
		$output = '';
		foreach ( $choices as $i => $choice ) {

			if ( is_object($choice) ) {
				if ( isset( $choice->title, $choice->id ) ) {
					$output .= sprintf('<option value="%s" %s>%s</option>', $choice->id, selected( $selected, $choice->id, false ), $choice->title );
				}
			} else if ( is_array( $choice ) ) {
				if ( ! count( $choice ) ) {
					continue;
				}
				$output .= sprintf( '<optgroup label="%s">', $i );
				$output .= $this->select_options( $choice, $selected );
				$output .= '</optgroup>';
			} else if ( is_null( $choice ) ) {
				continue;
			} else {
				$output .= sprintf('<option value="%s" %s>%s</option>', $i, selected( $selected, $i, false ), $choice );
			}
		}
		return $output;
	}

	/**
	 *	Make HTML attributes
	 *
	 *	@access private
	 *	@param	assoc	$attr
	 *
	 *	@return	string
	 */
	protected function mk_attr( $attr ) {
		$output = '';
		foreach ( $attr as $key => $value ) {
			if ( $value !== false && $value !== '' ) {
				if ( is_array( $value ) ) {
					switch ( $key ) {
						case 'class':
							$value = $this->implode_assoc( array_values( $value ), '', ' ' );
							break;
						case 'style':
							$value = $this->implode_assoc( $value, ':', ';' );
							break;
						default:
							$value = $this->implode_assoc( $value );
							break;
					}
				}
				$output .= sprintf(' %s="%s"', sanitize_title($key), esc_attr($value) );
			}
		}
		return $output;
	}

	/**
	 *	Reduce assoc to string.
	 *	Use for query strings, style attributes and such.
	 *
	 *	@access private
	 *	@param	assoc	$assoc
	 *	@param	string	$inner_glue
	 *	@param	string	$outer_glue
	 *	@param	bool	$keep_numeric_keys	How to handle numeric keys.
	 *
	 *	@return string
	 */
	protected function implode_assoc( $assoc, $inner_glue = '=', $outer_glue = '&', $keep_numeric_keys = false ) {
		$arr = array();
		foreach ( $assoc as $key => $value ) {
			if ( ! is_null( $value ) && $value !== '' ) {
				if ( ! $keep_numeric_keys && is_numeric( $key ) ) {
					$arr[] = $value;
				} else {
					$arr[] = $key . $inner_glue . $value;
				}
			}
		}
		return implode( $outer_glue, $arr );
	}

}
