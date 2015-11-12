<?php
$defaults = get_option('pbs_passport_authenticate');
$passport = new PBS_Passport_Authenticate(dirname(__FILE__));

$pluginImageDir = $passport->assets_url . 'img';
$station_nice_name = $defaults['station_nice_name'];
// ADD A NONCE!!
$laas_client = $passport->get_laas_client();
$userinfo = $laas_client->check_pbs_login();
if (empty($userinfo['first_name'])) {
  // not logged in, redirect to loginform
  wp_redirect(site_url('pbsoauth/loginform'));
  exit();
}
$mvault_client = new PBS_MVault_Client($defaults['mvault_client_id'], $defaults['mvault_client_secret'],$defaults['mvault_endpoint'], $defaults['station_call_letters']);
$mvaultinfo = array();
$mvaultinfo = $mvault_client->get_membership_by_uid($userinfo['pid']);
$userinfo["membership_info"] = array("offer" => null, "status" => "Off");
if (isset ($mvaultinfo["membership_id"])) {
  $userinfo["membership_info"] = $mvaultinfo;
}
$success = $laas_client->validate_and_append_userinfo($userinfo);


get_header();
?>
<div class='pbs-passport-authenticate-wrap cf'>
<div class="pbs-passport-authenticate userinfo-block">
<div class='passport-middle'>
<?php if (!empty($defaults['station_passport_logo'])) {
  echo '<img src="' . $defaults['station_passport_logo'] . '" />'; 
}

echo '<h2>Welcome ' . $userinfo['first_name'] . ' ' . $userinfo['last_name'] . '</h2>';

if (!empty($userinfo['membership_info']['first_name'])) {
  ?><p>Your membership info is connected to your login.  Hooray!</p><?php

 echo "<hr>";
  echo print_r($userinfo['membership_info']);    
  echo "<hr>";
}



if ($userinfo['membership_info']['offer'] != "" && $userinfo['membership_info']['status'] == "On") {
	echo "<p>" . $defaults['station_nice_name'] . " Passport <i class='fa fa-check-circle passport-green'></i></p>";
	if ($defaults['watch_url'] != "") {echo "<p><a href='".$defaults['watch_url']."' class='passport-txt-button'>Watch Programs</a></p>";}
}





echo "<p>" . $defaults['help_text'] . "</p>"; ?>


<i class="fa fa-times-circle"></i>


</div>
</div>
</div>
<?php get_footer();
