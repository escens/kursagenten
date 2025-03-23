<?php
function kursagenten_register_blocks() {
    $blocks = [
        'kurskalender', // block names
    ];

    foreach ( $blocks as $block ) {
        register_block_type( __DIR__ . '/' . $block );
    }
}
add_action( 'init', 'kursagenten_register_blocks' );
