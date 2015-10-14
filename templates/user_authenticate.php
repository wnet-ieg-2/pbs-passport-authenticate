<?php
/*
user_authenticate.php

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
$station = 'WNET';
require_once('initvars.php');
require_once('classes/class-PBS-LAAS-client.php');
require_once('classes/class-PBS-MVault-client.php');

$laas_client = new PBS_LAAS_Client($client_id, $client_secret, $oauthroot, $redirect_uri, $tokeninfo_cookiename, $userinfo_cookiename, $cryptkey, $encrypt_iv, $encrypt_method);

$rememberme = (isset($_POST["rememberme"])) ? $_POST["rememberme"] : false;
$nonce = (isset($_POST["nonce"])) ? $_POST["nonce"] : false;
$errors = array();
if (isset($_POST["code"])){
  $code = $_POST["code"];
  $userinfo = $laas_client->authenticate($code, $rememberme, $nonce);
} elseif (isset($_REQUEST["logout"])){
  $userinfo = $laas_client->logout();
} else {
  $userinfo = $laas_client->check_pbs_login();
}

// now we either have userinfo or null.

if (! isset($userinfo["pid"])){
  // we're not logged in, so exit
  echo json_encode($userinfo);
  die();
}

$pbs_uid = $userinfo["pid"];

// now we work with the mvault

if (isset($MVAULT_CREDS[$station])){
  $mvaultvars = $MVAULT_CREDS[$station];
  $MVAULT_USERNAME = $mvaultvars['MVAULT_USERNAME'];
  $MVAULT_SECRET = $mvaultvars['MVAULT_SECRET'];

  $mvault_client = new PBS_MVault_Client($MVAULT_USERNAME, $MVAULT_SECRET, $MVAULT_URL, $station);
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
  if (isset ($mvaultinfo["membership_id"])) {
    $userinfo["membership_info"] = $mvaultinfo;
    $success = $laas_client->validate_and_append_userinfo($userinfo);
    if ($success) {
      //$userinfo = $success;
    }
  }
}
$userinfo['errors'] = $errors;


// store it in its own cookie/session


echo json_encode($userinfo);
?>
