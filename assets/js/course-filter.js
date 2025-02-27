jQuery(document).ready(function($) {
    // Function to update URL parameters
    function updateUrlParameters(filters) {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Håndter filterparametre
        if (filters && filters.length > 0) {
            urlParams.set('k', filters.join(','));
        } else {
            urlParams.delete('k');
        }
        
        // Oppdater URL uten å laste siden på nytt
        const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
        window.history.pushState({ path: newUrl }, '', newUrl);
    }

    // Håndter filter-klikk
    $('.filter-option').on('click', function(e) {
        e.preventDefault();
        const $this = $(this);
        const filterValue = $this.data('filter');
        
        // Hent aktive filtre
        const activeFilters = [];
        $('.filter-option.active').each(function() {
            activeFilters.push($(this).data('filter'));
        });
        
        // Oppdater URL
        updateUrlParameters(activeFilters);
        
        // Utfør AJAX-kall for filtrering
        filterCourses(activeFilters);
    });

    // AJAX-filtreringsfunksjon
    function filterCourses(filters) {
        $.ajax({
            url: kursagenten_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'filter_courses',
                nonce: kursagenten_ajax.nonce,
                k: filters.join(',')
            },
            success: function(response) {
                if (response.success) {
                    $('#course-grid').html(response.data.html);
                    $('.pagination').html(response.data.html_pagination);
                }
            },
            error: function(xhr, status, error) {
                console.error('Filtreringsfeil:', error);
            }
        });
    }

    // Håndter pagination-klikk
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        const urlParams = new URLSearchParams(new URL(href).search);
        const page = urlParams.get('side');
        
        if (page) {
            const currentParams = new URLSearchParams(window.location.search);
            currentParams.set('side', page);
            
            const newUrl = `${window.location.pathname}?${currentParams.toString()}`;
            window.history.pushState({ path: newUrl }, '', newUrl);
            
            // Utfør AJAX-kall med oppdaterte parametre
            filterCourses(currentParams.get('k') ? currentParams.get('k').split(',') : []);
        }
    });

    // Reset filters
    $('.reset-filters').on('click', function(e) {
        e.preventDefault();
        $('.filter-option').removeClass('active');
        updateUrlParameters([]);
        filterCourses([]);
    });
}); 