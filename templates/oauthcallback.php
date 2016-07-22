<?php
/*
oauthcallback.php

This script handles the oAuth grant 'code' that is returned from the provider
(google/fb/openid), and calls the 'authenticate' method of the PBS_LAAS_Client.  
That method exchanges the grant 'code' with PBS's endpoints to get access and refresh tokens, 
uses those to get user info (email, name, etc), and then stores the tokens and userinfo encrypted 
in session variables.   
If the 'rememberme' variable was true, those tokens are also stored in an encrypted cookie.


*/
show_admin_bar(false);
remove_all_actions('wp_footer',1);
remove_all_actions('wp_header',1);

$defaults = get_option('pbs_passport_authenticate');

$passport = new PBS_Passport_Authenticate(dirname(__FILE__));

$laas_client = $passport->get_laas_client();


if (isset($_GET["state"])){
  $state=($_GET["state"]);
}

// this WILL be JWT, for now its just the membership_id
// $jwt = $passport->jwt_decode($state);
$membership_id = (!empty($state) ? $state : false);

$rememberme = false;
if (!empty($_COOKIE["pbsoauth_rememberme"])) {
  $rememberme = $_COOKIE["pbsoauth_rememberme"];
}

// nonce is going to be in the jwt
$nonce = false;


$errors = array();
if (isset($_GET["code"])){
  $code = $_GET["code"];
  $userinfo = $laas_client->authenticate($code, $rememberme, $nonce);
}

// now we either have userinfo or null.
if (isset($userinfo["pid"])){

  $pbs_uid = $userinfo["pid"];

  // now we work with the mvault
  $mvault_client = $passport->get_mvault_client();
  $mvaultinfo = array();
  if ($membership_id) {
    // this is an activation!
    $mvaultinfo = $mvault_client->get_membership($membership_id);
    if (isset($mvaultinfo["membership_id"])) {
      $mvaultinfo = $mvault_client->activate($membership_id, $pbs_uid);
    }
  }
}

$login_referrer = site_url();

if (!empty($_COOKIE["pbsoauth_login_referrer"])){
  $login_referrer = $_COOKIE["pbsoauth_login_referrer"];
}


wp_redirect($login_referrer);
exit();
?>
