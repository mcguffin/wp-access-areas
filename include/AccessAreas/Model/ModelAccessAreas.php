<?php


namespace AccessAreas\Model;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


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

		$data['capability'] = $this->get_capability_name( $title, $blog_id );

		$result = $this->insert( $data, array('%s','%d','%s'));

		if ( $result === false ) {
			return new \WP_Error( 'wpaa-create-failed', __( 'Error creating Access Area', 'wp-access-areas') );
		}

		return $this->fetch_one_by( $data );
	}

	/**
	 *	Get unused capability name
	 *	@param	string	$title
	 *	@param	int		$blog_id
	 *	@return string
	 */
	public function get_capability_name( $title, $blog_id = null ) {
		$prefix = $this->get_capability_prefix( $blog_id );
		$basename = $prefix . $this->sanitize_capability_name( $title );
		$basename = substr( $basename, 0, 60 );
		$cap_name = $basename;
		$counter = 1;
		while ( ! is_null( $this->fetch_one_by('capability', $cap_name ) ) ) {
			$cap_name = "{$basename}-{$counter}";
			$counter++;
		}
		return $cap_name;
	}

	public function get_capability_prefix( $blog_id = null ) {
		if ( is_null( $blog_id ) ) {
			$blog_id = get_current_blog_id();
		}

		$prefix = apply_filters( 'wpaa_capability_prefix', 'wpaa_', $blog_id );

		if ( intval( $blog_id ) > 0 ) {
			$prefix .= "{$blog_id}_";
		}
		return $prefix;
	}

	public function sanitize_capability_name( $title ) {

		$slug = sanitize_title_with_dashes($title,'','save');
		$slug = str_replace('%','',$slug);

		return sanitize_html_class($slug);



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
		$this->update_db();
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
			`title` varchar(255) NOT NULL,
		    `capability` varchar(64) NOT NULL,
		    `blog_id` bigint(20) NOT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `capability` (`capability`),
		    KEY `blog_id` (`blog_id`)
		) $charset_collate;";

		// updates DB
		dbDelta( $sql );
	}
}
