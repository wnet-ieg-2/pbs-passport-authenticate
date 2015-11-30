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

echo "<h3>USER STATUS</h3>";
echo "<div class='passport-username'>" . $userinfo['first_name'] . " " . $userinfo['last_name'] . "</div>";

  //echo print_r($userinfo['membership_info']);    


  
  $station_nice_name = $defaults['station_nice_name'];
  $join_url = $defaults['join_url'];
  $watch_url = $defaults['watch_url'];
  

/* active member */
if ($userinfo['membership_info']['offer'] != "" && $userinfo['membership_info']['status'] == "On") {
	echo "<p class='passport-status'>$station_nice_name Passport <i class='fa fa-check-circle passport-green'></i></p>";
	if (!empty($watch_url)) {echo "<p><a href='$watch_url' class='passport-txt-button'>Watch Programs</a></p>";}
}

/* not an active member */
elseif ($userinfo['membership_info']['offer'] == "" && $userinfo['membership_info']['status'] == "Off") {
	$active_url = site_url('pbsoauth/activate');
	echo "<div class='login-wrap cf'><ul>";
	echo "<li><p class='passport-status'>$station_nice_name Passport <span class='passport-exclamation'><i class='fa fa-exclamation'></i></span></p></li>";
	
	
	
	echo "<li class='passport-not-setup'><p>Your $station_nice_name Passport account is not setup.
$station_nice_name Passport is a benefit for eligible members of $station_nice_name.</p>

<p>If you are a member. please choose an option below. If you are not a member, use the \"Become a Member\" button.</p> </li>";
	
	
	
	echo "<li class='service-section-label'>I'm a member <strong>with</strong> an activation code</li>";
	echo "<li class='service-login-link activate'><a href='$active_url'><img src='$pluginImageDir/button-activate-account.png' alt='Activate Account'/></a></li>";
	if (!empty($join_url)) { 
		
		echo "<li class='service-section-label'>I'm a member <strong>without</strong> an activation code</li>";
		echo "<li class='service-login-link accountsetep'><a href='#'><img src='$pluginImageDir/button-request-account-setup.png' alt='Request Account Setup'/></a></li>";
	 	
		echo "<li class='service-section-label'>Not a Member?</li>";
		echo "<li class='service-login-link becomemember'><a href='$join_url'><img src='$pluginImageDir/button-become-a-member.png' alt='Become a member'/></a></li>";

	}
	echo "</ul></div>";
}

/* expired member */
else {
	echo "<p class='passport-status'>" . $defaults['station_nice_name'] . " Passport <i class='fa fa-times-circle passport-red'></i></p>";
	if (!empty($join_url)) {echo "<p><a href='$join_url' class='passport-txt-button'>Renew Membership</a></p>";}
}




echo "<p>" . $defaults['help_text'] . "</p>"; ?>





</div>
</div>
</div>
<?php get_footer();
