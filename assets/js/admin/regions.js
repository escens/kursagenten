jQuery(document).ready(function($) {
    var $useRegionsCheckbox = $('#use-regions-checkbox');
    var $regionsSettings = $('#regions-settings');
    var $regionMappingContainer = $('#region-mapping-container');
    var $resetButton = $('#reset-region-mapping');

    // Toggle regions on/off
    $useRegionsCheckbox.on('change', function() {
        var enabled = $(this).is(':checked');
        
        $.ajax({
            url: kursagentenRegions.ajax_url,
            type: 'POST',
            data: {
                action: 'kursagenten_toggle_regions',
                enabled: enabled,
                nonce: kursagentenRegions.nonce
            },
            success: function(response) {
                if (response.success) {
                    $regionsSettings.toggle(enabled);
                    if (enabled) {
                        // Reload page to show updated regions
                        location.reload();
                    }
                } else {
                    alert('Feil: ' + (response.data.message || 'Ukjent feil'));
                    $useRegionsCheckbox.prop('checked', !enabled);
                }
            },
            error: function() {
                alert('Feil ved kommunikasjon med serveren');
                $useRegionsCheckbox.prop('checked', !enabled);
            }
        });
    });

    // Store original mapping to detect changes
    var originalMapping = {};
    function storeOriginalMapping() {
        originalMapping = {};
        $('.region-column').each(function() {
            var region = $(this).data('region');
            var counties = [];
            $(this).find('.county-item').each(function() {
                var county = $(this).data('county');
                if (!county) {
                    county = $(this).clone().children().remove().end().text().trim();
                }
                if (county) {
                    counties.push(county);
                }
            });
            if (region) {
                originalMapping[region] = counties.slice().sort();
            }
        });
    }
    
    // Check if mapping has actually changed
    function hasMappingChanged() {
        var currentMapping = {};
        $('.region-column').each(function() {
            var region = $(this).data('region');
            var counties = [];
            $(this).find('.county-item').each(function() {
                var county = $(this).data('county');
                if (!county) {
                    county = $(this).clone().children().remove().end().text().trim();
                }
                if (county) {
                    counties.push(county);
                }
            });
            if (region) {
                currentMapping[region] = counties.slice().sort();
            }
        });
        
        // Compare mappings
        if (Object.keys(currentMapping).length !== Object.keys(originalMapping).length) {
            return true;
        }
        
        for (var region in currentMapping) {
            if (!originalMapping[region] || 
                currentMapping[region].length !== originalMapping[region].length ||
                JSON.stringify(currentMapping[region]) !== JSON.stringify(originalMapping[region])) {
                return true;
            }
        }
        
        return false;
    }

    // Debounce function to prevent multiple rapid saves
    var saveTimeout;
    function debouncedSave() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(function() {
            if (hasMappingChanged()) {
                saveRegionMapping();
                // Update original mapping after save
                setTimeout(function() {
                    storeOriginalMapping();
                }, 500);
            } else {
                console.log('No changes detected, skipping save');
            }
        }, 300);
    }

    // Store original mapping on page load
    storeOriginalMapping();

    // Initialize sortable for each region column
    // Use a small delay to ensure DOM is ready
    setTimeout(function() {
        $('.county-list').sortable({
            connectWith: '.county-list',
            placeholder: 'ui-state-highlight',
            tolerance: 'pointer',
            cursor: 'move',
            stop: function(event, ui) {
                // Only save on stop if something actually changed
                debouncedSave();
            }
        }).disableSelection();
    }, 100);

    // Save region mapping
    function saveRegionMapping() {
        var mapping = {};
        
        $('.region-column').each(function() {
            var region = $(this).data('region');
            var counties = [];
            
            $(this).find('.county-item').each(function() {
                // Try to get county from data attribute first, then from text content
                var county = $(this).data('county');
                if (!county) {
                    // Fallback: get text content and trim whitespace
                    county = $(this).clone().children().remove().end().text().trim();
                }
                if (county) {
                    counties.push(county);
                }
            });
            
            if (region && counties.length > 0) {
                mapping[region] = {
                    counties: counties,
                    municipalities: []
                };
            }
        });

        // Debug: log mapping to console
        console.log('Saving region mapping:', mapping);
        
        // Don't save if mapping is empty
        if (Object.keys(mapping).length === 0) {
            console.warn('No mapping data to save');
            return;
        }

        $.ajax({
            url: kursagentenRegions.ajax_url,
            type: 'POST',
            data: {
                action: 'kursagenten_save_region_mapping',
                mapping: JSON.stringify(mapping),
                nonce: kursagentenRegions.nonce
            },
            success: function(response) {
                console.log('Save response:', response);
                if (response.success) {
                    // Remove any existing notices first
                    $('.region-save-notice').remove();
                    
                    // Show a brief success indicator
                    var message = response.data.message || 'Regioninndeling lagret';
                    var $notice = $('<div class="notice notice-success is-dismissible region-save-notice" style="margin: 10px 0; padding: 12px 15px; background: #00a32a; color: white; border-left: 4px solid #00a32a; border-radius: 3px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"><p style="margin: 0; font-weight: 500;"><span style="font-size: 18px; margin-right: 8px;">✓</span>' + message + '</p></div>');
                    
                    // Insert after the reset button or h4 heading
                    var $target = $('#reset-region-mapping');
                    if ($target.length) {
                        $target.after($notice);
                    } else {
                        var $h4 = $('#regions-settings h4').first();
                        if ($h4.length) {
                            $h4.after($notice);
                        } else {
                            $('#regions-settings').prepend($notice);
                        }
                    }
                    
                    // Scroll to notice if needed
                    $('html, body').animate({
                        scrollTop: $notice.offset().top - 100
                    }, 300);
                    
                    // Fade out after 4 seconds
                    setTimeout(function() {
                        $notice.fadeOut(500, function() {
                            $(this).remove();
                        });
                    }, 4000);
                } else {
                    alert('Feil ved lagring: ' + (response.data.message || 'Ukjent feil'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                alert('Feil ved kommunikasjon med serveren: ' + error);
            }
        });
    }

    // Reset to default mapping
    $resetButton.on('click', function() {
        if (!confirm('Er du sikker på at du vil tilbakestille regioninndelingen til standardverdiene?')) {
            return;
        }

        $.ajax({
            url: kursagentenRegions.ajax_url,
            type: 'POST',
            data: {
                action: 'kursagenten_reset_region_mapping',
                nonce: kursagentenRegions.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Reload page to show reset mapping
                    location.reload();
                } else {
                    alert('Feil: ' + (response.data.message || 'Ukjent feil'));
                }
            },
            error: function() {
                alert('Feil ved kommunikasjon med serveren');
            }
        });
    });
});

