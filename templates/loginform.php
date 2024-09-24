<?php
$defaults = get_option('pbs_passport_authenticate');
$passport = new PBS_Passport_Authenticate(dirname(__FILE__));

$use_pmsso = isset($defaults['pmsso_is_default']) ? $defaults['pmsso_is_default'] : false;

$links = $passport->get_oauth_links(array('scope' => 'account vppa'));
$pluginImageDir = $passport->assets_url . 'img';
$station_nice_name = $defaults['station_nice_name'];
$auth_client = false;
if ($use_pmsso) {
	wp_enqueue_script( 'pbs_passport_pkce_js' , $passport->assets_url . 'js/pkce_loginform.js', array('jquery'), $passport->version, true );
	$auth_client = $passport->get_pmsso_client();
	$userinfo = $auth_client->check_pmsso_login();
	$pmsso_url = $passport->get_pmsso_link();
} else {
	wp_enqueue_script( 'pbs_passport_loginform_js' , $passport->assets_url . 'js/loginform_helpers.js', array('jquery'), $passport->version, true );
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
} else {
	// not an activation, for PMSSO lets add a jwt state string for security
	$jwt = $passport->create_jwt(array("not_member_path" => "pbsoauth/userinfo"));
	$pmsso_url .= "&state=" . $jwt;
}

define('DISABLE_PLEDGE', 1);

if ($use_pmsso) {
	?>
	<html><head>
	<script type="text/javascript" src="<?php echo get_site_url(); ?>/wp-includes/js/jquery/jquery.min.js?ver=3.7.1" id="jquery-core-js"></script>
	<script type="text/javascript" src="<?php echo($passport->assets_url); ?>js/pkce_loginform.js?ver=<?php echo($passport->version); ?>"></script>
	<style>body {background: #000525; color: #fff; font: bold 1.125em sans-serif; margin: 0; padding: 0;} a {color: #fff;} svg {vertical-align: middle;} a {text-decoration: none; color: #2b92ff;} a:hover {text-decoration: underline; color: #2b92ff;} .svg-txt {vertical-align: middle;} .svg-spin { -webkit-animation: svg-spin 2s infinite linear; animation: svg-spin 2s infinite linear; } .passport-login-wrap {height: 100vh; display: flex; flex-wrap: wrap; flex-flow: row wrap; align-items: center; justify-content: center;} @keyframes svg-spin { 0% { -webkit-transform: rotate(0deg); transform: rotate(0deg);} 100% { -webkit-transform: rotate(359deg); transform: rotate(359deg); }}</style>
	</head>
	<body>
	<div class='passport-login-wrap'><div class='login-inner'>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width: 1em; height: 1em;" class="svg-spin"><path fill="#fff" d="M224 32c0-17.7 14.3-32 32-32C397.4 0 512 114.6 512 256c0 46.6-12.5 90.4-34.3 128c-8.8 15.3-28.4 20.5-43.7 11.7s-20.5-28.4-11.7-43.7c16.3-28.2 25.7-61 25.7-96c0-106-86-192-192-192c-17.7 0-32-14.3-32-32z"/><path fill="#2b92ff" d="M256 64C150 64 64 150 64 256s86 192 192 192c70.1 0 131.3-37.5 164.9-93.6l.1 .1c-6.9 14.9-1.5 32.8 13 41.2c15.3 8.9 34.9 3.6 43.7-11.7c.2-.3 .4-.6 .5-.9l0 0C434.1 460.1 351.1 512 256 512C114.6 512 0 397.4 0 256S114.6 0 256 0c-17.7 0-32 14.3-32 32s14.3 32 32 32z"/></svg>
		<span class='svg-txt'><a href='<?php echo $pmsso_url; ?>' class="pmsso">Please Wait...</a></span>
	</div></div>
	</body></html>
	<?php

} else {

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
		<ul><li><a href="<?php echo $pmsso_url; ?>" class="pmsso">Sign in with PM SSO</a></li>

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
<?php 
get_footer();
}
