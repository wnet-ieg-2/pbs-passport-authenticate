<?php
$defaults = get_option('pbs_passport_authenticate');
$passport = new PBS_Passport_Authenticate(dirname(__FILE__));

$use_pmsso = isset($defaults['pmsso_is_default']) ? $defaults['pmsso_is_default'] : false;

wp_enqueue_script( 'pbs_passport_loginform_js' , $passport->assets_url . 'js/loginform_helpers.js', array('jquery'), $passport->version, true );

$links = $passport->get_oauth_links(array('scope' => 'account vppa'));
$pluginImageDir = $passport->assets_url . 'img';
$station_nice_name = $defaults['station_nice_name'];
$auth_client = false;
if ($use_pmsso) {
	$auth_client = $passport->get_pmsso_client();
	$userinfo = $auth_client->check_pmsso_login();
	$pmsso_url = "https://login.publicmediasignin.org/" . $defaults['pmsso_customerid'] ."/login/authorize?client_id=" . $defaults['pmsso_client_id'] . "&redirect_uri=" . site_url('pbsoauth/callback/') .  "&scope=openid&prompt=login&response_type=code";
} else {
	$auth_client = $passport->get_laas_client();
	$userinfo = $auth_client->check_pbs_login();
} 
$membership_id = (!empty($_REQUEST['membership_id']) ? $_REQUEST['membership_id'] : false);
if ($membership_id) {
  $mvault_client = $passport->get_mvault_client();
  $mvaultinfo = $mvault_client->get_membership($membership_id);
  if (empty($mvaultinfo['first_name'])){
    // then the membership_id is invalid so discard it
    $membership_id = false;  
  } else {
    $jwt = $passport->create_jwt(array("sub" => $membership_id, "not_member_path" => "pbsoauth/userinfo"));
	$pmsso_url .= "&state=" . $jwt;
	$links = $passport->get_oauth_links(array('scope' => 'account vppa'));
   	foreach ($links as $type => $link){
    	$statestring = "&activation=true&state=";
		if ($type == 'create_pbs') {
       		$statestring = urlencode($statestring);
      	}
      	$statestring .= $jwt;
      	$links[$type] = $link . $statestring; 
	}
  }
}

define('DISABLE_PLEDGE', 1);
get_header();
?>
<div class='pbs-passport-authenticate-wrap <?php if (empty($userinfo) && !$membership_id) {echo "wide"; }?> cf'>
<div class="pbs-passport-authenticate login-block">
<div class='passport-middle'>

<?php if (!empty($defaults['station_passport_logo'])) {
  echo '<div class="pp-logo-head"><img src="' . $defaults['station_passport_logo'] . '" /></div>'; 
}
if ($membership_id){
  // this is an activation
  echo "<div class='before-login'>";

  echo '<p class="activation-text add-login-fields">To complete your activation, please choose a sign-in method below.  You can use this sign-in method whenever you visit <a href="' . get_bloginfo('url') . '">' . get_bloginfo('name') . '</a> in the future to enjoy members-only content.</p>';

echo "</div>";

}
?>
 



<div class='passport-login-wrap <?php if ($membership_id){ echo "add-login-fields"; } ?> cf'>


	<div class='pp-narrow'>
	<?php if (!empty($userinfo['curlerrors'])) { 
    /* this only happens if there's a problem with connectivity to PBS.  
     * log an error and display a human readable message to the user 
     */
    error_log('PBS LAAS connection failure: ' . json_encode($userinfo));
  ?>
  <h3>We're sorry!</h3>
  <p>We have encountered an unexpected error.  Please try to reload or revisit this page.  If the error persists for more than a few minutes please let us know.</p>
  <?php } else if (empty($userinfo['pid'])) { ?> 
	<div class='service-sign-in cf'>
  <?php if (!$membership_id){ ?>
	<h3>MEMBER SIGN IN</h3>
	<p><strong>Members get extended access to PBS video on demand and more</strong></p>
 	<p>If you have already activated your <?php echo $station_nice_name; ?> Passport benefit, please sign in below.</p>
  <?php } 
		if ($use_pmsso) { 
		?>
		<ul><li class="pmsso"><a href="<?php echo $pmsso_url; ?>">Sign in with PM SSO</a></li>

		<?php } else {
  ?>
	<ul>
  <li class="pbs"><a href="<?php echo($links['pbs']); ?>" title="Sign in with PBS Account"><img src="<?php echo $pluginImageDir; ?>/sign-in-pbs.png" /></a>
  <?php if ($membership_id){ ?>
    <div class='create-pbs'>Don't have a PBS account? <a href="<?php echo($links['create_pbs']); ?>">Create one!</a></div>
  <?php } ?>
	<li class="google"><a href="<?php echo($links['google']); ?>" title="Sign in with Google"><img src="<?php echo $pluginImageDir; ?>/sign-in-google.png" /></a></li>
	<li class="facebook"><a href="<?php echo($links['facebook']); ?>" title="Sign in with Facebook"><img src="<?php echo $pluginImageDir; ?>/sign-in-facebook.png" /></a></li>
  <li class="apple"><a href="<?php echo($links['apple']); ?>" title="Sign in with Apple"><img src="<?php echo $pluginImageDir; ?>/sign-in-apple.png" /></a></li>
	</li>
	<?php  } ?>
	<li class="stay-logged-in"><input type="checkbox" id="pbsoauth_rememberme" name="pbsoauth_rememberme" value="true" checked /> Keep me logged in on this device</li>
	</ul>
	</div>
	<?php  } else { ?> 
  <p>You seem to already be signed in.  Wait one moment to be redirected to <a href="<?php echo site_url('pbsoauth/userinfo/'); ?>">your user profile page, or click here</a>.</p>
  <?php } ?>
	</div><!-- .pp-narrow -->
	
	<?php if (!$membership_id){ ?>
	<div class='service-options cf'>
	<ul>

	<li class="activate">
	<h4>First time using <?php echo $station_nice_name; ?> Passport?</h4>
	<a href="<?php echo site_url('pbsoauth/activate/'); ?>"><button class='pp-button-outline'><span>Activate Now</span></button></a>
	<p class='look-for-email'>*If you are an active member of <?php echo $station_nice_name; ?> ($60+ annual, or $5 monthly), look for an email which contains your activation code.</p>
	</li>
	
	<?php if (!empty($defaults['join_url'])) { ?>
	<li class="becomemember">
	<h4>Not a <?php echo $station_nice_name; ?> member?</h4>
	<a href="<?php echo $defaults['join_url']; ?>"><button class='pp-button-outline'>Become a Member <i class="fa fa-heart-o"></i></button></a></li>
	<?php } ?>

	</ul>
	</div>
	<?php } ?>
	<?php if (!empty($defaults['help_text'])) {echo "<p class='passport-help-text'><i class='fa fa-info-circle'></i> " . $defaults['help_text'] . "</p>";} ?>

</div><!-- .passport-login-wrap -->

</div>
</div>
</div>
<?php get_footer();
