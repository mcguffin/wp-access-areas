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
		add_action( 'admin_init' , array( __CLASS__ , 'admin_init' ) );
		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__ , 'user_menu' ));
			add_action( 'network_admin_menu', array( __CLASS__ , 'user_menu' ));
		}
	}

	static function admin_init() {
	}





	static function user_menu() { // @ admin_menu
		if ( (is_network_admin() && ! current_user_can( 'manage_network_users' )) || ( ! current_user_can( 'promote_users' ) ) )
			return;

		// 'users.php' or 'profile.php'
		
		// add_submenu_page( 'users.php' , __('Manage User-Labels','wpundisclosed'), __('User-Labels','wpundisclosed'), 'promote_users', 'user_labels', array(__CLASS__,'manage_userlabels_page') );
		// add_users_page(              $page_title, $menu_title, $capability, $menu_slug, $function);
		add_users_page(__('Manage User-Labels','wpundisclosed'), __('User-Labels','wpundisclosed'), 'promote_users', 'user_labels', array(__CLASS__,'manage_userlabels_page'));
		add_action( 'load-users_page_user_labels' , array( __CLASS__ , 'do_userlabel_actions' ) );
		add_action( 'load-users_page_user_labels' , array( __CLASS__ , 'load_style' ) );
	}
	static function load_style() {
		wp_enqueue_style( 'disclosure-admin' );
	}
	
	static function do_userlabel_actions() {
		if ( ! current_user_can( 'promote_users' ) ) 
			wp_die( __('You do not have permission to do this.' , 'wpundisclosed' ) );
		$table = new UserLabel_List_Table();
		$table->process_bulk_action();
			
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
						return wp_redirect(add_query_arg(array('page'=>'user_labels' , 'message' => 1 ),$_SERVER['SCRIPT_NAME']));
					break;
				case 'edit':
					// update and redirect
					if ( ! empty( $_POST ) && $edit_id = UndisclosedUserlabel::update_userlabel( $data ) ) 
						return wp_redirect( add_query_arg( array('id' => $edit_id , 'message'=>2 ) ) );
					
					if ( ! isset( $_GET['id'] ) ) 
						return wp_redirect( add_query_arg(array('page'=>'user_labels' ),$_SERVER['SCRIPT_NAME']) );
						
					break;
				case 'delete':
					// delete and redirect
					if ( false && isset( $_GET['id'] ) && UndisclosedUserlabel::get_userlabel( $_GET['id'] ) ) 
						UndisclosedUserlabel::delete_userlabel( $_GET['id'] );
					return wp_redirect( add_query_arg(array('page'=>'user_labels' , 'message'=>3 , 'deleted' => 1 ),$_SERVER['SCRIPT_NAME']) );
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
		?><div id="icon-undisclosed-userlabel" class="icon32"><br></div><?php
		?><h2><?php
			if ( $userlabel_id ) { 
				_e('Edit User-Label','wpundisclosed');
			} else {
				_e('Create User-Label','wpundisclosed');
			}
		?></h2>
		<?php self::_put_message( ) ?>
		<?php
			?><form id="create-user-label" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
				<!-- Now we can render the completed list table -->
			<?php if ( $userlabel_id ) { ?>
				<input type="hidden" name="id" value="<?php echo $userlabel_id ?>" />
			<?php } ?>

			<?php wp_nonce_field( 'userlabel-'.(  $userlabel_id  ? 'edit' : 'new' ) ) ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><label for="title"><?php _e('User-Label','wpundisclosed') ?></label></th>
							<td><input class="regular-text" maxlength="64" type="text" name="cap_title" value="<?php echo $userlabel->cap_title ?>" id="cap_title" placeholder="<?php _e('New User-Label','wpundisclosed') ?>" autocomplete="off" /></td>
						</tr>
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
	private static function _put_message( ) {
		if ( ! isset( $_REQUEST['message'] ) )
			return;
			
		$message_wrap = '<div id="message" class="updated"><p>%s<p></div>';
		switch( $_REQUEST['message'] ) {
			case 1: // created
				$message = __('User-label created.','wpundisclosed');
				break;
			case 2: // updated
				$message = __('User-label updated.','wpundisclosed');
				break;
			case 3: // deleted
				$message = sprintf(_n('User-label deleted.' , '%d User-labels deleted.' , $_REQUEST['deleted'] , 'wpundisclosed') , $_REQUEST['deleted'] );
				break;
			default:
				$message = '';
				break;
		}
		if ( $message )
			printf( $message_wrap , $message );
	}
	
	static function list_userlabels_screen() {
		$listTable = new UserLabel_List_Table( array() );
		$listTable->prepare_items();


		?><div class="wrap"><?php
		?><div id="icon-undisclosed-userlabel" class="icon32"><br></div><?php
		?><h2><?php _e('Manage User-Labels','wpundisclosed') ?>
			<a href="<?php echo remove_query_arg('message',add_query_arg(array('action'=>'new'))) ?>" class="add-new-h2"><?php _ex('Add New','userlabel','wpundisclosed') ?></a>
		</h2>
		<?php self::_put_message( ) ?>
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
			'blog_id'	=> 0,
		) );
		$data['cap_title'] = trim(strip_tags( $data['cap_title'] ));
		$data['blog_id'] = is_network_admin() ? 0 : get_current_blog_id();
		return $data;
	}
	
	

}
UndisclosedCaps::init();
endif;
