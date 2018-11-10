<?php


namespace AccessAreas\Model;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use AccessAreas\Core;


class ModelAccessAreas extends Model {

	protected $fields = array(
		'id'			=> '%d',
		'title'			=> '%s',
		'capability'	=> '%s',
		'blog_id'		=> '%d',
	);


	/**
	 *	@inheritdoc
	 */
	protected $_table = 'access_areas';

	/**
	 *	@inheritdoc
	 */
	protected $_global_table = true;

	/**
	 *	Create an access area
	 *	@param	string	$title
	 *	@param	int		$blog_id
	 *	@return object|WP_Error
	 */
	public function create( $title, $blog_id = null ) {

		$title = sanitize_text_field( $title );

		if ( is_null( $blog_id ) ) {
			$blog_id = get_current_blog_id();
		}

		$data = array(
			'title'			=> $title,
			'blog_id'		=> $blog_id,
		);

		if ( $result = $this->fetch_one_by( $data ) ) {
			return new \WP_Error( 'wpaa-create-failed', __( 'Access Area exists', 'wp-access-areas') );
		}
		$cap = $this->new_capability_name( $title, $blog_id );

		/**
		 *	Prevent attacks like:
		 *		add_filter('wpaa_create_capability_prefix','__return_empty_string') + self::create('Manage Network')
		 *		add_filter('wpaa_create_capability_prefix',function(){return 'manage'}) + self::create('Network')
		 */
		if ( false === $cap ) {
			return new \WP_Error( 'wpaa-create-failed', __( 'Invalid capability name!', 'wp-access-areas') );
		}

		$data['capability'] = $cap;

		$result = $this->insert( $data, array('%s','%d','%s'));

		if ( $result === false ) {
			return new \WP_Error( 'wpaa-create-failed', __( 'Error creating Access Area', 'wp-access-areas') );
		}

		return $this->fetch_one_by( $data );
	}

	/**
	 *	@param int $id
	 */
	public function delete_by_id( $id ) {
		do_action( 'wpaa_before_delete', $id );
		return parent::delete( array('id' => $id ) );
	}

	/**
	 *	Get unused capability name
	 *	@param	string	$title
	 *	@param	int		$blog_id
	 *	@return string
	 */
	public function new_capability_name( $title, $blog_id = null ) {
		$prefix = $this->get_capability_prefix( $blog_id );
		$core = Core\Core::instance();
		// force plugin prefix!
		if ( empty( $prefix ) || false === strpos( $prefix, $core->get_prefix() ) ) {
			return false;
		}
		$basename = $prefix . $this->sanitize_capability_name( $title );
		$basename = substr( $basename, 0, 60 );
		$cap_name = $basename;
		$counter = 1;
		while ( ! is_null( $this->fetch_one_by( 'capability', $cap_name ) ) ) {
			$cap_name = "{$basename}-{$counter}";
			$counter++;
		}
		return $cap_name;
	}

	/**
	 *	@param int $blog_id
	 *	@return string
	 */
	public function get_capability_prefix( $blog_id = null ) {
		if ( is_null( $blog_id ) ) {
			$blog_id = get_current_blog_id();
		}
		$core = Core\Core::instance();
		$prefix = sprintf( '%s%s_', $core->get_prefix(), $blog_id );
		$prefix = apply_filters( 'wpaa_create_capability_prefix', $prefix, $blog_id );

		return $prefix;
	}

	/**
	 *	@param string $title
	 *	@return string
	 */
	public function sanitize_capability_name( $title ) {

		$slug = sanitize_title_with_dashes( $title, '', 'save' );
		$slug = str_replace( '%', '', $slug );

		return sanitize_html_class( $slug );
	}

	/**
	 *	Fetch available access areas.
	 *	@param string	$context	user|post
	 *	@return array
	 */
	public function fetch_list( $key = false, $field = false ) {

		$blog_id = get_current_blog_id();
		$cache_key = "available_access_areas_{$key}_{$field}_{$blog_id}";
		$cache_group = 'wpaa';

		if ( ! ( $access_areas = wp_cache_get( $cache_key, $cache_group ) ) ) {

			$condition = array(
				'blog_id'	=> $blog_id,
			);

			$access_areas = $this->fetch_by( $condition );
			$access_areas = $this->setup_list( $access_areas, $key, $field );


			wp_cache_set( $cache_key, $access_areas, $cache_group );
		}
		return $access_areas;
	}

	/**
	 *	@return assoc
	 */
	public function get_access_options() {

		$roles_key =__( 'WordPress Roles', 'wp_access_areas');

		$wpaa_key = __( 'Local Access Areas', 'wp_access_areas');

		// gather intel
		$access = array();
		$access[ 'exist' ] = __( 'WordPress default', 'wp-access-areas' );
		$access[ 'read' ] = __( 'Logged in Users', 'wp-access-areas' );
		$access[ $roles_key ] = wp_roles()->get_names();
		$access[ $wpaa_key ] = $this->fetch_list( 'capability', 'title' );

		$access = apply_filters( 'wpaa_access_options', $access );

		return $access;
	}


	/**
	 *	@return assoc
	 */
	public function get_grant_options() {

		// gather intel
		$access = $this->fetch_list( 'id' );

		$access = apply_filters( 'wpaa_grant_options', $access );

		return $access;
	}




	/**
	 *	@inheritdoc
	 */
	public function activate() {
		// create table
		$this->update_db();
	}

	/**
	 *	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {

		if ( version_compare( $old_version, '2.0.0', '<' ) ) {
			$this->upgrade_1x();
		}

	}

	private function upgrade_1x() {
		global $wpdb;

		$old_table = $wpdb->base_prefix . 'disclosure_userlabels';
		$new_table = $wpdb->access_areas;

		$has_old = $wpdb->get_var("SHOW TABLES LIKE '{$old_table}'") === $old_table;
		$has_new = $wpdb->get_var("SHOW TABLES LIKE '{$new_table}'") === $new_table;
		$has_old_data = $has_old && $wpdb->get_var("SELECT COUNT(ID) FROM {$old_table}") > 0;
		$has_new_data = $has_new && $wpdb->get_var("SELECT COUNT(ID) FROM {$new_table}") > 0;

		if ( $has_old && ! $has_new ) {
			// rename
			$sql = "ALTER TABLE {$old_table} RENAME TO {$new_table}";
			$wpdb->query($sql);

			$sql = "ALTER TABLE {$new_table} RENAME COLUMN `cap_title` TO `title`";
			$wpdb->query($sql);
		} else if ( $has_old && $has_new ) {
			// move data, delete old
			$all_wpaa = $wpdb->get_results( "SELECT * FROM {$old_table}" );
			foreach ( $all_wpaa as $result ) {
				if ( 0 === intval( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$new_table} WHERE capability = %s", $result->capability ) ) ) ) {
					$data = array(
						'title'			=> $result->cap_title,
						'capability'	=> $result->capability,
						'blog_id'		=> $result->blog_id,
					);
					$this->insert( $data, array('%s','%s','%d') );
				}
			}

		}

	}

	/**
	 *	@inheritdoc
	 */
	private function update_db() {
		global $wpdb, $charset_collate;

		/*
		UPGRADE 2.0
			rename table wp_disclosure_userlabels > $this->table
			rename column cap_title > title

		*/

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$sql = "CREATE TABLE $wpdb->access_areas (
			`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`created` DATETIME DEFAULT CURRENT_TIMESTAMP,
			`title` varchar(255) NOT NULL,
		    `capability` varchar(128) NOT NULL,
		    `blog_id` bigint(20) NOT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `capability` (`capability`),
		    KEY `blog_id` (`blog_id`)
		) $charset_collate;";

		// updates DB
		dbDelta( $sql );
	}
}
