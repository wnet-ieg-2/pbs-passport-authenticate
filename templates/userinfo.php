<?php
$defaults = get_option('pbs_passport_authenticate');
$passport = new PBS_Passport_Authenticate(dirname(__FILE__));

$pluginImageDir = $passport->assets_url . 'img';
$station_nice_name = $defaults['station_nice_name'];
$laas_client = $passport->get_laas_client();
$userinfo = $laas_client->check_pbs_login();
if (empty($userinfo['first_name'])) {
  // just in case, log them out, maybe they've got a bad cookie
  $laas_client->logout();
  // not logged in, redirect to loginform
  wp_redirect(site_url('pbsoauth/loginform'));
  exit();
}
$mvault_client = $passport->get_mvault_client();
$mvaultinfo = array();
$mvaultinfo = $mvault_client->get_membership_by_uid($userinfo['pid']);
$userinfo["membership_info"] = array("offer" => null, "status" => "Off");
if (isset ($mvaultinfo["membership_id"])) {
  $userinfo["membership_info"] = $mvaultinfo;
  $userinfo = $laas_client->validate_and_append_userinfo($userinfo);
}

get_header();
?>
<div class='pbs-passport-authenticate-wrap cf'>
<div class="pbs-passport-authenticate userinfo-block">
<div class='passport-middle'>
<?php if (!empty($defaults['station_passport_logo'])) {
  echo '<div class="pp-logo-head"><img src="' . $defaults['station_passport_logo'] . '" /></div>'; 
}

echo "<div class='pp-narrow'>";

echo "<h3>USER STATUS</h3>";
echo "<div class='passport-username'>" . $userinfo['first_name'] . " " . $userinfo['last_name'] . "</div>";

  
  $station_nice_name = $defaults['station_nice_name'];
  $join_url = $defaults['join_url'];
  $watch_url = $defaults['watch_url'];
  

/* active member */
if ( !empty($userinfo['membership_info']['offer']) && $userinfo['membership_info']['status'] == "On" && $userinfo['vppa_status'] == 'valid') {
	echo "<p class='passport-status'>$station_nice_name Passport <i class='fa fa-check-circle passport-green'></i></p>";
	if (!empty($watch_url)) {echo "<p><a href='$watch_url'><button class='pp-button-outline'>Watch Programs <i class='fa fa-arrow-circle-right'></i></button></a></p>";}
}

/* not an active member */
elseif ( empty($userinfo['membership_info']['offer']) && $userinfo['membership_info']['status'] == "Off") {
	$active_url = site_url('pbsoauth/activate');
	echo "<div class='login-wrap cf'><ul>";
	echo "<li><p class='passport-status'>$station_nice_name Passport <span class='passport-exclamation'><i class='fa fa-exclamation'></i></span></p></li>";
	
	
	
	echo "<li class='passport-not-setup'><p>Your $station_nice_name Passport account is not setup.
$station_nice_name Passport is a benefit for eligible members of $station_nice_name.</p>

	<p>If you are a member. please choose an option below. If you are not a member, use the \"Become a Member\" button.</p> </li>";
	
	
	echo "</ul></div>";
	
	
	echo "<div class='activate-options cf'><ul>";
	echo "<li class='service-login-link activate'><h4>I'm a member <strong>with</strong> an activation code</h4><a href='$active_url'><button class='pp-button-outline'>Activate Account <span class='icon-passport'></span></button></a></li>";
	echo "<li class='service-login-link accountsetep'><h4>I'm a member <strong>without</strong> an activation code</h4><a href='". site_url('pbsoauth/alreadymember') ."'><button class='pp-button-outline'>Request Account Setup</button></a></li>";
	if (!empty($join_url)) { echo "<li class='single'><h4>Not a Member?</h4><a href='$join_url'><button class='pp-button-outline'>Become a Member <i class='fa fa-heart-o'></i></button></a></li>";}
	echo "</ul></div><!-- .activate-options -->";

}

/* needs VPPA */
elseif ( $userinfo['vppa_status'] != 'valid') {
  echo "<div class='login-wrap cf'><ul>";
  echo "<li><p class='passport-status'>$station_nice_name Passport <span class='passport-exclamation'><i class='fa fa-exclamation'></i></span></p></li>";

  echo "<li class='passport-not-setup'><p>We're unable to display $station_nice_name Passport videos unless you accept our terms of service.</p>";
  if ($userinfo['vppa_status'] == 'expired') {
    echo "<p>You accepted those terms previously, but we are required to renew your acceptance every two years.</p>";
  }
  $links = $passport->get_oauth_links();
  // We will now attempt to determine what the users current login_provider is
  $login_provider = false;
  $login_provider = !empty($_COOKIE['pbsoauth_loginprovider']) ? $_COOKIE['pbsoauth_loginprovider'] : false;

  if ($login_provider) {
    echo "<a href='" . $links[strtolower($login_provider)] . "'><button class='pp-button-outline'>Accept Terms of Service</button></a>";
  } else {
    echo "<p>Please log out and log back in and accept the terms of service</p>";
  }
  echo "</li></ul></div>";


}

/* expired member */
else {
	echo "<p class='passport-status'>" . $defaults['station_nice_name'] . " Passport <i class='fa fa-times-circle passport-red'></i></p>";
	if (!empty($join_url)) {echo "<p><a href='$join_url' class='passport-button'>Renew Membership</a></p>";}
}


	echo "</div><!-- .pp-narrow -->";

 ?>





<?php echo "<p class='passport-help-text border'><i class='fa fa-info-circle'></i> " . $defaults['help_text'] . "</p>"; ?>


</div>
</div>
</div>
<?php get_footer();
