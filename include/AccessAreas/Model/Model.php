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
	 *	@var bool is global table
	 */
	protected $_global_table = false;

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		// setup wpdb
		global $wpdb;
		if ( $this->_global_table ) {
			$wpdb->global_tables[] = $this->table;
			$wpdb->set_prefix( $wpdb->base_prefix );
		} else {
			$wpdb->tables[] = $this->table;
			$wpdb->set_blog_id( get_current_blog_id() );
		}
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
	 *	Setup a list
	 *
	 *	@param $list array
	 *	@param $key 	bool|string	item property to use as key. use false for numeric
	 *	@param $field	bool|string	item property to use as value. use false to fetch objects
	 *	@return array
	 */
	public function setup_list( $list, $key = false, $field = false ) {
		$out = array();
		foreach ( array_values($list) as $i => $item ) {
			if ( is_object( $item ) ) {
				$out[ $key ? $item->$key : $i ] = $field ? $item->$field : $item;
			} else if ( is_array( $item ) ) {
				$out[ $key ? $item[$key] : $i ] = $field ? $item[ $field ] : $item;
			}
		}
		return $out;
	}

	/**
	 *	Fetch results
	 *
	 *	@param	string 	$field
	 *	@param	mixed	$value
	 *	@return	null|object
	 */
	public function fetch_all( $limit, $page ) {
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
	 *	Fetch one result
	 *
	 *	@param	string|array	$fields
	 *	@param	mixed			$value
	 *	@return	null|object
	 */
	public function fetch_one_by( $fields, $value = null ) {
		global $wpdb;

		$table = $this->table;

		$where_sql = $this->get_where_sql( $fields, $value );

		foreach ( $wpdb->get_results( "SELECT * FROM {$wpdb->$table} $where_sql LIMIT 1" ) as $result ) {
			return $result;
		};
		return null;
	}

	/**
	 *	Fetch results
	 *
	 *	@param	string|array	$fields
	 *	@param	mixed			$value
	 *	@return	null|object
	 */
	public function fetch_by( $fields, $value = null ) {
		global $wpdb;
		$table = $this->table;

		$where_sql = $this->get_where_sql( $fields, $value );

		return $wpdb->get_results( "SELECT * FROM {$wpdb->$table} {$where_sql}" );
	}

	/**
	 *	Get WHERE clause from cols
	 *
	 *	@param	string|array	$fields
	 *	@param	mixed			$value
	 *	@return	string
	 */
	private function get_where_sql( $fields, $value = null ) {
		global $wpdb;

		if ( ! is_array( $fields ) && ! is_null( $value ) ) {
			$fields = array( $fields => $value );
		}

		$where_sql = array();

		foreach ( $fields as $col => $val ) {

			if ( ! isset( $this->fields[$col] ) ) {
				continue;
			}

			$format = $this->fields[$col];

			if ( is_array( $val ) && count( $val ) === 1 ) {
				$val = array_pop( $val );
			}

			if ( is_scalar( $val ) ) {
				$where_sql[] = $wpdb->prepare("$col = $format", $val );
			} else if ( is_array( $val ) ) {
				$formats = array_fill( 0, count($val), $format );
				$formats = implode(',',$formats);
				$where_sql[] = $wpdb->prepare("$col IN ($formats)", $val );
			}
		}

		return count( $where_sql ) ? 'WHERE ' . implode(' AND ', $where_sql ) : '';

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

}
