<?php


namespace AccessAreas\Model;

if ( ! defined('ABSPATH') ) {
die('FU!');
}

use AccessAreas\Core;

class ModelComment extends Core\Singleton {

	/**
	 *
	 */
	protected function __construct() {

		$this->post = ModelPost::instance();
		$this->user = ModelUser::instance();

		//	comment restrictions
		add_filter( 'comments_open', array( $this, 'comments_open') , 10 , 2 );
		add_filter( 'comments_clauses' , array( $this, 'comments_query_clauses' ) , 10 , 2 );
		add_filter( 'wp_count_comments' , array( $this, 'count_comments' ) , 10 , 2 );

		add_filter( 'comment_feed_join' , array( $this, 'get_comment_feed_join' ) );
		add_filter( 'comment_feed_where' , array( $this->post, 'get_archiveposts_where' ) , 10 , 2 );
	}

	/**
	 *	@filter comments_clauses
	 */
	public function comments_query_clauses( $clauses , $wp_comment_query ) {
		global $wpdb;
		$clauses['join'] = $this->get_comment_feed_join($clauses['join']);
		$clauses['where'] = $this->post->build_where( $clauses['where'] , $wpdb->posts );
		return $clauses;
	}

	/**
	 *	@filter comment_feed_join
	 */
	public function get_comment_feed_join( $join ) {
		global $wpdb;
		if ( strpos( $join , $wpdb->posts ) === false )
			$join .= " JOIN {$wpdb->posts} ON {$wpdb->posts}.ID = {$wpdb->comments}.comment_post_ID";
		return $join;
	}

	/**
	 *	@filter comments_open
	 */
	public function comments_open( $open, $post_id ) {
		if ( $post = get_post($post_id) ) {
			if ( $post->post_comment_cap != 'exist' )
				$open = $this->user->can( $post->post_comment_cap ) && $this->user->can( $post->post_view_cap );
		}
		return $open;
	}
	/**
	 *	@filter wp_count_comments
	 */
	public function count_comments( $stats , $post_id = 0 ) {
		global $wpdb;
		if ( $post_id ) {
			$post = get_post( $post_id );

			// user can read post. return empty stats to trigger WP stats count.
			if ( $post && $this->user->can( $post->post_view_cap ) ) {
				return $stats;
			}

			// user can not read post. Return empty stats.
			return (object) array(
				'moderated' => 0,
				'approved' => 0,
				'post-trashed' => 0,
				'spam' => 0,
				'total_comments' => 0,
				'trash' => 0,
			);
		}
		$stats = (array) $stats;
		$clauses = $this->comments_query_clauses( array(
			'join' => '',
			'where' => '',
		),null);
		$join	= $clauses['join'];
		$where	= $clauses['where'];

		// taken from wp_count_comments
		$count = $wpdb->get_results( "SELECT comment_approved, COUNT( * ) AS num_comments FROM {$wpdb->comments} {$join} {$where} GROUP BY comment_approved", ARRAY_A );

		$total = 0;
		$approved = array('0' => 'moderated', '1' => 'approved', 'spam' => 'spam', 'trash' => 'trash', 'post-trashed' => 'post-trashed');
		foreach ( (array) $count as $row ) {
			// Don't count post-trashed toward totals
			if ( 'post-trashed' != $row['comment_approved'] && 'trash' != $row['comment_approved'] )
				$total += $row['num_comments'];
			if ( isset( $approved[$row['comment_approved']] ) )
				$stats[$approved[$row['comment_approved']]] = $row['num_comments'];
		}

		$stats['total_comments'] = $total;
		foreach ( $approved as $key ) {
			if ( empty($stats[$key]) ) {
				$stats[$key] = 0;
			}
		}

		$stats = (object) $stats;
		wp_cache_set("comments-{$post_id}", $stats, 'counts');

		return $stats;
	}

}
