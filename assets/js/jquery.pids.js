jQuery(document).ready(function($) {

  var authenticate_script = '/pbsoauth/authenticate/';
  var loginform = window.location.protocol + '//' + window.location.hostname + '/pbsoauth/loginform/';
  var joinlink = "/donate/";
  var userinfolink = '/pbsoauth/userinfo/';
  var activatelink = '/pbsoauth/activate/';
  var station_call_letters_lc = 'wnet';
  var learnmorelink = '/passport/';
  var vppalink = '/pbsoauth/vppa/';

  if (typeof pbs_passport_authenticate_args !== "undefined"){
    authenticate_script = pbs_passport_authenticate_args.laas_authenticate_script;
    loginform = pbs_passport_authenticate_args.loginform;
    joinlink = pbs_passport_authenticate_args.joinurl;
    userinfolink = pbs_passport_authenticate_args.userinfolink;
    activatelink = pbs_passport_authenticate_args.activatelink;
    if (typeof pbs_passport_authenticate_args.station_call_letters_lc !== "undefined"){
       station_call_letters_lc = pbs_passport_authenticate_args.station_call_letters_lc;
    }
    if (typeof pbs_passport_authenticate_args.vppalink !== "undefined"){
      vppalink = pbs_passport_authenticate_args.vppalink;
    }
  }

  /* in case the loginform url has no protocol */
  if (!/^https?:\/\//i.test(loginform)) {
    /* in case the loginform url starts with '//' */
    loginform = loginform.replace(/^(\/\/)/, '');
    loginform = window.location.protocol + '//' + loginform;
  }

 
  function loginToPBS(event) {
    event.preventDefault();
    if (window.location != loginform) {
      document.cookie='pbsoauth_login_referrer=' + window.location + '?dontcachme=' + Math.random() + ';domain=' + window.location.hostname + ';path=/';
    }
    window.location = loginform;
  }

  function joinPBS(event) {
    event.preventDefault();
    if (window.location != loginform) {
      document.cookie='pbsoauth_login_referrer=' + window.location + '?dontcachme=' + Math.random() + ';domain=' + window.location.hostname + ';path=/';
    }
    window.location = joinlink;
  }

  function activatePBS(event) {
    event.preventDefault();
    if (window.location != loginform) {
      document.cookie='pbsoauth_login_referrer=' + window.location + '?dontcachme=' + Math.random() + ';domain=' + window.location.hostname + ';path=/';
    }
    window.location = activatelink;
  }

  function acceptVPPAPBS(event) {
    event.preventDefault();
    if (window.location != loginform) {
      document.cookie='pbsoauth_login_referrer=' + window.location + '?dontcachme=' + Math.random() + ';domain=' + window.location.hostname + ';path=/';
    }
    window.location = vppalink;
  }


  function learnMorePassport(event) {
    event.preventDefault();
    if (window.location != loginform) {
      document.cookie='pbsoauth_login_referrer=' + window.location + '?dontcachme=' + Math.random() + ';domain=' + window.location.hostname + ';path=/';
    }
    window.location = learnmorelink;
  }

 
  function checkPBSLogin() {
    user = Cookies.getJSON('pbs_passport_userinfo');
    if ( typeof(user) !== "undefined" && typeof(user.membership_info) !== "undefined") {
        updateUserLoginStatusArray(user);
        updateLoginVisuals(user);
      } else {
        $('.pbs_passport_authenticate button.launch, .pbs_passport_authenticate_logged_in_hide').hide();
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
        updateUserLoginStatusArray(user);
        updateLoginVisuals(user);
      }
    });
  }

  function updateUserLoginStatusArray(obj) {
    /* this function updates a globally scoped variable 
     * that derives the current user login status from the
     * object that our cookie or endpoint returns, and then
     * stores that in a javascript object. 
     * These combos from the cookie/endpoint: 
     *  status = On: member has not been disabled or expired
     *  offer = not null:  member is in mvault and activated
     *  status = Off + offer = null: default -- visitor not activated
     *  status = On + offer = not null: member activated and valid for video
     *  status = Off + offer = not null: activated member is expired
     *  status = On + offer = null: should not be possible, but not valid
     */

    // init the variable
    var currentarray = {memberStatus: 'not_logged_in'};
    if (typeof(obj) !== 'undefined' && obj) {
      // logged in.  Dunno if activated tho
      currentarray.memberStatus = 'not_activated';
      if (obj.membership_info.status == 'On') {
        currentarray.memberStatus = 'valid';
        // but what about VPPA?
        currentarray.VPPAStatus = 'false';
        if (typeof(obj.vppa_status) !== 'undefined') {
          currentarray.VPPAStatus = obj.vppa_status;
        } 
      } else {
        // not activated, expired, or manually disabled which we treat as expired
        // offer will be null if not activated, otherwise status is expired
        if (typeof user.membership_info.offer !== 'undefined' && user.membership_info.offer) {
          currentarray.memberStatus = 'expired';
        }
      }
    }
    // set the global object value 
    userPBSLoginStatus = currentarray;
    return currentarray;
  }


  //function updateLoginVisuals(user){
  updateLoginVisuals = function(user) {
    //first, the passport player.

    var vppa_string = '';
    if (userPBSLoginStatus.memberStatus == 'valid' && userPBSLoginStatus.VPPAStatus == 'valid'){
      vppa_string = '&uid='+user.pid+'&callsign='+station_call_letters_lc;
      // value above is a signifier that the person is logged in and we can collect their data
    }
    $(".passportcoveplayer").each(function (i) {
      if (typeof($(this).data('window')) !== 'undefined') {
        var videoWindow = $(this).data('window');
        var videoID = $(this).data('media');
        var videoTitle = $(this).data('title');
        if (!$(this).hasClass("playing")) {
          if ((videoWindow != 'public' && videoWindow != '' && vppa_string) || (videoWindow == 'public' || videoWindow == '') ) {
            $(this).html('<div class="embed-container video-wrap no-content nocontent"><iframe id="partnerPlayer_'+ i +'" title="'+videoTitle+'" frameborder="0" marginwidth="0" marginheight="0" scrolling="no" allow="encrypted-media" allowfullscreen="allowfullscreen" src="//player.pbs.org/widget/partnerplayer/'+videoID+'/?chapterbar=false&endscreen=false'+vppa_string+'"></iframe></div>');
            $(this).addClass('playing');
          }
        }
      }
    });
    // end passport player.


    if (user){
      // if somehow still on loginform after logging in, redirect to userinfo page
      if (window.location == loginform) { window.location = userinfolink; }

   
      if (userPBSLoginStatus.memberStatus == 'valid' && userPBSLoginStatus.VPPAStatus == 'valid') {passportIcon = 'passport-link-icon';}
      else {passportIcon = 'passport-alert-icon';} 

      if (userPBSLoginStatus.memberStatus == 'valid') {
        $('.pbs_passport_authenticate_activated_hide').hide();
        if (userPBSLoginStatus.VPPAStatus !== 'valid') {
          userinfolink = vppalink;
        }
      } else if (userPBSLoginStatus.memberStatus == 'expired') {
        $('.pbs_passport_authenticate_activated_hide .already-a-member, .passport-first-time').hide();
      }


      $('.pbs_passport_authenticate button.launch, .pbs_passport_authenticate_logged_in_hide').hide();
      thumbimage = '';
      if (user.thumbnail_URL) {
        thumbimage = "<a href='" + userinfolink + "' class='userthumb'><img src='" + user.thumbnail_URL + "' alt='' /></a>"; 
      }	
	

        if ($(".pbs_passport_authenticate div.messages-new")[0]){
            // new version of messages with drop down menu...
            if (user.thumbnail_URL) {thumbimage = "<span><img src='" + user.thumbnail_URL + "' alt='' /></span>";}	
             welcomestring = '<a href="#" class="toggle-user-drop userlink">' + thumbimage + '<span class="name">' + user.first_name + '</span></a> <ul class="user-drop"><li><a href="' + userinfolink + '">USER STATUS</a></li><li><a  class="signout">SIGN OUT</a></li></ul>';
            $('.pbs_passport_authenticate div.messages-new').html(welcomestring);
			
			if (typeof makeNavObserverPids == 'function') { 	
		 		if (window["MutationObserver"]) { makeNavObserverPids('.toggle-user-drop', '.user-drop'); }
	 		}
			
        }
        else {
            // old version of messages
             welcomestring = thumbimage + '<a href="' + userinfolink + '" class="' + passportIcon + '"><span class="welcome">' + user.first_name + '</span></a> <a class="signout">Sign Out</a>';
            $('.pbs_passport_authenticate div.messages').html(welcomestring);
        }
        
        
      
        //bs: i need to be able to use this on js changed div....
        //$('.pbs_passport_authenticate a.signout').click(logoutFromPBS);
        $('body').on('click', '.pbs_passport_authenticate a.signout', function(e) {
            logoutFromPBS(e);
        });    
        
        
		  // update thumb overlays
		  if ($(".passport-video-thumb")[0]){
			  $('.passport-video-thumb').each(function( index ) {
				  if (userPBSLoginStatus.memberStatus == 'not_activated') {
					  $('.passport-thumb-signin', this).html('ACTIVATE TO WATCH');
          } else if (userPBSLoginStatus.memberStatus == 'expired') {
            $('.passport-thumb-signin', this).html('BECOME A MEMBER TO WATCH');
  				} else if (userPBSLoginStatus.VPPAStatus != 'valid') {
            $('.passport-thumb-signin', this).html('ACCEPT TERMS TO WATCH');
	  			} else {
		  			$('.passport-thumb-signin', this).remove();  	
			  		$(this).removeClass('passport-video-thumb');  	
				  }	
  		  });
	  	}	  
		  // end update thumb overlays
	  
  		// if user signed in, but not activated. change video overlay link.
      if ($(".pbs_passport_authenticate a.learn-more")) {
        if (userPBSLoginStatus.memberStatus == 'not_activated') {
          $(".pbs_passport_authenticate a.learn-more").html('<a href="' + activatelink + '" class="learn-more"><button class="learn-more">ACTIVATE ACCOUNT</button></a>').off("click", learnMorePassport).on("click", activatePBS);
        } else if (userPBSLoginStatus.memberStatus != 'valid') {
          $(".pbs_passport_authenticate a.learn-more").html('<a href="' + joinlink + '" class="learn-more"><button class="learn-more">BECOME A MEMBER TO WATCH</button></a>').off("click", learnMorePassport).on("click", joinPBS);
        } else if (userPBSLoginStatus.VPPAStatus != 'valid'){
          $(".pbs_passport_authenticate a.learn-more").html('<a href="' + vppalink + '" class="learn-more"><button class="learn-more">REVIEW AND ACCEPT TERMS TO WATCH</button></a>').off("click", learnMorePassport).on("click", acceptVPPAPBS);
        }
      }
    } else {
      $('.pbs_passport_authenticate button.launch, .pbs_passport_authenticate_logged_in_hide').show();
      // putting a short delay on the following since it takes a little bit of time for the elements to render
      setTimeout(function() {
        $('.pbs_passport_authenticate button.launch, .pbs_passport_authenticate_login').on("click",loginToPBS);
        $('.pbs_passport_authenticate_join').on("click", joinPBS);
        $('.pbs_passport_authenticate_activate').on("click", activatePBS);
        $('.pbs_passport_authenticate .learn-more').on("click", learnMorePassport);
        $('.pbs_passport_authenticate_vppa').on("click", acceptVPPAPBS);
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
  
  
  
  	function getQueryStringParamPBS(sParam) {
	    var sPageURL = window.location.search.substring(1);
    	var sURLVariables = sPageURL.split('&');
	    for (var i = 0; i < sURLVariables.length; i++) {
        	var sParameterName = sURLVariables[i].split('=');
	        if (sParameterName[0] == sParam) {return sParameterName[1];}
    	}
	}
	
	
	function makeNavObserverPids(buttonClass, dropClass) {
		var watchobserver = new MutationObserver(function() {
			setTimeout(function(){
            visible = $(dropClass).is(":visible");
                if (visible == false) {$(buttonClass).removeClass('open');}
                else {$(buttonClass).addClass('open');}
            }, 100);
    		});
    	watchobserver.observe(document.querySelector(dropClass), {attributes: true});
	}
	
  
  	/* [ video placeholder to video swap ] ---------------------------------------------------------------- */
    $('body').on('click', '.video-placeholder a', function(e) {
	   var media_player = $(this).data('media-player');
		$(this).parent().html(window[media_player]);
		if (typeof(updateLoginVisuals) == 'function') {	updateLoginVisuals(user); }
        e.preventDefault();
    });
    /* [ END video placeholder to video swap ] ---------------------------------------------------------------- */
	
	
	
});

// globally scoped array we'll use elsewhere
var userPBSLoginStatus = {memberStatus: 'pending'};

function checkPBSLoginStatus() {
  console.log(userPBSLoginStatus.memberStatus);
  if (userPBSLoginStatus.memberStatus == 'pending') {
    setTimeout(checkPBSLoginStatus, 100);
  } else {
    return userPBSLoginStatus.memberStatus;
  }
}
