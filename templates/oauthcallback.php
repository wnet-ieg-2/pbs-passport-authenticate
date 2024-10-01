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
define('DISABLE_PLEDGE', 1);
remove_all_actions('wp_footer',1);
remove_all_actions('wp_header',1);

$defaults = get_option('pbs_passport_authenticate');

$passport = new PBS_Passport_Authenticate(dirname(__FILE__));

$use_pmsso = isset($defaults['pmsso_is_default']) ? $defaults['pmsso_is_default'] : false;
$auth_client = false;
$code_verifier = '';
if (!empty($_COOKIE["pkce_code_verifier"])){
  $code_verifier = $_COOKIE["pkce_code_verifier"];
  setcookie( 'pkce_code_verifier', '', 1, '/', $_SERVER['HTTP_HOST']);
}
if ($use_pmsso) {
  $auth_client = $passport->get_pmsso_client();
} else {
  $auth_client = $passport->get_laas_client();
}

$mvault_client = $passport->get_mvault_client();

$login_referrer = !empty($defaults['landing_page_url']) ? $defaults['landing_page_url'] : site_url();
if (!empty($_COOKIE["pbsoauth_login_referrer"])){
  $login_referrer = $_COOKIE["pbsoauth_login_referrer"];
  // prevent a possible loop
  if (strpos($login_referrer, 'loginform')) {
	$login_referrer = site_url();
  }
  setcookie( 'pbsoauth_login_referrer', '', 1, '/', $_SERVER['HTTP_HOST']);
}

$membership_id = false;

// where to direct a logged in visitor who isn't a member
$not_member_path = 'pbsoauth/userinfo';

if (isset($_GET["state"])){
  $state=($_GET["state"]);
  $jwt = $passport->read_jwt($state);
  if ($jwt) {
    $membership_id = !empty($jwt['sub']) ? $jwt['sub'] : false;
    // allow the jwt to override the current value with a return_path
    $login_referrer = !empty($jwt['return_path']) ? site_url($jwt['return_path']) : $login_referrer;
    // allow the jwt to set where the authenticated visitor who is not a member is sent
    $not_member_path = !empty($jwt['not_member_path']) ? $jwt['not_member_path'] : $not_member_path;
  } else {
    // fallback case for older clients when membership_id was passed as a plaintext string
    $membership_id = (!empty($state) ? $state : false);
  }
}



$rememberme = false;
if (!empty($_COOKIE["pbsoauth_rememberme"])) {
  $rememberme = $_COOKIE["pbsoauth_rememberme"];
}

// nonce is going to be in the jwt
$nonce = false;


$errors = array();
if (isset($_GET["code"])){
	// log any current session out
  	$auth_client->logout();
  	$code = $_GET["code"];
  	$userinfo = $auth_client->authenticate($code, $rememberme, $nonce, $code_verifier);
} else {
	// only if VPPA redirect I think will there not be a 'code'
    if ($use_pmsso) {
        $userinfo = $auth_client->check_pmsso_login();
		// are they already activated?
		if (isset($userinfo["pid"])) {
			$mvaultinfo = $mvault_client->get_membership_by_uid($pbs_uid);
			if (isset ($mvaultinfo["membership_id"])) {
  				$userinfo["membership_info"] = $mvaultinfo;
			}
			// create and update the userinfo cookie
			$success = $auth_client->validate_and_append_userinfo($userinfo);
			wp_redirect($login_referrer);
			exit();
		}
	}
}

// now we either have userinfo or null.
if (isset($userinfo["pid"])){

  $pbs_uid = $userinfo["pid"];
  error_log("membership_id exists $membership_id and : " .json_encode($userinfo));
  // now we work with the mvault
  $mvaultinfo = array();
  if ($membership_id) {
    // this is an activation!
    $mvaultinfo = $mvault_client->get_membership($membership_id);
    if (isset($mvaultinfo["membership_id"])) {
      $mvaultinfo = $mvault_client->activate($membership_id, $pbs_uid);
    }
	// handle VPPA assent during activation
	if (isset($userinfo["vppa_redirect"])) {
		// reset the login_referrer again
		setcookie( 'pbsoauth_login_referrer', $login_referrer, 0, '/', $_SERVER['HTTP_HOST']);
		wp_redirect($userinfo["vppa_redirect"]);
		exit();
	}
  }
  // is the person activated now?
  if (!isset($mvaultinfo["membership_id"])) {
    $mvaultinfo = $mvault_client->get_membership_by_uid($pbs_uid);
    if (!isset($mvaultinfo["membership_id"])) {
      // still not activated, redirect the member as needed
      $login_referrer = site_url($not_member_path);
    }
  }
}

wp_redirect($login_referrer);
exit();
?>
