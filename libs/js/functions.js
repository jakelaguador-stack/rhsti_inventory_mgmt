
function suggetion() {

     $('#sug_input').keyup(function(e) {
         $('#p_id').val('');
         var formData = {
             'product_name' : $('input[name=title]').val()
         };

         if(formData['product_name'].length >= 1){

           // process the form
           $.ajax({
               type        : 'POST',
               url         : 'ajax.php',
               data        : formData,
               dataType    : 'html',
               encode      : true
           })
               .done(function(data) {
                   $('#result').html(data).fadeIn();
                   $('#result li').click(function() {
                     $('#sug_input').val($(this).text());
                     $('#p_id').val($(this).data('id'));
                     $('#result').fadeOut(500);
                     $('#sug-form').submit();
                   });

                   $("#sug_input").blur(function(){
                     $("#result").fadeOut(500);
                   });
               });

         } else {

           $("#result").hide();

         };

         e.preventDefault();
     });

 }
  $('#sug-form').submit(function(e) {
      var formData = {
          'p_name' : $('input[name=title]').val()
      };
      var selectedId = $('#p_id').val();
      if(selectedId){
          formData = {'p_id': selectedId};
      }
        // process the form
        $.ajax({
            type        : 'POST',
            url         : 'ajax.php',
            data        : formData,
            dataType    : 'html',
            encode      : true
        })
            .done(function(data) {
                $('#product_info').html(data).show();
                total();
                $('.datepicker').datepicker({
                    format: 'yyyy-mm-dd',
                    todayHighlight: true,
                    autoclose: true
                }).datepicker('update', new Date());
            })
            .fail(function() {
                $('#product_info').html('<tr><td colspan="6" class="text-center text-danger">Unable to load product information. Please try again.</td></tr>').show();
            });
      e.preventDefault();
  });
  function total(){
    $('#product_info').on('input change', 'input[name=price], input[name=quantity]', function() {
            var price = +$('input[name=price]').val() || 0;
            var qty   = +$('input[name=quantity]').val() || 0;
            var total = qty * price;
                $('input[name=total]').val(total.toFixed(2));
    });
  }

  $(document).ready(function() {

    //tooltip
    $('[data-toggle="tooltip"]').tooltip();

    $('.submenu-toggle').click(function () {
       $(this).parent().children('ul.submenu').toggle(200);
    });
    //suggetion for finding product names
    suggetion();
    // Callculate total ammont
    total();

    $('.datepicker')
        .datepicker({
            format: 'yyyy-mm-dd',
            todayHighlight: true,
            autoclose: true
        });
  });
