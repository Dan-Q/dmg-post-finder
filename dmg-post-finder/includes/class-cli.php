<?php
/**
 * DMG Post Finder CLI commands.
 *
 * @package DMG_Post_Finder
 */

namespace DMG\PostFinder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implements the dmg-read-more WP-CLI command.
 */
class CLI {

	/**
	 * Search for posts containing the DMG Post Finder block within a date range.
	 *
	 * ## OPTIONS
	 *
	 * [--date-after=<date>]
	 * : Posts published after this date (YYYY-MM-DD format). Default is 30 days ago.
	 *
	 * [--date-before=<date>]
	 * : Posts published before this date (YYYY-MM-DD format). Default is current date.
	 *
	 * ## EXAMPLES
	 *
	 *     # Search for posts with DMG Post Finder block in the last 30 days
	 *     $ wp dmg-read-more search
	 *
	 *     # Search for posts with DMG Post Finder block between specific dates
	 *     $ wp dmg-read-more search --date-after=2023-01-01 --date-before=2023-12-31
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command options.
	 */
	public function search( $args, $assoc_args ) {
		// Set default date range (last 30 days).
		$date_after  = isset( $assoc_args['date-after'] ) ? $assoc_args['date-after'] : gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$date_before = isset( $assoc_args['date-before'] ) ? $assoc_args['date-before'] : gmdate( 'Y-m-d' );

		\WP_CLI::log( sprintf( 'Searching for posts with DMG Post Finder block between %s and %s', $date_after, $date_before ) );

		/*
		 * For maximum performance, we  add a custom WHERE clause to the query to find posts containing the DMG Post Finder block.
		 * MySQL/MariaDB is far faster at searching textual content than PHP, which in turn is faster than WP's has_block() etc.
		 * However, this approach isn't without its risks: e.g. a carefully-crafted HTML comment could trigger a false positive).
		 * That seems unlikely in this case, so I've gone for this high-performance approach. But in order to demonstrate a slower
		 * (but still "fast enough") approach, an alternative method is commented out below.
		 */
		add_filter(
			'posts_where',
			function ( $where ) {
				$where .= " AND post_content LIKE '%<!-- wp:dmg/post-finder%'";
				return $where;
			}
		);

		$post_ids = get_posts(
			array(
				'post_type'        => 'post',
				'post_status'      => 'publish',
				'date_query'       => array(
					array(
						'after'     => $date_after,
						'before'    => $date_before,
						'inclusive' => true,
					),
				),
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		if ( empty( $post_ids ) ) {
			\WP_CLI::warning( 'No posts found in the specified date range.' );
			return;
		}

		foreach ( $post_ids as $post_id ) {
			\WP_CLI::line( $post_id );
		}

		/**
		 * This alternative approach takes about 3 times longer than the above (both approaches scale linearly in approximately O(n)+k time),
		 * but leans on WP's own functionality. That makes it suitable for places that the above can't be deployed, and it doesn't "fall for"
		 * carefully-crafted HTML comments that are designed to trigger false positives.
		 */

		// phpcs:disable Squiz.PHP.CommentedOutCode.Found, Squiz.Commenting.InlineComment.InvalidEndChar -- this code is supposed to be commented out

		// Use WP_Query to get post IDs within the date range, using a LIKE query to find the DMG Post Finder block.
		// $posts = get_posts(
		// array(
		// 'post_type'      => 'post',
		// 'post_status'    => 'publish',
		// 'date_query'     => array(
		// array(
		// 'after'     => $date_after,
		// 'before'    => $date_before,
		// 'inclusive' => true,
		// ),
		// ),
		// 'posts_per_page' => -1,
		// )
		// );

		// if ( empty( $posts ) ) {
		// \WP_CLI::warning( 'No posts found in the specified date range.' );
		// return;
		// }

		// // Process posts in batches of 100 to improve performance.
		// foreach ( array_chunk( $posts, 100 ) as $chunk ) {

		// foreach ( $chunk as $post ) {
		// $post_content = get_post_field( 'post_content', $post );

		// Check if post content contains our block.
		// if ( has_block( 'dmg/post-finder', $post_content ) || strpos( $post_content, '<!-- wp:dmg/post-finder' ) !== false ) {
		// \WP_CLI::line( $post->ID );
		// }
		// }
		// }
	}
}

// Register the CLI command.
\WP_CLI::add_command( 'dmg-read-more', new CLI() );
