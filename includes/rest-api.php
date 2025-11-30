<?php
/**
 * REST API endpoints for the LearnDash Document Library block/app
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure the document helper class is available.
if ( file_exists( LEARNDASH_DOCUMENT_LIBRARY_DIR . 'includes/class-learndash-document-library-document.php' ) ) {
	require_once LEARNDASH_DOCUMENT_LIBRARY_DIR . 'includes/class-learndash-document-library-document.php';
}

/**
 * Simple debug logger (respects WP_DEBUG).
 *
 * @param string $message
 * @param array  $context
 */
function ldl_debug_log( $message, $context = array() ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$line = sprintf( '[LDL REST] %s %s', $message, wp_json_encode( $context ) );
		error_log( $line ); // phpcs:ignore
	}
}

add_action( 'rest_api_init', 'ldl_register_rest_routes' );

/**
 * Register custom REST routes.
 */
function ldl_register_rest_routes() {
	register_rest_route(
		'ldl/v1',
		'/folders',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'ldl_rest_get_folders',
			'permission_callback' => '__return_true',
			'args'                => array(
				'categories' => array(
					'description'       => __( 'Comma-separated list of term IDs to include.', 'learndash-document-library' ),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'libraries' => array(
					'description'       => __( 'Comma-separated list of term IDs to include.', 'learndash-document-library' ),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'exclude' => array(
					'description'       => __( 'Comma-separated list of term IDs to exclude.', 'learndash-document-library' ),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				),
			),
		)
	);

	register_rest_route(
		'ldl/v1',
		'/documents',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'ldl_rest_get_documents',
			'permission_callback' => '__return_true',
			'args'                => array(
				'folder'     => array(
					'sanitize_callback' => 'absint',
				),
				'libraries'  => array(
					'sanitize_callback' => 'ldl_rest_sanitize_int_list',
				),
				'categories' => array(
					'sanitize_callback' => 'ldl_rest_sanitize_int_list',
				),
				'exclude'    => array(
					'sanitize_callback' => 'ldl_rest_sanitize_int_list',
				),
				'tags' => array(
  					'sanitize_callback' => 'ldl_rest_sanitize_int_list',
				),
			),
		)
	);

	register_rest_route(
		'ldl/v1',
		'/favorite',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'ldl_toggle_favorite_document',
			'permission_callback' => function () {
				return is_user_logged_in();
			},
			'args'                => array(
				'user_id' => array(
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
				'doc_id'  => array(
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);

	register_rest_route(
		'ldl/v1',
		'/download',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'ldl_increment_download_count',
			'permission_callback' => '__return_true',
			'args'                => array(
				'doc_id' => array(
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);

	register_rest_route(
		'ldl/v1',
		'/tags',
		array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => function() {
				$terms = get_terms([ 'taxonomy' => 'ldl_tag', 'hide_empty' => true ]);
				if ( is_wp_error( $terms ) ) return $terms;
				return rest_ensure_response( array_map( fn($t) => [ 'id' => (int) $t->term_id, 'name' => $t->name ], $terms ) );
			},
			'permission_callback' => '__return_true',
		)
	);

	// Check access endpoint
	register_rest_route(
		'ldl/v1',
		'/check-access',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'ldl_rest_check_access',
			'permission_callback' => '__return_true',
			'args'                => array(
				'term_id'   => array(
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
				'taxonomy'  => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'action'    => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);

	// Verify password endpoint
	register_rest_route(
		'ldl/v1',
		'/verify-password',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'ldl_rest_verify_password',
			'permission_callback' => '__return_true',
			'args'                => array(
				'term_id'   => array(
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
				'taxonomy'  => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'password'  => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);

	// Get current user roles endpoint
	register_rest_route(
		'ldl/v1',
		'/user-roles',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'ldl_rest_get_user_roles',
			'permission_callback' => '__return_true',
		)
	);
}

/**
 * Sanitize a list of integers (array or comma separated string).
 *
 * @param mixed $value Value to sanitize.
 * @return array
 */
function ldl_rest_sanitize_int_list( $value ) {
	if ( is_string( $value ) ) {
		$value = explode( ',', $value );
	}
	if ( ! is_array( $value ) ) {
		return array();
	}
	$value = array_map( 'intval', $value );
	return array_filter( $value, static function ( $v ) {
		return $v > 0;
	} );
}

/**
 * Build a lightweight folder response from ldl_library taxonomy.
 */
function ldl_rest_get_folders(WP_REST_Request $request) {
	$folder      = $request->get_param( 'folder' );
	$libraries   = $request->get_param( 'libraries' );
	$categories  = $request->get_param( 'categories' );
	$exclude     = $request->get_param( 'exclude' );
	// Dummy data for now (mirrors frontend demo folders)
	// $folders = array(
	// 	array( 'id' => 1, 'name' => 'Folder 1', 'parentId' => null, 'count' => 0 ),
	// 	array( 'id' => 101, 'name' => 'Folder A', 'parentId' => 1, 'count' => 0 ),
	// 	array( 'id' => 102, 'name' => 'Folder B', 'parentId' => 1, 'count' => 0 ),
	// 	array( 'id' => 103, 'name' => 'Folder A1', 'parentId' => 101, 'count' => 0 ),
	// 	array( 'id' => 104, 'name' => 'Folder A2', 'parentId' => 101, 'count' => 0 ),
	// 	array( 'id' => 2, 'name' => 'Folder 2', 'parentId' => null, 'count' => 0 ),
	// 	array( 'id' => 3, 'name' => 'Folder 3', 'parentId' => null, 'count' => 0 ),
	// 	array( 'id' => 4, 'name' => 'Folder 4', 'parentId' => null, 'count' => 0 ),
	// 	array( 'id' => 5, 'name' => 'Folder 5', 'parentId' => null, 'count' => 0 ),
	// 	array( 'id' => 6, 'name' => 'Folder 6', 'parentId' => null, 'count' => 0 ),
	// 	array( 'id' => 7, 'name' => 'Folder 7', 'parentId' => null, 'count' => 0 ),
	// 	array( 'id' => 8, 'name' => 'Folder 8', 'parentId' => null, 'count' => 0 ),
	// 	array( 'id' => 9, 'name' => 'Folder 9', 'parentId' => null, 'count' => 0 ),
	// 	array( 'id' => 10, 'name' => 'Folder 10', 'parentId' => null, 'count' => 0 ),
	// );
	$categories_ids = [];
	$libraries_ids = [];
	$exclude_ids = [];
	if ( ! empty( $categories ) ) {
        $categories_ids = array_map( 'intval', array_filter( explode( ',', $categories ) ) );
    }
	if ( ! empty( $libraries ) ) {
        $libraries_ids = array_map( 'intval', array_filter( explode( ',', $libraries ) ) );
    }
	if ( ! empty( $exclude ) ) {
        $exclude_ids = array_map( 'intval', array_filter( explode( ',', $exclude ) ) );
    }
	if(!empty($categories_ids)){
		foreach($categories_ids as $categories_id){
			$get_libraries = get_term_meta( $categories_id, 'ldl_related_terms', true );
			if(is_array($get_libraries)){
				$libraries_ids = array_merge($libraries_ids, $get_libraries);
			}
		}
	}
	$term_args = array(
		'taxonomy'   => 'ldl_library',
		'hide_empty' => false,
	);
	if ( ! empty( array_filter( $libraries_ids ) ) ) {
		$all_ids = [];
		foreach ( $libraries_ids as $parent_id ) {
			$all_ids[] = $parent_id;
			// Get all child terms recursively
			$descendants = ldl_get_term_descendants( $parent_id, 'ldl_library' );
			$all_ids = array_merge( $all_ids, $descendants );
		}
		// Remove duplicates
		$all_ids = array_unique( $all_ids );
		$term_args['include'] = $all_ids;
	}
	// if(!empty(array_filter($libraries_ids))){
	// 	$term_args['include'] = $libraries_ids;
	// }
	if(!empty(array_filter($exclude_ids))){
		$term_args['exclude'] = $exclude_ids;
	}
	$terms = get_terms($term_args);
	$folders = array();
	foreach ( $terms as $term ) {
		$folders[] = array(
			'id'       => $term->term_id,
			'name'     => $term->name,
			'parentId' => $term->parent ? (int) $term->parent : null,
			'count'    => (int) $term->count,
		);
	}
	ldl_debug_log( 'folders response (dummy)', array( 'count' => count( $folders ) ) );
	return rest_ensure_response( $folders );
}

/**
 * Fetch documents with optional filters.
 */
function ldl_rest_get_documents( WP_REST_Request $request ) {
	$folder      = $request->get_param( 'folder' );
	$libraries   = $request->get_param( 'libraries' );
	$categories  = $request->get_param( 'categories' );
	$exclude     = $request->get_param( 'exclude' );
	$tags		 = $request->get_param( 'tags' );
	$tax_query   = array();
	$user_id     = get_current_user_id();
	$favorites   = $user_id ? (array) get_user_meta( $user_id, 'favorite_documents', true ) : array();
	$favorites   = array_map( 'absint', $favorites );

	ldl_debug_log(
		'documents request',
		array(
			'folder'     => $folder,
			'libraries'  => $libraries,
			'categories' => $categories,
			'exclude'    => $exclude,
		)
	);

	// Collect all ldl_library term IDs from different sources
	$library_term_ids = array();

	// 1. From folder parameter
	if ( $folder ) {
		$library_term_ids[] = (int) $folder;
	}

	// 2. From libraries parameter
	if ( ! empty( $libraries ) ) {
		if ( is_array( $libraries ) ) {
			$library_term_ids = array_merge( $library_term_ids, array_map( 'intval', $libraries ) );
		} else {
			$libraries_array = array_map( 'intval', array_filter( explode( ',', $libraries ) ) );
			$library_term_ids = array_merge( $library_term_ids, $libraries_array );
		}
	}

	// 3. From categories parameter (get related library terms)
	if ( ! empty( $categories ) ) {
		$categories_ids = array();
		// Check if $categories is already an array or a string
		if ( is_array( $categories ) ) {
			$categories_ids = array_map( 'intval', $categories );
		} else {
			$categories_ids = array_map( 'intval', array_filter( explode( ',', $categories ) ) );
		}
		if ( ! empty( $categories_ids ) ) {
			foreach ( $categories_ids as $category_id ) {
				$get_libraries = get_term_meta( $category_id, 'ldl_related_terms', true );
				if ( is_array( $get_libraries ) && ! empty( $get_libraries ) ) {
					$library_term_ids = array_merge( $library_term_ids, array_map( 'intval', $get_libraries ) );
				}
			}
		}
	}

	// Remove duplicates and add to tax query if we have any library terms
	if ( ! empty( $library_term_ids ) ) {
		$library_term_ids = array_unique( $library_term_ids );
		$tax_query[] = array(
			'taxonomy' => 'ldl_library',
			'field'    => 'term_id',
			'terms'    => $library_term_ids,
			'operator' => 'IN', // Post must be in ANY of these library terms
		);
	}

	if ( ! empty( $tags ) ) {
		$tax_query[] = [
			'taxonomy' => 'ldl_tag',
			'field'    => 'term_id',
			'terms'    => (array) $tags,
		];
	}

	// Add relation only if there are multiple different taxonomies
	if ( count( $tax_query ) > 1 ) {
		$tax_query['relation'] = 'AND';
	}

	$query_args = array(
		'post_type'      => 'ldl-document',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'post__not_in'   => ! empty( $exclude ) ? array_map( 'intval', (array) $exclude ) : array(),
	);

	if ( ! empty( $tax_query ) ) {
		$query_args['tax_query'] = $tax_query;
	}

	// // Dummy documents (mirrors demo JSON)
	// $documents = array(
	// 	array(
	// 		'id'           => 7,
	// 		'reference'    => 7,
	// 		'title'        => 'Document-Library-Demo',
	// 		'type'         => 'pdf',
	// 		'url'          => 'https://documentlibrary.barn2.com/wp-content/uploads/sites/26/2023/02/Sample-PDF.pdf',
	// 		'size'         => '27 KB',
	// 		'folderId'     => 4,
	// 		'published'    => '2025-10-05',
	// 		'lastModified' => '2025-10-10',
	// 		'author'       => 'Admin',
	// 		'favorites'    => 0,
	// 		'downloads'    => 12,
	// 		'image'        => includes_url( 'images/media/default.png' ),
	// 		'isFavorite'   => in_array( 7, $favorites, true ),
	// 	),
	// 	array(
	// 		'id'           => 8,
	// 		'reference'    => 8,
	// 		'title'        => 'FileBird Screenshot',
	// 		'type'         => 'png',
	// 		'url'          => 'https://media-folder.ninjateam.org/media-folder-manager-for-wordpress/wp-content/uploads/sites/24/2025/10/filebird-screenshot.png',
	// 		'size'         => '68 KB',
	// 		'folderId'     => 4,
	// 		'published'    => '2025-10-05',
	// 		'lastModified' => '2025-10-10',
	// 		'author'       => 'Admin',
	// 		'favorites'    => 0,
	// 		'downloads'    => 6,
	// 		'image'        => includes_url( 'images/media/default.png' ),
	// 		'isFavorite'   => in_array( 8, $favorites, true ),
	// 	),
	// 	array(
	// 		'id'           => 9,
	// 		'reference'    => 9,
	// 		'title'        => 'FileBird Screenshot',
	// 		'type'         => 'png',
	// 		'url'          => 'https://media-folder.ninjateam.org/media-folder-manager-for-wordpress/wp-content/uploads/sites/24/2025/10/filebird-screenshot.png',
	// 		'size'         => '68 KB',
	// 		'folderId'     => 4,
	// 		'published'    => '2025-10-05',
	// 		'lastModified' => '2025-10-10',
	// 		'author'       => 'Admin',
	// 		'favorites'    => 0,
	// 		'downloads'    => 6,
	// 		'image'        => includes_url( 'images/media/default.png' ),
	// 		'isFavorite'   => in_array( 9, $favorites, true ),
	// 	),
	// 	array(
	// 		'id'           => 10,
	// 		'reference'    => 10,
	// 		'title'        => 'FileBird Screenshot',
	// 		'type'         => 'png',
	// 		'url'          => 'https://media-folder.ninjateam.org/media-folder-manager-for-wordpress/wp-content/uploads/sites/24/2025/10/filebird-screenshot.png',
	// 		'size'         => '68 KB',
	// 		'folderId'     => 4,
	// 		'published'    => '2025-10-05',
	// 		'lastModified' => '2025-10-10',
	// 		'author'       => 'Admin',
	// 		'favorites'    => 0,
	// 		'downloads'    => 6,
	// 		'image'        => includes_url( 'images/media/default.png' ),
	// 		'isFavorite'   => in_array( 10, $favorites, true ),
	// 	),
	// 	array(
	// 		'id'           => 11,
	// 		'reference'    => 11,
	// 		'title'        => 'FileBird Screenshot',
	// 		'type'         => 'png',
	// 		'url'          => 'https://media-folder.ninjateam.org/media-folder-manager-for-wordpress/wp-content/uploads/sites/24/2025/10/filebird-screenshot.png',
	// 		'size'         => '68 KB',
	// 		'folderId'     => 4,
	// 		'published'    => '2025-10-05',
	// 		'lastModified' => '2025-10-10',
	// 		'author'       => 'Admin',
	// 		'favorites'    => 0,
	// 		'downloads'    => 6,
	// 		'image'        => includes_url( 'images/media/default.png' ),
	// 		'isFavorite'   => in_array( 11, $favorites, true ),
	// 	),
	// 	array(
	// 		'id'           => 12,
	// 		'reference'    => 12,
	// 		'title'        => 'FileBird Screenshot',
	// 		'type'         => 'png',
	// 		'url'          => 'https://media-folder.ninjateam.org/media-folder-manager-for-wordpress/wp-content/uploads/sites/24/2025/10/filebird-screenshot.png',
	// 		'size'         => '68 KB',
	// 		'folderId'     => 4,
	// 		'published'    => '2025-10-05',
	// 		'lastModified' => '2025-10-10',
	// 		'author'       => 'Admin',
	// 		'favorites'    => 0,
	// 		'downloads'    => 6,
	// 		'image'        => includes_url( 'images/media/default.png' ),
	// 		'isFavorite'   => in_array( 12, $favorites, true ),
	// 	),
	// 	array(
	// 		'id'           => 13,
	// 		'reference'    => 13,
	// 		'title'        => 'FileBird CSV',
	// 		'type'         => 'csv',
	// 		'url'          => 'https://example.com/sample.csv',
	// 		'size'         => '68 KB',
	// 		'folderId'     => 4,
	// 		'published'    => '2025-10-05',
	// 		'lastModified' => '2025-10-10',
	// 		'author'       => 'Admin',
	// 		'favorites'    => 0,
	// 		'downloads'    => 3,
	// 		'image'        => includes_url( 'images/media/default.png' ),
	// 		'isFavorite'   => in_array( 13, $favorites, true ),
	// 	),
	// 	array(
	// 		'id'           => 14,
	// 		'reference'    => 14,
	// 		'title'        => 'FileBird Json',
	// 		'type'         => 'json',
	// 		'url'          => 'https://example.com/sample.json',
	// 		'size'         => '68 KB',
	// 		'folderId'     => 4,
	// 		'published'    => '2025-10-05',
	// 		'lastModified' => '2025-10-10',
	// 		'author'       => 'Admin',
	// 		'favorites'    => 0,
	// 		'downloads'    => 3,
	// 		'image'        => includes_url( 'images/media/default.png' ),
	// 		'isFavorite'   => in_array( 14, $favorites, true ),
	// 	),
	// 	array(
	// 		'id'           => 1,
	// 		'reference'    => 1,
	// 		'title'        => 'Agreement-Purchase',
	// 		'type'         => 'pdf',
	// 		'url'          => 'https://media-folder.ninjateam.org/media-folder-manager-for-wordpress/wp-content/uploads/sites/24/2025/10/Document-Library-Demo.pdf',
	// 		'size'         => '108 KB',
	// 		'folderId'     => 1,
	// 		'published'    => '2025-10-03',
	// 		'lastModified' => '2025-10-03',
	// 		'author'       => 'Admin',
	// 		'favorites'    => 0,
	// 		'downloads'    => 5,
	// 		'image'        => includes_url( 'images/media/default.png' ),
	// 		'isFavorite'   => in_array( 1, $favorites, true ),
	// 	),
	// 	array(
	// 		'id'           => 2,
	// 		'reference'    => 2,
	// 		'title'        => 'General-Information',
	// 		'type'         => 'docx',
	// 		'url'          => 'https://media-folder.ninjateam.org/media-folder-manager-for-wordpress/wp-content/uploads/sites/24/2025/10/General-Information.docx',
	// 		'size'         => '109 KB',
	// 		'folderId'     => 1,
	// 		'published'    => '2025-10-03',
	// 		'lastModified' => '2025-10-03',
	// 		'author'       => 'Admin',
	// 		'favorites'    => 0,
	// 		'downloads'    => 4,
	// 		'image'        => includes_url( 'images/media/default.png' ),
	// 		'isFavorite'   => in_array( 2, $favorites, true ),
	// 	),
	// 	array(
	// 		'id'           => 3,
	// 		'reference'    => 3,
	// 		'title'        => 'Video-Tutorial',
	// 		'type'         => 'mp4',
	// 		'url'          => 'https://media-folder.ninjateam.org/media-folder-manager-for-wordpress/wp-content/uploads/sites/24/2025/10/Video-Tutorial.mp4',
	// 		'size'         => '1 MB',
	// 		'folderId'     => 2,
	// 		'published'    => '2025-10-03',
	// 		'lastModified' => '2025-10-03',
	// 		'author'       => 'Admin',
	// 		'favorites'    => 0,
	// 		'downloads'    => 7,
	// 		'image'        => includes_url( 'images/media/default.png' ),
	// 		'isFavorite'   => in_array( 3, $favorites, true ),
	// 	),
	// 	array(
	// 		'id'           => 4,
	// 		'reference'    => 4,
	// 		'title'        => 'Impact Moderato',
	// 		'type'         => 'mp3',
	// 		'url'          => 'https://documentlibrary.barn2.com/wp-content/uploads/sites/26/2016/11/Corporate-Inspiration-Background-Short-2.mp3',
	// 		'size'         => '746 KB',
	// 		'folderId'     => 2,
	// 		'published'    => '2025-10-03',
	// 		'lastModified' => '2025-10-03',
	// 		'author'       => 'Admin',
	// 		'favorites'    => 0,
	// 		'downloads'    => 8,
	// 		'image'        => includes_url( 'images/media/default.png' ),
	// 		'isFavorite'   => in_array( 4, $favorites, true ),
	// 	),
	// 	array(
	// 		'id'           => 5,
	// 		'reference'    => 5,
	// 		'title'        => 'Outline',
	// 		'type'         => 'pdf',
	// 		'url'          => 'https://documentlibrary.barn2.com/wp-content/uploads/sites/26/2023/02/Sample-PDF.pdf',
	// 		'size'         => '27 KB',
	// 		'folderId'     => 3,
	// 		'published'    => '2025-10-03',
	// 		'lastModified' => '2025-10-03',
	// 		'author'       => 'Admin',
	// 		'favorites'    => 0,
	// 		'downloads'    => 2,
	// 		'image'        => includes_url( 'images/media/default.png' ),
	// 		'isFavorite'   => in_array( 5, $favorites, true ),
	// 	),
	// 	array(
	// 		'id'           => 6,
	// 		'reference'    => 6,
	// 		'title'        => 'Financial',
	// 		'type'         => 'xlsx',
	// 		'url'          => 'https://documentlibrary.barn2.com/wp-content/uploads/sites/26/2021/02/Sample-Excel.xlsx',
	// 		'size'         => '81 KB',
	// 		'folderId'     => 3,
	// 		'published'    => '2025-10-03',
	// 		'lastModified' => '2025-10-03',
	// 		'author'       => 'Admin',
	// 		'favorites'    => 0,
	// 		'downloads'    => 3,
	// 		'image'        => includes_url( 'images/media/default.png' ),
	// 		'isFavorite'   => in_array( 6, $favorites, true ),
	// 	),
	// );

	$posts = get_posts( $query_args );
	$documents = array();
	foreach ( $posts as $post ) {
		$doc_data = ldl_rest_prepare_document( $post );
		$doc_data['isFavorite'] = in_array( $doc_data['id'], $favorites, true );
		$documents[] = $doc_data;
	}

	ldl_debug_log( 'documents response (dummy)', array( 'count' => count( $documents ) ) );

	return rest_ensure_response( $documents );
}

/**
 * Add or remove favorite document for user.
 */
function ldl_toggle_favorite_document( WP_REST_Request $req ) {
	$user_id = absint( $req->get_param( 'user_id' ) );
	$doc_id  = absint( $req->get_param( 'doc_id' ) );
	if ( ! $user_id || ! $doc_id ) {
		return new WP_Error( 'ldl_invalid_param', __( 'Invalid user ID or document ID.', 'learndash-document-library' ), array( 'status' => 400 ) );
	}

	$saved_docs = (array) get_user_meta( $user_id, 'favorite_documents', true );
	$saved_docs = array_values( array_map( 'absint', $saved_docs ) );

	if ( in_array( $doc_id, $saved_docs, true ) ) {
		$saved_docs = array_diff( $saved_docs, array( $doc_id ) );
		$action     = 'removed';
	} else {
		$saved_docs[] = $doc_id;
		$action       = 'added';
	}

	update_user_meta( $user_id, 'favorite_documents', $saved_docs );

	return rest_ensure_response(
		array(
			'success' => true,
			'userId'  => $user_id,
			'docId'   => $doc_id,
			'action'  => $action,
		)
	);
}

/**
 * Increase download count of a document.
 */
function ldl_increment_download_count( WP_REST_Request $req ) {
	$doc_id = absint( $req->get_param( 'doc_id' ) );
	if ( ! $doc_id ) {
		return new WP_Error( 'ldl_invalid_param', __( 'Invalid document ID.', 'learndash-document-library' ), array( 'status' => 400 ) );
	}

	$downloads = (int) get_post_meta( $doc_id, '_ldl_downloads', true );
	$downloads++;
	update_post_meta( $doc_id, '_ldl_downloads', $downloads );

	return rest_ensure_response(
		array(
			'success'         => true,
			'docId'           => $doc_id,
			'downloadsCount'  => $downloads,
		)
	);
}

/**
 * Prepare a single document for the REST response.
 *
 * @param WP_Post $post Document post object.
 * @return array
 */
function ldl_rest_prepare_document( WP_Post $post ) {
	$id              = $post->ID;
	$uploaded_id     = get_post_meta( $id, '_ldl_uploaded_file', true );
	$attached_id     = get_post_meta( $id, '_ldl_attached_file_id', true );
	$custom_url      = get_post_meta( $id, '_ldl_document_url', true );
	$downloads		 = get_post_meta( $id, '_ldl_downloads', true );
	$file_id         = $uploaded_id ?: $attached_id;
	$download_url    = $file_id ? wp_get_attachment_url( $file_id ) : '';
	$download_url    = $download_url ? $download_url : $custom_url;
	$filetype        = $download_url ? wp_check_filetype( $download_url ) : array( 'ext' => '' );
	$size_label      = '';
	$published_date  = get_the_date( 'Y-m-d', $id );
	$last_modified   = get_post_modified_time( 'Y-m-d', true, $id );
	$tag_terms 		 = wp_get_post_terms( $id, 'ldl_tag', ['fields' => 'names'] );

	if ( $file_id ) {
		$file_path = get_attached_file( $file_id );
		if ( $file_path && file_exists( $file_path ) ) {
			$size_bytes = filesize( $file_path );
			if ( $size_bytes ) {
				$size_label = size_format( $size_bytes );
			}
		}
	}

	// Prefer the explicitly selected folder; otherwise take the first ldl_library term.
	$folder_terms = wp_get_post_terms( $id, 'ldl_library' );
	$folder_ids   = array();
	if ( ! empty( $folder_terms ) && ! is_wp_error( $folder_terms ) ) {
		$folder_ids = array_map( 'intval', wp_list_pluck( $folder_terms, 'term_id' ) );
	}
	$folder_id = ! empty( $folder_ids ) ? $folder_ids[0] : null;

	return array(
		'id'            => (int) $id,
		'reference'     => (int) $id,
		'title'         => get_the_title( $post ),
		'type'          => $filetype['ext'] ? $filetype['ext'] : '',
		'url'           => $download_url,
		'size'          => $size_label,
		'folderId'      => $folder_id,
		'folderIds'     => $folder_ids,
		'published'     => $published_date,
		'lastModified'  => $last_modified,
		'author'		=> get_the_author_meta( 'display_name', $post->post_author ),
		'downloads'		=> (int) $downloads,
		'image'			=> get_the_post_thumbnail_url((int) $id, 'thumbnail') ?: includes_url('images/media/default.png'),
		'tags' 			=> $tag_terms,
	);
}

/**
 * Get all child terms recursively.
 */
function ldl_get_term_descendants( $term_id, $taxonomy ) {
    $children = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'parent'     => $term_id,
        'fields'     => 'ids',
    ]);
    $all = $children;
    foreach ( $children as $child_id ) {
        $all = array_merge( $all, ldl_get_term_descendants( $child_id, $taxonomy ) );
    }
    return $all;
}

/**
 * Check if user has access to a term (library/category)
 */
function ldl_rest_check_access( WP_REST_Request $request ) {
	$term_id  = absint( $request->get_param( 'term_id' ) );
	$taxonomy = sanitize_text_field( $request->get_param( 'taxonomy' ) );
	$action   = sanitize_text_field( $request->get_param( 'action' ) );
	if ( ! $term_id || ! $taxonomy ) {
		return new WP_Error( 'invalid_params', __( 'Invalid parameters.', 'learndash-document-library' ), array( 'status' => 400 ) );
	}
	// Get term
	$term = get_term( $term_id, $taxonomy );
	if ( is_wp_error( $term ) || ! $term ) {
		return new WP_Error( 'invalid_term', __( 'Term not found.', 'learndash-document-library' ), array( 'status' => 404 ) );
	}
	// Check role restrictions first
	$allowed_roles = get_term_meta( $term_id, 'library_user_roles', true );
	$has_password  = get_term_meta( $term_id, 'library_password', true );
	$response = array(
		'allowed'        => true,
		'needs_password' => false,
		'reason'         => '',
		'term_name'      => $term->name,
	);
	// If no restrictions, allow access
	if ( empty( $allowed_roles ) && empty( $has_password ) ) {
		return rest_ensure_response( $response );
	}
	// Check user roles
	if ( ! empty( $allowed_roles ) && is_array( $allowed_roles ) ) {
		$current_user = wp_get_current_user();
		$user_roles   = (array) $current_user->roles;
		// Check if user has any of the allowed roles
		$has_role = ! empty( array_intersect( $allowed_roles, $user_roles ) );
		if ( ! $has_role ) {
			$response['allowed'] = false;
			$response['reason']  = 'role';
			return rest_ensure_response( $response );
		}
	}
	// If role check passed, check password
	if ( ! empty( $has_password ) ) {
		$response['needs_password'] = true;
		$response['allowed']        = false;
		$response['reason']         = 'password';
	}
	return rest_ensure_response( $response );
}

/**
 * Verify password for a term
 */
function ldl_rest_verify_password( WP_REST_Request $request ) {
	$term_id  = absint( $request->get_param( 'term_id' ) );
	$taxonomy = sanitize_text_field( $request->get_param( 'taxonomy' ) );
	$password = sanitize_text_field( $request->get_param( 'password' ) );
	if ( ! $term_id || ! $taxonomy || empty( $password ) ) {
		return new WP_Error( 'invalid_params', __( 'Invalid parameters.', 'learndash-document-library' ), array( 'status' => 400 ) );
	}
	// Get stored password
	$stored_password = get_term_meta( $term_id, 'library_password', true );
	if ( empty( $stored_password ) ) {
		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'No password required.', 'learndash-document-library' ),
			)
		);
	}
	// Verify password
	if ( $password === $stored_password ) {
		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Access granted!', 'learndash-document-library' ),
			)
		);
	}
	return rest_ensure_response(
		array(
			'success' => false,
			'message' => __( 'Incorrect password. Please try again in 30 seconds.', 'learndash-document-library' ),
		)
	);
}

/**
 * Get current user roles
 */
function ldl_rest_get_user_roles() {
	$current_user = wp_get_current_user();
	$user_roles   = (array) $current_user->roles;
	return rest_ensure_response(
		array(
			'user_id' => $current_user->ID,
			'roles'   => $user_roles,
		)
	);
}