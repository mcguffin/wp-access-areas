<?php
/**
 * @package WP_AccessAreas
 * @version 1.0.0
 */



// ----------------------------------------
// This class initializes the WordPress Access Areas plugin.
// (As of version 1.0 it only loads an apropriate plugin textdomain for translation readyness.)
// ----------------------------------------


if ( ! class_exists( 'WPAA_Core' ) ) :
    class WPAA_Core {


        /**
         * Init Plugin
         */
        public static function init() {

            global $wpdb;
    		$wpdb->global_tables[] = 'disclosure_userlabels';
            $wpdb->set_prefix( $wpdb->base_prefix ); // force new table names generation

            add_action( 'plugins_loaded', array( __CLASS__, 'plugin_loaded' ) );

            if ( is_multisite() ) {
                add_action( 'wpmu_new_blog', array( __CLASS__, 'set_network_roles_for_blog' ), 10, 1 );
                add_action( 'wpmu_upgrade_site', array( __CLASS__, 'set_network_roles_for_blog' ), 10, 1 );
            }

            add_action( 'init', array( __CLASS__, 'admin_register_scripts' ) );

            add_option( 'wpaa_default_behavior', '404' );
            add_option( 'wpaa_fallback_page', 0 );
            add_option( 'wpaa_default_post_status', 'private' );
        }

        /**
         * Register Admin styles and scripts
         */
        public static function admin_register_scripts() {
            $version = WPUND_VERSION;
            wp_register_script( 'wpaa-admin-user-ajax', plugins_url( 'js/wpaa-admin-user-ajax.js', dirname( __FILE__ ) ), [], $version, true );
            wp_register_script( 'wpaa-quick-edit', plugins_url( 'js/wpaa-quick-edit.js', dirname( __FILE__ ) ), [], $version, true );
            wp_register_script( 'wpaa-edit', plugins_url( 'js/wpaa-edit.js', dirname( __FILE__ ) ), [], $version, true );
            wp_localize_script(
                'wpaa-edit',
                'wpaa_postedit',
                array(
					'ajax_nonce' => wp_create_nonce( 'get_accessarea_values' ),
                )
            );
            wp_register_style( 'wpaa-admin', plugins_url( 'css/wpaa-admin.css', dirname( __FILE__ ) ), [], $version );
        }

        /**
         * Load Textdomain, chcek version
         */
        public static function plugin_loaded() {
            self::check_version();
            load_plugin_textdomain( 'wp-access-areas', false, dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/' );
        }

        /**
         * Setup for multisite blog
         */
        public static function set_network_roles_for_blog( $blog_id /*, $user_id, $domain, $path, $site_id, $meta */ ) {
            include_once dirname( __FILE__ ) . '/class-wpaa_install.php';
            WPAA_Install::activate_for_blog( $blog_id );
        }
        /**
         * Upgrade DB after plugin version
         */
        public static function check_version() {
            if ( is_multisite() ) {
                $installed_version = get_site_option( 'accessareas_version' );
                update_site_option( 'accessareas_version', WPUND_VERSION );
            } else {
                $installed_version = get_option( 'accessareas_version' );
                update_option( 'accessareas_version', WPUND_VERSION );
            }
            if ( ! $installed_version || version_compare( WPUND_VERSION, $installed_version ) ) {
                accessareas_activate();
                if ( is_multisite() ) {
                    update_site_option( 'accessareas_version', WPUND_VERSION );
                } else {
                    update_option( 'accessareas_version', WPUND_VERSION );
                }
            }

        }
    }

endif;
