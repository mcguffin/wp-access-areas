<?php
/**
* @package RevisionRequest
* @version 1.0
*/



// ----------------------------------------
//	This class initializes the RevisionRequest plugin.
//	(As of version 1.0 it only loads an apropriate plugin textdomain for translation readyness.)
// ----------------------------------------


if ( ! class_exists('RevisionRequestCore') ) :
class RevisionRequestCore {
	static function init() {
		add_action( 'plugins_loaded' , array( __CLASS__, 'plugin_loaded' ) );
	}
	
	// translation ready.
	static function plugin_loaded() {
		load_plugin_textdomain( 'revisionrequest' , false, dirname(dirname( plugin_basename( __FILE__ ))) . '/lang');
	}
}
RevisionRequestCore::init();
endif;

?>