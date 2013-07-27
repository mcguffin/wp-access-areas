<?php
/**
* @package WP_AccessAreas
* @version 1.0.0
*/ 

if ( ! class_exists('WP_List_Table') ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


// ----------------------------------------
//	Defines the List view table for Access Areas in the backend.
// ----------------------------------------
if ( ! class_exists( 'UserLabel_List_Table' ) ) :
class UserLabel_List_Table extends WP_List_Table {
	
	function __construct( $args = array() ) {
		extract(wp_parse_args(
			$args,
			array( ) 
		));
		parent::__construct( array(
            'singular'  => 'userlabel',     //singular name of the listed records
            'plural'    => 'userlabels',    //plural name of the listed records
            'ajax'      => false,        //does this table support ajax?
        ) );
	}
	function column_cb($item) {
		if ( ( is_network_admin() ^ $item->blog_id ) )
			return sprintf(
				'<input type="checkbox" name="%1$s[]" value="%2$s" />',
				/*$1%s*/ $this->_args['plural'],
				/*$2%s*/ $item->ID
			);
		return '';
	}

    function get_columns() {
    	$columns = array(
			'cb'			=> '<input type="checkbox" />', //Render a checkbox instead of text
			'cap_title'		=> __('Name','wpundisclosed'),
    	);
    	if ( is_multisite() )
    		$columns['blog'] =  __('Scope','wpundisclosed');
    	
    	return $columns;
    }
    function get_sortable_columns() {
    	$columns = array(
			'cap_title'		=> array('cap_title',false),
			'blog'		=> array('blog_id',false),
    	);
    	return $columns;
    }
    function column_default( $item, $column_name ) {
    	$output = isset($item->$column_name) ? $item->$column_name : '';
        switch($column_name) {
        	case 'cap_title':
				if ( ! $item->blog_id ) 
					$ret = '<span title="Network" class="icon-undisclosed-network icon16"></span>';
				else 
					$ret = '<span title="Local" class="icon-undisclosed-local icon16"></span>';

        		if ( is_network_admin() ^ $item->blog_id ) {
					$url = add_query_arg( array( 'action'=>'edit','id'=>$item->ID ) );
					$url = remove_query_arg('message',$url);
					$url = remove_query_arg('deleted',$url);
					$ret .= sprintf( '<strong><a href="%s">%s</a></strong>' , $url , $output );
					
					$del_url = add_query_arg( array('action'=>'delete','id'=>$item->ID,'_wpnonce'=>wp_create_nonce('userlabel-delete')) );
					$del_url = remove_query_arg('message',$url);
					$del_url = remove_query_arg('deleted',$url);
					$ret .= sprintf('<br /><div class="row-actions"><span class="remove"><a href="%s" class="submitdelete">%s</a></span></div>',$url,__('Delete'));
					return $ret;
					if ( ( is_network_admin() ^ $item->blog_id ) ) {
					}
				} else {
					return $ret.$output;
				}
        	case 'capability':
        		return $output;
        	case 'blog':
        		return $item->blog_id ? get_blog_details( $item->blog_id , true )->siteurl : __('Network','wpundisclosed');
        	case 'blog_id':
        		return $output;
        	case 'actions':
        		if ( ( is_network_admin() ^ $item->blog_id ) ) {
					$url = add_query_arg( array('action'=>'delete','id'=>$item->ID,'_wpnonce'=>wp_create_nonce('userlabel-delete')) );
					$url = remove_query_arg('message',$url);
					$url = remove_query_arg('deleted',$url);
					return sprintf('<a href="%s" class="button">%s</button>',$url,__('Delete'));
				} else {
					return '';
				}
		}
	}
	function prepare_items() {
		global $wpdb; //This is used only if making any database queries
		$table_name = $wpdb->base_prefix . WPUND_USERLABEL_TABLE;
		
		$current_page = $this->get_pagenum();
		$per_page = 25;
		$limit = (($current_page-1)*$per_page).",$per_page";
		$total_items = UndisclosedUserlabel::get_count_available_userlabels( );
		
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		
		$this->_column_headers = array($columns, $hidden, $sortable);

		$this->process_bulk_action();

		$order = isset($_GET['orderby']) ? $_GET['orderby'] : null;
		if ( ! is_null( $order) ) {
			if ( isset($_GET['order']) )
				$order .= ' '.$_GET['order'];
			else 
				$order .= ' ASC'; 
		}
		// use wpdb here!
		$data = UndisclosedUserlabel::get_available_userlabels( $limit , $order );

		
		
		
		
		$this->items = $data;
		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args( array(
			'total_items' => $total_items,                  //WE have to calculate the total number of items
			'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
			'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
		) );
	}

	function get_bulk_actions_shortname($action) {
		return ucwords($this->get_bulk_actions_statusname($action));
	}
	function get_bulk_actions() {
		$actions = array(
			'delete'    => __('Delete'),
		);
		return $actions;
	}
	function process_bulk_action() {
		//Detect when a bulk action is being triggered...
		$action = $this->current_action();
		$nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : false;
		if ( !$nonce )
			return;
		if ( -1 !== $action && wp_verify_nonce($nonce,'bulk-'.$this->_args['plural'] ) ) {
			switch ($action) {
				case 'delete':
					foreach ($_REQUEST[$this->_args['plural']] as $ul_id)
						if ( UndisclosedUserlabel::get_userlabel( intval($ul_id) ) )
							UndisclosedUserlabel::delete_userlabel( intval($ul_id) );
					return wp_redirect( add_query_arg( array('page'=>'user_labels' , 'message'=>3 , 'deleted' => count($_REQUEST[$this->_args['plural']]) ) , $_SERVER['SCRIPT_NAME'] ) );
				default:
					
			}
		}
	}
}
endif;

?>