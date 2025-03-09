<?php

/**
 * Format a date according to WordPress settings and locale
 *
 * @param string $date_string Date string in Y-m-d H:i:s format
 * @param string $format Optional format override
 * @return string Formatted date
 */
function ka_format_date($date_string, $format = '') {
    if (empty($date_string)) {
        return '';
    }

    // Use provided format or get WordPress date format
    $date_format = $format ?: get_option('date_format');
    
    // Convert string to timestamp
    $timestamp = strtotime($date_string);
    
    // Return formatted date using wp_date() which handles timezone and localization
    return wp_date($date_format, $timestamp);
}

// Bildelasting
function kursagenten_upload_image($image_url, $post_id) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    $tmp_file = download_url($image_url);

    if (is_wp_error($tmp_file)) {
        error_log('Image download failed: ' . $tmp_file->get_error_message());
        return false;
    }

    $file = [
        'name'     => basename($image_url),
        'type'     => mime_content_type($tmp_file),
        'tmp_name' => $tmp_file,
        'error'    => 0,
        'size'     => filesize($tmp_file),
    ];

    $attachment_id = media_handle_sideload($file, $post_id);

    if (is_wp_error($attachment_id)) {
        error_log('Image upload failed: ' . $attachment_id->get_error_message());
        @unlink($tmp_file);
        return false;
    }

    return $attachment_id;
}
