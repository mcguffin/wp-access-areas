<?php
/**
 * @package WP_AccessAreas
 * @version 1.0.0
 */

// ----------------------------------------
// Frontend Post Filters
// ----------------------------------------

if ( ! class_exists( 'WPAA_Posts' ) ) :
    class WPAA_Posts {


        public static function init() {
            add_action( 'pre_get_posts', array( __CLASS__, 'wp_query_allow_filters' ) );
            // viewing restrictions on posts lists
            add_action( 'get_pages', array( __CLASS__, 'skip_undisclosed_items' ), 10, 1 ); // needed by nav menus
            add_filter( 'posts_where', array( __CLASS__, 'get_posts_where' ), 10, 2 );
            add_filter( 'getarchives_where', array( __CLASS__, 'get_archiveposts_where' ), 10, 2 );

            add_filter( 'posts_join', array( __CLASS__, 'get_posts_join' ), 10, 2 );

            add_filter( 'get_next_post_where', array( __CLASS__, 'get_adjacent_post_where' ), 10, 3 );
            add_filter( 'get_previous_post_where', array( __CLASS__, 'get_adjacent_post_where' ), 10, 3 );
            add_filter( 'get_next_post_join', array( __CLASS__, 'get_adjacent_post_join' ), 10, 3 );
            add_filter( 'get_previous_post_join', array( __CLASS__, 'get_adjacent_post_join' ), 10, 3 );

            // behavior
            add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ) ); // or wp

            // comment restrictions
            add_filter( 'comments_open', array( __CLASS__, 'comments_open' ), 10, 2 );
            add_filter( 'comments_clauses', array( __CLASS__, 'comments_query_clauses' ), 10, 2 );
            add_filter( 'wp_count_comments', array( __CLASS__, 'count_comments' ), 10, 2 );

            add_filter( 'comment_feed_join', array( __CLASS__, 'get_comment_feed_join' ) );
            add_filter( 'comment_feed_where', array( __CLASS__, 'get_archiveposts_where' ), 10, 2 );
            // misc
            add_filter( 'edit_post_link', array( __CLASS__, 'edit_post_link' ), 10, 2 );
            add_filter( 'post_class', array( __CLASS__, 'post_class' ), 10, 3 );

            // caps
            add_filter( 'map_meta_cap', array( __CLASS__, 'map_meta_cap' ), 10, 4 );
            add_filter( 'user_has_cap', array( __CLASS__, 'user_has_cap' ), 10, 3 );
        }

        public static function user_has_cap( $allcaps, $caps, $args ) {
            $user_id   = $args[1]; // user id
            $user_caps = get_user_meta( $user_id, WPUND_GLOBAL_USERMETA_KEY, true );
            if ( $user_caps ) {
                $allcaps += array_combine( $user_caps, array_fill( 0, count( $user_caps ), true ) );
            }
            return $allcaps;
        }

        public static function wp_query_allow_filters( $wp_query ) {
            $wp_query->set( 'suppress_filters', false );
        }

        // --------------------------------------------------
        // template redirect
        // --------------------------------------------------
        public static function template_redirect() {
            global $wp_query;
            if ( isset( $wp_query ) && is_singular() && $restricted_post = get_post() ) {
                if ( $restricted_post->ID !== 0 && ! wpaa_user_can( $restricted_post->post_view_cap ) ) {
                    do_action( 'wpaa_view_restricted_post', $restricted_post->ID, $restricted_post );
                    $redirect         = false;
                    $behavior         = get_post_meta( $restricted_post->ID, '_wpaa_post_behavior', true );
                    $fallback_page_id = get_post_meta( $restricted_post->ID, '_wpaa_fallback_page', true );
                    // no behavior? take default value
                    if ( ! $behavior ) {
                         $behavior = get_option( 'wpaa_default_behavior' );
                    }

                    if ( $behavior == 'login' && ! is_user_logged_in() ) {
                        // get user to login and return him to the requested page.
                        $redirect = wp_login_url( get_permalink( $restricted_post->ID ) );
                    } else {
                        if ( $behavior == 'page' || ( $behavior == 'login' && is_user_logged_in() ) ) {

                            // no fallback? take default value
                            if ( ! $fallback_page_id ) {
                                $fallback_page_id = get_option( 'wpaa_fallback_page' );
                            }

                            if ( $fallback_page_id && wpaa_is_post_public( $fallback_page_id ) ) {
                                // if accessable take user to the fallback page
                                $redirect = get_permalink( $fallback_page_id );
                            } else {
                                // last resort: send him home
                                $redirect = home_url();
                            }
                        } else { // assume 404
                            $wp_query->set_404();
                            status_header( 404 );
                            return;
                        }
                    }
                    $redirect = apply_filters( 'wpaa_restricted_post_redirect', $redirect, $restricted_post->ID, $restricted_post );
                    if ( $redirect ) {
                        wp_safe_redirect( $redirect );
                        exit();
                    }
                }
            }
        }

        // --------------------------------------------------
        // comments query
        // --------------------------------------------------
        public static function comments_query_clauses( $clauses, $wp_comment_query ) {
            global $wpdb;
            $clauses['join']  = self::get_comment_feed_join( $clauses['join'] );
            $clauses['where'] = self::_get_where( $clauses['where'], $wpdb->posts );
            return $clauses;
        }
        public static function get_comment_feed_join( $join ) {
            global $wpdb;
            if ( strpos( $join, $wpdb->posts ) === false ) {
                //$join .= $wpdb->prepare( " JOIN {$wpdb->posts} ON {$wpdb->posts}.ID = {$wpdb->comments}.comment_post_ID" );
                $join .= " JOIN {$wpdb->posts} ON {$wpdb->posts}.ID = {$wpdb->comments}.comment_post_ID";
            }
            return $join;
        }

        // --------------------------------------------------
        // comment restrictions
        // --------------------------------------------------
        public static function comments_open( $open, $post_id ) {
            if ( $post = get_post( $post_id ) ) {
                if ( $post->post_comment_cap != 'exist' ) {
                    $open = wpaa_user_can( $post->post_comment_cap ) && wpaa_user_can( $post->post_view_cap );
                }
            }
            return $open;
        }
        public static function count_comments( $stats, $post_id = 0 ) {
            global $wpdb;
            if ( $post_id ) {
                $post = get_post( $post_id );

                // user can read post. return empty stats to trigger WP stats count.
                if ( $post && wpaa_user_can( $post->post_view_cap ) ) {
                    return $stats;
                }

                // user can not read post. Return empty stats.
                return (object) array(
					'moderated'      => 0,
					'approved'       => 0,
					'post-trashed'   => 0,
					'spam'           => 0,
					'total_comments' => 0,
					'trash'          => 0,
                );
            }
            $stats   = (array) $stats;
            $clauses = self::comments_query_clauses(
                array(
					'join'  => '',
					'where' => '',
                ),
                null
            );
            $join    = $clauses['join'];
            $where   = $clauses['where'];

            // taken from wp_count_comments
            $count = $wpdb->get_results( "SELECT comment_approved, COUNT( * ) AS num_comments FROM {$wpdb->comments} {$join} {$where} GROUP BY comment_approved", ARRAY_A );

            $total    = 0;
            $approved = array(
				'0'            => 'moderated',
				'1'            => 'approved',
				'spam'         => 'spam',
				'trash'        => 'trash',
				'post-trashed' => 'post-trashed',
			);
            foreach ( (array) $count as $row ) {
                // Don't count post-trashed toward totals
                if ( 'post-trashed' != $row['comment_approved'] && 'trash' != $row['comment_approved'] ) {
                    $total += $row['num_comments'];
                }
                if ( isset( $approved[ $row['comment_approved'] ] ) ) {
                    $stats[ $approved[ $row['comment_approved'] ] ] = $row['num_comments'];
                }
            }

            $stats['total_comments'] = $total;
            foreach ( $approved as $key ) {
                if ( empty( $stats[ $key ] ) ) {
                    $stats[ $key ] = 0;
                }
            }

            $stats = (object) $stats;
            wp_cache_set( "comments-{$post_id}", $stats, 'counts' );

            return $stats;
        }

        // --------------------------------------------------
        // edit link
        // --------------------------------------------------
        public static function edit_post_link( $link, $post_ID ) {
            // we only get a post id, se we better rely on map_meta_cap, where the post object is loaded
            if ( current_user_can( 'edit_post', $post_ID ) ) {
                return $link;
            }
            return '';
        }

        // --------------------------------------------------
        // Post class
        // --------------------------------------------------
        public static function post_class( $classes, $class, $post_ID ) {
            if ( $post_ID ) {
                $post = get_post( $post_ID );
                if ( $post->post_view_cap != 'exist' && wpaa_user_can( $post->post_view_cap ) ) {
                    $classes[] = 'wpaa-view-restricted';
                    $classes[] = "wpaa-view-{$post->post_view_cap}";
                }
                if ( $post->post_edit_cap != 'exist' && current_user_can( 'edit_post', $post_ID ) ) {
                    $classes[] = 'wpaa-edit-restricted';
                    $classes[] = "wpaa-edit-{$post->post_edit_cap}";
                }
                if ( $post->post_comment_cap != 'exist' && wpaa_user_can( $post->post_comment_cap ) && wpaa_user_can( $post->post_view_cap ) ) {
                    $classes[] = 'wpaa-comment-restricted';
                    $classes[] = "wpaa-comment-{$post->post_comment_cap}";
                }
            }
            return array_unique( $classes );
        }

        // --------------------------------------------------
        // editing caps
        // --------------------------------------------------
        public static function map_meta_cap( $caps, $cap, $user_id, $args ) {
            switch ( $cap ) {
                case 'edit_post':
                case 'delete_post':
                case 'edit_page':
                case 'delete_page':
					if ( count( $args ) ) {
						$post_id = $args[0];
						// if he not can like specfied, ;
						$post     = get_post( $post_id );
						$edit_cap = $post->post_edit_cap;
						$view_cap = $post->post_view_cap;
						if ( ! $edit_cap ) {
							break;
						}

						if ( ! wpaa_user_can( $edit_cap ) || ! wpaa_user_can( $view_cap ) ) {
							$caps[] = 'do_not_allow';
						}
					}
                    break;
                case 'edit_comment':
					if ( count( $args ) ) {
						$comment_id = $args[0];
						$comment    = get_comment( $comment_id );
						if ( $comment && $comment->comment_post_ID ) {
							$post     = get_post( $comment->comment_post_ID );
							$view_cap = $post->post_view_cap;
							if ( ! wpaa_user_can( $view_cap ) ) {
								$caps[] = 'do_not_allow';
							}
                        }
					}
                    break;
            }
            return $caps;
        }

        // --------------------------------------------------
        // viewing restrictions
        // --------------------------------------------------
        public static function skip_undisclosed_items( $items ) {
            // everything's fine - return.
            if ( current_user_can( 'administrator' ) ) {
                return $items;
            }

            // remove undisclosed posts
            $ret = array();
            foreach ( $items as $i => $item ) {
                if ( wpaa_user_can( $item->post_view_cap ) ) {
                    $ret[] = $item;
                }
            }
            return $ret;
        }

        public static function get_archiveposts_where( $where, $args = null ) {
            $where = self::_get_where( $where, '' );
            return $where;
        }
        public static function get_posts_where( $where, $wp_query ) {
            global $wpdb;
            $where = self::_get_where( $where, $wpdb->posts );
            return $where;
        }
        public static function get_posts_join( $join, $wp_query ) {
            // global $wpdb;
            return $join;
        }

        public static function get_adjacent_post_where( $where, $in_same_cat, $excluded_categories ) {
            return self::_get_where( $where );
        }
        public static function get_adjacent_post_join( $join, $in_same_term, $excluded_terms ) {
            global $wpdb;
            $join .= " LEFT JOIN $wpdb->postmeta AS wpaa_postmeta ON wpaa_postmeta.meta_key = '_wpaa_post_behavior' AND wpaa_postmeta.meta_value IS NOT NULL";
            return $join;
        }


        private static function _get_where( $where, $table_name = 'p' ) {
            global $wpdb, $wp_query;
            // disable filtering: on queries for single posts/pages and for single blog administrators
            if ( ( isset( $wp_query ) && is_singular() && preg_match( "/{$wpdb->posts}.(post_name|ID)\s?=/", $where ) ) || ( ! is_multisite() && current_user_can( 'administrator' ) ) ) {
                return $where;
            }
            if ( $table_name && substr( $table_name, -1 ) !== '.' ) {
                $table_name .= '.';
            }

            $caps = array( 'exist' );
            if ( is_user_logged_in() ) {
                // get current user's groups
                $roles = new WP_Roles();

                // reading
                if ( current_user_can( 'read' ) ) {
                    $caps[] = 'read';
                }

                // user's roles
                $user_roles = wpaa_user_contained_roles();
                foreach ( $user_roles as $role ) {
                    $caps[] = $role;
                }

                // user's custom caps
                foreach ( array_keys( WPAA_AccessArea::get_label_array() ) as $cap ) {
                    if ( wpaa_user_can_accessarea( $cap ) ) {
                        $caps[] = $cap;
                    }
                }
            }
            if ( empty( $caps ) ) {
                return $where;
            }

            $fmt = array_fill( 0, count( $caps ), '%s' );

            $add_where = $wpdb->prepare(
                " {$table_name}post_view_cap IN (" . implode( ',', $fmt ) . ")",
                ...$caps
            );
            // if ( is_single() ) // why did I do this....?
            // $add_where .= " OR (wpaa_postmeta.meta_value IS NOT NULL)";

            $add_where = " AND ( $add_where ) ";
            return $where . $add_where;
        }

    }
endif;
