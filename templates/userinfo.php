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

define('DISABLE_PLEDGE', 1);
get_header();
?>
<div class='pbs-passport-authenticate-wrap cf'>
<div class="pbs-passport-authenticate">
	
	
<!--<div class='passport-middle'>-->
	
	<h1 class='ppa-page-header'>USER PROFILE</h1>
	
<?php 

echo "<div class='ppa-wrap cf'>";
echo "<div class='ppa-primary userinfo-block'>";
	
	
	

	

//echo print_r($mvaultinfo);
  $station_nice_name = $defaults['station_nice_name'];
  $join_url = $defaults['join_url'];
  $watch_url = $defaults['watch_url'];
  $donor_portal_url = trim($defaults['donor_portal_url']);  
	
	
	echo "<div class='passport-username'><strong>MEMBER:</strong> " . $userinfo['first_name'] . " " . $userinfo['last_name'] . "</div>";
	
	echo "<div class='ppa-user-wrap cf'>";

	if (!empty($defaults['station_passport_logo'])) {echo '<div class="pp-logo-inline"><img src="' . $defaults['station_passport_logo'] . '" /></div>';}
	
	
/* active member */
if ( !empty($userinfo['membership_info']['offer']) && $userinfo['membership_info']['status'] == "On" && $userinfo['vppa_status'] == 'valid') {

	echo "<p class='passport-status'>$station_nice_name Passport <i class='fa fa-check-circle passport-green'></i></p>";
	if (!empty($watch_url)) {echo "<div class='activate-options cf'><ul><li class='service-login-link watch'><p><a href='$watch_url'><button class='pp-button-blue'>Watch Programs</button></a></p></li></ul></div>";}
  if (!empty($donor_portal_url)) {echo "<div class='activate-options cf'><ul><li class='service-login-link watch'><p><a href='$donor_portal_url'><button class='pp-button-blue'>Member Portal</button></a></p></li></ul></div>";}

}

/* not an active member */
elseif ( empty($userinfo['membership_info']['offer']) && $userinfo['membership_info']['status'] == "Off") {

	$active_url = site_url('pbsoauth/activate');
	echo "<div class='login-wrap cf'><ul>";
	echo "<li><p class='passport-status'><strong>STATUS:</strong> Not activated <span class='passport-exclamation'><i class='fa fa-exclamation'></i></span></p></li>";
	
	
	
	echo "<li class='passport-not-setup alt-font'><p>Your $station_nice_name Passport membership is not setup with the account you've logged in with.
$station_nice_name Passport is a benefit for eligible members of $station_nice_name.</p>

	<p>If you are a member, please choose an option below. If you are not a member, use the \"Become a Member\" button.</p> </li>";
	
	
	echo "</ul></div>";
	
	
	echo "<div class='activate-options cf'><ul>";
	echo "<li class='service-login-link activate'><p>I'm a member <strong>with</strong> an activation code</p><a href='$active_url'><button class='pp-button-blue'>Activate Account <span class='icon-passport'></span></button></a></li>";

  $memberlookuplink = 'https://www.pbs.org/passport/lookup/';
  $memberlookuptext = 'Request Activation Code';
  if (class_exists('WNET_Passport_Already_Member')) {
    // dependency on an external plugin that depends on LuminateOnline.
    $memberlookuplink = site_url('pbsoauth/alreadymember');
    $memberlookuptext = 'Request Account Setup';
  } 
	echo "<li class='service-login-link accountsetep'><p>I'm a member <strong>without</strong> an activation code</p><a href='" . $memberlookuplink . "'><button class='pp-button-blue'>$memberlookuptext</button></a></li>";
	if (!empty($join_url)) { echo "<li class='service-login-link join'><p>Not a Member?</p><a href='$join_url'><button class='pp-button-blue'>Become a Member</button></a></li>";}

  //echo "<li class='service-login-link loginproblem'><p>I have <strong>already activated</strong></p><div class='pbs_passport_authenticate'><a class='signout'><button class='pp-button-blue'>Try a different login</button></a></div></li>";

	echo "</ul></div><!-- .activate-options -->
	
	<div class='alt-font'>
	<p>Do you have an activated $station_nice_name Passport membership that you've used before?</p><p>The account you are logged in with may not be the one you signed up for $station_nice_name Passport with. Please try <span class='pbs_passport_authenticate'><a class='signout'>signing out</a></span> and clicking 'Member Sign In' to sign in with a $station_nice_name Passport activated account.</p>
	</div>
	
	";

}

/* Valid member needs VPPA */
elseif ( $userinfo['vppa_status'] != 'valid' && $userinfo['membership_info']['status'] == "On") {
  wp_redirect(site_url('pbsoauth/vppa'));
  exit;
}

/* expired member */
else {
  echo "<div class='passport-username'><strong>MEMBER:</strong> " . $userinfo['first_name'] . " " . $userinfo['last_name'] . "</div>";
	echo "<p class='passport-status'><strong>STATUS:</strong> Expired <i class='fa fa-times-circle passport-red'></i></p>";
	if (!empty($join_url)) {echo "<p>Your $station_nice_name Passport membership has expired.  Please renew your $station_nice_name membership to continue enjoying $station_nice_name Passport content.</p><div class='activate-options cf'><ul><li class='service-login-link watch'><a href='$join_url'><button class='pp-button-blue'>Renew Membership</button></a></li></ul></div>";}
  if (!empty($donor_portal_url)) {echo "<div class='activate-options cf'><ul><li class='service-login-link watch'><p><a href='$donor_portal_url'><button class='pp-button-blue'>Member Portal</button></a></p></li></ul></div>";}
}



 ?>

	
	</div> <!-- .ppa-user-wrap -->
	
	
	<div class='ppa recent-videos-list cf'> <!-- populated with js --></div>
	

	</div> <!-- .ppa-primary -->

	
	<div class="ppa-secondary">
		
		<div class="ppa-box help">
			<h3 class="boxhead">NEED HELP?</h3>
      <?php echo $defaults['help_text']; ?>
		</div>
		
		<div class="ppa-box">
			<h3 class="boxhead">About <?php echo $defaults['station_nice_name']; ?> Passport</h3>
      <p><?php echo $defaults['station_nice_name']; ?> Passport is the member benefit that provides you with extended access to an on-demand library of quality public television programming, including current and past seasons of PBS shows.</p>
		</div>
		
	</div>

	
	</div> <!-- .ppa-wrap -->
	
	
<!--</div>-->
</div>
</div>
<?php get_footer();
