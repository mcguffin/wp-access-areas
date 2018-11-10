<?php

namespace AccessAreas\Core;

class Sanitize extends Singleton {

	/**
	 *	@param string $capability
	 *	@return string $capability if the current user can grant it
	 */
	public function post_cap_grantable( $capability ) {
		//
		return current_user_can( 'wpaa_grant_access', $capability );

	}

	/**
	 *	@param string $capability
	 *	@return string $capability if the current user can assign
	 */
	public function post_cap_assignable( $capability, $assign_type = 'view' ) {

		return current_user_can( sprintf( 'wpaa_set_%s_cap', $assign_type ), $capability );

	}


	/**
	 *	@param string $behavior
	 *	@return string
	 */
	public function behavior( $behavior ) {
		if ( in_array( $behavior, $this->get_behaviors() ) ) {
			return $behavior;
		}
		return '404';
	}

	/**
	 *	@param string $status
	 *	@return string
	 */
	public function http_status( $status ) {
		$stati = array();
		foreach (array_values( $this->get_http_stati() ) as $sect ) {
			$stati += $sect;
		}; // 2 arrays

		if ( isset( $stati[ $status ] ) ) {
			return $status;
		}
		return '404';
	}

	/**
	 *	@param string $status
	 *	@return string
	 */
	public function post_status( $status ) {
		$stati = get_post_stati();
		if ( isset( $stati[ $status ] ) ) {
			return $status;
		}
	}


	/**
	 *	@param string $value
	 *	@return string
	 */
	public function capability( $value, $default = 'exists' ) {
		// chack if exist,read,rolename exists or wpaa exists!
		if ( in_array( $value, array('exist','read') ) ) {
			return $value;
		}
		if ( $GLOBALS['wp_roles']->is_role( $value ) ) {
			return $value;
		}
		if ( wpaa_access_area_exists( $value ) ) {
			return $value;
		}
		return 'exist';
	}


	/**
	 *	@param int $page_id
	 *	@return int
	 */
	public function fallback_page( $page_id ) {
		$page = get_post($page_id);
		if ( $page->post_status === 'publish' && $page->post_view_cap === 'exist' ) {
			return $page_id;
		}
	}


	/**
	 *	@return array
	 */
	public function get_behaviors() {
		$behaviors = array(
			'404' => array(
				'value'	=> '404',
				'label' => __( 'Show WordPress 404' , 'wp-access-areas'),
			),
			'page' => array(
				'value'	=> 'page',
				'label' => __( 'Redirect to the fallback page.' , 'wp-access-areas'),
			),
			'status' => array(
				'value'	=> 'status',
				'label' => __( 'Show fallback page contents with HTTP-Status.' , 'wp-access-areas'),
			),
		);
		return $behaviors;
	}

	/**
	 *	@return array
	 */
	public function get_http_stati() {
		$http_stati = array(
			__( '2xx – Success','wp-access-areas') => array(
				'200' => __('200 - OK', 'wp-access-areas' ),
				'204' => __('204 – No Content', 'wp-access-areas' ),
			),
			__( '4xx – Client Errors', 'wp-access-areas' ) => array(
				'402' => __('402 - Payment Required', 'wp-access-areas' ),
				'403' => __('403 - Forbidden', 'wp-access-areas' ),
				'404' => __('404 - Not Found', 'wp-access-areas' ),
				'410' => __('410 - Gone', 'wp-access-areas' ),
				'418' => __('418 - I\'m a teapot', 'wp-access-areas' ),
				'451' => __('451 - Unavailable For Legal Reasons', 'wp-access-areas' ),
			),
		);
		return $http_stati;
	}

}
