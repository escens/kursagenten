jQuery(document).ready(function ($) {
    let previousData = {};

        // Hent filterinnstillinger fra PHP (WordPress options)
        const filterSettingsElement = $("#filter-settings");
        let filterSettings = {};

        if (filterSettingsElement.length && filterSettingsElement.text().trim().length > 0) {
            try {
                filterSettings = JSON.parse(filterSettingsElement.text());
            } catch (error) {
                console.error("Feil ved parsing av filterinnstillinger:", error);
            }
        } else {
            console.warn("Ingen filterinnstillinger funnet eller ugyldig JSON.");
        }


    // Dynamisk håndtering av chips
    $(document).on('click', '.filter-chip', function () {
        const filterKey = $(this).data('filter-key'); // Intern referanse til filteret
        const urlKey = $(this).data('url-key') || filterKey; // Bruk data-url-key hvis tilgjengelig
        const filterValue = $(this).data('filter');

        if ($(this).hasClass('active')) {
            $(this).removeClass('active');
            updateFiltersAndFetch({ [urlKey]: null }); // Fjern fra URL
        } else {
            $('.chip[data-filter-key="' + filterKey + '"]').removeClass('active');
            $(this).addClass('active');
            updateFiltersAndFetch({ [urlKey]: filterValue }); // Legg til i URL
        }
    });

    // Dynamisk håndtering av checkbox-baserte filter-lister
    $(document).on('change', '.filter-checkbox', function () {
        const filterKey = $(this).data('filter-key'); // Hent riktig filter-key fra checkbox
        const urlKey = $(this).data('url-key') || filterKey; // Bruk data-url-key hvis tilgjengelig
        const selectedValues = $('.filter-checkbox[data-filter-key="' + filterKey + '"]:checked').map(function () {
            return $(this).val();
        }).get();
    
        console.log("Filter valgt:", filterKey, "URL Key:", urlKey, "Verdier:", selectedValues); // Debugging
    
        updateFiltersAndFetch({ [urlKey]: selectedValues });
    });
    

    // Håndter søkefelt
    $('#search').on('keyup', function () {
        const sok = $(this).val();
        updateFiltersAndFetch({ sok: sok });
    });

    // Handle price slider
    /*const priceSlider = $("#price-range");
    const priceMin = $("#price-min");
    const priceMax = $("#price-max");

    priceSlider.slider({
        range: true,
        min: 0,
        max: 10000, // Juster dette etter maks kurspris
        values: [500, 5000], // Standardverdier, kan endres
        slide: function (event, ui) {
            priceMin.text(ui.values[0] + " kr");
            priceMax.text(ui.values[1] + " kr");
        },
        change: function (event, ui) {
            priceMin.text(ui.values[0] + " kr");
            priceMax.text(ui.values[1] + " kr");

            updateFiltersAndFetch({
                price_min: ui.values[0],
                price_max: ui.values[1]
            });
        }
    });

    // Sett innitiale verdier i tekstfeltene
    priceMin.text(priceSlider.slider("values", 0) + " kr");
    priceMax.text(priceSlider.slider("values", 1) + " kr");

    priceMin.text(priceSlider.slider("values", 0) + " kr");
    priceMax.text(priceSlider.slider("values", 1) + " kr");
*/
    

    function updateFiltersAndFetch(newFilters) {
        const currentFilters = getCurrentFiltersFromURL();
        const updatedFilters = { ...currentFilters, ...newFilters };
    
        // Sjekk om datofilter er definert og formater riktig
        if (updatedFilters.dato && typeof updatedFilters.dato === "object") {
            updatedFilters.dato.from = updatedFilters.dato.from || "";
            updatedFilters.dato.to = updatedFilters.dato.to || "";
        }
    
        console.log("Filtre som sendes til backend:", updatedFilters); // Debugging
    
        delete updatedFilters.nonce;
        delete updatedFilters.action;
        updateURLParams(updatedFilters);
        fetchCourses(updatedFilters);
        updateActiveFiltersList(updatedFilters);
        toggleResetFiltersButton(updatedFilters);
    }

    $(document).on('click', '.reset-filters', function (e) {
        e.preventDefault();
        resetFiltersUI();
        const url = new URL(window.location.href);
        url.search = '';
        window.history.pushState({}, '', url);
        fetchCourses({});
        updateActiveFiltersList({});
        toggleResetFiltersButton({});
    });

    function resetFiltersUI() {
        $('.filter-checkbox').prop('checked', false);
        $('#multi-coursecategory').val(null).trigger('change');
        $('#search').val('');
        $('.chip').removeClass('active');
    }

    function fetchCourses(data) {
        // Fjern denne sjekken eller gjør den mer presis
        /*if (JSON.stringify(data) === JSON.stringify(previousData)) {
            console.log("Ingen endringer i filteret. Skipper AJAX-kall.");
            return;
        }*/
        
        data.action = 'filter_courses';
        data.nonce = kurskalender_data.filter_nonce;
        
        console.log("Sender filterdata:", data);
        
        $.ajax({
            url: kurskalender_data.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                console.log("AJAX response:", response);
                if (response.success) {
                    $('#filter-results').html(response.data.html);
                    initAccordion();
                    initSlideInPanel();
                    updatePagination(response.data.max_num_pages);
                    updateCourseCount();
                } else {
                    console.error('Filter Error:', response.data);
                    $('#filter-results').html(response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr, status, error});
            }
        });
        
        previousData = {...data};
    }

    function updateURLParams(params) {
        const url = new URL(window.location.href);
        
        // Fjern eksisterende parametere
        url.search = '';
        
        // Legg til oppdaterte parametere
        Object.entries(params).forEach(([key, value]) => {
            if (value && value.length) {
                const paramValue = Array.isArray(value) ? value.join(',') : value;
                url.searchParams.set(key, paramValue);
            }
        });
        
        window.history.pushState({}, '', url);
    }

    function getCurrentFiltersFromURL() {
        const url = new URL(window.location.href);
        const params = {};
        
        // Gå gjennom alle URL-parametere
        for (const [key, value] of url.searchParams.entries()) {
            // Hvis verdien inneholder komma, splitt den til array
            params[key] = value.includes(',') ? value.split(',').map(v => v.trim()) : value;
        }
        
        console.log("Parsed URL params:", params); // Debugging
        return params;
    }

    function initializeFiltersFromURL() {
        const filters = getCurrentFiltersFromURL();
        console.log("Filtre hentet fra URL:", filters); // Debugging
    
        Object.keys(filters).forEach(function (filterKey) { // Sikrer at filterKey er definert
            const values = Array.isArray(filters[filterKey]) ? filters[filterKey] : [filters[filterKey]];
    
            console.log("Sjekker filter:", filterKey, "med verdi(er):", values); // Debugging
    
            if ($('.chip[data-url-key="' + filterKey + '"]').length) {
                values.forEach(value => {
                    console.log("Marker chip:", filterKey, "med verdi:", value); // Debugging
                    $('.chip[data-filter="' + value + '"][data-url-key="' + filterKey + '"]').addClass('active');
                });
            }
    
            if ($('.filter-checkbox[data-url-key="' + filterKey + '"]').length) {
                values.forEach(value => {
                    const lowercaseValue = value.toLowerCase(); // Sørg for at verdien er lowercase
                    console.log("Marker checkbox:", filterKey, "med verdi:", lowercaseValue); // Debugging
                    $('.filter-checkbox[data-url-key="' + filterKey + '"]').each(function () {
                        if ($(this).val().toLowerCase() === lowercaseValue) {
                            $(this).prop('checked', true);
                        }
                    });
                });
            }
        });
    
        updateActiveFiltersList(filters);
        toggleResetFiltersButton(filters);
    }
    

    

    function updateActiveFiltersList(filters) {
        const $activeFiltersContainer = $('#active-filters');
        $activeFiltersContainer.empty();

        Object.keys(filters).forEach(key => {
            if (key !== 'nonce' && key !== 'action' && filters[key] && filters[key].length > 0) {
                const values = Array.isArray(filters[key]) ? filters[key] : [filters[key]];
                values.forEach(value => {
                    const filterChip = $(
                        `<span class="active-filter-chip" data-filter-key="${key}" data-filter-value="${value}">
                            ${value} <span class="remove-filter tooltip" data-title="Fjern filter">×</span>
                        </span>`
                    );
                    filterChip.find('.remove-filter').on('click', function () {
                        const filterKey = $(this).parent().data('filter-key');
                        const filterValue = $(this).parent().data('filter-value');

                        if (filters[filterKey]) {
                            if (Array.isArray(filters[filterKey])) {
                                filters[filterKey] = filters[filterKey].filter(item => item !== filterValue);
                            } else {
                                filters[filterKey] = null;
                            }
                        }

                        updateFiltersAndFetch(filters);
                        $('.filter-checkbox[value="' + filterValue + '"]').prop('checked', false);
                    });
                    $activeFiltersContainer.append(filterChip);
                });
            }
        });
    }

    function toggleResetFiltersButton(filters) {
        const $resetButton = $('#reset-filters');
        const hasActiveFilters = Object.keys(filters).some(key => key !== 'nonce' && key !== 'action' && filters[key] && filters[key].length > 0);
        hasActiveFilters ? $resetButton.addClass('active-filters') : $resetButton.removeClass('active-filters');
    }

    // Initialiser ved innlasting
    initializeFiltersFromURL();
    const initialFilters = getCurrentFiltersFromURL();
    if (Object.keys(initialFilters).length > 0) {
        fetchCourses(initialFilters);
    }

    function updatePagination(maxPages) {
        // Oppdater eller bygg paginering her, hvis nødvendig
    }

    function updateCourseCount() {
        const counter = document.querySelector("#course-count");
        const elements = document.querySelectorAll(".courselist-item");
        //console.log("Course count:", elements.length);
        if (elements.length > 0) {
            elements.forEach((element) => {
                counter.textContent = document.querySelectorAll(".courselist-item").length + " kurs";
            });
        } else {
            counter.textContent = "0 kurs med dette filteret";
        }
    }


    // Datepicker for date filter
    //Documentation: https://preview.codecanyon.net/item/caleranjs-vanilla-js-date-range-picker/full_screen_preview/25972528
    const dateInput = document.getElementById("date-range");

    if (dateInput) {
        caleran("#date-range", {
            format: "DD.MM.YYYY",
            rangeOrientation: "vertical",
            calendarCount: 2,
            showHeader: true,
            showFooter: true,
            showButtons: true,
            applyLabel: "Bruk",
            cancelLabel: "Avbryt",
            nextMonthIcon: '<i class="ka-icon icon-chevron-right"></i>',
            prevMonthIcon: '<i class="ka-icon icon-chevron-left"></i>',
            rangeIcon: '<i class="ka-icon icon-calendar"></i>',
            headerSeparator: '<i class="ka-icon icon-chevron-right calendar-header-separator"></i>',
            rangeLabel: "Velg periode",
            ranges: [
            {
                title: "1 uke",                    
                startDate: moment(),
                endDate: moment().add(6, "days")
              },
              {
                title: "Neste 30 dager",                    
                startDate: moment(),
                endDate: moment().add(30, "days")
              },{
                title: "Neste 3 måneder",                    
                startDate: moment(),
                endDate: moment().add(90, "days")
              },{
                title: "Neste halvår",                    
                startDate: moment(),
                endDate: moment().add(180, "days")
              },{
                title: "Ett år",                    
                startDate: moment(),
                endDate: moment().add(365, "days")
              }
            ],
            onafterselect: function (caleran, startDate, endDate) {
                const fromDate = startDate.format("YYYY-MM-DD");
                const toDate = endDate.format("YYYY-MM-DD");

                console.log("Valgt dato:", fromDate, "til", toDate);
                updateFiltersAndFetch({ dato: { from: fromDate, to: toDate } });
            }
        });

        // Hindre at kalenderen lukkes ved første klikk på input-feltet
        dateInput.addEventListener("click", function (event) {
            event.stopPropagation();
            let caleranInstance = document.querySelector("#date-range").caleran; 
            if (!caleranInstance.isOpen) {
                caleranInstance.showDropdown();
            }
        });
    }
    
});

/*
    * Filter for pris : https://www.codingnepalweb.com/price-range-slider-html-css-javascript/
*/
/*
document.addEventListener("DOMContentLoaded", async function () {
    const rangeInputs = document.querySelectorAll(".range-input input"),
        priceInputs = document.querySelectorAll(".price-input input"),
        progress = document.querySelector(".slider .progress"),
        activePriceFilter = document.getElementById("active-price-filter"),
        filterWrapper = document.getElementById("filter-price");

    let priceGap = 100;
    let minPrice = 0;
    let maxPrice = 10000;
    let filterKey = filterWrapper.dataset.filterKey; // Henter filter-key for prisen

    // Henters minimum og maksimum pris fra kurslisten
    async function fetchPriceRange() {
        try {
            const response = await fetch(kurskalender_data.ajax_url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: new URLSearchParams({
                    action: "get_course_price_range",
                    nonce: kurskalender_data.filter_nonce
                })
            });

            const data = await response.json();

            if (data.success) {
                minPrice = data.min_price || 0;
                maxPrice = data.max_price || 10000;

                priceInputs[0].value = minPrice;
                priceInputs[1].value = maxPrice;
                rangeInputs[0].value = minPrice;
                rangeInputs[1].value = maxPrice;
                rangeInputs[0].min = minPrice;
                rangeInputs[1].max = maxPrice;

                updateProgress(minPrice, maxPrice);
                updateActiveFilter(minPrice, maxPrice);
            }
        } catch (error) {
            console.error("Feil ved henting av prisdata:", error);
        }
    }

    function updateProgress(minVal, maxVal) {
        progress.style.left = ((minVal / maxPrice) * 100) + "%";
        progress.style.right = 100 - (maxVal / maxPrice) * 100 + "%";
    }

    function updateActiveFilter(min, max) {
        activePriceFilter.textContent = `${min} kr - ${max} kr`;
        activePriceFilter.dataset.filter = `${min}-${max}`; // Oppdaterer data-attributt
    }

    function updateFiltersAndFetch(newFilters) {
        fetchCourses(newFilters); // Kaller AJAX-filtrering
    }

    function handlePriceInputChange(e) {
        let minPrice = parseInt(priceInputs[0].value);
        let maxPrice = parseInt(priceInputs[1].value);

        if ((maxPrice - minPrice >= priceGap) && maxPrice <= maxPrice) {
            rangeInputs[0].value = minPrice;
            rangeInputs[1].value = maxPrice;
            updateProgress(minPrice, maxPrice);
            updateActiveFilter(minPrice, maxPrice);
            updateFiltersAndFetch({ [filterKey + "_min"]: minPrice, [filterKey + "_max"]: maxPrice });
        }
    }

    function handleSliderChange(e) {
        let minVal = parseInt(rangeInputs[0].value);
        let maxVal = parseInt(rangeInputs[1].value);

        if ((maxVal - minVal) < priceGap) {
            if (e.target.classList.contains("range-min")) {
                rangeInputs[0].value = maxVal - priceGap;
            } else {
                rangeInputs[1].value = minVal + priceGap;
            }
        } else {
            priceInputs[0].value = minVal;
            priceInputs[1].value = maxVal;
            updateProgress(minVal, maxVal);
            updateActiveFilter(minVal, maxVal);
            updateFiltersAndFetch({ [filterKey + "_min"]: minVal, [filterKey + "_max"]: maxVal });
        }
    }

    priceInputs.forEach(input => input.addEventListener("input", handlePriceInputChange));
    rangeInputs.forEach(input => input.addEventListener("input", handleSliderChange));

    await fetchPriceRange(); // Hent min/maks-pris ved oppstart
});
*/

/* Filter list dropdown - functionality for dropdown*/
document.addEventListener("DOMContentLoaded", function () {
    const dropdowns = document.querySelectorAll(".filter-dropdown");

    dropdowns.forEach(dropdown => {
        const dropdownToggle = dropdown.querySelector(".filter-dropdown-toggle");
        const dropdownContent = dropdown.querySelector(".filter-dropdown-content");
        const dropdownIcon = dropdown.querySelector(".dropdown-icon");

        if (dropdownToggle && dropdownContent && dropdownIcon) {
            // Åpne/lukke dropdown ved klikk på toggle
            dropdownToggle.addEventListener("click", function (event) {
                event.stopPropagation();
                const isOpen = dropdownContent.style.display === "block";
                
                // Lukk alle andre dropdowns først
                dropdowns.forEach(otherDropdown => {
                    if (otherDropdown !== dropdown) {
                        const otherContent = otherDropdown.querySelector(".filter-dropdown-content");
                        const otherIcon = otherDropdown.querySelector(".dropdown-icon");
                        if (otherContent && otherIcon) {
                            otherContent.style.display = "none";
                            otherIcon.innerHTML = '<i class="ka-icon icon-chevron-down"></i>';
                            otherDropdown.classList.remove("open");
                        }
                    }
                });

                // Toggle nåværende dropdown
                dropdown.classList.toggle("open", !isOpen);
                dropdownContent.style.display = isOpen ? "none" : "block";
                dropdownIcon.innerHTML = isOpen ? 
                    '<i class="ka-icon icon-chevron-down"></i>' : 
                    '<i class="ka-icon icon-minus"></i>';
            });
        }
    });

    // Lukk alle dropdowns ved klikk utenfor
    document.addEventListener("click", function (event) {
        if (!event.target.closest('.filter-dropdown')) {
            dropdowns.forEach(dropdown => {
                const dropdownContent = dropdown.querySelector(".filter-dropdown-content");
                const dropdownIcon = dropdown.querySelector(".dropdown-icon");
                if (dropdownContent && dropdownIcon) {
                    dropdownContent.style.display = "none";
                    dropdownIcon.innerHTML = '<i class="ka-icon icon-chevron-down"></i>';
                    dropdown.classList.remove("open");
                }
            });
        }
    });
});
