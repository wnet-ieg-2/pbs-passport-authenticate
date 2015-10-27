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

$required = array('laas_client_id', 'laas_client_secret', 'oauth2_endpoint', 'mvault_endpoint', 'mvault_client_id', 'mvault_client_secret');

foreach ($required as $arg) {
  if (empty($defaults[$arg])){
    die(json_encode(array('missing_arg'=>$arg)));
  }
}


function mvault_curl_extras($ch) {
  curl_setopt($ch, CURLOPT_CAINFO, '/etc/pki/tls/certs/AddTrustExternalCARoot.crt');
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  return $ch;
}

$laas_args = array(
  'client_id' => $defaults['laas_client_id'],
  'client_secret' => $defaults['laas_client_secret'],
  'oauthroot' => $defaults['oauth2_endpoint'],
  'redirect_uri' => site_url('/pbsoauth/callback/'),
  'tokeninfo_cookiename' => 'safdsafa',
  'userinfo_cookiename' => 'pbs_passport_userinfo',
  'cryptkey' => 'rioueqnfa2e',
  'encrypt_iv' => 'adsfafdsaafddsaf'
);

$laas_client = new PBS_LAAS_Client($laas_args);


if (isset($_GET["state"])){
  $state=($_GET["state"]);
}


$rememberme = (isset($_POST["rememberme"])) ? $_POST["rememberme"] : false;
$nonce = (isset($_POST["nonce"])) ? $_POST["nonce"] : false;


$errors = array();
if (isset($_GET["code"])){
  $code = $_GET["code"];
  $userinfo = $laas_client->authenticate($code, $rememberme, $nonce);
}

// now we either have userinfo or null.

if (isset($userinfo["pid"])){

  $pbs_uid = $userinfo["pid"];

  // now we work with the mvault

  $mvault_client = new PBS_MVault_Client($defaults['mvault_client_id'], $defaults['mvault_client_secret'],$defaults['mvault_endpoint'], $defaults['station_call_letters']);
  $mvaultinfo = array();

  // get the mvault record if available
  $mvaultinfo = $mvault_client->get_membership_by_uid($pbs_uid);  
  $errors['byuid'] = $mvaultinfo['errors'];
  if (isset ($mvaultinfo["membership_id"])) {
    $userinfo["membership_info"] = $mvaultinfo;
    $success = $laas_client->validate_and_append_userinfo($userinfo);
    if ($success) {
      //$userinfo = $success;
    }
  }
  $userinfo['errors'] = $errors;

}
// store it in its own cookie/session

$login_referrer = site_url();

if (!empty($_COOKIE["pbsoauth_login_referrer"])){
  $login_referrer = $_COOKIE["pbsoauth_login_referrer"];
}


wp_redirect(site_url($login_referrer));
exit()
?>
