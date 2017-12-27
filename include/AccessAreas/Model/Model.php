<?php


namespace AccessAreas\Model;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use AccessAreas\Core;

abstract class Model extends Core\PluginComponent {

	/**
	 *	@var string table name for model
	 */
	protected $_table = null;

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		// setup wpdb
		global $wpdb;
		$wpdb->tables[] = $this->table;
		$wpdb->set_blog_id( get_current_blog_id() );

		parent::__construct();
	}

	/**
	 *	magic getter
	 */
	public function __get( $what ) {
		if ( $what === 'table' ) {
			return $this->_table;
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
	public function uninstall() {
		// drop table
		global $wpdb;
		$tbl = $this->table;
		$wpdb->query("DROP TABLE {$wpdb->$tbl}");

	}

	/**
	 *	Fetch one result
	 *
	 *	@param	string 		$field
	 *	@param	string|int	$value
	 *	@return	null|object
	 */
	public function fetch_one_by( $field, $value ) {
		global $wpdb;
		$table = $this->table;
		// check fields
		if ( $field == 'id' ) {
			$field = 'ID';
		}
		if ( ! isset( $this->fields[$field] ) ) {
			return null;
		}
		$format = $this->fields[$field];

		foreach ( $wpdb->get_results( $wpdb->prepare("SELECT * FROM $wpdb->$table WHERE $field = $format LIMIT 1", $value ) ) as $result ) {
			return $result;
		};
		return null;
	}

	/**
	 *	Fetch results
	 *
	 *	@param	string 	$field
	 *	@param	mixed	$value
	 *	@return	null|object
	 */
	public function fetch_by( $field, $value ) {
		global $wpdb;
		$table = $this->table;
		// check fields
		if ( $field == 'id' ) {
			$field = 'ID';
		}
		if ( ! isset( $this->fields[$field] ) ) {
			return null;
		}

		$format = $this->fields[$field];

		return $wpdb->get_results( $wpdb->prepare("SELECT * FROM $wpdb->$table WHERE $field = $format", $value ) );
	}


	/**
	 *	WPDB Wrapper
	 *
	 *	@param	array 		$data
	 *	@param	null|array	$format
	 *	@return	int|false
	 */
	public function insert( $data, $format = null ) {
		global $wpdb;
		$table = $this->table;
		return $wpdb->insert( $wpdb->$table, $data, $format );
	}

	/**
	 *	WPDB Wrapper
	 *
	 *	@param	array 		$data
	 *	@param	array 		$where
	 *	@param	null|array	$format
	 *	@param	null|array	$where_format
	 *	@return	int|false
	 */
	public function update( $data, $where, $format = null, $where_format = null ) {
		global $wpdb;
		$table = $this->table;
		return $wpdb->update( $wpdb->$table, $data, $where, $format, $where_format );
	}

	/**
	 *	WPDB Wrapper
	 *
	 *	@param	array 		$data
	 *	@param	null|array	$format
	 *	@return	int|false
	 */
	public function replace( $data, $format = null ) {
		global $wpdb;
		$table = $this->table;
		return $wpdb->replace( $wpdb->$table, $data, $format );
	}

	/**
	 *	WPDB Wrapper
	 *
	 *	@param	array 		$where
	 *	@param	null|array	$where_format
	 *	@return	int|false
	 */
	public function delete( $where, $where_format = null ) {
		global $wpdb;
		$table = $this->table;
		return $wpdb->delete( $wpdb->$table, $where, $where_format );
	}


}
