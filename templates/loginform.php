<?php
$defaults = get_option('pbs_passport_authenticate');
$passport = new PBS_Passport_Authenticate(dirname(__FILE__));

wp_enqueue_script( 'pbs_passport_loginform_js' , $passport->assets_url . 'js/loginform_helpers.js', array('jquery'), $passport->version, true );

$links = $passport->get_oauth_links();
$pluginImageDir = $passport->assets_url . 'img';
$station_nice_name = $defaults['station_nice_name'];
$laas_client = $passport->get_laas_client();
$userinfo = $laas_client->check_pbs_login();
$membership_id = (!empty($_REQUEST['membership_id']) ? $_REQUEST['membership_id'] : false);
if ($membership_id) {
  $mvault_client = $passport->get_mvault_client();
  $mvaultinfo = $mvault_client->get_membership($membership_id);
  if (empty($mvaultinfo['first_name'])){
    // then the membership_id is invalid so discard it
    $membership_id = false;  
  } else {
    foreach ($links as $type => $link){
      //$jwt = json_encode(array("membership_id" => $membership_id));
      // for now lets just pass the membership_id
      $statestring = "&state=" . $membership_id;
      if ($type == 'create_pbs') {
        $statestring = urlencode($statestring);
      }
      $links[$type] = $link . $statestring; 
    }
  }
}

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
	<?php if (empty($userinfo)) { ?> 
	<div class='service-sign-in cf'>
  <?php if (!$membership_id){ ?>
	<h3>MEMBER SIGN IN</h3>
  <?php } ?>
	<ul>
	<li class="google"><a href="<?php echo($links['google']); ?>" title="Sign in with Google"><img src="<?php echo $pluginImageDir; ?>/sign-in-google.png" /></a></li>
	<li class='or'><span>OR</span></li>
	<li class="facebook"><a href="<?php echo($links['facebook']); ?>" title="Sign in with Facebook"><img src="<?php echo $pluginImageDir; ?>/sign-in-facebook.png" /></a></li>
	<li class='or'><span>OR</span></li>
	<li class="pbs"><a href="<?php echo($links['pbs']); ?>" title="Sign in with PBS Account"><img src="<?php echo $pluginImageDir; ?>/sign-in-pbs.png" /></a>
		<div class='create-pbs'>Don't have a PBS account? <a href="<?php echo($links['create_pbs']); ?>">Create one!</a></div>
	</li>
	<li class="stay-logged-in"><input type="checkbox" id="pbsoauth_rememberme" name="pbsoauth_rememberme" value="true" checked /> Keep me logged in on this device</li>
	</ul>
	</div>
	<?php } ?> 
	</div><!-- .pp-narrow -->
	
	<?php if (!$membership_id){ ?>
	<div class='service-options cf'>
	<ul>

	<li class="activate">
	<h4>First time using <?php echo $station_nice_name; ?> Passport?</h4>
	<a href="<?php echo site_url('pbsoauth/activate/'); ?>"><button class='pp-button-outline'>Activate Now &nbsp;<img src='<?php echo $pluginImageDir; ?>/passport_compass_gray.svg'/></button></a>
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
	<?php if (!empty($defaults['help_text'])) {echo "<p class='passport-help-text border'><i class='fa fa-info-circle'></i> " . $defaults['help_text'] . "</p>";} ?>

</div><!-- .passport-login-wrap -->

</div>
</div>
</div>
<?php get_footer();
