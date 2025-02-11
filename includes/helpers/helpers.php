<?php

// Datoformatering
function kursagenten_format_date($date_string) {
    return date_i18n('d.m.Y', strtotime($date_string));
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
