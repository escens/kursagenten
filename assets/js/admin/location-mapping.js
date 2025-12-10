jQuery(document).ready(function($) {
    'use strict';

    // Load available location terms when "Add new mapping" button is clicked
    $('#add-location-mapping-btn').on('click', function() {
        loadLocationTerms();
        $('#new-location-mapping-row').slideDown();
        $(this).prop('disabled', true);
    });

    // Cancel adding new mapping
    $('#cancel-new-location-mapping').on('click', function() {
        $('#new-location-mapping-row').slideUp();
        $('#add-location-mapping-btn').prop('disabled', false);
        $('#new-location-select').val('');
        $('#new-location-manual').val('');
        $('#new-location-name').val('');
    });

    // Clear manual input when selecting from dropdown
    $('#new-location-select').on('change', function() {
        if ($(this).val()) {
            $('#new-location-manual').val('');
        }
    });

    // Clear dropdown when typing in manual input
    $('#new-location-manual').on('input', function() {
        if ($(this).val()) {
            $('#new-location-select').val('');
        }
    });

    // Save new location mapping
    $('#save-new-location-mapping').on('click', function() {
        const oldNameFromSelect = $('#new-location-select').val();
        const oldNameFromManual = $('#new-location-manual').val().trim();
        const oldName = oldNameFromSelect || oldNameFromManual;
        const newName = $('#new-location-name').val().trim();

        if (!oldName || !newName) {
            alert('Begge feltene må fylles ut');
            return;
        }

        $.ajax({
            url: kursagentenLocationMapping.ajax_url,
            type: 'POST',
            data: {
                action: 'kursagenten_add_location_mapping',
                nonce: kursagentenLocationMapping.nonce,
                old_name: oldName,
                new_name: newName
            },
            success: function(response) {
                if (response.success) {
                    // Reload page to show new mapping
                    location.reload();
                } else {
                    alert(response.data.message || 'En feil oppstod');
                }
            },
            error: function() {
                alert('En feil oppstod ved lagring');
            }
        });
    });

    // Store original value to compare on blur
    $(document).on('focus', '.location-new-name', function() {
        $(this).data('original-value', $(this).val());
    });

    // Update existing mapping when new name field changes and loses focus
    $(document).on('blur', '.location-new-name', function() {
        const $input = $(this);
        const oldName = $input.data('old-name');
        const newName = $input.val().trim();
        const originalValue = $input.data('original-value');

        if (!newName) {
            alert('Nytt navn kan ikke være tomt');
            $input.val(originalValue);
            $input.focus();
            return;
        }

        // Only update if value has changed
        if (newName === originalValue) {
            return;
        }

        $.ajax({
            url: kursagentenLocationMapping.ajax_url,
            type: 'POST',
            data: {
                action: 'kursagenten_update_location_mapping',
                nonce: kursagentenLocationMapping.nonce,
                old_name: oldName,
                new_name: newName
            },
            success: function(response) {
                if (response.success) {
                    // Update stored original value
                    $input.data('original-value', newName);
                    // Show success message briefly
                    const $td = $input.closest('td');
                    // Remove any existing success message
                    $td.find('.save-success-msg').remove();
                    const $msg = $('<span class="save-success-msg" style="color: green; margin-left: 10px;">✓ Lagret</span>');
                    $td.append($msg);
                    setTimeout(function() {
                        $msg.fadeOut(function() {
                            $(this).remove();
                        });
                    }, 2000);
                } else {
                    alert(response.data.message || 'En feil oppstod');
                    $input.val(originalValue);
                }
            },
            error: function() {
                alert('En feil oppstod ved oppdatering');
                $input.val(originalValue);
            }
        });
    });

    // Remove location mapping
    $(document).on('click', '.remove-location-mapping', function() {
        if (!confirm('Er du sikker på at du vil fjerne denne navnendringen?')) {
            return;
        }

        const $button = $(this);
        const oldName = $button.data('old-name');

        $.ajax({
            url: kursagentenLocationMapping.ajax_url,
            type: 'POST',
            data: {
                action: 'kursagenten_remove_location_mapping',
                nonce: kursagentenLocationMapping.nonce,
                old_name: oldName
            },
            success: function(response) {
                if (response.success) {
                    // Remove row from table
                    $button.closest('tr').fadeOut(function() {
                        $(this).remove();
                        // Reload page to refresh available terms
                        location.reload();
                    });
                } else {
                    alert(response.data.message || 'En feil oppstod');
                }
            },
            error: function() {
                alert('En feil oppstod ved sletting');
            }
        });
    });

    /**
     * Load available location terms for dropdown
     */
    function loadLocationTerms() {
        $.ajax({
            url: kursagentenLocationMapping.ajax_url,
            type: 'POST',
            data: {
                action: 'kursagenten_get_location_terms',
                nonce: kursagentenLocationMapping.nonce
            },
            success: function(response) {
                if (response.success) {
                    const $select = $('#new-location-select');
                    $select.empty();
                    $select.append('<option value="">Velg sted...</option>');
                    
                    if (response.data.terms && response.data.terms.length > 0) {
                        $.each(response.data.terms, function(index, term) {
                            $select.append('<option value="' + escapeHtml(term.name) + '">' + escapeHtml(term.name) + '</option>');
                        });
                    } else {
                        $select.append('<option value="">Ingen ledige steder</option>');
                    }
                } else {
                    alert(response.data.message || 'Kunne ikke laste steder');
                }
            },
            error: function() {
                alert('En feil oppstod ved lasting av steder');
            }
        });
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
