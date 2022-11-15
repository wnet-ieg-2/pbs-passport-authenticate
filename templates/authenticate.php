<?php
/*
authenticate.php

This script is designed to work with jquery.pids.js and the PBS_LAAS_Client class.
It handles the oAuth authentication.

In the initial mode, it handles the oAuth grant 'code' that is returned from the provider
(google/fb/openid), and calls the 'authenticate' method of the PBS_LAAS_Client.  
That method exchanges the grant 'code' with PBS's endpoints to get access and refresh tokens, 
uses those to get user info (email, name, etc), and then stores the tokens and userinfo encrypted 
in session variables.   
If the 'rememberme' variable was true, those tokens are also stored in an encrypted cookie.

After the initial 'code' grant, this script uses the 'check_pbs_login' method to check 
for the presence of those tokens in either session or cookie, and refresh them as necessary.

This script also exposes the 'logout' method, which clears the tokens and cookies.

*/
show_admin_bar(false);
define('DISABLE_PLEDGE', 1);
remove_all_actions('wp_footer',1);
remove_all_actions('wp_header',1);
header('cache-control: no-cache');
$defaults = get_option('pbs_passport_authenticate');

$required = array('laas_client_id', 'laas_client_secret', 'oauth2_endpoint', 'mvault_endpoint', 'mvault_client_id', 'mvault_client_secret');

foreach ($required as $arg) {
  if (empty($defaults[$arg])){
    die(json_encode(array('missing_arg'=>$arg)));
  }
}

$passport = new PBS_Passport_Authenticate(dirname(__FILE__));

$laas_client = $passport->get_laas_client();


$rememberme = (isset($_POST["rememberme"])) ? $_POST["rememberme"] : false;
$nonce = (isset($_POST["nonce"])) ? $_POST["nonce"] : false;
$errors = array();
if (isset($_REQUEST["logout"])){
  $userinfo = $laas_client->logout();
} else {
  $userinfo = $laas_client->check_pbs_login();
  if (is_object($userinfo)) {
    $userinfo = get_object_vars($userinfo);
  }
}

// now we either have userinfo or null.

if (empty($userinfo["pid"])){
  // we're not logged in, so exit
  echo json_encode($userinfo);
  die();
}

$pbs_uid = $userinfo["pid"];

// now we work with the mvault
$mvault_client = $passport->get_mvault_client();
$mvaultinfo = array();

  // if we are handling an mvault activation, associate the userinfo with the mvault record
if (isset($_REQUEST["membership_id"])){
  $mvault_id = $_REQUEST["membership_id"];
  $mvaultinfo = $mvault_client->activate($mvault_id, $pbs_uid);
  $errors['activate'] = $mvaultinfo['errors'];
}
if (! isset($mvaultinfo["membership_id"])) {
  // get the mvault record if available
  $mvaultinfo = $mvault_client->get_membership_by_uid($pbs_uid);  
  $errors['byuid'] = $mvaultinfo['errors'];
}
// preset these for later cleanup
$userinfo["membership_info"] = array("offer" => null, "status" => "Off");
if (isset ($mvaultinfo["membership_id"])) {
  $userinfo["membership_info"] = $mvaultinfo;
  // we may as well setup a VPPA link
  $vppa_links = $passport->get_oauth_links(array('scope' => 'account vppa'));
  // We will now attempt to determine what the users current login_provider is
  // mvault is fallback
  $login_provider = !empty($mvaultinfo["pbs_profile"]["login_provider"]) ? strtolower($mvaultinfo["profile"]["pbs_login_provider"]) : false; 
  if ( !in_array($login_provider, array("pbs", "google", "facebook", "apple") ) ) {
    $login_provider = "pbs";
  }
  // what they last used on the website is better option
  $login_provider = !empty($_COOKIE['pbsoauth_loginprovider']) ? $_COOKIE['pbsoauth_loginprovider'] : $login_provider;
  $vppa_link = $login_provider ? $vppa_links[$login_provider] : false;
}
$success = $laas_client->validate_and_append_userinfo($userinfo);
if ($success) {
  $userinfo = $success;
}
$userinfo['errors'] = $errors;


// store it in its own cookie/session


echo json_encode($userinfo, JSON_UNESCAPED_UNICODE);
exit()
?>
