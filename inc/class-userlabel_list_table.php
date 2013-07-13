<?php

if ( ! class_exists('WP_List_Table') ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

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
	function column_cb($item){
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['plural'],  //Let's simply repurpose the table's singular label
			/*$2%s*/ $item->ID                //The value of the checkbox should be the record's id
		);
	}

    function get_columns() {
    	$columns = array(
			'cb'			=> '<input type="checkbox" />', //Render a checkbox instead of text
			'cap_title'		=> __('Name','wpundisclosed'),
//			'capability'	=> __('Capability','wpundisclosed'),
			'blog'		=> __('Blog','wpundisclosed'),
    	);
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
        		$url = add_query_arg( array( 'action'=>'edit','id'=>$item->ID ) );
        		return sprintf( '<a href="%s">%s</a>' , $url , $output );
        	case 'capability':
        		return $output;
        	case 'blog':
        		return $item->blog_id ? get_blog_details( $item->blog_id , true )->siteurl : __('(Network)','wpundisclosed');
        		$output;
        	case 'blog_id':
        		return $output;
		}
	}
	function prepare_items() {
		global $wpdb; //This is used only if making any database queries
		$table_name = $wpdb->base_prefix . WPUND_USERLABEL_TABLE;

		$per_page = 25;

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		
		$this->_column_headers = array($columns, $hidden, $sortable);

		$this->process_bulk_action();

		// use wpdb here!
		$data = UndisclosedUserlabel::get_available_userlabels();
		$current_page = $this->get_pagenum();
		$total_items = count($data);
		
		
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
		if ( -1 !== $action && wp_verify_nonce(@$_REQUEST['_wpnonce'],'bulk-'.$this->_args['plural'] ) ) {
			switch ($action) {
				case 'delete':
					foreach ($_REQUEST[$this->_args['plural']] as $ul_id)
						UndisclosedUserlabel::delete_userlabel( $ul_id );
					break;
			}
		}
	}
}
endif;

?>