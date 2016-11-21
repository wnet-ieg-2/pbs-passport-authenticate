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

  $(".passport-login-wrap li a").click(function(event) {
    event.preventDefault();
    var logintype = $(this).closest('li').attr("class");
    if (logintype) {
      document.cookie='pbsoauth_loginprovider=' + logintype + ';domain=' + window.location.hostname + ';path=/';
    }
    window.location.href = $(this).attr('href');
  }); 

});

