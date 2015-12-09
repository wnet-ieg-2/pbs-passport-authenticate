jQuery(document).ready(function($) {

  function setPBSOAuthRememberMe() {
    var rememberme = 'false';
    if ( $("input[name='pbsoauth_rememberme']").prop("checked") ){
      rememberme = 'true'; 
    } else {
      rememberme = 'false';
    }
    console.log(rememberme);
    document.cookie='pbsoauth_rememberme=' + rememberme + ';domain=' + window.location.hostname + ';path=/';
  }

  setPBSOAuthRememberMe();

  $("input[name='pbsoauth_rememberme']").change(function() {
    setPBSOAuthRememberMe();
  });

  

   	/* optin challenge */
	$( "#passport-confirm-optin" ).click(function() {
		if ($('input#pbsoauth_optin').prop('checked')) {
			$('.add-login-fields').removeClass('hide');
			if ($(".passport-optin-challenge")[0]){$('.passport-optin-challenge').hide();}
		}
		else {
			$('.passport-optin-button').append('<p class="passport-error">Sorry, you must click the checkbox to continue.</p>');
		}
	
	});
  	/* end optin challenge */
  
  
  

});

