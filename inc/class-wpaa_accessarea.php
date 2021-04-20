<?php
/**
 * @package WP_AccessAreas
 * @version 1.0.0
 */

// ----------------------------------------
// Data model for Userlabels.
// ----------------------------------------

if ( ! class_exists( 'WPAA_AccessArea' ) ) :
    class WPAA_AccessArea {


        private static $_what_went_wrong = 0;
        private static $_query_cache     = array();

        public static function get_count_available_userlabels() {
            global $wpdb;

            $query = "SELECT COUNT(*) FROM $wpdb->disclosure_userlabels WHERE blog_id=0 ";
            if ( ! is_network_admin() ) {
                $blog_id = get_current_blog_id();
                $query  .= "OR blog_id=$blog_id";
            }
            return self::_get_cached_result( $query, 'get_var' );
        }

        public static function get_available_userlabels( $limit = 0, $order = 'blog_id DESC,cap_title ASC' ) {
            global $wpdb;

            $blog_id_in = array();
            if ( ! is_multisite() || is_accessareas_active_for_network() ) {
                $blog_id_in[0] = '%d';
            }

            $query = "SELECT * FROM $wpdb->disclosure_userlabels WHERE blog_id IN ";

            if ( ! is_network_admin() ) {
                $blog_id_in[ get_current_blog_id() ] = '%d';
            }
            if ( empty( $blog_id_in ) ) {
                return array();
            }

            $query .= '(' . implode( ',', array_values( $blog_id_in ) ) . ')';
            $sql_orderby = sanitize_sql_orderby( $order );

            if ( $sql_orderby ) {
                $query .= " ORDER BY $sql_orderby";
            }
            if ( $limit ) {
                $query .= " LIMIT $limit";
            }
            if ( count( $blog_id_in ) ) {
                $args = array_keys( $blog_id_in );
                $query = $wpdb->prepare( $query, ...$args );
            }

            return self::_get_cached_result( $query );
        }
        public static function get_blog_userlabels( $blog_id = 0, $order_by = 'cap_title', $order = 'ASC' ) {
            global $wpdb;
            if ( ! $blog_id ) {
                $blog_id = get_current_blog_id();
            }
            $query = "SELECT * FROM $wpdb->disclosure_userlabels WHERE blog_id=%d ";
            $sql_orderby = sanitize_sql_orderby( "$order_by $order" );
            if ( $sql_orderby ) {
                $query .= " ORDER BY $sql_orderby";
            }
            $query = $wpdb->prepare( $query, $blog_id );
            return self::_get_cached_result( $query );
        }
        public static function get_network_userlabels(  $order_by = 'cap_title', $order = 'ASC' ) {
            global $wpdb;
            $query      = "SELECT * FROM $wpdb->disclosure_userlabels WHERE blog_id=0 ";
            $sql_orderby = sanitize_sql_orderby( "$order_by $order" );
            if ( $sql_orderby ) {
                $query .= " ORDER BY $sql_orderby";
            }

            return self::_get_cached_result( $query );
        }
        public static function get_label_array() {
            $labels    = self::get_available_userlabels();
            $label_map = array();
            foreach ( $labels as $item ) {
                $label_map[ $item->capability ] = array(
					'title'  => $item->cap_title,
					'global' => is_multisite() && ! $item->blog_id,
                );
                // if ( is_multisite() && ! $item->blog_id )
                // $label_map[$item->capability] .= ' '.__('(Network)','wp-access-areas');
            }
            return $label_map;
        }

        public static function delete_userlabel( $id ) {
            global $wpdb;

            $userlabel = self::get_userlabel( $id );
            if ( ! $userlabel ) {
                self::$_what_went_wrong = 5;
                return false;
            }
            if ( is_multisite() ) {
                if ( ! $userlabel->blog_id ) { // network wide !
                    $count_blogs = get_sites([
                        'public' => 1,
                        'count' => true,
                    ]);
                    $blogids = get_sites([
                        'fields' => 'ids',
                        'public' => 1,
                        'number' => $count_blogs,
                    ]);
                } else {
                    $blogids = array( $userlabel->blog_id );
                }
                foreach ( $blogids as $blog_id ) {
                    switch_to_blog( $blog_id );
                    self::_delete_userlabel_from_blog( $userlabel );
                }
                restore_current_blog();

                // remove global capabilities
                $query = $wpdb->prepare(
                    'SELECT * FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value LIKE %s',
                    WPUND_GLOBAL_USERMETA_KEY,
                    '%' . $wpdb->esc_like( WPUND_USERLABEL_PREFIX ) . '%'
                );
                $usermeta = $wpdb->get_results( $query );

                foreach ( $usermeta as $meta ) {
                    $caps = maybe_unserialize( $meta->meta_value );
                    $caps = array_filter( $caps, array( __CLASS__, 'is_not_custom_cap' ) );

                    $wpdb->update(
                        $wpdb->prefix . 'usermeta',
                        [ 'meta_value' => $caps ],
                        [ 'umeta_id' => $meta->umeta_id ],
                        '%s',
                        '%d'
                    );
                }
            } else {
                self::_delete_userlabel_from_blog( $userlabel );
            }
            self::_clear_cache();

            return $wpdb->delete(
                $wpdb->disclosure_userlabels,
                [ 'ID' => $id ],
                '%d'
            );
        }

        private static function _delete_userlabel_from_blog( &$userlabel ) {
            global $wpdb;
            $post_status_sql     = '';
            $default_post_status = get_option( 'wpaa_default_post_status' );
            if ( $default_post_status && in_array( $default_post_status, WPAA_Settings::get_post_stati() ) ) {
                $post_status_sql = $wpdb->prepare( " , post_status=%s ", $default_post_status );
            }

            // delete everything from posts and restore usefull default values
            // reset post_*_caps to default
            $wpdb->update(
                $wpdb->prefix . 'posts',
                [ 'post_view_cap' => 'exist' ],
                [ 'post_view_cap' => $userlabel->capability ],
                '%s', '%s'
            );
            $wpdb->update(
                $wpdb->prefix . 'posts',
                [ 'post_edit_cap' => 'exist' ],
                [ 'post_edit_cap' => $userlabel->capability ],
                '%s', '%s'
            );
            $wpdb->update(
                $wpdb->prefix . 'posts',
                [ 'post_comment_cap' => 'exist' ],
                [ 'post_comment_cap' => $userlabel->capability ],
                '%s', '%s'
            );

            // set back options
            if ( get_option( 'wpaa_default_view_cap' ) == $userlabel->capability ) {
                update_option( 'wpaa_default_view_cap', 'exist' );
            }

            if ( get_option( 'wpaa_default_edit_cap' ) == $userlabel->capability ) {
                update_option( 'wpaa_default_edit_cap', 'exist' );
            }

            if ( get_option( 'wpaa_default_comment_cap' ) == $userlabel->capability ) {
                update_option( 'wpaa_default_comment_cap', 'exist' );
            }

            if ( is_multisite() ) {
                $current_blog_id = get_current_blog_id();
            }

            // remove all caps from users
            $users = get_users();
            foreach ( $users as $user ) {
                if ( is_multisite() ) {
                    $user->for_site( $current_blog_id );
                }
                $user->remove_cap( $userlabel->capability );
            }
            self::_clear_cache();
        }
        private static function is_not_custom_cap( $capname ) {
            return strpos( $capname, WPUND_USERLABEL_PREFIX ) !== 0;
        }
        public static function get_userlabel( $id ) {
            global $wpdb;
            $query      = $wpdb->prepare( "SELECT * FROM $wpdb->disclosure_userlabels WHERE ID = %d", $id );
            return self::_get_cached_result( $query, 'get_row' );
        }
        public static function get_userlabel_by_cap( $cap_name ) {
            global $wpdb;
            $query      = $wpdb->prepare( "SELECT * FROM $wpdb->disclosure_userlabels WHERE capability = %s", $cap_name );
            return self::_get_cached_result( $query, 'get_row' );
        }
        public static function what_went_wrong() {
            $ret                    = self::$_what_went_wrong;
            self::$_what_went_wrong = 0;
            return $ret;
        }
        public static function create_userlabel( $data ) {
            global $wpdb;

            $blog_id = intval( $data['blog_id'] );
            $cap_title = $data['cap_title'];

            if ( self::title_exists( $cap_title, $blog_id ) ) {
                self::$_what_went_wrong = 4;
                return false;
            }

            $capability  = $blog_id ? wpaa_get_local_prefix( $blog_id ) : WPUND_USERLABEL_PREFIX;
            $capability .= sanitize_title( $cap_title );

            $wpdb->insert(
                $wpdb->disclosure_userlabels,
                [
                    'cap_title' => $cap_title,
                    'capability' => $capability,
                    'blog_id' => $blog_id,
                ],
                [ '%s', '%s', '%d' ]
            );

            self::_clear_cache();
            $insert_id = $wpdb->insert_id;
            do_action( 'wpaa_create_access_area', $capability, $cap_title, $blog_id, $insert_id );
            return $insert_id;
        }
        public static function update_userlabel( $update_data ) {
            global $wpdb;
            $update_data = apply_filters( 'wpaa_update_access_area_data', $update_data );
            if ( empty( $update_data ) ) {
                return false;
            }

            $id = intval( $update_data['id'] );
            $blog_id = intval( $update_data['blog_id'] );
            $cap_title = $update_data['cap_title'];

            if ( self::title_exists( $cap_title, $blog_id ) ) {
                self::$_what_went_wrong = 4;
                return false;
            }

            $wpdb->update(
                $wpdb->disclosure_userlabels,
                [ 'cap_title' => $cap_title ],
                [ 'ID' => $id ],
                '%s',
                '%d'
            );

            self::_clear_cache();
            do_action( 'wpaa_update_access_area', $id, $update_data );
            return $id;
        }
        public static function title_exists( $cap_title, $blog_id ) {
            global $wpdb;
            $query      = $wpdb->prepare( "SELECT id FROM $wpdb->disclosure_userlabels WHERE cap_title=%s AND blog_id=%d", $cap_title, $blog_id );
            return self::_get_cached_result( $query, 'get_var' );
        }
        public static function capability_exists( $cap ) {
            global $wpdb;
            $query      = $wpdb->prepare( "SELECT id FROM $wpdb->disclosure_userlabels WHERE capability=%s", $cap );
            return self::_get_cached_result( $query, 'get_var' );
        }
        private static function _get_cached_result( $query, $retrieval_function = 'get_results' ) {
            global $wpdb;
            if ( ! is_callable( array( $wpdb, $retrieval_function ) ) ) {
                $retrieval_function = 'get_results';
            }

            $query_key = md5( $query );
            if ( ! isset( self::$_query_cache[ $retrieval_function ] ) ) {
                self::$_query_cache[ $retrieval_function ] = array();
            }

            if ( ! isset( self::$_query_cache[ $retrieval_function ][ $query_key ] ) ) {
                self::$_query_cache[ $retrieval_function ][ $query_key ] = $wpdb->$retrieval_function( $query );
            }
            return self::$_query_cache[ $retrieval_function ][ $query_key ];
        }
        private static function _clear_cache() {
            self::$_query_cache = array();
        }
    }
endif;
