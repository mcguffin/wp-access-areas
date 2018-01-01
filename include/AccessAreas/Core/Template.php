<?php

namespace AccessAreas\Core;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use AccessAreas\Model;

class Template extends Singleton {

	/**
	 *	Access Area
	 *	@param object	$access_area
	 */
	public function user_access_area( $access_area, $user_id ) {
		if ( $user_id instanceOf \WP_User ) {
			$user_id = $user_id->ID;
		}
		$output = sprintf( '<span class="wpaa-access-area wpaa-%s" data-wpaa-scope="%s">', $access_area->capability, $access_area->blog_id );
		$output .= $access_area->title;
		if ( $access_area->id ) {
			$output .= sprintf('<button %s><span class="screen-reader-text">%s</span></button>',
				$this->mk_attr(array(
					'class'					=> 'button-link dashicons dashicons-dismiss',
					'data-wpaa-action'		=> 'revoke',
					'data-wpaa-access-area'	=> $access_area->id,
					'data-wpaa-user'		=> $user_id,
				)), __('Revoke Access','wp-access-areas') );
		}
		$output .= '</span>';
		return $output;
	}
	/**
	 *	Add access area button in users admin.
	 *	@param int $user_id
	 */
	public function user_add_access_area( $user_id ) {
		$output = sprintf('<button %s><span class="screen-reader-text">%s</span></button>',
			$this->mk_attr(array(
				'class'					=> 'button dashicons dashicons-plus-alt',
				'data-wpaa-action'		=> 'grant',
				'data-wpaa-user'		=> $user_id,
			)), __('Grant Access','wp-access-areas') );
		return $output;
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
	protected function optgroup( $choices, $selected ) {
		$output = '';
		foreach ( $choices as $i => $choice ) {
//vaR_dump(is_object($choice),is_array( $choice ),is_null($choice ));continue;
			if ( is_object($choice) ) {
				if ( isset( $choice->title, $choice->id ) ) {
					$output .= sprintf('<option value="%s" %s>%s</option>', $choice->id, selected( $selected, $choice->id, false ), $choice->title );
				}
			} else if ( is_array( $choice ) ) {
				$output .= sprintf( '<optgroup label="%s">', $i );
				$output .= $this->optgroup( $choice, $selected );
				$output .= '</optgroup>';
			} else if ( is_null($choice ) ) {
				continue;
			} else {
				$output .= sprintf('<option value="%s" %s>%s</option>', $i, selected( $selected, $i, false ), $choice );
			}
		}
		return $output;
	}
	/**
	 *	Access Area select drowpdown
	 */
	public function access_areas_dropdown( $access_areas, $context = 'post', $dropdown_attr = array() ) {

		if ( 'user' === $context ) {
			$access_areas = $this->filter_grantable( $access_areas );
		} else if ('post' === $context ) {

		}


		$dropdown_attr = wp_parse_args( $dropdown_attr, array(
			'name'			=> 'access-area-'.$context,
			'id'			=> 'access-area-'.$context,
			'placeholder'	=> __('—Select—','wp-access-areas'),
		));


		return $this->dropdown( $access_areas, null, $dropdown_attr );
	}
	/**
	 *	Filter array with Access Areas the current may grant to other users
	 */
	public function filter_grantable( $access_areas ) {

		$user_can = current_user_can( 'promote_users' );
		$grantable = array();
		foreach ( $access_areas as $i => $access_area ) {
			if ( is_array( $access_area ) ) {
				$grantable[$i] = $this->filter_grantable( $access_area );
			} else {
				if ( apply_filters( 'wpaa_allow_grant_access', $user_can, $access_area ) ) {
					$grantable[$i] = $access_area;
				}
			}

		}
		return $grantable;
	}

	/**
	 *	Select Behaviour for restricted posts.
	 *	@param string $selected_behavior
	 *	@param string $fieldname
	 */
	public function select_behaviour( $selected_behavior = '404', $fieldname = '_wpaa_post_behavior' ) {

		$behaviors = array(
			array(
				'value'	=> '404',
				'label' => __( 'Show WordPress 404' , 'wp-access-areas'),
			),
			array(
				'value'	=> 'page',
				'label' => __( 'Redirect to the fallback page.' , 'wp-access-areas'),
			),
			array(
				'value'	=> 'status',
				'label' => __( 'Show fallback page contents with HTTP-Status.' , 'wp-access-areas'),
			),
		);

		$output = '';

		foreach ( $behaviors as $item ) {
			extract( $item );
			$id = 'wpaa-view-post-behavior-' . $value;
			$output .= sprintf( '<label for="%s">', $id );
			$output .= sprintf( '<input type="radio" name="%s" id="%s" value="%s" %s />', $fieldname, $id, $value, checked( $value , $selected_behavior, false ) );
			$output .= $label;
			$output .= '</label><br />';
		}
		$output .= <<<EOT
		<script type="text/javascript">
		(function($){
			$('[name="{$fieldname}"]').on('change',function(){
				var val = $('[name="{$fieldname}"]:checked').val();

				$('#wpaa-http-status-select').prop('disabled', val.indexOf('status') === -1);
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
	public function select_http_status( $selected_status = '', $fieldname = '_wpaa_post_behavior_status' ) {

		$http_stati = array(
			__('2xx – Success','wp-access-areas') => array(
				'200' => __('200 - OK', 'wp-access-areas' ),
				'204' => __('204 – No Content', 'wp-access-areas' ),
			),
			__('4xx – Client Errors') => array(
				'402' => __('402 - Payment Required', 'wp-access-areas' ),
				'403' => __('403 - Forbidden', 'wp-access-areas' ),
				'404' => __('404 - Not Found', 'wp-access-areas' ),
				'410' => __('410 - Gone', 'wp-access-areas' ),
				'418' => __('418 - I\'m a teapot', 'wp-access-areas' ),
				'451' => __('451 - Unavailable For Legal Reasons', 'wp-access-areas' ),
			),
		);
		return $this->dropdown( $http_stati, $selected_status, array(
			'name'			=> $fieldname,
			'id'			=> 'wpaa-http-status-select',
			'placeholder'	=> __( '—Select—', 'wp-access-areas' ),
		) );
	}


	/**
	 *	Select HTTP-Status for restricted posts.
	 *	@param string $selected_behavior
	 *	@param string $fieldname
	 */
	public function select_login_redirect( $login_redirect = 0, $fieldname = '_wpaa_post_behavior_login_redirect' ) {
		$output = '';
		$id = 'wpaa-post-behaviour-login-redirect';
		$output .= sprintf('<input type="hidden" name="%s" value="0" />', $fieldname, $id );
		$output .= sprintf('<input type="checkbox" name="%s" value="1" id="%s" %s />', $fieldname, $id, checked( $login_redirect, 1, false) );
		$output .= sprintf('<label for="%s">%s</label>', $id, __('If not logged in, redirect to login.', 'wp-access-areas' ) );
		return $output;

	}
	public function select_fallback_page( $post_fallback_page = 0, $fieldname = '_wpaa_fallback_page' ) {
		$post_fallback_page = get_option('wpaa_fallback_page');
		return;

		global $wpdb;
		if ( ! wpaa_is_post_public( $post_fallback_page ) )
			$post_fallback_page = 0;

		//
		$restricted_pages = $wpdb->get_col($wpdb->prepare("SELECT id
			FROM $wpdb->posts
			WHERE
				post_type=%s AND
				post_status=%s AND
				post_view_cap=%s", 'page','publish','exist' ) );

		wp_dropdown_pages(array(
			'selected' 	=> $post_fallback_page,
			'name'		=> $fieldname,
			'exclude'	=> $restricted_pages,
			'show_option_none' => __( 'Front page', 'wp-access-areas' ),
			'option_none_value' => 0,
		));
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
