(function($) {
	// Store a reference to $ in the outer scope
	let previousData = {};

	// Get filter settings from PHP (WordPress options)
	const filterSettingsElement = $("#filter-settings");
	let filterSettings = {};

	if (filterSettingsElement.length && filterSettingsElement.text().trim().length > 0) {
		try {
			filterSettings = JSON.parse(filterSettingsElement.text());
		} catch (error) {
			console.error("Error parsing filter settings:", error);
		}
	} else {
		console.warn("No filter settings found or invalid JSON.");
	}

	// Dynamic handling of filter chips
	$(document).on('click', '.filter-chip', function () {
		const filterKey = $(this).data('filter-key'); // Internal filter reference
		const urlKey = $(this).data('url-key') || filterKey; // Use data-url-key if available
		const filterValue = $(this).data('filter');

		if ($(this).hasClass('active')) {
			$(this).removeClass('active');
			updateFiltersAndFetch({ [urlKey]: null }); // Remove from URL
		} else {
			$('.chip[data-filter-key="' + filterKey + '"]').removeClass('active');
			$(this).addClass('active');
			updateFiltersAndFetch({ [urlKey]: filterValue }); // Add to URL
		}
	});

	// Dynamic handling of checkbox-based filter lists
	$(document).on('change', '.filter-checkbox', function (e) {
		const filterKey = $(this).data('filter-key');
		const urlKey = $(this).data('url-key') || filterKey;
		
		const selectedValues = $('.filter-checkbox[data-filter-key="' + filterKey + '"]:checked').map(function () {
			return $(this).val();
		}).get();

		// Update dropdown text immediately
		updateDropdownText(filterKey, selectedValues);

		// Update filters and perform AJAX call with empty arrays if no values are selected
		const filterUpdate = selectedValues.length > 0 ? { [urlKey]: selectedValues } : { [urlKey]: null };
		updateFiltersAndFetch(filterUpdate);
	});

	// Handle search field
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
		
		// Map filter keys to their URL parameters
		const filterKeyMap = {
			'language': 'sprak',
			'locations': 'sted',
			'instructors': 'i',
			'categories': 'k',
			'months': 'mnd',
		};

		// Update dropdown texts for all filter types
		Object.entries(filterKeyMap).forEach(([filterKey, urlKey]) => {
			if (newFilters.hasOwnProperty(urlKey)) {
				const values = newFilters[urlKey] || [];
				updateDropdownText(filterKey, Array.isArray(values) ? values : [values]);
			} else if (updatedFilters[urlKey]) {
				const values = updatedFilters[urlKey];
				updateDropdownText(filterKey, Array.isArray(values) ? values : [values]);
			}
		});

		delete updatedFilters.nonce;
		delete updatedFilters.action;
		updateURLParams(updatedFilters);
		fetchCourses(Object.assign(updatedFilters, {side: 1}));
		updateActiveFiltersList(updatedFilters);
		toggleResetFiltersButton(updatedFilters);
	}

	function updateDropdownText(filterKey, activeFilters) {
		const $dropdown = $(`.filter-dropdown-toggle[data-filter="${filterKey}"]`);
		if (!$dropdown.length) {
			return;
		}

		const placeholder = $dropdown.data('placeholder') || 'Velg';

		// If no active filters, show only placeholder
		if (!activeFilters || activeFilters.length === 0) {
			const placeholderHtml = `<span class="selected-text">${placeholder}</span><span class="dropdown-icon"><i class="ka-icon icon-chevron-down"></i></span>`;
			$dropdown.html(placeholderHtml);
			$dropdown.removeClass('has-active-filters');
			return;
		}

		// Process active filter names
		let activeNames = [];
		if (filterKey === 'language') {
			activeNames = activeFilters.map(filter => filter.charAt(0).toUpperCase() + filter.slice(1));
		} else {
			activeFilters.forEach(slug => {
				const $element = $(`.filter-checkbox[data-filter-key="${filterKey}"][value="${slug}"]`);
				if ($element.length) {
					const labelText = $element.siblings('.checkbox-label').text().trim();
					activeNames.push(labelText);
				}
			});
		}

		// Display text format: show all if 2 or fewer, otherwise show count
		let displayText = activeNames.length <= 2 ? activeNames.join(', ') : `${activeNames.length} valgt`;
		const finalHtml = `<span class="selected-text">${displayText}</span><span class="dropdown-icon"><i class="ka-icon icon-chevron-down"></i></span>`;

		$dropdown.html(finalHtml);
		$dropdown.addClass('has-active-filters');
	}

	function resetAllFilters() {
		// Lagre eksisterende sorteringsparametere
		const currentFilters = getCurrentFiltersFromURL();
		const sort = currentFilters.sort;
		const order = currentFilters.order;

		// Reset all dropdowns to their placeholder state
		$('.filter-dropdown-toggle').each(function() {
			const $dropdown = $(this);
			const placeholder = $dropdown.data('placeholder') || 'Velg';
			const html = `<span class="selected-text">${placeholder}</span><span class="dropdown-icon"><i class="ka-icon icon-chevron-down"></i></span>`;
			$dropdown.html(html);
			$dropdown.removeClass('has-active-filters');
		});

		// Clear all active checkboxes
		$('.filter-checkbox:checked').prop('checked', false);

		// Oppdater URL og fetch med bare sorteringsparametere
		const updatedFilters = {};
		if (sort && order) {
			updatedFilters.sort = sort;
			updatedFilters.order = order;
		}

		updateURLParams(updatedFilters);
		fetchCourses({
			...updatedFilters,
			action: 'filter_courses',
			nonce: kurskalender_data.filter_nonce
		});

		updateActiveFiltersList(updatedFilters);
		toggleResetFiltersButton(updatedFilters);
	}

	function fetchCourses(data) {
		// Add required AJAX parameters
		data.action = 'filter_courses';
		data.nonce = kurskalender_data.filter_nonce;

		// Vis loading indikator
		$('.course-loading').show();

		$.ajax({
			url: kurskalender_data.ajax_url,
			type: 'POST',
			data: data,
			success: function(response) {
				if (response.success) {
					$('#filter-results').html(response.data.html);
					$('#course-count').html(response.data['course-count']);
					initAccordion();
					initSlideInPanel();
					updatePagination(response.data.html_pagination);

					// Scroll til toppen av resultatene med større offset
					$('html, body').animate({
						scrollTop: $('#filter-results').offset().top - 170
					}, 500);

					// Update dropdown states based on current URL filters
					const currentFilters = getCurrentFiltersFromURL();

					// Handle language filter updates
					if (currentFilters.sprak) {
						const languages = Array.isArray(currentFilters.sprak) ?
							currentFilters.sprak :
							[currentFilters.sprak];
						updateDropdownText('language', languages);
					}

					// Handle location filter updates
					if (currentFilters.sted) {
						const locations = Array.isArray(currentFilters.sted) ?
							currentFilters.sted :
							[currentFilters.sted];
						updateDropdownText('locations', locations);
					}

					// Handle instructor filter updates
					if (currentFilters.i) {
						const instructors = Array.isArray(currentFilters.i) ?
							currentFilters.i :
							[currentFilters.i];
						updateDropdownText('instructors', instructors);
					}

					// Handle month filter updates
					if (currentFilters.mnd) {
						const months = Array.isArray(currentFilters.mnd) ?
							currentFilters.mnd :
							[currentFilters.mnd];
						updateDropdownText('months', months);
					}
				} else {
					console.error('Filter Error:', response.data);
					$('#filter-results').html(response.data.message);
					$('#course-count').html('');
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX Error:', {xhr, status, error});
			},
			complete: function() {
				// Skjul loading indikator
				$('.course-loading').hide();
			}
		});

		previousData = {...data};
	}

	function clean(obj) {
		for (let propName in obj) {
			if (obj[propName] === null || obj[propName] === undefined || obj[propName] === "") {
				delete obj[propName];
			}
		}
		return obj
	}


	function updateURLParams(params) {
		const url = new URL(window.location.href);
		url.search = new URLSearchParams(clean(params)).toString();

		window.history.pushState({}, '', url.toString());
	}

	function getCurrentFiltersFromURL() {
		const url = new URL(window.location.href);
		const params = {};

		// Process all URL parameters
		for (const [key, value] of url.searchParams.entries()) {
			// Split comma-separated values into arrays
			params[key] = value.includes(',') ? value.split(',').map(v => v.trim()) : value;
		}

		return params;
	}

	function initializeFiltersFromURL() {
		const filters = getCurrentFiltersFromURL();

		// Initialize each filter based on URL parameters
		Object.keys(filters).forEach(function (filterKey) {
			const values = Array.isArray(filters[filterKey]) ? filters[filterKey] : [filters[filterKey]];

			// Handle chip-based filters
			if ($('.chip[data-url-key="' + filterKey + '"]').length) {
				values.forEach(value => {
					$('.chip[data-filter="' + value + '"][data-url-key="' + filterKey + '"]').addClass('active');
				});
			}

			// Handle checkbox-based filters
			if ($('.filter-checkbox[data-url-key="' + filterKey + '"]').length) {
				values.forEach(value => {
					const lowercaseValue = value.toLowerCase();
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

		// Create chips for each active filter
		Object.keys(filters).forEach(key => {
			// Ekskluder sorteringsparametere og andre systemparametere
			if (key !== 'nonce' && key !== 'action' && key !== 'sort' && key !== 'order' && key !== 'side' &&
				filters[key] && filters[key].length > 0) {
				const values = Array.isArray(filters[key]) ? filters[key] : [filters[key]];
				values.forEach(value => {
					// For månedsfilteret, bruk 'months' som filter-key for å matche med checkbox
					const filterKey = key === 'mnd' ? 'months' : key;
					
					// Finn riktig visningstekst basert på filtertype
					let displayText = value;
					
					// Prøv å finne checkbox med data-filter-key
					let $checkbox = $(`.filter-checkbox[data-filter-key="${filterKey}"][value="${value}"]`);
					
					// Hvis ikke funnet, prøv med data-url-key
					if (!$checkbox.length) {
						$checkbox = $(`.filter-checkbox[data-url-key="${key}"][value="${value}"]`);
					}
					
					if ($checkbox.length) {
						displayText = $checkbox.siblings('.checkbox-label').text().trim();
					} else if (filterKey === 'language' || key === 'sprak') {
						displayText = value.charAt(0).toUpperCase() + value.slice(1);
					}

					const filterChip = $(`<span class="active-filter-chip" data-filter-key="${filterKey}" data-filter-value="${value}">
						${displayText} <span class="remove-filter tooltip" data-title="Fjern filter">×</span>
					</span>`);

					// Handle filter removal
					filterChip.find('.remove-filter').on('click', function () {
						const filterKey = $(this).parent().data('filter-key');
						const filterValue = $(this).parent().data('filter-value');

						// Konverter tilbake til URL-nøkkel for månedsfilteret
						const urlKey = filterKey === 'months' ? 'mnd' : filterKey;

						if (filters[urlKey]) {
							if (Array.isArray(filters[urlKey])) {
								filters[urlKey] = filters[urlKey].filter(item => item !== filterValue);
							} else {
								filters[urlKey] = null;
							}
						}

						// Behold sorteringsparameterne når et filter fjernes
						const updatedFilters = {
							...filters,
							sort: filters.sort,
							order: filters.order
						};

						// Uncheck the corresponding checkbox
						const $checkbox = $(`.filter-checkbox[data-filter-key="${filterKey}"][value="${filterValue}"]`);
						if ($checkbox.length) {
							$checkbox.prop('checked', false);
						}

						// Update filters and fetch new results
						updateFiltersAndFetch(updatedFilters);
					});
					$activeFiltersContainer.append(filterChip);
				});
			}
		});

	}

	function toggleResetFiltersButton(filters) {
		const $resetButton = $('#reset-filters');
        const $activeFiltersContainer = $('#active-filters-container');
		const hasActiveFilters = Object.keys(filters).some(key =>
			key !== 'nonce' && key !== 'action' && key !== 'sort' && key !== 'order' &&
			filters[key] && filters[key].length > 0
		);
		hasActiveFilters ? $resetButton.addClass('active-filters') : $resetButton.removeClass('active-filters');
		hasActiveFilters ? $activeFiltersContainer.addClass('active') : $activeFiltersContainer.removeClass('active');
	}

	// Initialize filters on page load
	initializeFiltersFromURL();



	$(document).on('click', '.pagination-wrapper .pagination a', function (e) {
		e.preventDefault();
		const href = $(this).attr('href');
		const locate = new URL(href);

		// Oppdater URL uten å laste siden på nytt
		window.history.pushState({}, '', href);

		// Hent nye resultater via AJAX
		fetchCourses(Object.fromEntries(locate.searchParams.entries()));
	})

	function updatePagination(html) {
		$('.pagination-wrapper .pagination').html(html);
	}

	// Håndter browser back/forward
	window.addEventListener('popstate', function() {
		const currentFilters = getCurrentFiltersFromURL();
		fetchCourses(currentFilters);
	});

	// Date picker configuration
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
			showOn: "bottom",
			arrowOn: "center",
			autoAlign: false,
			inline: false,
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
				},
				{
					title: "Neste 3 måneder",
					startDate: moment(),
					endDate: moment().add(90, "days")
				},
				{
					title: "Neste halvår",
					startDate: moment(),
					endDate: moment().add(180, "days")
				},
				{
					title: "Ett år",
					startDate: moment(),
					endDate: moment().add(365, "days")
				}
			],
			onafterselect: function (caleran, startDate, endDate) {
				const fromDate = startDate.format("YYYY-MM-DD");
				const toDate = endDate.format("YYYY-MM-DD");
				updateFiltersAndFetch({ 'dato[from]': fromDate, 'dato[to]': toDate});
			}
		});

		// // Prevent calendar from closing on first input field click
		// dateInput.addEventListener("click", function (event) {
		// 	event.stopPropagation();
		// 	let caleranInstance = document.querySelector("#date-range").caleran;
		// 	if (!caleranInstance.isOpen) {
		// 		caleranInstance.showDropdown();
		// 	}
		// });
		// dateInput.addEventListener("focus", function (e) {
		// 	e.stopPropagation();
		// 	if (!dateInput.caleran.isOpen) {
		// 		dateInput.caleran.showDropdown();
		// 	}
		// })
	}

	// Initialize reset filters functionality
	$(document).ready(function() {
		$(document).on('click', '.reset-filters', function(e) {
			e.preventDefault();
			resetAllFilters();
		});
	});

	function initializeSorting() {
		const $sortDropdown = $('.sort-dropdown');
		if (!$sortDropdown.length) {
			console.log('Sort dropdown not found');
			return;
		}

		// Toggle dropdown
		$('.sort-dropdown').on('click', function(e) {
			e.stopPropagation();
			$(this).toggleClass('active');
		});

		const setCurrentSorting = () => {
			const currentAddress = new URL(window.location.toString());
			const sort = currentAddress.searchParams.get('sort');
			const order = currentAddress.searchParams.get('order');

			if (sort && order) {
				const title = $('.sort-option[data-sort="' + sort + '"][data-order="' + order + '"]').text();
				if (title) {
					$('.sort-dropdown .selected-text').text(title);
				}
			}
		}

		setCurrentSorting();

		// Handle sort option clicks
		$('.sort-option').on('click', function(e) {
			e.stopPropagation();
			const sortBy = $(this).data('sort');
			const order = $(this).data('order');

			// Oppdater selected text
			$('.sort-dropdown .selected-text').text($(this).text());

			// Hent eksisterende filtre og legg til sortering
			const currentFilters = getCurrentFiltersFromURL();
			const updatedFilters = {
				...currentFilters,
				sort: sortBy,
				order: order,
				side: 1
			};

			// Utfør filtrering med sortering
			updateFiltersAndFetch(updatedFilters);

			// Lukk dropdown
			$('.sort-dropdown').removeClass('active');
		});

		// Lukk dropdown når man klikker utenfor
		$(document).on('click', function(e) {
			if (!$(e.target).closest('.sort-dropdown').length) {
				$('.sort-dropdown').removeClass('active');
			}
		});
	}

	// Initialiser sortering - nå innenfor IIFE
	initializeSorting();

	function initializeRangePrice() {
		const rangeMin = document.getElementById('range-min');
		const rangeMax = document.getElementById('range-max');
		const priceMin = document.getElementById('price-min');
		const priceMax = document.getElementById('price-max');

		if (!rangeMin || !rangeMax || !priceMin || !priceMax) {
			return;
		}

		rangeMin.addEventListener('input', function () {
			priceMin.value = Math.min(this.value, rangeMax.value);
			this.value = priceMin.value;
		})
		rangeMax.addEventListener('input', function () {
			priceMax.value = Math.max(this.value, priceMin.value);
			this.value = priceMax.value;
		})

		function updateRangeFilter() {
			updateFiltersAndFetch({'pris[from]': priceMin.value, 'pris[to]': priceMax.value})
		}

		rangeMax.addEventListener('change', updateRangeFilter)
		rangeMin.addEventListener('change', updateRangeFilter)
		priceMin.addEventListener('change', updateRangeFilter)
		priceMax.addEventListener('change', updateRangeFilter)
	}

	initializeRangePrice();

})(jQuery);

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
			// Open/close dropdown on toggle click
			dropdownToggle.addEventListener("click", function (event) {
				event.stopPropagation();
				const isOpen = dropdownContent.style.display === "block";

				// Close all other dropdowns first
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

				// Toggle current dropdown
				dropdown.classList.toggle("open", !isOpen);
				dropdownContent.style.display = isOpen ? "none" : "block";
				dropdownIcon.innerHTML = isOpen ?
					'<i class="ka-icon icon-chevron-down"></i>' :
					'<i class="ka-icon icon-minus"></i>';
			});
		}
	});

	// Close all dropdowns when clicking outside
	document.addEventListener("click", function (event) {
		// Sjekk om klikket var utenfor alle dropdowns
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

	// Stopp propagering av klikk inni dropdowns
	dropdowns.forEach(dropdown => {
		dropdown.addEventListener("click", function(event) {
			event.stopPropagation();
		});
	});

	// Lukk dropdowns når man trykker ESC
	document.addEventListener("keydown", function(event) {
		if (event.key === "Escape") {
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

// Fjern eller kommenter ut alle disse gamle funksjonene og variabler
/*
let currentSort = '';
let currentOrder = '';

document.querySelector('.sort-dropdown').addEventListener('click', function(e) {
    this.classList.toggle('active');
});

document.querySelectorAll('.sort-option').forEach(option => {
    option.addEventListener('click', function() {
        const sortBy = this.dataset.sort;
        const order = this.dataset.order;

        document.querySelector('.sort-dropdown .selected-text').textContent = this.textContent;

        currentSort = sortBy;
        currentOrder = order;

        updateResults();
    });
});

function updateResults() {
    const filters = getActiveFilters();
    const searchQuery = document.querySelector('.filter-search')?.value || '';

    const data = {
        action: 'filter_courses',
        filters: filters,
        search: searchQuery,
        sort: currentSort,
        order: currentOrder,
        security: kursagentenAjax.nonce
    };
}
*/

// Initialiser sortering når dokumentet er klart
/*$(document).ready(function() {
    initializeSorting();
    // ... existing document.ready code ...
});*/

// ... rest of existing code ...
