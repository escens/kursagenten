<?php

/**
 * Hide course images (attachments with meta key `is_course_image`) in Media Library by default.
 *
 * Important:
 * - Grid view and most "select image" modals use AJAX (`query-attachments`).
 * - If we only hide items via JS/CSS, pagination becomes confusing (you must click "Load more" many times).
 * - Therefore we filter at query level for both upload.php (list view) and AJAX attachment queries.
 */

/**
 * Add the "Vis kursbilder" toggle to the Media Library list view.
 *
 * UI text is Norwegian by design.
 *
 * @param array $views Views.
 * @return array
 */
function kursagenten_add_show_hidden_files_option( $views ) {
	$current = ( isset( $_GET['invisible_files'] ) && '1' === $_GET['invisible_files'] ) ? 'current' : '';

	// "All media items" (default).
	$views['all'] = '<a href="' . esc_url( remove_query_arg( 'invisible_files' ) ) . '" class="' . ( ! $current ? 'current' : '' ) . '">Websidens bilder</a>';

	// Toggle to show course images.
	$views['hidden_files'] = '<a href="' . esc_url( add_query_arg( 'invisible_files', '1' ) ) . '" class="' . esc_attr( $current ) . '">Kursbilder (fra Kursagenten)</a>';

	return $views;
}
add_filter( 'views_upload', 'kursagenten_add_show_hidden_files_option' );

/**
 * Apply meta_query filtering to upload.php list view.
 *
 * @param WP_Query $query WP query.
 * @return void
 */
function kursagenten_filter_media_library_query_for_hidden_files( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	// Only affect the Media Library screen (upload.php) list table query.
	global $pagenow;
	if ( 'upload.php' !== $pagenow ) {
		return;
	}

	if ( 'attachment' !== $query->get( 'post_type' ) ) {
		return;
	}

	$show_course_images = isset( $_GET['invisible_files'] ) && '1' === $_GET['invisible_files'];

	// If toggle is on: show only course images. Otherwise: hide course images.
	$query->set(
		'meta_query',
		array(
			array(
				'key'     => 'is_course_image',
				'compare' => $show_course_images ? 'EXISTS' : 'NOT EXISTS',
			),
		)
	);
}
add_action( 'pre_get_posts', 'kursagenten_filter_media_library_query_for_hidden_files' );

/**
 * Apply the same filtering to media modal / grid queries (AJAX query-attachments).
 *
 * This prevents the "only one visible image + click 'Load more' many times" issue,
 * because course images are excluded at the database query level (not hidden after render).
 *
 * @param array $args Query args.
 * @return array
 */
function kursagenten_filter_ajax_query_attachments_args( $args ) {
	// Respect the same toggle if it is passed along (rare, but harmless).
	$show_course_images = false;
	if ( isset( $_REQUEST['invisible_files'] ) ) {
		$show_course_images = ( '1' === (string) $_REQUEST['invisible_files'] );
	} elseif ( isset( $_REQUEST['query']['invisible_files'] ) ) {
		$show_course_images = ( '1' === (string) $_REQUEST['query']['invisible_files'] );
	}

	$meta_query   = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();
	$meta_query[] = array(
		'key'     => 'is_course_image',
		'compare' => $show_course_images ? 'EXISTS' : 'NOT EXISTS',
	);
	$args['meta_query'] = $meta_query;

	return $args;
}
add_filter( 'ajax_query_attachments_args', 'kursagenten_filter_ajax_query_attachments_args', 20 );
