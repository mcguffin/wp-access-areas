<?php
/**
 * @package WP_AccessAreas
 * @version 1.0.0
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
    include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}


// ----------------------------------------
// Defines the List view table for Access Areas in the backend.
// ----------------------------------------
if ( ! class_exists( 'AccessAreas_List_Table' ) ) :
    class AccessAreas_List_Table extends WP_List_Table {


        public function __construct( $args = array() ) {
            parent::__construct(
                array(
					'singular' => 'userlabel',     // singular name of the listed records
					'plural'   => 'userlabels',    // plural name of the listed records
					'ajax'     => false,        // does this table support ajax?
                )
            );
        }
        public function column_cb( $item ) {
            if ( ( is_network_admin() ^ $item->blog_id ) ) {
                return sprintf(
                    '<input type="checkbox" name="%1$s[]" value="%2$s" />',
                    /*$1%s*/ $this->_args['plural'],
                    /*$2%s*/ $item->ID
                );
            }
            return '';
        }

        public function get_columns() {
            $columns = array(
				'cb'        => '<input type="checkbox" />', // Render a checkbox instead of text
				'cap_title' => __( 'Name', 'wp-access-areas' ),
            );
            if ( is_multisite() ) {
                $columns['blog'] = __( 'Scope', 'wp-access-areas' );
            }

            $columns['capability'] = __( 'WP Capability', 'wp-access-areas' );
            return $columns;
        }
        public function get_sortable_columns() {
            $columns = array(
				'cap_title' => array( 'cap_title', false ),
				'blog'      => array( 'blog_id', false ),
            );
            return $columns;
        }
        public function column_default( $item, $column_name ) {
            $output = isset( $item->$column_name ) ? $item->$column_name : '';
            switch ( $column_name ) {
                case 'cap_title':
					$ret = WPAA_Template::access_area( $output, ! $item->blog_id );
					if ( is_network_admin() ^ $item->blog_id ) {
						$url = add_query_arg(
                            array(
								'action' => 'edit',
								'id'     => $item->ID,
							)
                        );
						$url = remove_query_arg( 'message', $url );
						$url = remove_query_arg( 'deleted', $url );
						$ret = sprintf( '<a href="%s">%s</a>', $url, $ret );

						$del_url = add_query_arg(
                            array(
								'action'   => 'delete',
								'id'       => $item->ID,
								'_wpnonce' => wp_create_nonce( 'userlabel-delete' ),
							)
                        );
						$del_url = remove_query_arg( 'message', $del_url );
						$del_url = remove_query_arg( 'deleted', $del_url );
						$ret    .= sprintf( '<br /><div class="row-actions"><span class="remove"><a href="%s" class="submitdelete">%s</a></span></div>', $del_url, __( 'Delete', 'wp-access-areas' ) );
					}
                    return $ret;
                case 'capability':
                    return "<code>$output</code>";
                case 'blog':
                    return $item->blog_id ? get_blog_details( $item->blog_id, true )->siteurl : __( 'Network', 'wp-access-areas' );
                case 'blog_id':
                    return $output;
                case 'actions':
					if ( ( is_network_admin() ^ $item->blog_id ) ) {
						$url = add_query_arg(
                            array(
								'action'   => 'delete',
								'id'       => $item->ID,
								'_wpnonce' => wp_create_nonce( 'userlabel-delete' ),
							)
                        );
						$url = remove_query_arg( 'message', $url );
						$url = remove_query_arg( 'deleted', $url );
						return sprintf( '<a href="%s" class="button">%s</button>', $url, __( 'Delete', 'wp-access-areas' ) );
					}
                    return '';
            }
        }

        public function prepare_items() {
            global $wpdb; // This is used only if making any database queries

            $current_page = $this->get_pagenum();
            $per_page     = 25;
            $limit        = ( ( $current_page - 1 ) * $per_page ) . ",$per_page";
            $total_items  = WPAA_AccessArea::get_count_available_userlabels();

            $columns  = $this->get_columns();
            $hidden   = array();
            $sortable = $this->get_sortable_columns();

            $this->_column_headers = array( $columns, $hidden, $sortable );

            // $this->process_bulk_action();

            $order = 'ASC';
            $orderby = 'cap_title';
            $order_sql = 'blog_id DESC,cap_title ASC';

            if ( isset( $_REQUEST['orderby'] ) ) {
    			$orderby = wp_unslash( $_REQUEST['orderby'] );
    		}

    		if ( isset( $_REQUEST['order'] ) ) {
    			$order = wp_unslash( $_REQUEST['order'] );
    		}

            $order_sql = sanitize_sql_orderby( "{$orderby} {$order}" );

            // use wpdb here!
            $data = WPAA_AccessArea::get_available_userlabels( $limit, $order_sql );

            $this->items = $data;
            /**
             * REQUIRED. We also have to register our pagination options & calculations.
             */
            $this->set_pagination_args(
                array(
					'total_items' => $total_items,                  // WE have to calculate the total number of items
					'per_page'    => $per_page,                     // WE have to determine how many items to show on a page
					'total_pages' => ceil( $total_items / $per_page ),   // WE have to calculate the total number of pages
                )
            );
        }

        public function get_bulk_actions_shortname( $action ) {
            return ucwords( $this->get_bulk_actions_statusname( $action ) );
        }

        public function get_bulk_actions() {
            $actions = array(
				'bulk-delete' => __( 'Delete', 'wp-access-areas' ),
            );
            return $actions;
        }
    }
endif;
