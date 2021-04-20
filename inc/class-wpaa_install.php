<?php
/**
 * @package WP_AccessAreas
 * @version 1.0.0
 */

// ----------------------------------------
// This class provides install and uninstall
// routines for the WP Access Areas plugin.
// ----------------------------------------

if ( ! class_exists( 'WPAA_Install' ) ) :

    class WPAA_Install {


        // --------------------------------------------------
        // de-/activation/uninstall hooks
        // --------------------------------------------------
        public static function activate() {
            global $wpdb;

            if ( ! current_user_can( 'activate_plugins' ) ) {
                return;
            }

            if ( is_multisite() ) {
                switch_to_blog( get_network()->site_id );
            }
            self::_install_capabilities_table();
            if ( is_multisite() ) {
                restore_current_blog();
            }
            if ( is_multisite() && is_network_admin() ) {
                $blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
                foreach ( $blogids as $blog_id ) {
                    switch_to_blog( $blog_id );
                    self::install_posts_table();
                    self::install_role_caps();
                    restore_current_blog();
                }
            } else {
                self::install_posts_table();
                self::install_role_caps();
            }
        }
        public static function deactivate( $networkwide = false ) {
            if ( ! current_user_can( 'activate_plugins' ) ) {
                return;
            }
            // self::uninstall();
        }
        public static function uninstall() {
            global $wpdb;
            if ( ! current_user_can( 'activate_plugins' ) ) {
                return;
            }

            self::_uninstall_custom_caps();
            self::_uninstall_capabilities_table();

            if ( function_exists( 'is_multisite' ) && is_multisite() && is_network_admin() ) {
                $blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
                foreach ( $blogids as $blog_id ) {
                    switch_to_blog( $blog_id );
                    self::_uninstall_posts_table();
                    self::_remove_options();
                    self::uninstall_role_caps();
                    restore_current_blog();
                }
            } else {
                self::_uninstall_posts_table();
                self::_remove_options();
                self::uninstall_role_caps();
            }
        }

        public static function activate_for_blog( $blog_id ) {
            switch_to_blog( $blog_id );
            self::install_posts_table();
            // will break during install, wp-admin/includes/users.php not loaded.
            // self::install_role_caps();
            restore_current_blog();
        }
        private static function _remove_options() {
            delete_option( 'wpaa_default_behavior' );
            delete_option( 'wpaa_fallback_page' );
            delete_option( 'wpaa_default_post_status' );
            delete_option( 'wpaa_default_caps' );
            delete_option( 'wpaa_enable_assign_cap' );
        }

        // --------------------------------------------------
        // posts table
        // --------------------------------------------------
        public static function install_posts_table() {
            global $wpdb;
            // , 'edit_cap'=>'post_edit_cap' will be used later.
            $cols = array(
				'comment_cap' => 'post_comment_cap',
				'edit_cap'    => 'post_edit_cap',
				'view_cap'    => 'post_view_cap',
			);
            foreach ( $cols as $idx => $col ) {
                $c = $wpdb->get_results( "SHOW COLUMNS FROM $wpdb->posts LIKE '$col'" );
                if ( empty( $c ) ) {
                    $wpdb->query( "ALTER TABLE $wpdb->posts ADD COLUMN $col varchar(128) NOT NULL DEFAULT 'exist' AFTER `post_status`;" );
                }

                $i = $wpdb->query( "SHOW INDEX FROM $wpdb->posts WHERE Key_name = '$idx'" );
                if ( empty( $i ) ) {
                    $wpdb->query( "ALTER TABLE $wpdb->posts ADD INDEX `$idx` (`$col`);" );
                }
            }
        }
        private static function _uninstall_posts_table() {
            global $wpdb;
            // , 'edit_cap'=>'post_edit_cap' will be used later.
            $cols = array(
				'comment_cap' => 'post_comment_cap',
				'edit_cap'    => 'post_edit_cap',
				'view_cap'    => 'post_view_cap',
			);
            foreach ( $cols as $idx => $col ) {
                $c = $wpdb->get_results( "SHOW COLUMNS FROM $wpdb->posts LIKE '$col'" );
                if ( ! empty( $c ) ) {
                    $wpdb->query( "ALTER TABLE $wpdb->posts DROP COLUMN $col;" );
                }

                $i = $wpdb->query( "SHOW INDEX FROM $wpdb->posts WHERE Key_name = '$idx'" );
                if ( ! empty( $i ) ) {
                    $wpdb->query( "ALTER TABLE $wpdb->posts DROP INDEX ('$idx');" );
                }
            }
        }

        // --------------------------------------------------
        // Role caps
        // --------------------------------------------------
        public static function install_role_caps() {
            global $wp_roles;
            if ( ! function_exists( 'get_editable_roles' ) ) {
                include_once ABSPATH . '/wp-admin/includes/user.php';
            }
            $roles = get_editable_roles();
            foreach ( array_keys( $roles ) as $role_slug ) {
                $role = get_role( $role_slug );
                if ( $role->has_cap( 'publish_posts' ) ) {
                    if ( ! $role->has_cap( 'wpaa_set_view_cap' ) ) {
                         $role->add_cap( 'wpaa_set_view_cap' );
                    }
                    if ( ! $role->has_cap( 'wpaa_set_comment_cap' ) ) {
                          $role->add_cap( 'wpaa_set_comment_cap' );
                    }
                }
                if ( $role->has_cap( 'edit_others_posts' ) && ! $role->has_cap( 'wpaa_set_edit_cap' ) ) {
                    $role->add_cap( 'wpaa_set_edit_cap' );
                }
            }
        }
        public static function uninstall_role_caps() {
            if ( ! function_exists( 'get_editable_roles' ) ) {
                include_once ABSPATH . '/wp-admin/includes/user.php';
            }
            $roles = get_editable_roles();
            foreach ( array_keys( $roles ) as $role_slug ) {
                $role = get_role( $role_slug );
                if ( $role->has_cap( 'wpaa_set_view_cap' ) ) {
                    $role->remove_cap( 'wpaa_set_view_cap' );
                }
                if ( $role->has_cap( 'wpaa_set_edit_cap' ) ) {
                    $role->remove_cap( 'wpaa_set_edit_cap' );
                }
                if ( $role->has_cap( 'wpaa_set_comment_cap' ) ) {
                    $role->remove_cap( 'wpaa_set_comment_cap' );
                }
            }
        }
        // --------------------------------------------------
        // capabilities table
        // --------------------------------------------------
        private static function _install_capabilities_table() {
            global $wpdb;
            if ( $wpdb->get_var( "show tables like '{$wpdb->prefix}disclosure_userlabels'" ) != "{$wpdb->prefix}disclosure_userlabels" ) {
                $sql = "CREATE TABLE {$wpdb->prefix}disclosure_userlabels (
				ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				cap_title varchar(64) NOT NULL,
				capability varchar(128) NOT NULL,
				blog_id bigint(20) NOT NULL,
				PRIMARY KEY id (`id`),
				UNIQUE KEY capability (`capability`),
				KEY blog_id (`blog_id`)
				);";

                include_once ABSPATH . 'wp-admin/includes/upgrade.php';
                dbDelta( $sql );
            }
        }


        private static function _uninstall_capabilities_table() {
            global $wpdb;
            if ( $wpdb->get_var( "show tables like '{$wpdb->prefix}disclosure_userlabels'" ) == "{$wpdb->prefix}disclosure_userlabels" ) {
                $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}disclosure_userlabels" );
            }
        }
        // --------------------------------------------
        // remove Caps from User
        private function _uninstall_custom_caps() {
            global $wpdb;

            $usermeta = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM $wpdb->usermeta WHERE meta_key LIKE '{$wpdb->base_prefix}%capabilities' AND meta_value LIKE %s",
                '%' . $wpdb->esc_like( WPUND_USERLABEL_PREFIX ) . '%'
            ) );
            foreach ( $usermeta as $meta ) {
                $caps = maybe_unserialize( $meta->meta_value );
                foreach ( array_keys( $caps ) as $key ) {
                    if ( strpos( $key, WPUND_USERLABEL_PREFIX ) === 0 ) {
                        unset( $caps[ $key ] );
                    }
                }
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE $wpdb->usermeta SET meta_value=%s WHERE umeta_id=%d",
                        serialize( $caps ),
                        $meta->umeta_id
                    )
                );
            }

            $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE meta_key = %s", WPUND_GLOBAL_USERMETA_KEY ) );

        }

    }
endif;
