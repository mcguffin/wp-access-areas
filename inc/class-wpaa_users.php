<?php
/**
 * @package WP_AccessAreas
 * @version 1.0.0
 */

// ----------------------------------------
// This class provides an UI to assign Userlabels to Users.
// ----------------------------------------

if ( ! class_exists( 'WPAA_Users' ) ) :
    class WPAA_Users {


        public static function init() {
            if ( is_admin() ) {
                add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
                if ( is_accessareas_active_for_network() ) {
                    add_filter( 'wpmu_users_columns', array( __CLASS__, 'add_userlabels_column' ) );
                }
                add_filter( 'manage_users_columns', array( __CLASS__, 'add_userlabels_column' ) );
                add_filter( 'manage_users_custom_column', array( __CLASS__, 'manage_userlabels_column' ), 10, 3 );

                // bulk editing
                add_action( 'restrict_manage_users', array( __CLASS__, 'bulk_grant_access_dropdown' ) );
                add_action( 'restrict_manage_users', array( __CLASS__, 'bulk_revoke_access_dropdown' ) );
                add_action( 'load-users.php', array( __CLASS__, 'bulk_edit_access' ) );
            }
            add_action( 'add_user_to_blog', array( __CLASS__, 'add_user_to_blog' ), 10, 3 );
        }


        // --------------------------------------------------
        // general actions
        // --------------------------------------------------
        public static function admin_init() {
            if ( current_user_can( 'promote_users' ) ) {
                add_action( 'profile_update', array( __CLASS__, 'profile_update' ), 10, 2 );
                add_action( 'edit_user_profile', array( __CLASS__, 'personal_options' ) );
                add_action( 'show_user_profile', array( __CLASS__, 'personal_options' ) );

                // css
                add_action( 'load-users.php', array( __CLASS__, 'load_style' ) );
                add_action( 'load-profile.php', array( __CLASS__, 'load_style' ) );
                add_action( 'load-user-edit.php', array( __CLASS__, 'load_style' ) );

                // js
                add_action( 'load-profile.php', array( __CLASS__, 'load_edit_script' ) );
                add_action( 'load-user-edit.php', array( __CLASS__, 'load_edit_script' ) );

                // ajax
                add_action( 'wp_ajax_add_accessarea', array( __CLASS__, 'ajax_add_access_area' ) );

                add_filter( 'views_users', array( __CLASS__, 'table_views' ) );
            }
            add_filter( 'additional_capabilities_display', '__return_false' );
        }

        public static function load_edit_script() {

            wp_enqueue_script( 'wpaa-admin-user-ajax' );
        }
        public static function load_style() {
            wp_enqueue_style( 'wpaa-admin' );
        }

        // --------------------------------------------------
        // ajax adding access areas
        // --------------------------------------------------
        public static function ajax_add_access_area() {
            // $nonce = isset( $_REQUEST['_wp_ajax_nonce'] ) ?  : '';
            check_ajax_referer( 'userlabel-new' );
            // if ( ! check_ajax_referer( 'userlabel-new' ) ) {
            //     esc_html_e( 'CSRF-Token did not verify.', 'wp-access-areas' );
            //     die();
            // }
            $success    = false;
            $params     = wp_parse_args( $_POST, [
                'blog_id'           => 0,
                'cap_title'         => '',
            ]);
            if ( current_user_can( 'promote_users' ) ) {

                $blog_id    = intval( $params['blog_id'] );
                $cap_title  = esc_html( trim( $params['cap_title'] ) );

                if ( ( ! $blog_id && ! is_super_admin() ) || ( $blog_id && ( $blog_id != get_current_blog_id() ) ) ) {
                    $message = __( 'Insufficient privileges.', 'wp-access-areas' );
                } elseif ( empty( $cap_title ) ) {
                    $message = __( 'Empty name.', 'wp-access-areas' );
                } else {
                    $create_id = WPAA_AccessArea::create_userlabel(
                        array(
							'cap_title' => $cap_title,
							'blog_id'   => $blog_id,
						)
                    );

                    if ( $create_id ) {
                        $label   = WPAA_AccessArea::get_userlabel( $create_id );
                        $success = true;
                        self::_select_label_formitem( $label, true );
                    } else {
                        switch ( WPAA_AccessArea::what_went_wrong() ) {
                            case 4: // Error: area exists
                                $message = __( 'Access Area exists.', 'wp-access-areas' );
                                break;
                        };
                    }
                }
            } else {
                $message = __( 'Insufficient privileges.', 'wp-access-areas' );
                // throw_error: insufficient privileges
            }

            if ( ! $success ) {
                printf(
                    '<span class="wpaa-label-item error dashicons-before dashicons-no">%s</span>',
                    esc_html( $message )
                );
            }

            die();
        }
        // --------------------------------------------------
        // bulk editing
        // --------------------------------------------------
        public static function bulk_grant_access_dropdown( $position ) {

            if ( current_user_can( 'promote_users' ) ) {
                ?></div>
                <?php

                $field_name = 'grant_access_area';

                if ( $position === 'top' ) {
                    wp_nonce_field( 'bulk-assign-access-areas', '_wpaa_nonce', true );
                } else {
                    $field_name .= '-bottom';
                }

                ?>
                <div class="alignleft actions">
                <?php

                self::_label_select_all( $field_name, __( 'Grant Access … ', 'wp-access-areas' ), true );
                submit_button( __( 'Grant', 'wp-access-areas' ), 'button', 'grantit', false );
            }
        }
        public static function bulk_revoke_access_dropdown( $position ) {
            if ( current_user_can( 'promote_users' ) ) {
                ?>
                </div><div class="alignleft actions">
                <?php

                $field_name = 'revoke_access_area';

                if ( $position === 'bottom' ) {
                    $field_name .= '-bottom';
                }

                self::_label_select_all( $field_name, __( 'Revoke Access … ', 'wp-access-areas' ), true );
                submit_button( __( 'Revoke', 'wp-access-areas' ), 'button', 'revokeit', false );
            }
        }

        public static function bulk_edit_access() {

            if ( ! check_ajax_referer( 'bulk-assign-access-areas', '_wpaa_nonce', false ) ) {
                return;
            }

            if ( ! current_user_can( 'promote_users' ) ) {
                wp_die( esc_html__( 'You can&#8217;t edit that user.', 'wp-access-areas' ) );
            }

            $params = wp_parse_args( $_REQUEST, [
                'grant_access_area' => '',
                'revoke_access_area' => '',
                'grant_access_area-bottom' => '',
                'revoke_access_area-bottom' => '',
                'grantit'  => false,
                'revokeit'  => false,
                'users'     => [],
            ] );
            if ( empty( $params['grant_access_area'] ) ) {
                $params['grant_access_area'] = $params['grant_access_area-bottom'];
            }
            if ( empty( $params['revoke_access_area'] ) ) {
                $params['revoke_access_area'] = $params['revoke_access_area-bottom'];
            }

            $params['grant_access_area'] = sanitize_key( $params['grant_access_area'] );
            $params['revoke_access_area'] = sanitize_key( $params['revoke_access_area'] );
            $params['users'] = array_map( 'intval', $params['users'] );
            $params['users'] = array_filter( $params['users'] );
            $is_grant = $params['grant_access_area'] && $params['grantit'];
            $is_revoke = $params['revoke_access_area'] && $params['revokeit'];

            if ( $is_grant && ! wpaa_access_area_exists( $params['grant_access_area'] ) ||
                $is_revoke && ! wpaa_access_area_exists( $params['revoke_access_area'] )
            ) {
                wp_die( esc_html__( 'Access Area does not exist.', 'wp-access-areas' ) );
            }

            if ( $is_grant || $is_revoke ) {

                foreach ( $params['users'] as $user_id ) {
                    $user = new WP_User( $user_id );
                    if ( $is_grant ) {
                        self::_set_cap_for_user( $params['grant_access_area'], $user, true );
                    } else {
                        self::_set_cap_for_user( $params['revoke_access_area'], $user, false );
                    }
                }

                wp_safe_redirect( add_query_arg( 'update', 'promote', 'users.php' ) );
                exit();
            }
        }

        // --------------------------------------------------
        // user editing
        // --------------------------------------------------
        public static function profile_update( $user_id, $old_user_data ) {

            if ( ! isset( $_POST['_wpaa_nonce'] ) ) {
                return;
            }

            if ( ! check_admin_referer( 'update-userlabels', '_wpaa_nonce' ) ) {
                wp_die( esc_html__( 'You do not have permission to do this.', 'wp-access-areas' ) );
            }

            if ( ! current_user_can( 'promote_users' ) ) {
                return;
            }

            $label_data = false;

            if ( isset( $_POST['userlabels'] ) && is_array( $_POST['userlabels'] ) ) {
                $userlabels = array_map( 'boolval', wp_unslash( $_POST['userlabels'] ) );
                $label_data = array_combine(
                    array_map( 'intval', array_keys( wp_unslash( $userlabels ) ) ),
                    array_values( wp_unslash( $userlabels ) )
                );
            }

            // sanitize
            global $wpdb;

            if ( is_multisite() ) {
                $count_blogs = get_sites([
                    'public' => 1,
                    'count' => true,
                ]);
                $blogids = get_sites([
                    'fields' => 'ids',
                    'public' => 1,
                    'number' => $count_blogs,
                ]);
                $current_blog_id = get_current_blog_id();
            }

            $user = new WP_User( $user_id );
            $global_label_data = array();

            foreach ( $label_data as $label_id => $add ) {
                $label = WPAA_AccessArea::get_userlabel( $label_id );
                if ( is_multisite() && ! $label->blog_id ) { // global
                    if ( $add ) {
                        $global_label_data[] = $label->capability;
                    }
                    foreach ( $blogids as $blog_id ) {
                        if ( is_user_member_of_blog( $user_id, $blog_id ) ) {
                            switch_to_blog( $blog_id );
                            $user->for_site( $blog_id );
                            self::_set_cap_for_user( $label->capability, $user, $add );
                        }
                    }
                    restore_current_blog();
                } else { // local or single page
                    if ( is_multisite() ) {
                        switch_to_blog( $current_blog_id );
                        $user->for_site( $current_blog_id );
                    }
                    self::_set_cap_for_user( $label->capability, $user, $add );
                }
            }
            if ( is_multisite() ) {
                update_user_meta( $user_id, WPUND_GLOBAL_USERMETA_KEY, $global_label_data );
                switch_to_blog( $current_blog_id );
                $user->for_site( $current_blog_id );
            }
        }

        public static function add_user_to_blog( $user_id, $role, $blog_id ) {
            switch_to_blog( $blog_id );
            $label_caps = get_user_meta( $user_id, WPUND_GLOBAL_USERMETA_KEY, true );
            if ( ! $label_caps ) {
                return;
            }
            $user = new WP_User( $user_id );
            foreach ( $label_caps as $cap ) {
                self::_set_cap_for_user( $cap, $user, true );
            }
            restore_current_blog();
        }


        private static function _set_cap_for_user( $capability, &$user, $add ) {
            // prevent blogadmin from granting network permissions he does not own himself.
            $network   = ! wpaa_is_local_cap( $capability );
            $can_grant = current_user_can( $capability ) || ! $network;
            $has_cap   = $user->has_cap( $capability );
            $is_change = ( $add && ! $has_cap ) || ( ! $add && $has_cap );
            if ( $is_change ) {
                if ( ! $can_grant ) {
                    wp_die( esc_html__( 'You do not have permission to do this.', 'wp-access-areas' ) );
                }
                if ( $add ) {
                    $user->add_cap( $capability, true );
                    do_action( 'wpaa_grant_access', $user, $capability );
                    do_action( "wpaa_grant_{$capability}", $user );
                } elseif ( ! $add ) {
                    $user->remove_cap( $capability );
                    do_action( 'wpaa_revoke_access', $user, $capability );
                    do_action( "wpaa_revoke_{$capability}", $user );
                }
            }
        }
        public static function personal_options( $profileuser ) {
            // IS_PROFILE_PAGE : self or other
            if ( ! current_user_can( 'promote_users' ) || ( is_network_admin() && ! is_accessareas_active_for_network() ) ) {
                return;
            }

            $labels = WPAA_AccessArea::get_available_userlabels();

            ?>
            <h3><?php esc_html_e( 'Access Areas', 'wp-access-areas' ); ?></h3><table class="form-table" id="wpaa-group-items">
            <?php

            wp_nonce_field( 'update-userlabels', '_wpaa_nonce' );

            $labelrows = array();
            // wtf happens on single install?
            if ( ! is_network_admin() ) {
				$labelrows[ __( 'Grant Access', 'wp-access-areas' ) ] = array(
					'network'      => false,
					'labels'       => WPAA_AccessArea::get_blog_userlabels(),
					'can_ajax_add' => current_user_can( 'promote_users' ),
				);
            }
            if ( ( is_network_admin() || is_super_admin() ) && is_accessareas_active_for_network() ) {
				$labelrows[ __( 'Grant Network-Wide Access', 'wp-access-areas' ) ] = array(
					'network'      => true,
					'labels'       => WPAA_AccessArea::get_network_userlabels(),
					'can_ajax_add' => is_network_admin() || is_super_admin(),
				);
            }
            foreach ( $labelrows as $row_title => $value ) {

                $network = $value['network'];
                $labels = $value['labels'];
                $can_ajax_add = $value['can_ajax_add'];

				?>
                <tr class="wpaa-section">
                    <th>
                    <?php

                    printf(
                        '<span class="dashicons-before dashicons-admin-%s">%s</span>',
                        sanitize_html_class( $network ? 'site' : 'home' ), esc_html( $row_title )
                    );

                    ?>
                    </th>
                    <td>
                    <?php
                    foreach ( $labels as $label ) {
                        $can_grant    = current_user_can( $label->capability ) || ! $network;
                        $user_has_cap = $profileuser->has_cap( $label->capability );
                        self::_select_label_formitem( $label, $user_has_cap, $can_grant );
    				}

                    if ( $can_ajax_add ) {
                        self::_ajax_add_area_formitem( $network ? 0 : get_current_blog_id() );
    				}

                    ?>
                    </td>
                </tr>
                <?php

            }
            ?>
            </table>
            <?php
        }

        private static function _select_label_formitem( $label, $checked, $enabled = true ) {

            $item_class    = array( '' );

            $name = sprintf( 'userlabels[%d]', $label->ID );
            $id = sprintf( 'cap-%s', sanitize_key( $label->capability ) )

            ?>
            <span class="wpaa-label-item <?php echo $enabled ? ' disabled' : ''; ?>">
                <?php
                printf(
                    '<input type="hidden" id="%s-hidden" name="%s" value="0" />',
                    esc_attr( $id ),
                    esc_attr( $name )
                );

                printf(
                    '<input type="checkbox" id="%s" name="%s" value="1" %s %s />',
                    esc_attr( $id ),
                    esc_attr( $name ),
                    disabled( ! $enabled, true ),
                    checked( $checked, true, false )
                );

                printf(
                    '<label for="%s">%s</label>',
                    esc_attr( $id ),
                    esc_html__( $label->cap_title )
                );

            ?>
            </span>
            <?php
        }

        private static function _ajax_add_area_formitem( $blog_id ) {
            ?>
            <span class="wpaa-label-item ajax-add-item">
                <?php wp_nonce_field( 'userlabel-new', '_ajax_nonce' ); ?>
                <input type="hidden" name="blog_id" value="<?php echo intval( $blog_id ); ?>" />
                <input class="cap-add" type="text" name="cap_title" placeholder="<?php esc_attr_e( 'Add New', 'wp-access-areas' ); ?>" />
                <button href="#" class="cap-add-submit button" disabled data-nonce="<?php esc_attr_e( wp_create_nonce( 'userlabel-new' ) ); ?>">
                    <span class=" dashicons dashicons-plus"></span>
                    <span class="screen-reader-text">
                        <?php esc_html_e( 'Add New', 'wp-access-areas' ); ?>
                    </span>
                </button>
            </span>
            <?php
        }




        // --------------------------------------------------
        // user admin list view
        // --------------------------------------------------

        public static function table_views( $views ) {
            global $role;

            $current_label = $role;
            $ret  = '';
            $ret .= self::_listtable_label_select( WPAA_AccessArea::get_blog_userlabels(), $current_label );
            if ( is_accessareas_active_for_network() ) {
                $ret .= self::_listtable_label_select( WPAA_AccessArea::get_network_userlabels(), $current_label, true );
            }
            if ( $ret ) {
                $views['labels'] = '<strong>' . __( 'Access Areas:', 'wp-access-areas' ) . ' </strong>' . $ret;
            }
            return $views;
        }

        private static function _listtable_label_select( $labels, $current_label, $global = false ) {
            if ( ! count( $labels ) ) {
                return '';
            }
            $slug          = $global ? 'netowrk' : 'local';
            $ret           = '';
            $ret          .= sprintf( '<form class="wpaa-access-area dashicons-before dashicons-admin-%s select-accessarea-form" method="get">', $global ? 'site' : 'home' );
            $ret          .= sprintf( '<label for="select-accessarea-%s">', $slug );
            $ret          .= sprintf( '<select id="select-accessarea-%s" onchange="this.form.submit()" name="role">', $slug );
            $ret          .= sprintf( '<option value="%s">%s</option>', '', __( '&mdash; Select &mdash;', 'wp-access-areas' ) );
            $ret          .= self::_label_select_options( $labels, $current_label );
            $ret          .= '</select>';
            $ret          .= '</label>';
            $ret          .= '</form>';
            return $ret;
        }

        private static function _label_select_all( $name, $first_element_label = false, $echo = false ) {
            $network = is_accessareas_active_for_network();
            $ret     = '';
            $ret    .= '<select name="' . $name . '">';

            if ( $first_element_label !== false ) {
                $ret .= sprintf( '<option value="">%s</option>', $first_element_label );
            }

            if ( $network ) {
                $ret .= sprintf( '<optgroup label="%s">', esc_html__( 'Local', 'wp-access-areas' ) );
            }
            $ret .= self::_label_select_options( WPAA_AccessArea::get_blog_userlabels() );
            if ( $network ) {
                $ret .= '</optgroup>';

                $ret .= sprintf( '<optgroup label="%s">', esc_html__( 'Network', 'wp-access-areas' ) );
                $ret .= self::_label_select_options( WPAA_AccessArea::get_network_userlabels() );
                $ret .= '</optgroup>';
            }
            $ret .= '</select>';
            if ( $echo ) {
                echo wp_kses($ret, [
                    'select'    => [
                        'name' => [],
                    ],
                    'option'    => [
                        'value' => [],
                        'selected' => [],
                    ],
                    'optgroup'    => [
                        'label' => [],
                    ],
                ]);
            }
            return $ret;
        }

        private static function _label_select_options( $labels, $current_label = false ) {
            $ret = '';
            foreach ( $labels as $label ) {
                $ret .= sprintf( '<option %s value="%s">%s</option>', selected( $current_label, $label->capability, false ), $label->capability, $label->cap_title );
            }
            return $ret;
        }

        // --------------------------------------------------
        // user admin list columns
        // --------------------------------------------------
        public static function add_userlabels_column( $columns ) {

            $columns['access'] = __( 'Access Areas', 'wp-access-areas' );
            return $columns;
        }
        public static function manage_userlabels_column( $column_content, $column, $user_ID ) {
            if ( $column != 'access' ) {
                return $column_content;
            }

            $ugroups = array();

            $labels = WPAA_AccessArea::get_available_userlabels();
            $user   = new WP_User( $user_ID );
            if ( ( is_multisite() && is_super_admin( $user_ID ) ) || ( ! is_multisite() && $user->has_cap( 'administrator' ) ) ) {
                return WPAA_Template::access_area( __( 'Everywhere', 'wp-access-areas' ), true );
            }

            foreach ( $labels as $label ) {
                if ( $user->has_cap( $label->capability ) ) {
                    $ugroups[] = WPAA_Template::access_area( $label->cap_title, ! $label->blog_id );
                }
            }
            if ( count( $ugroups ) ) {
                return '<div class="wpaa-labels">' . implode( "", $ugroups ) . '</div>';
            }

            return '';
        }
    }
endif;

