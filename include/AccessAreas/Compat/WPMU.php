<?php

namespace AccessAreas\Compat;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use AccessAreas\Core;


class WPMU extends Core\PluginComponent {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

	}

	/**
	 *	@inheritdoc
	 */
	 public function activate() {

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
		 // iterate blogs, alter posts table
	 }

	/**
 	 *	@inheritdoc
	 */
	public function upgrade( $new_version, $old_version ) {
	}

}
