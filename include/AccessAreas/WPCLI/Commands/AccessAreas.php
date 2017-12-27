<?php

namespace AccessAreas\WPCLI\Commands;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

class AccessAreas extends \WP_CLI_Command {

	/**
	 * Create an access area
	 *
	 * ## OPTIONS
	 * <label>...
	 * : Human readable name of the access area
	 *
	 * --blog=<blog_id>
	 * : Multisite only: Passing 0 will create a sitewide Access Area
	 * ---
	 * default: 0
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp access-areas add "Premium Content" --blog=1
	 */
	public function add( $args, $assoc_args ) {
		$total = 0;
		foreach ( $args as $animal ) {
			if ( in_array( $animal, array( 'dog', 'wolve' ) ) ) {
				$total++;
				$bark = __( "Rouff", 'wp-access-areas' );
				switch ( $assoc_args['volume'] ) {
					case 'loud':
						$bark = strtoupper($bark) . '!!!';
						break;
					case 'quiet':
						$bark = '(' . strtolower($bark) . ')';
						break;
				}
				\WP_CLI::line( $bark );
			} else if ( $animal === 'cat' ) {
				\WP_CLI::error( __( "Bad Idea, chuck!", 'wp-access-areas' ) );
			} else {
				\WP_CLI::warning( __( "$animal did not bark.", 'wp-access-areas' ) );
			}
		}
		\WP_CLI::success( sprintf( __( "%d animal(s) barked.", 'wp-access-areas' ), $total ) );
	}

	/**
	 * Remove an access area
	 *
	 * ## OPTIONS
	 * <access_area>...
	 * : capability name or database ID of the access area
	 *
	 * ## EXAMPLES
	 *
	 *     wp access-areas remove wpaa_1_premium_content
	 */
	public function remove( $args, $assoc_args ) {

	}


	/**
	 * Grant access to user
	 *
	 * ## OPTIONS
	 * --access=<access_area>...
	 * : comma separated capability names or database IDs of the access areas to grant
	 *
	 * --grantee=<user_id>...
	 * : comma separated WP User IDs
	 *
	 * ## EXAMPLES
	 *
	 *     wp access-areas grant --access=wpaa_1_premium_content,wpaa_1_vip_content --grantee=1,2,3
	 */
	public function grant( $args, $assoc_args ) {
		vaR_dump($args, $assoc_args);
	}

	/**
	 * Remove an access area
	 *
	 * ## OPTIONS
	 * --access=<access_area>...
	 * : comma separated capability names or database IDs of the access areas to grant
	 *
	 * --grantee=<user_id>...
	 * : comma separated WP User IDs
	 *
	 * ## EXAMPLES
	 *     wp access-areas revoke --access=wpaa_1_premium_content,wpaa_1_vip_content --grantee=1,2,3
	 */
	public function revoke( $args, $assoc_args ) {

	}


}
