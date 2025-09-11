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
		const $checkbox = $(this);
		
		// Spesiell UX-logikk for kategorier: når et barn krysses av, fjern kryss på tilhørende forelder.
		// Når en forelder krysses av, fjern kryss på alle dens barn.
		if (filterKey === 'categories') {
			const isChecked = $checkbox.is(':checked');
			const $childrenWrapper = $checkbox.closest('.ka-children');
			if ($childrenWrapper.length) {
				// Dette er et barn
				if (isChecked) {
					const $parentCategory = $childrenWrapper.prev('.filter-category.toggle-parent');
					const $parentCheckbox = $parentCategory.find('input.filter-checkbox');
					if ($parentCheckbox.prop('checked')) {
						// Fjern kryss på forelder uten å trigge ny change (vi oppdaterer samlet under)
						$parentCheckbox.prop('checked', false);
					}
				}
			} else {
				// Dette er en forelder
				if (isChecked) {
					const $children = $checkbox.closest('.filter-category').next('.ka-children');
					if ($children && $children.length) {
						$children.find('input.filter-checkbox:checked').prop('checked', false);
					}
				}
			}
		}
		
		const selectedValues = $('.filter-checkbox[data-filter-key="' + filterKey + '"]:checked').map(function () {
			return $(this).val();
		}).get();

		// Update dropdown text immediately
		updateDropdownText(filterKey, selectedValues);

		// Update filters and perform AJAX call with empty arrays if no values are selected
		const filterUpdate = selectedValues.length > 0 ? { [urlKey]: selectedValues } : { [urlKey]: null };
		updateFiltersAndFetch(filterUpdate);
		
		// Oppdater filter counts etter at filtre er endret
		setTimeout(updateFilterCounts, 500);
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
		
		// Fjern null/undefined verdier
		Object.keys(updatedFilters).forEach(key => {
			if (updatedFilters[key] === null || updatedFilters[key] === undefined) {
				delete updatedFilters[key];
			}
		});

		// Konverter datoformat hvis nødvendig
		if (updatedFilters.dato && updatedFilters.dato.includes(',')) {
			const [fromDate, toDate] = updatedFilters.dato.split(',');
			const from = moment(fromDate).format('DD.MM.YYYY');
			const to = moment(toDate).format('DD.MM.YYYY');
			updatedFilters.dato = `${from}-${to}`;
		}

		// Reset til side 1 hvis vi fjerner eller legger til filtre
		// (men ikke hvis vi eksplisitt navigerer til en side)
		if (!newFilters.hasOwnProperty('side')) {
			updatedFilters.side = 1;
		}

		delete updatedFilters.nonce;
		delete updatedFilters.action;
		
		updateURLParams(updatedFilters);
		fetchCourses(updatedFilters);
		updateActiveFiltersList(updatedFilters);
		toggleResetFiltersButton(updatedFilters);

		// Varsle mobil-overlay om at filtre er oppdatert
		try { window.dispatchEvent(new CustomEvent('ka:filters-updated', { detail: updatedFilters })); } catch(e) {}
	}

	// Tillat mobilscript å trigge samme flyt via CustomEvent
	window.addEventListener('ka:update-filters', function(e) {
		try {
			const payload = (e && e.detail && typeof e.detail === 'object') ? e.detail : {};
			updateFiltersAndFetch(payload);
		} catch(err) { console.error('ka:update-filters handler error', err); }
	});

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

		// Reset datofilteret
		const dateInput = document.getElementById("date-range");
		if (dateInput) {
			dateInput.value = '';
		}

		// Oppdater URL og fetch med bare sorteringsparametere
		const updatedFilters = {};
		if (sort && order) {
			updatedFilters.sort = sort;
			updatedFilters.order = order;
		}

		// Legg til kortkode-parametre hvis de finnes
		if (typeof kurskalender_data !== 'undefined' && kurskalender_data.has_shortcode_filters && kurskalender_data.shortcode_params) {
			Object.keys(kurskalender_data.shortcode_params).forEach(key => {
				updatedFilters[key] = kurskalender_data.shortcode_params[key];
			});
		}

		// Fjern datofilteret fra URL-en
		delete updatedFilters['fromdate'];
		delete updatedFilters['todate'];

		updateURLParams(updatedFilters);
		fetchCourses({
			...updatedFilters,
			action: 'filter_courses',
			nonce: kurskalender_data.filter_nonce
		});

		updateActiveFiltersList(updatedFilters);
		toggleResetFiltersButton(updatedFilters);
		
		// Oppdater filter counts etter nullstilling
		setTimeout(updateFilterCounts, 500);
	}

	function fetchCourses(data) {
		// console.log('=== START: fetchCourses ===');
		// console.log('AJAX-kall med data:', data);
		
		// Add required AJAX parameters
		data.action = 'filter_courses';
		data.nonce = kurskalender_data.filter_nonce;

		// Behold side-parameteren fra URL hvis den finnes og ingen ny side er spesifisert
		if (!data.side) {
			const urlParams = new URLSearchParams(window.location.search);
			const urlSide = urlParams.get('side');
			if (urlSide) {
				data.side = urlSide;
				// console.log('Bruker side-parameter fra URL:', urlSide);
			}
		}

		// Vis loading indikator
		$('.course-loading').show();
		// console.log('Sender AJAX-forespørsel til:', kurskalender_data.ajax_url);

		$.ajax({
			url: kurskalender_data.ajax_url,
			type: 'POST',
			data: data,
			success: function(response) {
				// console.log('Svar fra server:', response);
				if (response.success) {
					// console.log('Antall kurs funnet:', response.data['course-count']);
					// console.log('HTML-lengde:', response.data.html.length);
					
					// Legg til null-sjekk for html_pagination
					if (response.data.html_pagination) {
						// console.log('Paginering HTML-lengde:', response.data.html_pagination.length);
						updatePagination(response.data.html_pagination);
					} else {
						// console.log('Ingen paginering HTML mottatt');
						updatePagination('');
					}
					
					$('#filter-results').html(response.data.html);
					$('#course-count').html(response.data['course-count']);
					initAccordion();
					initSlideInPanel();

					// Scroll til toppen av resultatene med bedre offset
					const $filterResults = $('#filter-results');
					if ($filterResults.length) {
						// console.log('Scroller til filter-results');
						
						// Finn den beste scroll-posisjonen
						let scrollTarget;
						
						// Sjekk først etter topp-filter-seksjonen
						const $topFilterSection = $('.top-filter-section');
						if ($topFilterSection.length) {
							scrollTarget = $topFilterSection.offset().top - 100;
						} else {
							// Fallback til aktive filtre hvis de er synlige
							const $activeFilters = $('#active-filters-container');
							if ($activeFilters.length && $activeFilters.is(':visible')) {
								scrollTarget = $activeFilters.offset().top - 100;
							} else {
								// Siste fallback til filter-results med offset for sticky meny
								scrollTarget = $filterResults.offset().top - 100;
							}
						}
						
						$('html, body').animate({
							scrollTop: scrollTarget
						}, 500);
					}

					// Update dropdown states based on current URL filters
					const currentFilters = getCurrentFiltersFromURL();
					// console.log('Filtre etter oppdatering:', currentFilters);
					
					// Oppdater filter counts etter at kurs er hentet
					setTimeout(updateFilterCounts, 100);

					// Handle language filter updates
					if (currentFilters.sprak) {
						const languages = Array.isArray(currentFilters.sprak) ?
							currentFilters.sprak :
							[currentFilters.sprak];
						// console.log('Oppdaterer språkfilter:', languages);
						updateDropdownText('language', languages);
					}

					// Handle location filter updates
					if (currentFilters.sted) {
						const locations = Array.isArray(currentFilters.sted) ?
							currentFilters.sted :
							[currentFilters.sted];
						// console.log('Oppdaterer lokasjonsfilter:', locations);
						updateDropdownText('locations', locations);
					}

					// Handle instructor filter updates
					if (currentFilters.i) {
						const instructors = Array.isArray(currentFilters.i) ?
							currentFilters.i :
							[currentFilters.i];
						// console.log('Oppdaterer instruktørfilter:', instructors);
						updateDropdownText('instructors', instructors);
					}

					// Handle month filter updates
					if (currentFilters.mnd) {
						const months = Array.isArray(currentFilters.mnd) ?
							currentFilters.mnd :
							currentFilters.mnd.split(',').map(m => m.trim());
						
						// console.log('Oppdaterer månedsfilter:', months);
						
						// Konverter måneder til to-sifret format
						const formattedMonths = months.map(month => {
							const numMonth = parseInt(month, 10);
							return numMonth >= 1 && numMonth <= 12 ? 
								numMonth.toString().padStart(2, '0') : 
								month;
						});
						
						// console.log('Formaterte måneder:', formattedMonths);
						updateDropdownText('months', formattedMonths);
					}
				} else {
					console.error('Filter Error:', response.data);
					$('#filter-results').html(response.data.message);
					$('#course-count').html('');
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX Error:', {
					status: status,
					error: error,
					response: xhr.responseText
				});
			},
			complete: function() {
				// Skjul loading indikator
				$('.course-loading').hide();
				// console.log('=== END: fetchCourses ===');
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
		
		// Legg til kortkode-parametre hvis de finnes
		if (typeof kurskalender_data !== 'undefined' && kurskalender_data.has_shortcode_filters && kurskalender_data.shortcode_params) {
			Object.keys(kurskalender_data.shortcode_params).forEach(key => {
				// Kun legg til hvis parameteren ikke allerede finnes i params
				if (!params[key]) {
					params[key] = kurskalender_data.shortcode_params[key];
				}
			});
		}
		
		url.search = new URLSearchParams(clean(params)).toString();

		window.history.pushState({}, '', url.toString());
	}

	function getCurrentFiltersFromURL() {
		const url = new URL(window.location.href);
		const params = {};

		// Process all URL parameters
		for (const [key, value] of url.searchParams.entries()) {
			// Spesiell håndtering for dato-parameter
			if (key === 'dato') {
				params[key] = value;  // Behold dato-stringen som den er
			} else if (key === 'side') {
				params[key] = parseInt(value, 10);  // Konverter side til tall
			} else {
				// Håndter andre parametere som før
				params[key] = value.includes(',') ? value.split(',').map(v => v.trim()) : value;
			}
		}

		// Legg til kortkode-parametre hvis de finnes
		if (typeof kurskalender_data !== 'undefined' && kurskalender_data.has_shortcode_filters && kurskalender_data.shortcode_params) {
			Object.keys(kurskalender_data.shortcode_params).forEach(key => {
				// Kun legg til hvis parameteren ikke allerede finnes i URL
				if (!params[key]) {
					params[key] = kurskalender_data.shortcode_params[key];
				}
			});
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
			if (key !== 'nonce' && key !== 'action' && key !== 'sort' && key !== 'per_page' && key !== 'order' && key !== 'side' &&
				filters[key] && filters[key].length > 0) {
				
				// Ekskluder kortkode-parametere fra aktive filtre
				if (typeof kurskalender_data !== 'undefined' && kurskalender_data.has_shortcode_filters && 
					kurskalender_data.shortcode_params && kurskalender_data.shortcode_params[key]) {
					return;
				}
				
				// Spesiell håndtering for datofilteret
				if (key === 'dato' && filters['dato']) {
					const [fromDate, toDate] = filters['dato'].split('-');
					
					const filterChip = $(`<span class="active-filter-chip button-filter" data-filter-key="date" data-filter-value="date">
						${fromDate} - ${toDate} <span class="remove-filter tooltip" data-title="Fjern filter">×</span>
					</span>`);

					filterChip.find('.remove-filter').on('click', function() {
						updateFiltersAndFetch({ 
							'dato': null
						});
					});

					$activeFiltersContainer.append(filterChip);
					return;
				}

				// Hopp over todate siden den er håndtert over
				if (key === 'todate' || key === 'fromdate') {
					return;
				}

				const values = Array.isArray(filters[key]) ? filters[key] : [filters[key]];
				values.forEach(value => {
					// For månedsfilteret, bruk 'months' som filter-key for å matche med checkbox
					const filterKey = key === 'mnd' ? 'months' : 
					                   key === 'k' ? 'categories' : 
					                   key === 'sted' ? 'locations' : 
					                   key === 'i' ? 'instructors' : 
					                   key === 'sprak' ? 'language' : key;
					
					// Finn riktig visningstekst basert på filtertype
					let displayText = value;
					
					// Prøv å finne checkbox med data-filter-key (søk i både topp og venstre filter)
					let $checkbox = $(`.filter-checkbox[data-filter-key="${filterKey}"][value="${value}"]`);
					
					// Hvis ikke funnet, prøv med data-url-key (søk i både topp og venstre filter)
					if (!$checkbox.length) {
						$checkbox = $(`.filter-checkbox[data-url-key="${key}"][value="${value}"]`);
					}
					
					// Hvis fortsatt ikke funnet, prøv å søke spesifikt i venstre kolonne
					if (!$checkbox.length) {
						$checkbox = $('.left-column .filter-checkbox[data-filter-key="' + filterKey + '"][value="' + value + '"]');
					}
					
					// Hvis fortsatt ikke funnet, prøv å søke i venstre kolonne med data-url-key
					if (!$checkbox.length) {
						$checkbox = $('.left-column .filter-checkbox[data-url-key="' + key + '"][value="' + value + '"]');
					}
					
					// Hvis checkbox ikke funnet, prøv å finne chip-knapp
					if (!$checkbox.length) {
						let $chip = $(`.filter-chip[data-filter-key="${filterKey}"][data-filter="${value}"]`);
						
						if (!$chip.length) {
							$chip = $(`.filter-chip[data-url-key="${key}"][data-filter="${value}"]`);
						}
						
						if (!$chip.length) {
							$chip = $('.left-column .filter-chip[data-filter-key="' + filterKey + '"][data-filter="' + value + '"]');
						}
						
						if (!$chip.length) {
							$chip = $('.left-column .filter-chip[data-url-key="' + key + '"][data-filter="' + value + '"]');
						}
						
						if ($chip.length) {
							displayText = $chip.text().trim();
						}
					}
					
					if ($checkbox.length) {
						displayText = $checkbox.siblings('.checkbox-label').text().trim();
					} else if (filterKey === 'language' || key === 'sprak') {
						displayText = value.charAt(0).toUpperCase() + value.slice(1);
					} 

					const filterChip = $(`<span class="active-filter-chip button-filter" data-filter-key="${key}" data-url-key="${filterKey}" data-filter-value="${value}">
						${displayText} <span class="remove-filter tooltip" data-title="Fjern filter">×</span>
					</span>`);

					// Handle filter removal
					filterChip.find('.remove-filter').on('click', function () {
						const filterKey = $(this).parent().data('filter-key');
						const filterValue = $(this).parent().data('filter-value');

						// Konverter tilbake til URL-nøkkel for månedsfilteret
						const urlKey = filterKey === 'months' ? 'mnd' : 
						               filterKey === 'categories' ? 'k' : 
						               filterKey === 'locations' ? 'sted' : 
						               filterKey === 'instructors' ? 'i' : 
						               filterKey === 'language' ? 'sprak' : filterKey;

						if (filters[urlKey]) {
							if (Array.isArray(filters[urlKey])) {
								// For måneder, sørg for at vi sammenligner med samme format
								const formattedValue = String(filterValue).padStart(2, '0');
								filters[urlKey] = filters[urlKey].filter(item => {
									const formattedItem = String(item).padStart(2, '0');
									return formattedItem !== formattedValue;
								});
								if (filters[urlKey].length === 0) {
									filters[urlKey] = null;
								}
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
						let $checkbox = $(`.filter-checkbox[data-url-key="${filterKey}"][value="${filterValue}"]`);
						if (!$checkbox.length) {
							$checkbox = $(`.filter-checkbox[data-filter-key="${filterKey}"][value="${filterValue}"]`);
						}
						// Hvis fortsatt ikke funnet, prøv å søke spesifikt i venstre kolonne
						if (!$checkbox.length) {
							$checkbox = $('.left-column .filter-checkbox[data-url-key="' + filterKey + '"][value="' + filterValue + '"]');
						}
						if (!$checkbox.length) {
							$checkbox = $('.left-column .filter-checkbox[data-filter-key="' + filterKey + '"][value="' + filterValue + '"]');
						}
						if ($checkbox.length) {
							$checkbox.prop('checked', false);
						}

						// Update dropdown text using the correct key
						const dropdownFilterKey = $checkbox.closest('.filter').find('.filter-dropdown-toggle').data('filter');
						updateDropdownText(dropdownFilterKey, filters[urlKey]);
						
						// Remove active class from corresponding filter chip
						$(`.filter-chip[data-filter="${filterValue}"][data-url-key="${filterKey}"]`).removeClass('active');

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
			key !== 'nonce' && key !== 'action' && key !== 'sort' && key !== 'order' && key !== 'per_page' &&
			filters[key] && filters[key].length > 0 &&
			// Ekskluder kortkode-parametere fra aktive filtre
			!(typeof kurskalender_data !== 'undefined' && kurskalender_data.has_shortcode_filters && 
			  kurskalender_data.shortcode_params && kurskalender_data.shortcode_params[key])
		);
		hasActiveFilters ? $resetButton.addClass('active-filters') : $resetButton.removeClass('active-filters');
		hasActiveFilters ? $activeFiltersContainer.addClass('active') : $activeFiltersContainer.removeClass('active');
	}

	// Oppdater filter counts når filtre endres
	function updateFilterCounts() {
		const currentFilters = getCurrentFiltersFromURL();
		
		$.ajax({
			url: kurskalender_data.ajax_url,
			type: 'POST',
			data: {
				action: 'get_filter_counts',
				nonce: kurskalender_data.filter_nonce,
				...currentFilters
			},
			success: function(response) {
				if (response.success && response.data.counts) {
					updateFilterCountsDisplay(response.data.counts);
				}
			}
		});
	}

	// Oppdater visning av filter counts - kun visuell indikator
	function updateFilterCountsDisplay(counts) {
		// Reset all states before applying new counts to avoid lingering classes
		const $allLabels = $('.filter-list-item.checkbox');
		const $allCheckboxes = $allLabels.find('.filter-checkbox');
		$allLabels.removeClass('filter-empty filter-available');
		$allCheckboxes.prop('disabled', false);
		
		// Oppdater kategorier
		if (counts.categories) {
			Object.keys(counts.categories).forEach(slug => {
				const count = counts.categories[slug];
				const $element = $(`.filter-checkbox[data-filter-key="categories"][value="${slug}"]`);
				if ($element.length) {
					const $label = $element.closest('label');
					
					if (count === 0) {
						$label.addClass('filter-empty');
						$element.prop('disabled', true);
					} else {
						$label.addClass('filter-available');
						$element.prop('disabled', false);
					}
				}
			});
		}

		// Oppdater andre filtre på samme måte
		['locations', 'instructors', 'language', 'months'].forEach(filterType => {
			if (counts[filterType]) {
				Object.keys(counts[filterType]).forEach(value => {
					const count = counts[filterType][value];
					const $element = $(`.filter-checkbox[data-filter-key="${filterType}"][value="${value}"]`);
					if ($element.length) {
						const $label = $element.closest('label');
						
						if (count === 0) {
							$label.addClass('filter-empty');
							$element.prop('disabled', true);
						} else {
							$label.addClass('filter-available');
							$element.prop('disabled', false);
						}
					}
				});
			}
		});
	}

	// Forhindre klikk på tomme filtervalg
	$(document).on('click', '.filter-empty .filter-checkbox', function(e) {
		e.preventDefault();
		e.stopPropagation();
		return false;
	});

	// Initialize filters on page load
	initializeFiltersFromURL();

	// Sjekk om vi har en side-parameter i URL-en ved sidelasting
	$(document).ready(function() {
		const urlParams = new URLSearchParams(window.location.search);
		const sideParam = urlParams.get('side');
		
		if (sideParam) {
			const currentFilters = getCurrentFiltersFromURL();
			// console.log('Direkte tilgang til side:', sideParam);
			// console.log('Nåværende filtre:', currentFilters);
			fetchCourses(currentFilters);
		}
	});

	$(document).on('click', '.pagination-wrapper .pagination a', function (e) {
		e.preventDefault();
		const href = $(this).attr('href');
		const locate = new URL(href);
		
		// Behold alle eksisterende filtre
		const currentFilters = getCurrentFiltersFromURL();
		
		// Konverter datoformat hvis nødvendig
		if (currentFilters.dato && currentFilters.dato.includes(',')) {
			// Konverter fra YYYY-MM-DD,YYYY-MM-DD til DD.MM.YYYY-DD.MM.YYYY
			const [fromDate, toDate] = currentFilters.dato.split(',');
			const from = moment(fromDate).format('DD.MM.YYYY');
			const to = moment(toDate).format('DD.MM.YYYY');
			currentFilters.dato = `${from}-${to}`;
		}
		
		// Legg til side-parameter
		const newFilters = {
			...currentFilters,
			side: locate.searchParams.get('side')
		};

		// Oppdater URL og hent resultater
		window.history.pushState({}, '', href);
		fetchCourses(newFilters);
	});

	function updatePagination(html) {
		$('.pagination-wrapper .pagination').html(html);
	}

	// Håndter browser back/forward
	window.addEventListener('popstate', function() {
		const currentFilters = getCurrentFiltersFromURL();
		fetchCourses(currentFilters);
	});

	// Initialiser Caleran
	const dateInput = document.getElementById('date-range');
	if (dateInput) {
		// Sjekk om datepickeren er i venstre kolonne
		const isLeftFilter = dateInput.classList.contains('caleran-left');
		
		const caleranInstance = caleran(dateInput, {
			showOnClick: true,
			autoCloseOnSelect: false,
			format: "DD.MM.YYYY",
			rangeOrientation: "vertical",
			calendarCount: 2,
			showHeader: true,
			showFooter: true,
			showButtons: true,
			applyLabel: "Bruk",
			cancelLabel: "Avbryt",
			showOn: isLeftFilter ? "right" : "bottom", // Dynamisk posisjonering
			arrowOn: isLeftFilter ? "top" : "center",
			autoAlign: true,
			inline: false,
			minDate: moment(),
			startEmpty: true,
			nextMonthIcon: '<i class="ka-icon icon-chevron-right"></i>',
			prevMonthIcon: '<i class="ka-icon icon-chevron-left"></i>',
			rangeIcon: '<i class="ka-icon icon-calendar"></i>',
			headerSeparator: '<i class="ka-icon icon-chevron-right calendar-header-separator"></i>',
			rangeLabel: "Velg periode",
			ranges: [
				{
					title: "Neste uke",
					startDate: moment(),
					endDate: moment().add(1, 'week')
				},
				{
					title: "Neste 3 måneder",
					startDate: moment(),
					endDate: moment().add(3, 'month')
				},
				{
					title: "Neste 6 måneder",
					startDate: moment(),
					endDate: moment().add(6, 'month')
				},
				{
					title: "Resten av året",
					startDate: moment(),
					endDate: moment().endOf('year')
				},
				{
					title: "Neste år",
					startDate: moment().add(1, 'year').startOf('year'),
					endDate: moment().add(1, 'year').endOf('year')
				}
			],
			verticalOffset: 10,
			locale: 'nb',
			onafterselect: function(caleran, startDate, endDate) {
				if (startDate && endDate) {
					const fromDate = startDate.format("DD.MM.YYYY");
					const toDate = endDate.format("DD.MM.YYYY");
					updateFiltersAndFetch({ 
						'dato': `${fromDate}-${toDate}`
					});
				}
			},
			oncancel: function() {
				updateFiltersAndFetch({ 
					'dato': null
				});
			}
		});

		// Enkel CSS for å sikre at kalenderen vises over andre elementer
		const style = document.createElement('style');
		style.textContent = `
			.caleran-container { 
				z-index: 100000 !important;
				transform: translateY(5px) !important; // Justerer posisjonen ned
			}
			body.admin-bar .caleran-container {
				transform: translateY(35px) !important; // Ekstra justering når admin-bar er til stede
			}
		`;
		document.head.appendChild(style);
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

			// console.log('Sortering startet:', { sortBy, order });

			// Oppdater selected text
			$('.sort-dropdown .selected-text').text($(this).text());

			// Hent eksisterende filtre
			const currentFilters = getCurrentFiltersFromURL();
			// console.log('Eksisterende filtre før sortering:', currentFilters);
			
			// Konverter datoformat hvis nødvendig
			if (currentFilters.dato && currentFilters.dato.includes(',')) {
				const [fromDate, toDate] = currentFilters.dato.split(',');
				const from = moment(fromDate).format('DD.MM.YYYY');
				const to = moment(toDate).format('DD.MM.YYYY');
				currentFilters.dato = `${from}-${to}`;
			}

			// Oppdater sorteringsparametere og reset side til 1
			const updatedFilters = {
				...currentFilters,
				sort: sortBy,
				order: order,
				side: 1  // Reset til side 1 ved ny sortering
			};

			// console.log('Filtre som sendes til server:', updatedFilters);

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

	// Ensure active filter chips are shown above the course list on mobile (single container, move only)
	(function manageActiveFiltersPlacement() {
		// Remove any legacy mobile container if present
		const legacy = document.getElementById('mobile-top-active-filters-container');
		if (legacy && legacy.parentNode) {
			legacy.parentNode.removeChild(legacy);
		}

		// Ensure we have an origin placeholder before the active filters container
		var activeFiltersContainer = document.getElementById('active-filters-container');
		if (!activeFiltersContainer) return;
		if (!document.getElementById('active-filters-origin')) {
			var origin = document.createElement('div');
			origin.id = 'active-filters-origin';
			origin.style.display = 'none';
			activeFiltersContainer.parentNode.insertBefore(origin, activeFiltersContainer);
		}

		function placeForViewport() {
			var isMobile = window.matchMedia('(max-width: 768px)').matches;
			var container = document.getElementById('active-filters-container');
			if (!container) return;

			if (isMobile) {
				// Try place after courselist header in right column
				var header = document.querySelector('.courselist-items-wrapper.right-column .courselist-header');
				if (header && header.parentNode) {
					header.parentNode.insertBefore(container, header.nextSibling);
				} else {
					// Fallback: prepend to right column wrapper
					var rightCol = document.querySelector('.courselist-items-wrapper.right-column');
					if (rightCol) {
						rightCol.insertBefore(container, rightCol.firstChild);
					}
				}
			} else {
				// Move back after origin on desktop
				var origin = document.getElementById('active-filters-origin');
				if (origin && origin.parentNode) {
					origin.parentNode.insertBefore(container, origin.nextSibling);
				}
			}
		}

		placeForViewport();
		window.addEventListener('resize', placeForViewport);
	})();

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
		// Sjekk om eventet kommer fra caleran ved å se på event path
		const isCaleranEvent = event.composedPath && event.composedPath().some(el => 
			el && el.classList && el.classList.contains('caleran')
		);

		if (!isCaleranEvent) {
			try {
				// Sjekk om klikket var utenfor alle dropdowns
				const isOutsideDropdowns = !event.target.closest('.filter-dropdown');

				if (isOutsideDropdowns) {
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
			} catch (error) {
				console.debug('Ignorerer event fra caleran date picker');
			}
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

