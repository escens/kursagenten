document.addEventListener('DOMContentLoaded', function() {
    const filterButton = document.querySelector('.filter-toggle-button');
    const filterOverlay = document.querySelector('.mobile-filter-overlay');
    const closeButton = document.querySelector('.close-filter-button');
    const applyButton = document.querySelector('.apply-filters-button');

    // Vis/skjul filter-knapp basert på skjermstørrelse
    function toggleFilterButton() {
        if (window.innerWidth <= 768) {
            filterButton.style.display = 'flex';
        } else {
            filterButton.style.display = 'none';
        }
    }

    // Åpne filter overlay
    filterButton.addEventListener('click', () => {
        filterOverlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    });

    // Lukk filter overlay
    closeButton.addEventListener('click', () => {
        filterOverlay.style.display = 'none';
        document.body.style.overflow = '';
    });

    // Anvendt filter og lukk
    applyButton.addEventListener('click', () => {
        filterOverlay.style.display = 'none';
        document.body.style.overflow = '';
        // Oppdater resultater her
    });

    // Lytt på vindusendringer
    window.addEventListener('resize', toggleFilterButton);
    toggleFilterButton();
});