jQuery(document).ready(function($){
  // init the luminateExtend stuff but only if it hasn't been inited otherwise
  if (typeof luminateExtend === 'undefined') {
    luminateExtend.init({
      apiKey: 'apithirteenwnet', 
      path: {
        nonsecure: 'http://support.thirteen.org/site/', 
        secure: 'https://secure2.convio.net/wnet/site/'
      }
    });
  }
  $('.success').hide();
  /* begin submit function */
  $('#alreadyamember').submit(function () {
    var wcs_email = $("input[name='cons_email']", this).val();
    var email_regex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/i;
    var formdata = $(this).serialize(); 
  
    if(!email_regex.test(wcs_email)){ 
      $('.errors').html ('<div class="ajax-wnet-convio error">Please enter a valid email address.</div>');
    } else {
      var subscribeHandlerCallback = function(data) {
        if(typeof data.errorResponse !== 'undefined') {
          $('.errors').html ('<div class="ajax-wnet-convio error">' + data.errorResponse.message + '</div>');
        } else {
          if(typeof data.submitSurveyResponse.errors !== 'undefined'){
            $.each(data.submitSurveyResponse.errors, function(i, error) {
              var inputname = error.errorField;
              $("label[for='" + inputname + "']").append("<div class='error'>" + error.errorMessage + "</div>");
            });
          } else {
            $('.success').show();
            $('#alreadyamember').hide();
            $.ajax({
              method: "POST",
              url: "/pbsoauth/alreadymember/",
              data: formdata
            }).done(function(response) {
              console.log("submitted");
            });
          }
        }
      };
      luminateExtend.api.request([{
        api: 'survey',
        callback: subscribeHandlerCallback,
        data: formdata,
        requiresAuth: true
      }]);
    }
    return false; 
  });
  /* END submit function */
});
