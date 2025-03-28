jQuery(document).ready(function() {
    // Function to copy text and show tooltip
    jQuery('.copytext').on('click', function() {
        // Get the text inside the span
        var textToCopy = jQuery(this).text();
        
        // Create a temporary textarea element to hold the text for copying
        var tempTextarea = jQuery('<textarea>');
        jQuery('body').append(tempTextarea);
        tempTextarea.val(textToCopy).select();
        document.execCommand('copy');
        tempTextarea.remove(); // Remove the temporary element

        // Create and show the tooltip
        var tooltip = jQuery('<span class=\"tooltip-copytext\">Kopiert</span>');
        jQuery('body').append(tooltip); // Append to body to avoid positioning issues 

        // Get the offset position of the clicked element
        var offset = jQuery(this).offset();
        
        // Position the tooltip near the copied text
        tooltip.css({
            'position': 'absolute',
            'top': offset.top - tooltip.outerHeight() - 10,
            'left': offset.left + (jQuery(this).width() / 2),
            'transform': 'translateX(-50%)',
            'z-index': 1000,
            'background-color': '#333',
            'color': '#fff',
            'padding': '5px 10px',
            'border-radius': '5px',
            'font-size': '12px',
            'text-align': 'center'
        });

        // Fade out and remove the tooltip after 1.5 seconds
        setTimeout(function() {
            tooltip.fadeOut(500, function() {
                jQuery(this).remove();
            });
        }, 1500);
    });
});