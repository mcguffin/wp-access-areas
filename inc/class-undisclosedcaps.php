<?php
/**
* @package WPUndisclosed
* @version 1.0
*/

// ----------------------------------------
//	This class provides an interface for editing the permission groups
// ----------------------------------------

if ( ! class_exists('UndisclosedCaps' ) ) :

class UndisclosedCaps {
	
	static function init( ) {
		if ( is_admin() ) {
			add_action( 'admin_init' , array( __CLASS__ , 'admin_init' ) );
			add_action( 'admin_menu', array( __CLASS__ , 'user_menu' ));
		}
	}

	static function admin_init() {

	}





	static function user_menu() { // @ admin_menu
		add_users_page(__('Manage User-Labels','wpundisclosed'), __('User-Labels','wpundisclosed'), 'promote_users', 'user_labels', array(__CLASS__,'manage_userlabels_page'));
		add_action( 'load-users_page_user_labels' , array( __CLASS__ , 'do_userlabel_actions' ) );
	}
	static function do_userlabel_actions() {
		if ( ! current_user_can( 'promote_users' ) ) 
			wp_die( __('You do not have permission to do this.' , 'wpundisclosed' ) );

		if (isset($_REQUEST['action'])) {
			// do actions
			$data = self::_sanitize_userlabel_data( $_POST );
			
			// integrity check.
			if ( !empty($_POST) && ! $data['cap_title'] ) 
				wp_die( __('Please enter a Label.' , 'wpundisclosed' ) );
			if (( ! empty( $_POST ) && ! wp_verify_nonce(@$_REQUEST['_wpnonce'],'userlabel-'.$_REQUEST['action'] ) ) ||
			 		( ! $data['blog_id'] && ! current_user_can('manage_network_users') ))
				wp_die( __('You do not have permission to edit network wide user labels.' , 'wpundisclosed' ) );
			
			switch ( $_REQUEST['action'] ) {
				case 'new':
					// do create action
					if ( ! empty( $_POST ) && $edit_id = UndisclosedUserlabel::create_userlabel( $data ) ) 
						return wp_redirect( add_query_arg(array( 'action'=>'edit' , 'id' => $edit_id ) ) );
					break;
				case 'edit':
					// update and redirect
					if ( ! empty( $_POST ) && $edit_id = UndisclosedUserlabel::update_userlabel( $data ) ) 
						wp_redirect( add_query_arg( array('id' => $edit_id ) ) );
					
					if ( ! isset( $_GET['id'] ) ) 
						wp_redirect( remove_query_arg('action') );
						
					break;
				default:
					wp_redirect( remove_query_arg('action') );
			}
		}
		// create and redirect
		$data;
	}
	static function manage_userlabels_page( ) {
		if (isset($_REQUEST['action'])) {
			// do actions
			// display forms
			switch ( $_REQUEST['action'] ) {
				case 'new':
					return self::edit_userlabels_screen(  );
				case 'edit':
					return self::edit_userlabels_screen( $_GET['id'] );
			}
		}
		return self::list_userlabels_screen();
	}
	
	static function edit_userlabels_screen( $userlabel_id = 0 ) {
		global $wpdb;
		if ( $userlabel_id ) 
			$userlabel = UndisclosedUserlabel::get_userlabel( $userlabel_id );
		else
			$userlabel = (object) array(
				'cap_title' => '',
				'blog_id'	=> get_current_blog_id(),
			) ;
		
		?><div class="wrap"><?php
		?><div id="icon-users" class="icon32"><br></div><?php
		?><h2><?php
			if ( $userlabel_id ) { 
				_e('Edit User-Label','wpundisclosed');
			} else {
				_e('Create User-Label','wpundisclosed');
			}
		 ?></h2>
		<?php
			?><form id="create-user-label" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
				<!-- Now we can render the completed list table -->
			<?php if ( $userlabel_id ) { ?>
			<input type="hidden" name="id" value="<?php echo $userlabel_id ?>" />
			<?php } ?>
			
			<input type="hidden" name="blog_id" value="<?php echo get_current_blog_id() ?>" />
			<?php wp_nonce_field( 'userlabel-'.(  $userlabel_id  ? 'edit' : 'new' ) ) ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><label for="title"><?php _e('User-Label','wpundisclosed') ?></label></th>
							<td><input class="regular-text" maxlength="64" type="text" name="cap_title" value="<?php echo $userlabel->cap_title ?>" id="cap_title" placeholder="<?php _e('New User-Label','wpundisclosed') ?>" autocomplete="off" /></td>
						</tr>
					<?php if ( current_user_can('manage_network_users') ) { ?>
						<tr>
							<th scope="row"><?php _e('Availability','wpundisclosed') ?></th>
							<td>
								<input type="checkbox" name="blog_id" id="blog_id" value="0" <?php checked( $userlabel->blog_id , 0 ) ?> />
								<label for="blog_id"><?php _e( 'Network wide available' , 'wpundisclosed' ) ?></label>
							</td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
				
				<button type="submit" class="button button-primary button-large"><?php 
			if ( $userlabel_id ) { 
				_e('Save changes','wpundisclosed');
			}  else {
				_e( 'Create User-Label' , 'wpundisclosed' );
			}
				
				
				?></button>
			</form><?php
		?></div><?php
		
	}
	
	
	static function list_userlabels_screen() {
		$listTable = new UserLabel_List_Table( array() );
		$listTable->prepare_items();


		?><div class="wrap"><?php
		?><div id="icon-users" class="icon32"><br></div><?php
		?><h2><?php _e('Manage User-Labels','wpundisclosed') ?>
			<a href="<?php echo add_query_arg(array('action'=>'new')) ?>" class="add-new-h2"><?php _ex('Add New','userlabel','wpundisclosed') ?></a>
		</h2>
		<?php

			?><form id="camera-reservations-filter" method="get">
				<!-- Now we can render the completed list table -->
				<input type="hidden" name="page" value="user_labels" />
				<?php $listTable->display() ?>
			</form><?php
		
		

		?></div><?php

	}
	static function _sanitize_userlabel_data( $data ) {
		global $wpdb;
		$data = wp_parse_args( $data , array(
			'cap_title' => '',
			'blog_id'	=> get_current_blog_id(),
		) );
		$data['cap_title'] = trim(strip_tags( $data['cap_title'] ));
		$data['blog_id'] = intval( $data['blog_id'] );
		return $data;
	}
	
	

}
UndisclosedCaps::init();
endif;
