<?php
/**
 * @package WP_AccessAreas
 * @version 1.0.0
 */

// ----------------------------------------
// This class provides an interface for editing access areas
// ----------------------------------------

if ( ! class_exists( 'WPAA_Settings' ) ) :

    class WPAA_Settings {

        private static $role_caps;

        public static function init() {
            global $wp_post_statuses;
            self::$role_caps = array(
				'wpaa_set_view_cap'    => __( 'Change View Access', 'wp-access-areas' ),
				'wpaa_set_edit_cap'    => __( 'Change Edit Access', 'wp-access-areas' ),
				'wpaa_set_comment_cap' => __( 'Change Comment Access', 'wp-access-areas' ),
            );
            add_option( 'wpaa_default_behavior', '404' );
            add_option( 'wpaa_fallback_page', 0 );
            add_option( 'wpaa_default_caps', array() );
            add_option( 'wpaa_default_post_status', 'publish' );
            add_option( 'wpaa_enable_assign_cap', 0 );

            add_action( 'update_option_wpaa_enable_assign_cap', array( __CLASS__, 'enable_assign_cap' ), 10, 2 );
            add_filter( 'pre_update_option_wpaa_enable_assign_cap', array( __CLASS__, 'assign_role_cap' ), 10 );

            add_action( 'admin_menu', array( __CLASS__, 'create_menu' ) );
            add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

            add_action( 'load-settings_page_wpaa_settings', array( __CLASS__, 'load_style' ) );

            add_action( 'admin_notices', array( __CLASS__, 'selftest' ) );

            if ( isset( $_GET['action'] ) && wp_unslash( $_GET['action'] ) === 'wpaa-selfrepair' ) {
                add_action( 'admin_init', array( __CLASS__, 'selfrepair' ) );
            }
        }

        public static function selftest() {
            if ( current_user_can( 'manage_options' ) ) {
                global $wpdb;
                $result           = $wpdb->get_results( "SHOW COLUMNS FROM $wpdb->posts" );
                $view_cap_okay    = false;
                $edit_cap_okay    = false;
                $comment_cap_okay = false;
                foreach ( $result as $col ) {
                    if ( $col->Field == 'post_view_cap' ) {
                        $view_cap_okay = true;
                    } elseif ( $col->Field == 'post_edit_cap' ) {
                        $edit_cap_okay = true;
                    } elseif ( $col->Field == 'post_comment_cap' ) {
                        $comment_cap_okay = true;
                    }
                }
                if ( ! ( $view_cap_okay && $edit_cap_okay && $comment_cap_okay ) ) {
                    ?><div class="updated settings-error error">
                        <p>
                            <strong>
                                <?php
                                    esc_html_e( 'WP Access Areas:', 'wp-access-areas' )
                                ?>
                            </strong>
                            <?php
                                esc_html_e( 'Something looks wrong with your Posts table.', 'wp-access-areas' );
                            ?>
                            <br />
                            <?php
                            $repair_url = add_query_arg(
                                array(
                                    '_wpnonce'  => wp_create_nonce( 'wpaa-selfrepair' ),
                                    'action' => 'wpaa-selfrepair',
                                )
                            );
                            ?>
                            <a class="button button-secondary" href="<?php esc_attr_e( $repair_url ); ?>">
                                <?php esc_html_e( 'Please fix it for me', 'wp-access-areas' ); ?>
                            </a>
                        </p>
                    </div>
                    <?php
                }
            }
        }

        public static function selfrepair() {
            if ( check_admin_referer( 'wpaa-selfrepair' ) ) {
                WPAA_Install::install_posts_table();
                $redirect_url = remove_query_arg( array( '_wpnonce', 'action' ) );
                wp_safe_redirect( $redirect_url );
                exit();
            }
        }
        public static function load_style() {
            wp_enqueue_style( 'wpaa-admin' );
        }
        public static function enable_assign_cap( $old_value, $new_value ) {
            if ( $new_value && ! $old_value ) {
                // check if admin/editor/author
                $admin_role = get_role( 'administrator' );
                if ( ! $admin_role->has_cap( 'wpaa_set_view_cap' )
                    || ! $admin_role->has_cap( 'wpaa_set_edit_cap' )
                    || ! $admin_role->has_cap( 'wpaa_set_comment_cap' )
                ) {
                    WPAA_Install::install_role_caps();
                }
            }
        }
        public static function assign_role_cap( $value ) {

            check_admin_referer( "wpaa_settings-options" );

            if ( current_user_can( 'promote_users' ) ) {

                wp_roles()->use_db = true;

                $input = wp_unslash( $_POST );

                $input = wp_parse_args( $input, [
                    'grant_cap' => [],
                    'revoke_cap' => [],
                ] );

                $input['grant_cap'] = array_map( 'sanitize_key', $input['grant_cap'] );
                $input['revoke_cap'] = array_map( 'sanitize_key', $input['revoke_cap'] );

                foreach ( $input['grant_cap'] as $role_slug => $cap ) {
                    if ( 'administrator' != $role_slug && array_key_exists( $cap, self::$role_caps ) ) {
                        $role = get_role( $role_slug );
                        if ( ! $role || $role->has_cap( $cap ) ) {
                            continue;
                        }
                        $role->add_cap( $cap );
                    }
                }

                foreach ( $input['revoke_cap'] as $role_slug => $cap ) {

                    if ( 'administrator' != $role_slug && array_key_exists( $cap, self::$role_caps ) ) {
                        $role = get_role( $role_slug );

                        if ( ! $role || ! $role->has_cap( $cap ) ) {
                            continue;
                        }

                        $role->remove_cap( $cap );

                    }
                }
            }
            return $value;
        }
        public static function get_post_stati() {
            $stati = array();
            foreach ( get_post_stati() as $post_status ) {
                if ( $post_status !== 'future' && ( $status_obj = get_post_status_object( $post_status ) ) && $status_obj->internal === false ) {
                    $stati[ $post_status ] = $status_obj;
                }
            }
            return apply_filters( 'wpaa_allowed_post_stati', $stati );
        }
        public static function create_menu() {
            // @ admin_menu
            add_options_page( __( 'Access Settings', 'wp-access-areas' ), __( 'Access Settings', 'wp-access-areas' ), 'promote_users', 'wpaa_settings', array( __CLASS__, 'settings_page' ) );
        }
        public static function register_settings() {
            // @ admin_init

            register_setting( 'wpaa_settings', 'wpaa_default_behavior', array( __CLASS__, 'sanitize_behavior' ) );
            register_setting( 'wpaa_settings', 'wpaa_fallback_page', array( __CLASS__, 'sanitize_fallbackpage' ) );
            register_setting( 'wpaa_settings', 'wpaa_default_post_status', array( __CLASS__, 'sanitize_poststatus' ) );
            register_setting( 'wpaa_settings', 'wpaa_default_caps', array( __CLASS__, 'sanitize_access_caps' ) );
            register_setting( 'wpaa_settings', 'wpaa_enable_assign_cap', 'intval' );

            add_settings_section( 'wpaa_main_section', __( 'Restricted Access Behavior', 'wp-access-areas' ), array( __CLASS__, 'main_section_intro' ), 'wpaa' );

            add_settings_field( 'wpaa_default_behavior', __( 'Default Behaviour', 'wp-access-areas' ), array( __CLASS__, 'select_behavior' ), 'wpaa', 'wpaa_main_section' );
            add_settings_field( 'wpaa_fallback_page', __( 'Default Fallback Page', 'wp-access-areas' ), array( __CLASS__, 'select_fallback_page' ), 'wpaa', 'wpaa_main_section' );

            add_settings_section( 'wpaa_post_access_section', __( 'Access Defaults for new Posts', 'wp-access-areas' ), array( __CLASS__, 'post_access_section_intro' ), 'wpaa' );
            add_settings_field( 'wpaa_default_caps', __( 'Default Access:', 'wp-access-areas' ), array( __CLASS__, 'select_default_caps' ), 'wpaa', 'wpaa_post_access_section' );

            add_settings_section( 'wpaa_posts_section', __( 'Posts defaults', 'wp-access-areas' ), '__return_false', 'wpaa' );
            add_settings_field( 'wpaa_default_post_status', __( 'Default Post Status', 'wp-access-areas' ), array( __CLASS__, 'select_post_status' ), 'wpaa', 'wpaa_posts_section' );
            add_settings_field( 'wpaa_enable_assign_cap', __( 'Role Capabilities', 'wp-access-areas' ), array( __CLASS__, 'set_enable_capability' ), 'wpaa', 'wpaa_posts_section' );
        }
        public static function main_section_intro() {
            ?>
            <p class="small description"><?php esc_html_e( 'You can also set these Options for each post individually.', 'wp-access-areas' ); ?></p>
            <?php
        }
        public static function post_access_section_intro() {
            ?>
            <p class="small description"><?php esc_html_e( 'Default settings for newly created posts.', 'wp-access-areas' ); ?></p>
            <?php
        }
        public static function settings_page() {

            ?>
            <div class="wrap">
                <h2><?php esc_html_e( 'Access Areas Settings', 'wp-access-areas' ); ?></h2>
                
                <form id="wpaa-options" method="post" action="options.php">
                    <?php
                        settings_fields( 'wpaa_settings' );
                        do_settings_sections( 'wpaa' );
                    ?>
                    <input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'wp-access-areas' ); ?>" />
                </form>
            </div>
            <?php
        }
        public static function select_default_caps() {
            $option_values = get_option( 'wpaa_default_caps' );
            $post_types    = get_post_types(
                array(
					'show_ui' => true,
                )
            );

            global $wp_roles;
            $roles          = $wp_roles->get_names();
            $user_role_caps = wpaa_get_user_role_caps();
            $rolenames      = array();
            $edit_rolenames = array();
            foreach ( $roles as $role => $rolename ) {
                $rolenames[ $role ] = $rolename;
            }

            $groups = WPAA_AccessArea::get_label_array();
            ?>
            <table class="wp-list-table widefat set-default-caps">
            <?php
            foreach ( array( 'thead', 'tfoot' ) as $tag ) {
				?>
                <<?php echo $tag; ?>>
                    <tr>
                        <th class="manage-column">
                            <?php esc_html_e( 'Post Type', 'wp-access-areas' ); ?>
                        </th>
                        <th class="manage-column">
                            <span class=" dashicons-before dashicons-visibility"></span>
                            <?php esc_html_e( 'Reading', 'wp-access-areas' ); ?>
                        </th>
                        <th class="manage-column">
                            <span class=" dashicons-before dashicons-edit"></span>
                            <?php esc_html_e( 'Edit', 'wp-access-areas' ); ?>
                        </th>
                        <th class="manage-column">
                            <span class=" dashicons-before dashicons-admin-comments"></span>
                            <?php esc_html_e( 'Post Comment', 'wp-access-areas' ); ?>
                        </th>
                    </tr>
                </<?php echo $tag; ?>>
                <?php
            }
            ?>
            <tbody>
                <?php
                $alternate = false;
                foreach ( $post_types as $post_type ) {
                    $post_type_object = get_post_type_object( $post_type );
                    $editing_cap      = $post_type_object->cap->edit_posts;

                    $alternate      = ! $alternate;
                    $edit_rolenames = array();
                    foreach ( $roles as $role => $rolename ) {
                        if ( get_role( $role )->has_cap( $editing_cap ) ) {
                            $edit_rolenames[ $role ] = $rolename;
                        }
                	}
    				?>
                    <tr class="post-select <?php echo $alternate ? 'alternate' : ''; ?>">
                        <th>
    						<?php esc_html_e( $post_type_object->labels->name ); ?>
                        </th>
                        <td>
    						<?php
    						$action = 'post_view_cap';
    						$cap    = isset( $option_values[ $post_type ][ $action ] ) ? $option_values[ $post_type ][ $action ] : 'exist';
    						if ( $post_type != 'attachment' && ( $post_type_object->public || $post_type_object->show_ui ) ) {
                                WPAA_Template::access_area_dropdown(
                                    $roles,
                                    $groups,
                                    wpaa_sanitize_access_cap( $cap ),
                                    "wpaa_default_caps[$post_type][$action]"
                                );
    						}
    						?>
                        </td>
                        <td>
    						<?php
    						$action = 'post_edit_cap';
    						$cap    = isset( $option_values[ $post_type ][ $action ] ) ? $option_values[ $post_type ][ $action ] : 'exist';

                            WPAA_Template::access_area_dropdown(
    							$edit_rolenames,
    							$groups,
    							wpaa_sanitize_access_cap( $cap ),
    							"wpaa_default_caps[$post_type][$action]"
    						);
    						?>
                        </td>
                        <td>
        					<?php
                            $action = 'post_comment_cap';
                            $cap    = isset( $option_values[ $post_type ][ $action ] ) ? $option_values[ $post_type ][ $action ] : 'exist';
        					if ( post_type_supports( $post_type, 'comments' ) ) {
                                WPAA_Template::access_area_dropdown(
                                    $roles,
                                    $groups,
                                    wpaa_sanitize_access_cap( $cap ),
                                    "wpaa_default_caps[$post_type][$action]"
                                );
                            }
        					?>
                        </td>
                    </tr>
    				<?php
                }
                ?>
                </tbody>
            </table>
            <?php
        }

        public static function set_enable_capability() {
            $enabled = get_option( 'wpaa_enable_assign_cap' );

            ?>
            <input type="hidden" name="wpaa_enable_assign_cap" value="<?php echo intval( $enabled ); ?>" />
            <?php
                if ( $enabled ) {
				$roles = get_editable_roles();
				?>
                    <p class="description">
				<?php esc_html_e( 'This table shows which Roles are allowed to set the ‘Who can view’, ‘Who can edit’ and ‘Who can comment’ properties.', 'wp-access-areas' ); ?>
                    </p>
                    <table class="wp-list-table widefat set-default-caps">
                        <thead>
                            <tr>
                                <th class="manage-column">
				<?php esc_html_e( 'Role', 'wp-access-areas' ); ?>
                                </th>
				<?php
				foreach ( self::$role_caps as $cap => $label ) {
					?>
                                    <th class="manage-column">
						<?php esc_html_e( $label ); ?>
                                        <br />
                                        <code><small><?php esc_html_e( $cap ); ?></small></code>
                                    </th>
						<?php
						}
				?>
                            </tr>
                        </thead>
                        <tbody>
				<?php

				$alternate = false;
				foreach ( $roles as $role_slug => $role_details ) {
					$role = get_role( $role_slug );
					if ( $role->has_cap( 'edit_posts' ) || $role->has_cap( 'edit_pages' ) ) {
						$alternate = ! $alternate;
						$row_classes = [ 'role-select' ];
						if ( $alternate ) {
							$row_classes[] = 'alternate';
							}
						?>
                                <tr class="role-select <?php echo $alternate ? 'alternate' : ''; ?>">
                                    <th>
        								<?php
        								esc_html_e( translate_user_role( $role_details['name'] ) );
        								?>
                                    </th>
								<?php
								foreach ( array_keys( self::$role_caps ) as $cap ) {
									?>
                                        <td>
    										<?php

    										if ( $role->has_cap( $cap ) ) {
    											?>
                                                    <button <?php echo $role_slug == 'administrator' ? 'disabled' : ''; ?> name="revoke_cap[<?php esc_attr_e( $role_slug ); ?>]" value="<?php esc_attr_e( $cap ); ?>" type="submit" class="button-secondary" />
    												<?php esc_attr_e( 'Forbid', 'wp-access-areas' ); ?>
                                                    </button>
                                                <?php
                                            } else {
                                                ?>
                                                <button name="grant_cap[<?php esc_attr_e( $role_slug ); ?>]" value="<?php esc_attr_e( $cap ); ?>" type="submit" class="button-primary" />
                                                    <?php esc_html_e( 'Allow', 'wp-access-areas' ); ?>
                                                </button>
                                                <?php
                                            }

    										?>
                                        </td>
                                        <?php
                                    }
								?>
                                </tr>
                                <?php
                            }
					}
				?>
                    </tbody>
                </table>
                <p class="description">
				<?php
				echo wp_kses_post(__( 'If you are running a role editor plugin such as <a href="https://wordpress.org/plugins/user-role-editor/">User Role editor by Vladimir Garagulya</a> or <a href="https://wordpress.org/plugins/wpfront-user-role-editor/">WPFront User Role Editor by Syam Mohan</a> you can do the same as here by assigning the custom capabilites <code>wpaa_set_view_cap</code>, <code>wpaa_set_edit_cap</code> and <code>wpaa_set_comment_cap</code>.', 'wp-access-areas' ));
				?>
                </p>
                <p class="description">
				<?php
				esc_html_e( 'By disabling the role capabilities feature you will allow everybody who can at least publish a post to edit the access properties as well.', 'wp-access-areas' );
				?>
                </p>
                <button name="wpaa_enable_assign_cap" value="0" type="submit" class="button-secondary" />
				<?php esc_html_e( 'Disable Role Capabilities', 'wp-access-areas' ); ?>
                </button>
                <?php
            } else {
				?>
                <p class="description">
                    <?php
						echo wp_kses_post(__( 'By default everybody who can publish an entry can also edit the access properties such as ‘Who can view’ or ‘Who can edit’.<br /> If this is too generous for you then click on the button below.', 'wp-access-areas' ));
                    ?>
                </p>
                <button name="wpaa_enable_assign_cap" value="1" type="submit" class="button-secondary" />
                    <?php esc_html_e( 'Enable Role Capabilities', 'wp-access-areas' ); ?>
                </button>
                <?php
            }
        }
        public static function select_behavior() {
            $behavior = get_option( 'wpaa_default_behavior' );
            ?>
            <p>
                <?php esc_html_e( 'If somebody tries to view a restricted post directly:', 'wp-access-areas' ); ?>
            </p>
            <?php

            WPAA_Template::behavior_select( $behavior, 'wpaa_default_behavior' );
        }

        public static function sanitize_behavior( $behavior ) {
            if ( ! preg_match( '/^(404|page|login)$/', $behavior ) ) {
                $behavior = '404';
            }
            return $behavior;
        }
        public static function select_fallback_page() {
            $post_fallback_page = get_option( 'wpaa_fallback_page' );
            WPAA_Template::fallback_page_dropdown( $post_fallback_page, 'wpaa_fallback_page' );
        }
        public static function sanitize_fallbackpage( $fallback_page_id ) {
            $page = get_post( $fallback_page_id );
            if ( ! $page || $page->post_status != 'publish' || $page->post_type != 'page' || $page->post_view_cap != 'exist' ) {
                $fallback_page_id = 0;
            }
            return $fallback_page_id;
        }
        public static function sanitize_access_caps( $caps ) {
            $return_caps = array();
            foreach ( $caps as $post_type => $post_type_caps ) {
                // check is_post_type()
                if ( ! isset( $return_caps[ $post_type ] ) && post_type_exists( $post_type ) ) {
                    $return_caps[ $post_type ] = array();
                }
                foreach ( $post_type_caps as $action => $cap ) {
                    $return_caps[ $post_type ][ $action ] = wpaa_sanitize_access_cap( $cap );
                }
            }
            return $return_caps;
        }
        public static function select_post_status() {
            $default_post_status = get_option( 'wpaa_default_post_status' );

            ?>
            <select id="default-post-status-select" name="wpaa_default_post_status">
                <option value="" <?php selected( $default_post_status, '', true ); ?>>
                    <?php esc_html_e( 'Don‘t change', 'wp-access-areas' ); ?>
                </option>
                <?php
                foreach ( self::get_post_stati() as $post_status => $status_obj ) {
                    ?>
                    <option value="<?php esc_attr_e( $post_status ); ?>" <?php selected( $default_post_status, $post_status, true ); ?>>
                        <?php esc_html_e( $status_obj->label ); ?>
                    </option>
                    <?php
                }
                ?>
            </select>
            <p class="description">
                <?php
                    esc_html_e( 'Set post status of assigned posts after an Access Area has been deleted.', 'wp-access-areas' );
                ?>
            </p>
            <?php
        }
        public static function sanitize_poststatus( $post_status ) {
            if ( array_key_exists( $post_status, self::get_post_stati() ) ) {
                return $post_status;
            }
            return false;
        }
    }
endif;
