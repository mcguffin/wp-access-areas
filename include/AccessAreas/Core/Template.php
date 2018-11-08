<?php

namespace AccessAreas\Core;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use AccessAreas\Model;

class Template extends Singleton {


	public function grant_access_controls( $user_id ) {
		
	}

	/**
	 *	Access Area
	 *	@param object	$access_area
	 */
	public function user_access_area( $access_area, $user_id ) {
		if ( $user_id instanceOf \WP_User ) {
			$user_id = $user_id->ID;
		}
		$output = sprintf( '<label class="wpaa-access-area wpaa-%s" data-wpaa-scope="%s">', $access_area->capability, $access_area->blog_id );
		$output .= $access_area->title;
		if ( $access_area->id ) {
			$output .= sprintf('<a %s><span class="screen-reader-text">%s</span></a>',
				$this->mk_attr(array(
					'class'					=> 'button-link dashicons dashicons-dismiss',
					'data-wpaa-action'		=> 'revoke',
					'data-wpaa-access-area'	=> $access_area->id,
					'data-wpaa-user'		=> $user_id,
				)), __('Revoke Access','wp-access-areas') );
		}
		$output .= '</label>';
		return $output;
	}


	/**
	 *	Add access area button in users admin.
	 *	@param int $user_id
	 */
	public function user_add_access_area( $user_id ) {
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
	 *	@param object $post_type_object
	 *	@param array $values array( 'post_view_cap' => 'exists', 'post_edit_cap' => 'exists', 'post_comment_cap' => 'exist' )
	 *	@param array $options array( 'name_template' => '%s', 'id_template' => '%s',  )
	 *	@return string access control html
	 */
	public function access_controls( $post_type_object, $values = array(), $options = array() ) {
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

		$model = Model\ModelAccessAreas::instance();

		$roles = wp_roles()->get_names();

		$roles_key =__( 'WordPress Roles', 'wp_access_areas');

		$access_options = $access_options_edit = $model->get_access_options();

		unset( $access_options_edit['read'] );

		// generate output
		$output = '';

		$output .= '<div class="wpaa-access-controls">';

		// View cap
		if ( $post_type_object->public || $post_type_object->show_ui ) {
			$output .=	'<div class="wpaa-control wpaa-access-control wpaa-access-view">';

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
			));

			$output	.=	'</div>';
		} else {
			$output .=	sprintf( '<input type="hidden" name="%s" value="exist" />', $name );
		}


		// Edit cap
		$output .= 		'<div class="wpaa-control wpaa-access-control wpaa-access-edit">';

		$name	= sprintf( $options['name_template'], 'post_edit_cap' );
		$id		= sprintf( $options['id_template'], 'edit' );

		$output .=			sprintf( '<label for="%s">%s</label>', $id, __( 'Who can edit', 'wp-access-areas' ) );

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
		));
		$output	.=		'</div>';

		// Comment cap
		if ( post_type_supports( $post_type_object->name, 'comments' ) ) {
			// comment access
			$output .= 	'<div class="wpaa-control wpaa-access-control wpaa-access-edit">';

			$name	= sprintf( $options['name_template'], 'post_comment_cap' );
			$id		= sprintf( $options['id_template'], 'comment' );

			$output .=			sprintf( '<label for="%s">%s</label>', $id, __( 'Who can comment', 'wp-access-areas' ) );

			$output .=		$this->assign_access_select( $access_options, array(
				'name'		=> $name,
				'id'		=> $id,
				'selected'	=> $values['post_comment_cap'],
			));
			$output	.=	'</div>';
		} else {
			printf( '<input type="hidden" name="%s" value="exist" />', $name );
		}

		$output	.=	'</div>';

		return $output;

	}

	/**
	 *	Access controls html
	 *
	 *	@param object $post_type_object
	 *	@param array $values array( 'post_view_cap' => 'exists', 'post_edit_cap' => 'exists', 'post_comment_cap' => 'exist' )
	 *	@param array $options array( 'name_template' => '%s', 'id_template' => '%s',  )
	 *	@return string access control html
	 */
	public function behavior_controls( $post_type_object, $values = array(), $options = array() ) {
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

		$output .=		sprintf('<div class="wpaa-control wpaa-behavior-control wpaa-behavior" id="%1$s" data-value="%1$s">', $id );

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


		$output .=		'<div class="wpaa-control wpaa-behavior-control wpaa-behavior-http-status">';

		$name = sprintf($options['name_template'],'http_status');
		$id = sprintf( $options['id_template'], 'http-status' );

		$output .=			sprintf( '<label for="%s">%s</label>', $id, __( 'HTTP Status', 'wp-access-areas' ) );

		$output .= $this->select_http_status(
			$values['http_status'], $name, $id
		);
		$output	.=		'</div>';


		$output .=		'<div class="wpaa-control wpaa-behavior-control wpaa-behavior-fallback-page">';

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
	 *	Access Area select drowpdown
	 */
	public function capablities_dropdown( $access_areas, $context = 'post', $dropdown_attr = array() ) {
	}

	/**
	 *	Access Area select drowpdown
	 */
	public function access_areas_dropdown( $access_areas, $context = 'post', $dropdown_attr = array() ) {

		$dropdown_attr = wp_parse_args( $dropdown_attr, array(
			'name'			=> 'access-area-'.$context,
			'id'			=> 'access-area-'.$context,
			'selected'		=> null,
		));
		$selected = null;
		if ( isset( $dropdown_attr['selected'] ) ) {
			$selected = $dropdown_attr['selected'];
			unset($dropdown_attr['selected']);
		}
		$access_areas = apply_filters( "wpaa_access_areas_dropdown_{$context}", $access_areas );


		if ( 'user' === $context ) {
			$dropdown_attr['placeholder'] = __( '—Select—', 'wp-access-areas' );
			$access_areas = $this->filter_grantable( $access_areas );
		} else if ( 'post' === $context ) {
			$access_areas = $this->filter_assignable( $access_areas );
		}

		return $this->dropdown( $access_areas, $selected, $dropdown_attr );
	}

	/**
	 *	Assign Access Dropdown
	 *
	 *	@param assoc $access_areas
	 *	@param assoc $dropdown_attr
	 *	@return string
	 */
	public function assign_access_select( $access_areas, $dropdown_attr = array() ) {

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

		$access_areas = $this->filter_assignable( $access_areas );

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

		$access_areas = $this->filter_grantable( $access_areas );

		return $this->dropdown( $access_areas, $selected, $dropdown_attr );
	}

	/**
	 *	Filter array with Access Areas the current may grant to other users
	 */
	public function filter_grantable( $access_areas ) {

		$sanitize = Sanitize::instance();

		$user_can = current_user_can( 'promote_users' );
		$grantable = array();
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
	 *	Filter array with Access Areas the current may grant to other users
	 */
	public function filter_assignable( $access_areas ) {
		$sanitize = Sanitize::instance();

		$assignable = array();
		foreach ( $access_areas as $i => $access_area ) {
			if ( is_array( $access_area ) ) {
				$assignable[$i] = $this->filter_assignable( $access_area );
			} else {
				if ( is_object( $access_area ) ) {
					$capability = $access_area->capability;
				} else {
					$capability = $access_area;
				}

				if ( $sanitize->post_cap_assignable( $capability ) ) {
					$assignable[$i] = $access_area;
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
		$output .= <<<EOT
		<script type="text/javascript">
		(function($){
			$('#{$id_prefix} [type="radio"]').on('change',function(){
				console.log(this,$('#{$id_prefix} [type="radio"]:checked'));
				var val = $('#{$id_prefix} [type="radio"]:checked').val();
				$(this).closest('[data-value]').attr( 'data-value', val );
			})
		})(jQuery);
		</script>
EOT;

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

		$post_fallback_page = get_option('wpaa_fallback_page');
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
		$output .= $this->optgroup( $choices, $selected );
		$output .= '</select>';
		return $output;
	}

	/**
	 *	Generic optigroup. Used by dropdown()
	 */
	protected function optgroup( $choices, $selected, $identifier = 'id' ) {
		$output = '';
		foreach ( $choices as $i => $choice ) {

			if ( is_object($choice) ) {
				if ( isset( $choice->title, $choice->id ) ) {
					$output .= sprintf('<option value="%s" %s>%s</option>', $choice->id, selected( $selected, $choice->id, false ), $choice->title );
				}
			} else if ( is_array( $choice ) ) {
				$output .= sprintf( '<optgroup label="%s">', $i );
				$output .= $this->optgroup( $choice, $selected );
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
