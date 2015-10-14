jQuery(document).ready(function($) {

  function createProvisionalMembership() {
    var trans_id = $('#transaction_id').text();
    var first_name = $('#trans_first_name').text();
    var last_name = $('#trans_last_name').text();
    var email = $('#trans_email').text();
    var station = $('#trans_station').text();
    var ajax = $.ajax({
      type: "POST",
      url: ajaxurl,
      data:{
        'trans_id': trans_id,
        'first_name': first_name,
        'last_name': last_name,
        'email': email,
        'station': station
      },
      xhr: function() {
        var xhr = $.ajaxSettings.xhr();
        return xhr;
      },
      dataType: "json"
    });
    ajax.done(function(response) {
      console.log(response);
      var responsetxt;      
      if (typeof response.token !== 'undefined') {
        responsetxt = 'Thirteen Passport Video Account Created.  <a href="http://watch.thirteen.org/plus/activate/' + response.token + '">Click here to enable your account!</a>';
      } else if (response.activated == true) {
        responsetxt = 'You have already activated your Passport account. <a href="http://watch.thirteen.org/">Click here to watch video (you may need to sign in)</a>';
      } else {
        if (typeof response.errors !== 'undefined') {
          if (response.errors == 'multiple accounts') {
            responsetxt = "Thank you!  There is already an account on record with your email address that has access to PBS Passport, and you should already have recieved an email with your login info.";
          } else {
            responsetxt = "Could not create Thirteen Passport Account! Errors: " + response.errors;
          }
        }
      }
      $('#mvault_status_window').html('<p>' + responsetxt + '</p>');
    });

    ajax.fail(function(response) {
      $('#mvault_status_window').html('<p>Could not create Thirteen Passport Account!</p>');
    });

  }

  $(function() {
    //$('#mvault_init_vars').hide();
    $('#mvault_status_window').html('<p>Creating Thirteen Passport Account...</p>');
    createProvisionalMembership();
  });
});

var default_ajaxurl = "https://www.thirteen.org/mvod/libs/get_mvod_access_link.php";

console.log(ajaxurl);

if (typeof ajaxurl === 'undefined') {
  var ajaxurl = default_ajaxurl;
}


