<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Register routes */
add_action( 'rest_api_init', function () {
	register_rest_route( 'ldl/v1', '/libraries', array(
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'ldl_get_libraries_data',
		'permission_callback' => '__return_true',
		'args' => array(
			'exclude'    => array( 'sanitize_callback' => 'ldl_rest_sanitize_ids' ),
			'limit'      => array( 'default' => 9, 'sanitize_callback' => 'absint' ),
			'page'       => array( 'default' => 1, 'sanitize_callback' => 'absint' ),
			'libraries'  => array( 'sanitize_callback' => 'ldl_rest_sanitize_ids' ),
			'categories' => array( 'sanitize_callback' => 'ldl_rest_sanitize_ids' ),
			'layout'     => array( 'default' => 'list', 'sanitize_callback' => 'sanitize_text_field' ),
			'search'     => array( 'default' => true, 'sanitize_callback' => 'rest_sanitize_boolean' ),
			'nested'     => array( 'default' => false, 'sanitize_callback' => 'rest_sanitize_boolean' ),
			's'          => array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
			'orderby'    => array( 'default' => 'date', 'sanitize_callback' => 'sanitize_text_field' ),
			'order'      => array( 'default' => 'DESC', 'sanitize_callback' => function( $v ){ $v = strtoupper( $v ); return in_array( $v, array('ASC','DESC'), true ) ? $v : 'DESC'; } ),
		),
	) );

	// Favorites toggle (auth required)
	register_rest_route( 'ldl/v1', '/favorite', array(
		'methods'  => WP_REST_Server::CREATABLE,
		'callback' => 'ldl_toggle_favorite',
		'permission_callback' => function () { return is_user_logged_in(); },
		'args' => array(
			'post_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
		),
	) );
});

/** Utilities */
function ldl_rest_sanitize_ids( $raw ) {
	$ids = is_array($raw) ? $raw : ( strlen(trim((string)$raw)) ? explode(',', (string)$raw) : array() );
	return array_values( array_filter( array_map('absint', $ids) ) );
}

/** Build one document row */
function ldl_build_document_item( $post_id, $include_files = false ) {
	$file_url   = get_post_meta( $post_id, '_ldl_file_url', true );
	$file_id    = get_post_meta( $post_id, '_ldl_file_id', true );
	$reference  = get_post_meta( $post_id, '_ldl_reference', true );
	$downloads  = (int) get_post_meta( $post_id, '_ldl_downloads', true );

	// favorites: store user IDs in post meta _ldl_favorited_by (array)
	$favorited_by = get_post_meta( $post_id, '_ldl_favorited_by', true );
	$favorited_by = is_array( $favorited_by ) ? array_values( array_map('absint', $favorited_by) ) : array();
	$user_id      = get_current_user_id();
	$is_favorite  = $user_id ? in_array( $user_id, $favorited_by, true ) : false;

	$file_type = $file_size = '';
	if ( $file_id ) {
		$file_type = get_post_mime_type( $file_id );
		$path = get_attached_file( $file_id );
		if ( $path && file_exists( $path ) ) {
			$file_size = size_format( filesize( $path ) );
		}
	}

	$author_id = (int) get_post_field( 'post_author', $post_id );
	$terms     = wp_get_post_terms( $post_id, 'ldl_library' );
	$lib_ids   = array(); $lib_names = array();
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $t ) { $lib_ids[] = (int)$t->term_id; $lib_names[] = $t->name; }
	}

	$item = array(
		'id'          => (int) $post_id,
		'image'       => get_the_post_thumbnail_url( $post_id, 'thumbnail' ),
		'thumbnail'   => get_the_post_thumbnail_url( $post_id, 'medium' ),
		'reference'   => $reference ? (string)$reference : '',
		'title'       => get_the_title( $post_id ),
		'published'   => get_the_date( get_option('date_format'), $post_id ),
		'modified'    => get_the_modified_date( get_option('date_format'), $post_id ),
		'author'      => $author_id ? get_the_author_meta( 'display_name', $author_id ) : '',
		'isFavorite'  => (bool) $is_favorite,
		'favoritesCount' => count( $favorited_by ),
		'downloadsCount' => $downloads,
		'downloadUrl' => $file_url ? $file_url : ( $file_id ? wp_get_attachment_url( $file_id ) : '' ),
		'viewUrl'     => get_permalink( $post_id ),
		'meta'        => array(
			'fileType'   => $file_type,
			'fileSize'   => $file_size,
			'libraries'  => $lib_names,
			'libraryIds' => $lib_ids,
		),
	);

	if ( $include_files ) {
		$item['files'] = ldl_get_document_files( $post_id );
	}
	return $item;
}

/** Attachment list for nested files */
function ldl_get_document_files( $post_id ) {
	$out = array();
	$ids = get_posts( array(
		'post_type'      => 'attachment',
		'post_parent'    => (int)$post_id,
		'posts_per_page' => -1,
		'post_status'    => 'inherit',
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	foreach ( $ids as $aid ) {
		$size = ''; $path = get_attached_file( $aid );
		if ( $path && file_exists( $path ) ) { $size = size_format( filesize( $path ) ); }
		$out[] = array(
			'id'   => (int)$aid,
			'name' => get_the_title( $aid ),
			'downloadUrl' => wp_get_attachment_url( $aid ),
			'type' => get_post_mime_type( $aid ),
			'size' => $size,
		);
	}
	return $out;
}

/** Main GET /libraries */
function ldl_get_libraries_data( WP_REST_Request $req ) {
	$p = $req->get_params();

	$exclude   = ldl_rest_sanitize_ids( $p['exclude'] ?? array() );
	$libraries = ldl_rest_sanitize_ids( $p['libraries'] ?? array() );
	$cats      = ldl_rest_sanitize_ids( $p['categories'] ?? array() );
	$limit     = max( 1, min( 100, absint( $p['limit'] ?? 9 ) ) );
	$page      = max( 1, absint( $p['page'] ?? 1 ) );
	$offset    = ( $page - 1 ) * $limit;
	$orderby   = sanitize_key( $p['orderby'] ?? 'date' );
	$order     = strtoupper( $p['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';
	$layout    = in_array( $p['layout'] ?? 'list', array('list','grid','folder'), true ) ? $p['layout'] : 'list';
	$include_files = rest_sanitize_boolean( $p['nested'] ?? false );
	$search    = sanitize_text_field( $p['s'] ?? '' );
	$ui_search = rest_sanitize_boolean( $p['search'] ?? true );

	$general   = get_option( 'ldl_general_settings' );
	$use_cat   = ! empty( $general['enable_categories_filter'] );

	if ( $use_cat && ! empty( $libraries ) ) {
		return new WP_Error( 'ldl_invalid_param', __( 'Please use the categories attribute.', 'learndash-document-library' ), array( 'status' => 400 ) );
	}
	if ( ! $use_cat && ! empty( $cats ) ) {
		return new WP_Error( 'ldl_invalid_param', __( 'Please use the libraries attribute.', 'learndash-document-library' ), array( 'status' => 400 ) );
	}

	$tax_query = array();
	if ( $use_cat && ! empty( $cats ) ) {
		$lib_ids = array();
		foreach ( $cats as $cid ) {
			$rel = get_term_meta( $cid, 'ldl_related_terms', true );
			if ( is_array( $rel ) ) { $lib_ids = array_merge( $lib_ids, array_map('absint', $rel) ); }
		}
		$lib_ids = array_values( array_unique( array_filter( $lib_ids ) ) );
		if ( $lib_ids ) {
			$tax_query[] = array(
				'taxonomy' => 'ldl_library',
				'field'    => 'term_id',
				'terms'    => $lib_ids,
				'include_children' => true,
			);
		}
	} elseif ( ! $use_cat && ! empty( $libraries ) ) {
		$tax_query[] = array(
			'taxonomy' => 'ldl_library',
			'field'    => 'term_id',
			'terms'    => $libraries,
		);
	}

	$args = array(
		'post_type'           => 'ldl-document',
		'post_status'         => 'publish',
		'posts_per_page'      => $limit,
		'offset'              => $offset,
		'post__not_in'        => $exclude,
		'orderby'             => $orderby,
		'order'               => $order,
		's'                   => $search,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => false,
	);
	if ( $tax_query ) { $args['tax_query'] = $tax_query; }

	$q = new WP_Query( $args );

	$docs = array();
	if ( $q->have_posts() ) {
		while ( $q->have_posts() ) { $q->the_post();
			$docs[] = ldl_build_document_item( get_the_ID(), $include_files );
		}
		wp_reset_postdata();
	}

	$found = (int) $q->found_posts;
	$pages = (int) ceil( $found / max(1,$limit) );

	// Group docs by library for folder layout
	$groups = array();
	if ( 'folder' === $layout ) {
		$bucket = array();
		foreach ( $docs as $d ) {
			$ids = $d['meta']['libraryIds'] ?? array();
			$names = $d['meta']['libraries'] ?? array();
			if ( ! $ids ) {
				$bucket[0]['id'] = 0;
				$bucket[0]['title'] = __( 'General', 'learndash-document-library' );
				$bucket[0]['documents'][] = $d;
			} else {
				foreach ( $ids as $i => $tid ) {
					$tid = (int) $tid;
					if ( ! isset( $bucket[ $tid ] ) ) {
						$bucket[ $tid ] = array(
							'id'    => $tid,
							'title' => $names[ $i ] ?? __( 'Library', 'learndash-document-library' ),
							'documents' => array(),
						);
					}
					$bucket[ $tid ]['documents'][] = $d;
				}
			}
		}
		$groups = array_values( $bucket );
	}

	return rest_ensure_response( array(
		'success'   => true,
		'layout'    => $layout,
		'search'    => (bool) $ui_search,
		'limit'     => (int) $limit,
		'page'      => (int) $page,
		'per_page'  => (int) $limit,
		'found'     => $found,
		'total'     => count( $docs ),
		'pages'     => $pages,
		'documents' => 'folder' === $layout ? array() : $docs,
		'groups'    => 'folder' === $layout ? $groups : array(),
	) );
}

/** POST /favorite — toggle */
function ldl_toggle_favorite( WP_REST_Request $req ) {
	$post_id = absint( $req['post_id'] );
	if ( ! $post_id || get_post_type( $post_id ) !== 'ldl-document' ) {
		return new WP_Error( 'ldl_bad_doc', __( 'Invalid document.', 'learndash-document-library' ), array( 'status' => 400 ) );
	}
	$user_id = get_current_user_id();
	$list = get_post_meta( $post_id, '_ldl_favorited_by', true );
	$list = is_array( $list ) ? array_map('absint', $list) : array();

	if ( in_array( $user_id, $list, true ) ) {
		$list = array_values( array_diff( $list, array( $user_id ) ) );
		$is_fav = false;
	} else {
		$list[] = $user_id;
		$list = array_values( array_unique( $list ) );
		$is_fav = true;
	}
	update_post_meta( $post_id, '_ldl_favorited_by', $list );

	return rest_ensure_response( array(
		'post_id'        => $post_id,
		'isFavorite'     => $is_fav,
		'favoritesCount' => count( $list ),
	) );
}
