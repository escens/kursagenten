/**
 * Course Dates Modal - Håndterer popup for visning av alle kursdatoer
 * 
 * Usage: Add class "show-ka-modal" to trigger element with data-course-id attribute
 * Modal should have id="modal-{course_id}" and class "ka-course-dates-modal"
 */

jQuery(document).ready(function($) {
    /**
     * Open modal when clicking on trigger link
     */
    $(document).on('click', '.show-ka-modal', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const courseId = $(this).data('course-id');
        const modal = $('#modal-' + courseId);
        
        if (modal.length) {
            modal.fadeIn(200);
            $('body').css('overflow', 'hidden');
            
            // Trigger event for tracking/analytics
            $(document).trigger('ka:modal:opened', { courseId: courseId });
        }
    });
    
    /**
     * Close modal when clicking on close button or overlay
     */
    $(document).on('click', '.ka-modal-close, .ka-modal-overlay', function(e) {
        e.preventDefault();
        const modal = $(this).closest('.ka-course-dates-modal');
        closeModal(modal);
    });
    
    /**
     * Close modal when pressing ESC key
     */
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            const visibleModal = $('.ka-course-dates-modal:visible');
            if (visibleModal.length) {
                closeModal(visibleModal);
            }
        }
    });
    
    /**
     * Helper function to close modal with animation
     */
    function closeModal(modal) {
        modal.fadeOut(200, function() {
            $('body').css('overflow', '');
            
            // Trigger event for tracking/analytics
            const courseId = modal.attr('id').replace('modal-', '');
            $(document).trigger('ka:modal:closed', { courseId: courseId });
        });
    }
});

