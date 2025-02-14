
jQuery(document).ready(function ($) {
    $('.cat-list_item').on('click', function() {
        $('.cat-list_item').removeClass('active');
        $(this).addClass('active');
      
        $.ajax({
          type: 'POST',
          url: '/wp-admin/admin-ajax.php',
          dataType: 'html',
          data: {
            action: 'filter_projects',
            category: $(this).data('slug'),
          },
          success: function(res) {
            $('.project-tiles').html(res);
          }
        })
      });
});

