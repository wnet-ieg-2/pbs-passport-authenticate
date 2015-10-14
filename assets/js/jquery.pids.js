jQuery(document).ready(function($) {

  var authenticate_script = 'authenticate.php';
  if (typeof laas_authenticate_script !== "undefined"){
    authenticate_script = laas_authenticate_script;
  }

  // constants 
  var PBSAccessToken;
 
  var loggedIn = false;
    
  function loginOAuthPBS(serviceurl) {
    if (serviceurl) {
    var win =   window.open(serviceurl, "PBSloginwindow", 'width=800, height=600'); 
    var pollTimer   =   window.setInterval(function() { 
      try {
        console.log('url: ' + win.document.URL);
        if (win.document.URL.indexOf('code=') != -1) {
          window.clearInterval(pollTimer);
          var response_url = win.document.URL;
          console.log('url: ' + response_url);
          var request_code = response_url.substring(win.document.URL.indexOf('code='));
          if (request_code.indexOf('#_=_') > 0){
            request_code = request_code.replace(/#.*/, '');
          }
          console.log('requestcode: ' + request_code);
          win.close();
          finishPBSLogin(request_code);
        }
      } catch(e) {
      }
    }, 500);
    }
  }
 

  function finishPBSLogin(request_code) {
    if ($('#rememberme:checked').val()){
      request_code += "&rememberme=" + $('#rememberme:checked').val();
    }
    console.log(request_code);
    $.ajax({
      url: authenticate_script,
      data: request_code,
      type: 'POST',
      dataType: 'json',
      success: function(response) {
        user = response;
        console.log(user);
        $('#statusdiv').text('You are logged in as ' + user.email);
        $('#login-block').hide();
        if (user.membership_info) {
          $('#statusdiv').append(' and you are a member.  Your expiration date is ' + user.membership_info.expire_date);
        }
      }
    });
  }

  function checkPBSLogin() {
    $('#login-block').hide();

    $.ajax({
      url: authenticate_script,
      data: null,
      type: 'POST',
      dataType: 'json',
      success: function(response) {
        user = response;
        console.log(user);
        if (user){
          $('#statusdiv').text('You are logged in as ' + user.email);
          $('#login-block').hide();
          $('#logout-block').show();
          if (user.membership_info) {
            $('#statusdiv').append(' and you are a member.  Your expiration date is ' + user.membership_info.expire_date);
          }
        } else {
          $('#login-block').show();
          $('#logout-block').hide();
        }
      }
    });
  }


  function logoutFromPBS() {
    $.ajax({
      url: authenticate_script,
      data: 'logout=true',
      type: 'POST',
      dataType: 'json',
      success: function(response) {
        $('#login-block').show();
        $('#logout-block').hide();
        $('#statusdiv').text('You have logged out');
      }
    });
  }

  $(function() {
    checkPBSLogin();
    $("a.service-login-link").click(function(event) {
      event.preventDefault();
      var serviceurl = $(this).attr("href");
      loginOAuthPBS(serviceurl);
    });
    $('#logout-block').click(logoutFromPBS);
  });
});

