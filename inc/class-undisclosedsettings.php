<?php
/**
* @package WP_AccessAreas
* @version 1.0.0
*/ 

// ----------------------------------------
//	This class provides an interface for editing access areas
// ----------------------------------------

if ( ! class_exists('UndisclosedSettings' ) ) :

class UndisclosedSettings {
	
	static function init( ) {
		add_option( 'wpaa_default_behavior' , '404' );
		add_option( 'wpaa_fallback_page' , 0 );
		
		add_action( 'admin_menu', array( __CLASS__ , 'create_menu' ));
		add_action( 'admin_init', array( __CLASS__ , 'register_settings' ) );
	}

	static function create_menu() { // @ admin_menu
		add_options_page(__('Access Settings','wpundisclosed'), __('Access Settings','wpundisclosed'), 'promote_users', 'wpaa_settings', array(__CLASS__,'settings_page'));
	}
	static function register_settings() { // @ admin_init
		register_setting( 'wpaa_settings' , 'wpaa_default_behavior', array(__CLASS__,'sanitize_behavior') );
		register_setting( 'wpaa_settings' , 'wpaa_fallback_page' , array(__CLASS__,'sanitize_fallbackpage') );
		add_settings_section('wpaa_main_section', __('Restricted Access Behavior','wpundisclosed'), '__return_false', 'wpaa');
		add_settings_field('wpaa_default_behavior', __('Default Behaviour','wpundisclosed'), array( __CLASS__ , 'select_behavior'), 'wpaa', 'wpaa_main_section');
		add_settings_field('wpaa_fallback_page', __('Default Fallback Page','wpundisclosed'), array( __CLASS__ , 'select_fallback_page'), 'wpaa', 'wpaa_main_section');
	}
	
	static function settings_page() {
		/*
		if ( isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'],'wpaa_settings-options') ) {
			if ( isset( $_POST['wpaa_default_behavior'] ) ) {
				update_option( 'wpaa_default_behavior', $_POST['wpaa_default_behavior'] );
			}
		}*/
		?>
		<div class="wrap">
			<h2><?php _e('Access Areas Settings','wpundisclosed') ?></h2>
			
			<form id="github-options" method="post" action="options.php">
				<?php 
					settings_fields( 'wpaa_settings' );
				?>
				<?php do_settings_sections( 'wpaa' );  ?>
				<p class="small description"><?php _e('You can also set these Options for each post individually.' , 'wpundisclosed' ); ?></p>
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
				
			</form>
		</div>
		<?php
	}
	static function select_behavior() {
		$behavior = get_option('wpaa_default_behavior');
		?><p><?php _e('If somebody tries to view a restricted post directly:' , 'wpundisclosed' ); ?></p><?php
		UndisclosedEditPost::behavior_select( $behavior , 'wpaa_default_behavior' );
	}
	static function sanitize_behavior( $behavior ) {
		return preg_replace('/!^(|page|login)$/','',$behavior);
	}
	static function select_fallback_page(){
		$post_fallback_page = get_option('wpaa_fallback_page');
		UndisclosedEditPost::fallback_page_dropdown( $post_fallback_page , 'wpaa_fallback_page' );
	}
	static function sanitize_fallbackpage($fallback_page_id) {
		$page = get_post( $fallback_page_id );
		if ( $page->post_status != 'publish' || $page->post_type != 'page' || $page->post_view_cap != 'exist' )
			$fallback_page_id = 0;
		return $fallback_page_id;
	}
	
}
UndisclosedSettings::init();
endif;
