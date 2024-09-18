jQuery(document).ready(function($) {

  function setPBSOAuthRememberMe() {
    var rememberme = 'false';
    if ( $("input[name='pbsoauth_rememberme']").prop("checked") ){
      rememberme = 'true'; 
    } else {
      rememberme = 'false';
    }
    document.cookie='pbsoauth_rememberme=' + rememberme + ';domain=' + window.location.hostname + ';path=/';
  }

  setPBSOAuthRememberMe();

  $("input[name='pbsoauth_rememberme']").change(function() {
    setPBSOAuthRememberMe();
  });

  /* Various things to do when someone clicks on a login link */
  $(".passport-login-wrap li a").on("click") {
    event.preventDefault();
    // set the loginprovider cookie
    var logintype = $(this).closest('li').attr("class");
    if (logintype) {
      document.cookie='pbsoauth_loginprovider=' + logintype + ';domain=' + window.location.hostname + ';path=/';
      window.dataLayer = window.dataLayer || [];
      dataLayer.push({ 'event': 'login', 'method': logintype });
    }
	var appended_href =  $(this).attr('href');
    // send them along their way
    window.location.href = appended_href;
  }); 

});

