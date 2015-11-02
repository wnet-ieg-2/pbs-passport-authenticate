jQuery(document).ready(function($) {

  var authenticate_script = '/authenticate';
  var loginform = '/loginform';
  if (typeof pbs_passport_authenticate_args !== "undefined"){
    authenticate_script = pbs_passport_authenticate_args.laas_authenticate_script;
    loginform = pbs_passport_authenticate_args.loginform;
  }
 
   
  function loginToPBS(event) {
    event.preventDefault();
    document.cookie='pbsoauth_login_referrer=' + window.location + ';domain=' + window.location.hostname + ';path=/';
    window.location = loginform;
  }

 
  function checkPBSLogin() {
    $.ajax({
      url: authenticate_script,
      data: null,
      type: 'POST',
      dataType: 'json',
      success: function(response) {
        user = response;
        console.log(user);
        if (user){
          $('.pbs_passport_authenticate div.messages').text('Welcome ' + user.first_name);
          $('.pbs_passport_authenticate div.messages').append("<img src=" + user.thumbnail_URL + " />");
          $('.pbs_passport_authenticate button.launch').addClass('logout');
          $('.pbs_passport_authenticate button.launch').text('Sign out');
          $('.pbs_passport_authenticate button.launch').click(logoutFromPBS);
        } else {
          $('.pbs_passport_authenticate button.launch').click(loginToPBS);
        } 
      }
    });
  }


  function logoutFromPBS(event) {
    event.preventDefault();
    $.ajax({
      url: authenticate_script,
      data: 'logout=true',
      type: 'POST',
      dataType: 'json',
      success: function(response) {
        $('.pbs_passport_authenticate div.messages').text('You have signed out');
        $('.pbs_passport_authenticate button.launch').text('Sign in');
		$('.pbs_passport_authenticate button.launch').removeClass('logout');
        $('.pbs_passport_authenticate button.launch').click(loginToPBS);
      }
    });
  }

  $(function() {
    checkPBSLogin();
  });
});

