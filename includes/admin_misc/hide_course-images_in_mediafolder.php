<?php

// Add the "Show Hidden Files" option to the list view in the media library
function add_show_hidden_files_option($views) {
    $current = (isset($_GET['invisible_files']) && $_GET['invisible_files'] === '1') ? 'current' : '';

    // Add back the default "All Media Items" link
    $views['all'] = '<a href="' . remove_query_arg('invisible_files') . '" class="' . (!$current ? 'current' : '') . '">Alle mediefiler</a>';

    // Add the "Show Hidden Files" link
    $views['hidden_files'] = '<a href="' . esc_url(add_query_arg('invisible_files', '1')) . '" class="' . $current . '">Vis kursbilder</a>';

    // Add back the default "All Media Items" link
    $views['all'] = '<a href="' . remove_query_arg('invisible_files') . '" class="' . (!$current ? 'current' : '') . '">Alle mediefiler</a>';

    return $views;
}
add_filter('views_upload', 'add_show_hidden_files_option');

// Modify the media library query to show or hide hidden files in the list view
function filter_media_library_query_for_hidden_files($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    // Adjust the query based on the "invisible_files" parameter
    if (isset($_GET['invisible_files']) && $_GET['invisible_files'] === '1') {
        $meta_query = array(
            array(
                'key'     => 'is_course_image',
                'value'   => true,
                'compare' => 'EXISTS',
            ),
        );
        $query->set('meta_query', $meta_query);
    } else {
        // Exclude hidden files by default
        $meta_query = array(
            array(
                'key'     => 'is_course_image',
                'value'   => true,
                'compare' => 'NOT EXISTS',
            ),
        );
        $query->set('meta_query', $meta_query);
    }
}
add_action('pre_get_posts', 'filter_media_library_query_for_hidden_files');

// Add JavaScript to hide specific images in the media grid view based on the aria-label attribute
function load_more_if_hidden_items() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Function to hide images whose aria-label starts with 'kursbilde-zrs'
            function hideImagesByAriaLabel() {
                $('.attachment').each(function() {
                    var ariaLabel = $(this).attr('aria-label');
                    if (ariaLabel && ariaLabel.startsWith('kursbilde-zrs')) {
                        $(this).hide();
                    }
                });
            }

            // Function to check if we need to load more items
            function checkHiddenItems() {
                var hiddenCount = $('.attachments .attachment[style*="display: none"]').length;
                var visibleCount = $('.attachments .attachment:not([style*="display: none"])').length;

                // If more than 80 items are hidden, trigger the "Load More" button
                if (hiddenCount >= 80 && visibleCount === 0) {
                    $('.load-more-wrapper .load-more').trigger('click');
                }
            }

            // Initial hide on page load
            hideImagesByAriaLabel();
            checkHiddenItems();

            // Monitor for changes in the media library and Insert Media views
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && $(mutation.target).find('.attachment').length > 0) {
                        hideImagesByAriaLabel();
                        checkHiddenItems();
                    }
                });
            });

            // Observe the media library and modal for changes
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            // Hide images in the Insert Media view when it's opened
            $(document).on('click', '.media-modal .media-frame-menu .media-menu-item', function() {
                setTimeout(function() {
                    hideImagesByAriaLabel();
                    checkHiddenItems();
                }, 500); // Delay to ensure content has loaded
            });

            // Monitor category or folder change within Insert Media view
            $(document).on('click', '.media-modal .attachments-browser .media-toolbar .media-button', function() {
                setTimeout(function() {
                    hideImagesByAriaLabel();
                    checkHiddenItems();
                }, 500); // Delay to ensure new images are loaded
            });
        });
    </script>
    <?php
}
add_action('admin_footer', 'load_more_if_hidden_items');
