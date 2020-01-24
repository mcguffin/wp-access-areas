<?php
/**
 * @package WP_AccessAreas
 * @version 1.0.0
 */

// ----------------------------------------
// This class provides an interface for editing access areas
// ----------------------------------------

if ( ! class_exists( 'WPAA_Caps' ) ) :

    class WPAA_Caps {


        public static function init() {
            if ( is_admin() ) {
                add_action( 'admin_menu', array( __CLASS__, 'user_menu' ) );
                if ( is_accessareas_active_for_network() ) {
                    add_action( 'network_admin_menu', array( __CLASS__, 'user_menu' ) );
                }
            }
        }


        public static function user_menu() {
            // @ admin_menu
            if ( ( is_network_admin() && ! current_user_can( 'manage_network_users' ) ) || ( ! current_user_can( 'promote_users' ) ) ) {
                return;
            }

            add_users_page( __( 'Manage Access Areas', 'wp-access-areas' ), __( 'Access Areas', 'wp-access-areas' ), 'promote_users', 'user_labels', array( __CLASS__, 'manage_userlabels_page' ) );
            add_action( 'load-users_page_user_labels', array( __CLASS__, 'do_userlabel_actions' ) );
        }

        public static function bulk_edit_access_areas() {
            // Detect when a bulk action is being triggered...
            if ( ! check_ajax_referer( 'bulk-userlabels', '_wpnonce', false ) ) {
                return;
            }
            $action = $this->current_action();

            $ul_ids = array_map( 'intval', $_REQUEST[ 'userlabels' ] );
            $ul_ids = array_filter( $ul_ids );
            if ( -1 !== $action ) {
                switch ( $action ) {
                    case 'delete':
    					foreach ( $ul_ids as $ul_id ) {
                            $ul = WPAA_AccessArea::get_userlabel( intval( $ul_id ) );
    						if ( $ul ) {
    							WPAA_AccessArea::delete_userlabel( intval( $ul_id ) );
    						}
    					}
                        return wp_safe_redirect(
                            add_query_arg(
                                array(
    								'page'    => 'user_labels',
    								'message' => 3,
    								'deleted' => count( $ul_ids ),
    							),
                                admin_url( 'users.php' )
                            )
                        );
                }
            }
        }

        private static function get_action() {
            $action = -1;
            if ( isset( $_REQUEST['action'] ) && intval( $_REQUEST['action'] ) !== -1 ) {
                $action = wp_unslash( $_REQUEST['action'] );
            }
            if ( isset( $_REQUEST['action2'] ) && intval( $_REQUEST['action2'] ) !== -1 ) {
                $action = wp_unslash( $_REQUEST['action2'] );
            }
            return $action;
        }

        public static function do_userlabel_actions() {

            $action = self::get_action();
            if ( $action === -1 ) {
                return;
            }

            $nonce_action = 'userlabel-' . $action;

            if ( $action === 'bulk-delete' ) {
                $nonce_action = 'bulk-userlabels';
            }

            if ( ! check_ajax_referer( $nonce_action, '_wpnonce', false ) ) {
                return;
            }

            if ( ! current_user_can( 'promote_users' ) ) {
                wp_die( esc_html__( 'You do not have permission to do this.', 'wp-access-areas' ) );
            }

            wp_enqueue_style( 'wpaa-admin' );

            $redirect_url = false;

            // do actions
            $data = self::_sanitize_action_input( $_POST );

            if ( is_multisite() && ! $data['blog_id'] && ! current_user_can( 'manage_network_users' ) ) {
                wp_die( esc_html__( 'You do not have permission to edit network wide user labels.', 'wp-access-areas' ) );
            }

            switch ( $action ) {
                case 'new':
					// do create action
                    
                    // integrity check.
                    if ( ! $data['cap_title'] ) {
                        wp_die( esc_html__( 'Please enter a Label.', 'wp-access-areas' ) );
                    }

                    $edit_id = WPAA_AccessArea::create_userlabel( $data );
                    if ( $edit_id ) {
                        $redirect_url = add_query_arg(
                            array(
                                'page'    => 'user_labels',
                                'action'  => 'new',
                                'message' => 1,
                            ),
                            admin_url( 'users.php' )
                        );
                    } else {
                        $redirect_url = add_query_arg(
                            array(
                                'page'      => 'user_labels',
                                'action'    => 'new',
                                'message'   => WPAA_AccessArea::what_went_wrong(),
                                'cap_title' => sanitize_text_field( $data['cap_title'] ),
                            ),
                            admin_url( 'users.php' )
                        );
                    }
                    break;
                case 'edit':
					// update and redirect
					if ( ! empty( $_POST ) ) {
                        $edit_id = WPAA_AccessArea::update_userlabel( $data );
						if ( $edit_id ) {
							$redirect_url = add_query_arg(
                                array(
									'id'      => $edit_id,
									'message' => 2,
								)
                            );
						} else {
							$redirect_url = add_query_arg(
                                array(
									'id'        => $edit_id,
									'message'   => WPAA_AccessArea::what_went_wrong(),
									'cap_title' => sanitize_text_field( $data['cap_title'] ),
								)
                            );
						}
					}

					if ( ! isset( $_GET['id'] ) ) {
						$redirect_url = add_query_arg( array( 'page' => 'user_labels' ), $_SERVER['SCRIPT_NAME'] );
					}

                    break;
                case 'delete':
					// delete and redirect
                    
					if ( isset( $_REQUEST['id'] ) ) {
                        $deleted = WPAA_AccessArea::delete_userlabel( intval( $_REQUEST['id'] ) );
						if ( $deleted ) {
                            $redirect_url = add_query_arg(
                                array(
									'page'    => 'user_labels',
									'message' => 3,
									'deleted' => $deleted,
								),
                                admin_url( 'users.php' )
                            );
						} else {
                            $redirect_url = add_query_arg(
                                array(
									'page'    => 'user_labels',
									'message' => WPAA_AccessArea::what_went_wrong(),
								),
                                admin_url( 'users.php' )
                            );
						}
					}

                    break;
                case 'bulk-delete':
                    $ul_ids = array_map( 'intval', $_REQUEST[ 'userlabels' ] );
                    $ul_ids = array_filter( $ul_ids );
                    foreach ( $ul_ids as $ul_id ) {
                        $ul = WPAA_AccessArea::get_userlabel( intval( $ul_id ) );
                        if ( $ul ) {
                            WPAA_AccessArea::delete_userlabel( intval( $ul_id ) );
                        }
                    }
                    $redirect_url = add_query_arg(
                        array(
                            'page'    => 'user_labels',
                            'message' => 3,
                            'deleted' => count( $ul_ids ),
                        ),
                        admin_url( 'users.php' )
                    );
                    break;
                default:
                    $redirect_url = remove_query_arg( [ 'action', 'action2' ] );
            }
            if ( $redirect_url ) {
                wp_safe_redirect( $redirect_url );
                exit();
            }

        }

        public static function manage_userlabels_page() {
            
            if ( isset( $_REQUEST['action'] ) ) {
                // do actions
                // display forms
                switch ( $_REQUEST['action'] ) {
                    case 'new':
                        return self::edit_userlabels_screen();
                    case 'edit':
                        return self::edit_userlabels_screen( $_GET['id'] );
                }
            }
            return self::list_userlabels_screen();
        }

        public static function edit_userlabels_screen( $userlabel_id = 0 ) {
            global $wpdb;
            if ( $userlabel_id ) {
                $userlabel = WPAA_AccessArea::get_userlabel( $userlabel_id );
            } else {
                $userlabel = (object) array(
					'cap_title' => '',
					'blog_id'   => get_current_blog_id(),
                );
            }
            $cap_title = $userlabel->cap_title;
            if ( ! $cap_title && isset( $_REQUEST['cap_title'] ) ) {
                $cap_title = wp_unslash( $_REQUEST['cap_title'] );
            }

            ?><div class="wrap">
                <div id="icon-undisclosed-userlabel" class="icon32">
                    <br />
                </div>
                <h2>
                    <?php
                        if ( $userlabel_id ) {
						esc_html_e( 'Edit Access Area', 'wp-access-areas' );
                        } else {
						esc_html_e( 'Create Access Area', 'wp-access-areas' );
                        }
                    ?>
                </h2>
                <?php self::_put_message(); ?>
                <form id="create-user-label" method="post" action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>">
                    <!-- Now we can render the completed list table -->
                    <?php if ( $userlabel_id ) { ?>
                        <input type="hidden" name="id" value="<?php echo intval( $userlabel_id ); ?>" />
                    <?php } ?>

                    <?php wp_nonce_field( 'userlabel-' . ( $userlabel_id ? 'edit' : 'new' ) ); ?>
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="title">
                                        <?php esc_html_e( 'Access Area', 'wp-access-areas' ); ?>
                                    </label>
                                </th>
                                <td>
                                    <input class="regular-text" maxlength="64" type="text" name="cap_title" value="<?php esc_attr_e( $cap_title ); ?>" id="cap_title" placeholder="<?php esc_attr_e( 'New Access Area', 'wp-access-areas' ); ?>" autocomplete="off" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <button type="submit" class="button button-primary button-large">
                        <?php
                        if ( $userlabel_id ) {
                            esc_html_e( 'Save changes', 'wp-access-areas' );
                        } else {
                            esc_html_e( 'Create Access Area', 'wp-access-areas' );
                        }

                        ?>
                    </button>
                </form>
            </div>
            <?php
        }

        private static function _put_message() {
            if ( ! isset( $_REQUEST['message'] ) ) {
                return;
            }
            $message = intval( $_REQUEST['message'] );
            $message_wrap = '<div id="message" class="updated"><p>%s</p></div>';
            switch ( $message ) {
                case 1: // created
					$message = __( 'Access Area created.', 'wp-access-areas' );
                    break;
                case 2: // updated
					$message = __( 'Access Area updated.', 'wp-access-areas' );
                    break;
                case 3: // deleted
					$message = sprintf( _n( 'Access Area deleted.', '%d Access Areas deleted.', $_REQUEST['deleted'], 'wp-access-areas' ), $_REQUEST['deleted'] );
                    break;
                case 4: // exists
					$message = __( 'An Access Area with that Name already exists.', 'wp-access-areas' );
                    break;
                case 5: // not found
					$message = __( 'Could not find the specified Access Area.', 'wp-access-areas' );
                    break;
                default:
					$message = '';
                    break;
            }
            if ( $message ) {
                printf( $message_wrap, $message );
            }
        }

        public static function list_userlabels_screen() {

            $listTable = new AccessAreas_List_Table( array() );
            $listTable->prepare_items();
            $add_new_url = remove_query_arg( 'message', add_query_arg( array( 'action' => 'new' ) ) );

            ?>
            <div class="wrap"><h2>
                <?php
                    esc_html_e( 'Manage Access Areas', 'wp-access-areas' );
                ?>
                <a href="<?php echo esc_url( $add_new_url ); ?>" class="add-new-h2">
                    <?php esc_html_e( 'Add New', 'wp-access-areas' ); ?>
                </a>
            </h2>
            <?php self::_put_message(); ?>
            <form id="camera-reservations-filter" method="get">
                <!-- Now we can render the completed list table -->
                <input type="hidden" name="page" value="user_labels" />
                <?php $listTable->display(); ?>
            </form></div><!-- .wrap -->
            <?php

        }

        public static function _sanitize_action_input( $data ) {

            $data = wp_parse_args(
                $data,
                array(
					'cap_title' => '',
					'blog_id'   => 0,
                    'userlabels' => [],
                )
            );
            $data['cap_title'] = trim( strip_tags( $data['cap_title'] ) );
            $data['blog_id']   = is_network_admin() ? 0 : get_current_blog_id();
            $data['userlabels']   = is_network_admin() ? 0 : get_current_blog_id();
            return $data;
        }



    }
endif;
