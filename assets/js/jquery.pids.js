jQuery(document).ready(function($) {

  var authenticate_script = '/authenticate';
  var loginform = '/loginform';
  var joinlink = "http://support.thirteen.org/passport";
  var userinfolink = '/pbsoauth/userinfo/';
  var activatelink = '/pbsoauth/activate/';

  if (typeof pbs_passport_authenticate_args !== "undefined"){
    authenticate_script = pbs_passport_authenticate_args.laas_authenticate_script;
    loginform = pbs_passport_authenticate_args.loginform;
    joinlink = pbs_passport_authenticate_args.joinurl;
    userinfolink = pbs_passport_authenticate_args.userinfolink;
    activatelink = pbs_passport_authenticate_args.activatelink;
  }

   
  function loginToPBS(event) {
    event.preventDefault();
    if (window.location != loginform) {
      document.cookie='pbsoauth_login_referrer=' + window.location + ';domain=' + window.location.hostname + ';path=/';
    }
    window.location = loginform;
  }

  function joinPBS(event) {
    event.preventDefault();
    if (window.location != loginform) {
      document.cookie='pbsoauth_login_referrer=' + window.location + ';domain=' + window.location.hostname + ';path=/';
    }
    window.location = joinlink;
  }

  function activatePBS(event) {
    event.preventDefault();
    if (window.location != loginform) {
      document.cookie='pbsoauth_login_referrer=' + window.location + ';domain=' + window.location.hostname + ';path=/';
    }
    window.location = activatelink;
  }

 
  function checkPBSLogin() {
    user = Cookies.getJSON('pbs_passport_userinfo');
    if ( typeof(user) !== "undefined" && typeof(user.membership_info) !== "undefined") {
        updateLoginVisuals(user);
      } else {
        retrievePBSLoginInfoViaAJAX();
      }
  }

  function retrievePBSLoginInfoViaAJAX() {
    $.ajax({
      url: authenticate_script,
      data: null,
      type: 'POST',
      dataType: 'json',
      success: function(response) {
        user = response;
        window.updateLoginVisuals(user);
      }
    });
  }

  //function updateLoginVisuals(user){
  var window.updateLoginVisuals = function(user) {
    if (user){
      // if somehow still on loginform after logging in, redirect to userinfo page
      if (window.location == loginform) { window.location = userinfolink; }
		if (user.membership_info.offer) {passportIcon = 'passport-link-icon';}
		else {passportIcon = 'passport-alert-icon';} 
	
		welcomestring = 'Welcome <a href="' + userinfolink + '" class="' + passportIcon + '">' + user.first_name + '</a>';
     
      $('.pbs_passport_authenticate div.messages').html(welcomestring);
	  
	  if (user.thumbnail_URL) { $('.pbs_passport_authenticate div.messages').append("<img src=" + user.thumbnail_URL + " />");}
      
	  $('.pbs_passport_authenticate button.launch').addClass('logout');
      $('.pbs_passport_authenticate button.launch').text('Sign out');
      $('.pbs_passport_authenticate button.launch').click(logoutFromPBS);
	  
	  	// update thumb overlays
		if ($(".passport-video-thumb")[0]){
			$('.passport-video-thumb').each(function( index ) {
				if (user && user.membership_info.status == 'Off') {
					$('.passport-thumb-signin', this).html('ACTIVATE TO WATCH');
				}
				else {
					$('.passport-thumb-signin', this).remove();  	
					$(this).removeClass('passport-video-thumb');  	
				}	
		  });
		}	  
	  // end update thumb overlays
	  
	  // TO DO:
	  // if user signed in, but not active... in main video overlay, we need to change html to "ACTIVATE ACCOUNT"
	  // <div class='pp-sign-in pbs_passport_authenticate cf'><button class='launch'>SIGN IN NOW</button></div>
	  
	  
      console.log(user);
    } else {
      setTimeout(function() {
        $('.pbs_passport_authenticate button.launch, .pbs_passport_authenticate_login').click(loginToPBS);
        $('.pbs_passport_authenticate_join').click(joinPBS);
        $('.pbs_passport_authenticate_activate').click(activatePBS);
      }, 500);
    }
  }

  function logoutFromPBS(event) {
    event.preventDefault();
    $.ajax({
      url: authenticate_script,
      data: 'logout=true',
      type: 'POST',
      dataType: 'json',
      success: function(response) {
        window.location.href = window.location.protocol + '//' + window.location.host;
      }
    });
  }

  $(function() {
    checkPBSLogin();
  });
});

