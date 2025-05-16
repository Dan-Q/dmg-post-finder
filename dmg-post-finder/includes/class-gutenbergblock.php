<?php
/**
 * PHP code for the DMG Post Finder block.
 * Registers the block with WordPress and handles the server-side rendering of the block. Includes a REST API
 * endpoint for searching posts.
 *
 * @package DMG\PostFinder
 */

namespace DMG\PostFinder;

/**
 * Convenience class for registering the DMG Post Finder Gutenberg block and wrapping the block's functionality.
 */
class GutenbergBlock {
	/**
	 * Initialize the Gutenberg block. Call this after construction.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register block assets and server-side render callback.
	 */
	public function register_block() {
		// Register JS script for the block. This is built from the src/index.js file.
		wp_register_script(
			'dmg-post-finder-editor',
			plugins_url( '../build/index.js', __FILE__ ),
			array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-api-fetch' ),
			filemtime( plugin_dir_path( __FILE__ ) . '../build/index.js' ),
			true
		);

		// Register the block with WordPress.
		register_block_type(
			'dmg/post-finder',
			array(
				'editor_script'   => 'dmg-post-finder-editor',
				'render_callback' => array( $this, 'render_block' ),
				'attributes'      => array(
					'postId' => array(
						'type'    => 'number',
						'default' => 0,
					),
				),
			)
		);
	}

	/**
	 * Renders the block as it appears in the frontend, based on the postId attribute.
	 * In the event that the postId is not set, or the post is not found (or not published), the block
	 * render an empty string.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Rendered block output.
	 */
	public function render_block( $attributes ) {
		$post_id = isset( $attributes['postId'] ) ? intval( $attributes['postId'] ) : 0;

		if ( empty( $post_id ) ) {
			return '';
		}

		$post = get_post( $post_id );

		if ( ! $post || 'publish' !== $post->post_status ) {
			return '';
		}

		$post_title = get_the_title( $post_id );
		$post_url   = get_permalink( $post_id );

		return sprintf(
			// translators: 1: Post URL. 2: Post title.
			'<p class="dmg-read-more">Read More: <a href="%1$s">%2$s</a></p>',
			esc_url( $post_url ),
			esc_html( $post_title )
		);
	}

	/**
	 * Add a REST API endpoint for searching posts.
	 */
	public function register_rest_routes() {
		register_rest_route(
			'dmg-post-finder/v1',
			'/search',
			array(
				'methods'             => 'GET', // Searching is idempotent, so GET is appropriate.
				'callback'            => array( $this, 'search_posts' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'search'   => array(
						'required' => false,
						'type'     => 'string',
					),
					'page'     => array(
						'required' => false,
						'type'     => 'integer',
						'default'  => 1,
					),
					'per_page' => array(
						'required' => false,
						'type'     => 'integer',
						'default'  => 10,
					),
				),
			)
		);
	}

	/**
	 * Format a post for the API response: return an array with the post's ID, title, and URL.
	 *
	 * @param WP_Post $post The post to format.
	 * @return array The formatted post as an array [ 'id' => ..., 'title' => ..., 'url' => ... ].
	 */
	public function format_post_for_api( $post ) {
		return array(
			'id'    => $post->ID,

			/*
			 * NOTE: get_the_title() runs default filters which introduce HTML entities, which:
			 * (a) is incorrect for an API endpoint, and
			 * (b) is not needed because React will escape the output for our use case;
			 * so we use the raw post_title here.
			 */
			'title' => $post->post_title,
			'url'   => get_permalink( $post ),
		);
	}

	/**
	 * Search posts handler for REST API.
	 *
	 * @param WP_REST_Request $request Request object, which is expected to contain params `search` (the search term),
	 *                                 `page` (the page number), and `per_page` (the number of posts per page).
	 * @return WP_REST_Response Response object, ready for conversion to JSON.
	 */
	public function search_posts( $request ) {
		$search   = $request->get_param( 'search' );
		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );

		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// Search by post ID if it's a number.
		if ( is_numeric( $search ) ) {
			$post = get_post( absint( $search ) );
			if ( $post && 'publish' === $post->post_status ) {
				// We found a published post with that ID, so return it (otherwise continue to search by title or content).
				return array(
					'posts' => array(
						$this->format_post_for_api( $post ),
					),
					'total' => 1,
					'pages' => 1,
				);
			}
		}

		// Otherwise search by title or content.
		if ( ! empty( $search ) ) {
			$args['s'] = sanitize_text_field( $search );
		}

		$query = new \WP_Query( $args );

		$posts = array();
		foreach ( $query->posts as $post ) {
			$posts[] = $this->format_post_for_api( $post );
		}

		return array(
			'posts' => $posts,
			'total' => $query->found_posts,
			'pages' => $query->max_num_pages,
		);
	}
}
