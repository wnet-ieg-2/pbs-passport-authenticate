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
        updateLoginVisuals(user);
      }
    });
  }

  //function updateLoginVisuals(user){
  updateLoginVisuals = function(user) {
  
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
				if (user.membership_info.status == 'Off') {
					$('.passport-thumb-signin', this).html('ACTIVATE TO WATCH');
				}
				else {
					$('.passport-thumb-signin', this).remove();  	
					$(this).removeClass('passport-video-thumb');  	
				}	
		  });
		}	  
		// end update thumb overlays
	  
		// if user signed in, but not activated. change video overlay link.
		if ($(".pp-sign-in.pbs_passport_authenticate")[0] && user.membership_info.status == 'Off'){
			$('.pp-sign-in.pbs_passport_authenticate').html('<a href="/pbsoauth/activate" class="passport-activate"><span>ACTIVATE ACCOUNT</span></a>');
		}
		
		//passport player.
		if ($("#passportcoveplayer[data-window]")[0] && user.membership_info.status == 'On'){
			var videoWindow = $('#passportcoveplayer').data('window');
			var videoID = $('#passportcoveplayer').data('media');
			
			if (videoWindow != 'public' && videoWindow != '' && !$('#passportcoveplayer').hasClass("playing")) {
				$("#passportcoveplayer").html('<div class="embed-container video-wrap"><iframe id="partnerPlayer" frameborder="0" marginwidth="0" marginheight="0" scrolling="no" src="http://player.pbs.org/widget/partnerplayer/'+videoID+'/?chapterbar=false&uid='+user.pid+'&callsign=wnet"></iframe></div>');
				$("#passportcoveplayer").addClass('playing'); /* adds class to prevent reloading on load more videos */
			}
		}
	  	// end passport player.
	  
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
  
  
     /* optin challenge */
	$( "#passport-confirm-optin" ).click(function() {
		if ($('input#pbsoauth_optin').prop('checked')) {
		
			if (user) {
				console.log('registered user function here');
			}
			else {
				$('.add-login-fields').removeClass('hide');
				if ($(".passport-optin-challenge")[0]){$('.passport-optin-challenge').hide();}
			}	
		}
		else {
			$('.passport-optin-button').append('<p class="passport-error">Sorry, you must check the checkbox to continue.</p>');
		}
	});
  	/* end optin challenge */
  
  
});

