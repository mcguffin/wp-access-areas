<?php


namespace AccessAreas\Model;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


class ModelAccessAreas extends Model {

	protected $fields = array(
		'ID'	=> '%d',
//		'title'	=> '%s',
//		...
	);


	/**
	 *	@inheritdoc
	 */
	protected $_table = 'access_areas';

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
			`ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`title` varchar(64) NOT NULL,
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
