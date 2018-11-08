<?php


namespace AccessAreas\WPRest;

use AccessAreas\Admin;
use AccessAreas\Core;
use AccessAreas\Model;


class WPRestAccessAreas extends Core\Singleton {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		$admin = Admin\AdminPosts::instance();

		$namespace = WPRest::instance()->get_namespace();
		$item_schema = array(
			'title' => array(
				'type'				=> 'string',
				'sanitize_callback'	=> 'sanitize_text_field',
				'description'		=> __( 'Name of the Item', 'wp-access-areas' ),
			),
			'capability'	=> array(
				'type'			=> 'string',
				'readonly'		=> true,
			),
			'blog_id'		=> array(
				'type'				=> 'integer',
//				'sanitize_callback'	=> 'intval',
				'description'		=> __( 'Blog-ID. `0` for global scope.', 'wp-access-areas' ),
				'readonly'			=> false,
			),
		);
		$item_schema_edit = $item_schema + array();
		$item_schema_edit['blog_id']['readonly'] = false;
		$item_schema_edit['title']['required'] = true;

		$grant_schema = array(
			'user_id'		=> array(
				'type'			=> 'array',
				'items'		=> array(
					'type'		=> 'integer',
				),
				'required'			=> true,
//				'sanitize_callback'	=> 'intval',
				'description'		=> __( 'User-ID(s)', 'wp-access-areas' ),
			),
		);

		register_rest_route( $namespace, '/access-area', array(
			array(
				'methods'				=> \WP_REST_Server::READABLE,
				'permission_callback'	=> array( $this, 'get_permissions' ),
				'callback'				=> array( $this, 'get_items' ),
				'args' 					=> $item_schema,
			),
			array(
				'methods'				=> \WP_REST_Server::CREATABLE,
				'permission_callback'	=> array( $this, 'get_permissions' ),
				'callback'				=> array( $this, 'create_item' ),
				'args' 					=> $item_schema_edit,
			)
		) );

		register_rest_route( $namespace, '/access-area/(?P<id>[\d]+)', array(
			array(
				'methods'				=> \WP_REST_Server::READABLE,
				'permission_callback'	=> array( $this, 'get_permissions' ),
				'callback'				=> array( $this, 'get_item' ),
				'args' 					=> $item_schema,
			),
			array(
				'methods'				=> \WP_REST_Server::EDITABLE,
				'permission_callback'	=> array( $this, 'get_permissions' ),
				'callback'				=> array( $this, 'edit_item' ),
				'args' 					=> $item_schema,
			),
			array(
				'methods'				=> \WP_REST_Server::DELETABLE,
				'permission_callback'	=> array( $this, 'get_permissions' ),
				'callback'				=> array( $this, 'delete_item' ),
			)
		) );

		register_rest_route( $namespace, '/access-area/(?P<id>[\d]+)/grant', array(
			array(
				'methods'				=> \WP_REST_Server::EDITABLE,
				'permission_callback'	=> array( $this, 'get_permissions' ),
				'callback'				=> array( $this, 'grant_access' ),
				'args' 					=> $grant_schema,
			)
		) );
		register_rest_route( $namespace, '/access-area/(?P<id>[\d]+)/revoke', array(
			array(
				'methods'				=> \WP_REST_Server::EDITABLE,
				'permission_callback'	=> array( $this, 'get_permissions' ),
				'callback'				=> array( $this, 'revoke_access' ),
				'args' 					=> $grant_schema,
			)
		) );

	}
	/**
	 *
	 */
	public function get_permissions( $request ) {
		$attr = $request->get_attributes();
		$param = $request->get_params();

		switch ( $attr['callback'][1] ) {
			case 'edit_item':
				$allowed = current_user_can( 'promote_users' );
				$allowed = apply_filters( 'wpaa_allow_edit_accessarea', $allowed, $request->get_param('id') );
				return $allowed;

			case 'create_item':
				$allowed = current_user_can( 'promote_users' );
				$allowed = apply_filters( 'wpaa_allow_create_accessarea', $allowed, $request->get_params() );
				return $allowed;

			case 'delete_item':
				$allowed = current_user_can( 'promote_users' );
				$allowed = apply_filters( 'wpaa_allow_delete_accessarea', $allowed, $request->get_param('id') );
				return $allowed;

			case 'get_items';
			case 'get_item':
				$allowed = current_user_can( 'edit_posts' );
				$allowed = apply_filters( 'wpaa_allow_read_accessarea', $allowed );
				return $allowed;

			case 'grant_access';
			case 'revoke_access':
				$model = Model\ModelAccessAreas::instance();
				$access_area = $model->fetch_one_by( 'id', $request->get_param('id') );

				$allowed = current_user_can( 'promote_users' );
				$allowed = apply_filters( 'wpaa_allow_grant_access', $allowed, $access_area );
				return true;
		}
		return current_user_can( 'manage_options' );
	}

	/**
	 *	Get item
	 *	@param $request
	 */
	public function get_item( $request ) {
		$model = Model\ModelAccessAreas::instance();
		$item = $model->fetch_one_by( $request->get_params() );
		if ( is_null( $item ) ) {
			return new \WP_Error( 'wpaa-not-found', __( 'Invalid ID.' ), array( 'status' => 404 ) );
		}
		return $item;
	}

	/**
	 *	Edit item
	 *	@param $request
	 */
	public function get_items( $request ) {

		$model = Model\ModelAccessAreas::instance();
		$blog_id = $request->get_param( 'blog_id' );

		if ( is_null( $blog_id ) ) {
			$blog_id = get_current_blog_id();
		}

		$response = rest_ensure_response( $model->fetch_by( 'blog_id', $blog_id ) );
		return $response;
	}


	/**
	 *	Create an item
	 *	@param $request
	 */
	public function create_item( $request ) {
		$params = wp_parse_args(
			$request->get_params(),
			array(
				'blog_id'	=> get_current_blog_id(),
			)
		);

		$model = Model\ModelAccessAreas::instance();
		$result = $model->create( $params['title'], $params['blog_id'] );
		if ( is_wp_error( $result ) ) {
			$response = $result;
		} else {
			$response = rest_ensure_response( $result );

			$response->set_status( 201 );
			$response->header( 'Location', rest_url( sprintf( '%s/%s/%d', WPRest::instance()->get_namespace(), 'access-area', $result->id ) ) );
		}

		return $response;
	}


	/**
	 *	Edit item
	 *	@param $request
	 */
	public function edit_item( $request ) {
		$model = Model\ModelAccessAreas::instance();
		$id = $request->get_param('id');
		$item = $model->fetch_one_by( 'id', $id );

		if ( is_null( $item ) ) {
			return new \WP_Error( 'wpaa-not-found', __( 'Invalid ID.' ), array( 'status' => 404 ) );
		}
		$result = $model->update(array( 'title' => $request->get_param('title') ), array( 'id' => $id ) );

		$response = rest_ensure_response( $model->fetch_one_by( 'id', $id ) );
		return $response;
	}

	/**
	 *	Delete an item
	 *	@param $request
	 */
	public function delete_item( $request ) {

		$model = Model\ModelAccessAreas::instance();

		$item = $model->fetch_one_by( $request->get_params() );

		if ( is_null( $item ) ) {
			return new \WP_Error( 'wpaa-not-found', __( 'Invalid ID.' ), array( 'status' => 404 ) );
		}

		$result = $model->delete_by_id( $request->get_param('id') );

		if ( $result === false ) {
			return new \WP_Error( 'wpaa-delete-failed', __( 'Error deleting Access Area', 'wp-access-areas') );
		}

		$response = new \WP_REST_Response();
		$response->set_data( array( 'deleted' => true, 'previous' => $item ) );

		return $response;
	}



	/**
	 *	Revoke Access
	 *	@param $request
	 */
	public function grant_access( $request ) {
		$model = Model\ModelAccessAreas::instance();
		$user_model = Model\ModelUser::instance();
		$access_area = $model->fetch_one_by( 'id', $request->get_param('id') );


		$user_ids = $request->get_param('user_id');
		$edited_users = array();

		foreach ( $user_ids as $user_id ) {
			$user = new \WP_User( $user_id );
			if ( $user_model->grant_access( $user, $access_area ) ) {
				$edited_users[] = $user_id;
			}

		}
		$response = new \WP_REST_Response();
		$response->set_data( array( 'success' => true, 'access_area' => $access_area, 'user_id' => $edited_users ) );
		return $response;
	}

	/**
	 *	Grant Access
	 *	@param $request
	 */
	public function revoke_access( $request ) {
		$model = Model\ModelAccessAreas::instance();
		$access_area = $model->fetch_one_by( 'id', $request->get_param('id') );
		$user_ids = $request->get_param('user_id');
		$edited_users = array();

		foreach ( $user_ids as $user_id ) {
			$user = new \WP_User( $user_id );
			if ( $user->has_cap( $access_area->capability ) ) {
				$user->remove_cap( $access_area->capability, true );
				do_action( 'wpaa_revoke_access', $user, $access_area->capability );
				do_action( "wpaa_revoke_{$access_area->capability}", $user );
				$edited_users[] = $user_id;
			}
		}
		$response = new \WP_REST_Response();
		$response->set_data( array( 'success' => true, 'access_area' => $access_area, 'user_id' => $edited_users ) );
		return $response;
	}

}
