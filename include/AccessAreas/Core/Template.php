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
		$output .= sprintf('<button %s><span class="screen-reader-text">%s</span></button>',
			$this->mk_attr(array(
				'class'					=> 'button-link dashicons dashicons-trash',
				'data-wpaa-action'		=> 'revoke',
				'data-wpaa-access-area'	=> $access_area->id,
				'data-wpaa-user'		=> $user_id,
			)), __('Revoke Access','wp-access-areas') );
		$output .= '</span>';
		return $output;
	}

	public function user_add_access_area( $user_id ) {
		$output = sprintf('<button %s><span class="screen-reader-text">%s</span></button>',
			$this->mk_attr(array(
				'class'					=> 'button-link dashicons dashicons-plus-alt',
				'data-wpaa-action'		=> 'grant',
				'data-wpaa-user'		=> $user_id,
			)), __('Grant Access','wp-access-areas') );
		return $output;
	}
	/**
	 *
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
	protected function optgroup( $choices, $selected ) {
		$output = '';
		foreach ( $choices as $i => $choice ) {
			if ( is_object($choice) ) {
				if ( isset( $choice->title, $choice->id ) ) {
					$output .= sprintf('<option value="%s" %s>%s</option>', $choice->id, selected( $selected, $choice->id, false ), $choice->title );
				}
			} else if ( is_array( $choice ) ) {
				$output .= $this->optgroup( $choices, $selected );
			} else if ( is_null($choice ) ) {
				continue;
			} else {
				$output .= sprintf('<option value="%s" %s>%s</option>', $i, selected( $selected, $i, false ), $choice );
			}
		}
		return $output;
	}

	public function access_areas_dropdown( $context = 'post', $dropdown_attr = array() ) {

		$model = Model\ModelAccessAreas::instance();

		$access_areas = $model->fetch_available( $context );

		$options = apply_filters( "wpaa_available_access_areas_{$context}", $access_areas );

		$options = $this->filter_grantable( $options );

		$dropdown_attr = wp_parse_args($dropdown_attr,array(
			'name'			=> 'access-area-'.$context,
			'id'			=> 'access-area-'.$context,
			'placeholder'	=> __('—Select—','wp-access-areas'),
		));
		return $this->dropdown( $options, null, $dropdown_attr );
	}

	private function filter_grantable( $access_areas ) {
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
		return array_values($grantable);
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
