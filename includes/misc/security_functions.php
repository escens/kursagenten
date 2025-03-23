<?php 

//Enabling Clickjacking Protection (X-Frame-Options) in WordPress. Strenghten security headers.

function strenghten_security_headers( $headers ) {
    $headers['X-XSS-Protection'] = '1; mode=block';
    $headers['X-Content-Type-Options'] = 'nosniff';
    $headers['X-Content-Security-Policy'] = 'default-src \'self\'; script-src \'self\';';

    return $headers;
}

add_filter( 'wp_headers', 'strenghten_security_headers' );


// Disable the Plugin and Theme Editor

if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

//Remove WordPress Version Number

add_filter('the_generator', '__return_empty_string');