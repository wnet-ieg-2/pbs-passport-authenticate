<?php
/*
activate.php

*/
show_admin_bar(false);
define('DISABLE_PLEDGE', 1);
get_header();

$passport = new PBS_Passport_Authenticate(dirname(__FILE__));
$pluginImageDir = $passport->assets_url . 'img';


$defaults = get_option('pbs_passport_authenticate');
$station_nice_name = $defaults['station_nice_name'];
$use_pmsso = isset($defaults['pmsso_is_default']) ? $defaults['pmsso_is_default'] : false;
// this script only takes two possible arguments

$activation_token = (!empty($_REQUEST['activation_token']) ? str_replace(' ', '-', trim($_REQUEST['activation_token'])) : '');

if ($activation_token){
  $mvaultinfo = $passport->lookup_activation_token($activation_token);
  $return = array();
  if (empty($mvaultinfo['membership_id'])){
    $return['errors'] = array('message' => 'This activation code is invalid', 'class' => 'error');
  } else {
    // this is a theoretically valid token.  

    if ($mvaultinfo['status']!='On') {
      $return['errors'] = array('message' => 'This account has been disabled', 'class' => 'error');
    }
    if (!empty($mvaultinfo['activation_date'])) {
      $obscured = $passport->obscured_login_account($mvaultinfo);
      $obs_msg = '';
      if ($obscured) {
        $obs_msg = "</h3><p>This is the email that was used to activate your account:<br /><b>$obscured</b><br />We've obscured all but the first characters and changed the lengths of each part of the email address to protect your privacy.<br />If you recognize this account, please use it to <a href='" . site_url('pbsoauth/loginform') . "'>sign in</a>.</p><h3>";
      }
      $return['errors'] = array('message' => 'Your account has already been activated.  <a href="' . site_url('pbsoauth/loginform')  . '">Please sign in here</a>.' . $obs_msg . 'You only need to activate the first time you use ' . $station_nice_name . ' Passport.<br /><br />', 'class' => 'info');
    }

    if (empty($return['errors']) ){ 
      // nothing wrong with this account, so
      // see if we're already logged in
	  if (!$use_pmsso) {
	      $auth_client = $passport->get_laas_client();
    	  $userinfo = $auth_client->check_pbs_login();
	  } else {
		  $auth_client = $passport->get_pmsso_client();
          $userinfo = $auth_client->check_pmsso_login();
	  }
      if ($userinfo){
        // the user is logged in already.  Activate them!
        $pbs_uid = $userinfo["pid"];
        $mvault_client = $passport->get_mvault_client();
        $mvaultinfo = $mvault_client->activate($mvaultinfo['membership_id'], $pbs_uid);
        $userinfo["membership_info"] = $mvaultinfo;
        $success = $auth_client->validate_and_append_userinfo($userinfo);
        $login_referrer = site_url();
        if ( !empty($_COOKIE["pbsoauth_login_referrer"]) ){
          $login_referrer = $_COOKIE["pbsoauth_login_referrer"];
          setcookie( 'pbsoauth_login_referrer', '', 1, '/', $_SERVER['HTTP_HOST']);
        }
        wp_redirect($login_referrer);
        exit();
      }
      // if NOT logged in, redirect to the login page so they can activate there
      $loginuri = site_url('pbsoauth/loginform') . '?membership_id=' . $mvaultinfo['membership_id'];
      wp_redirect($loginuri);
      exit();
      
    }
  }
}
?>
<div class='pbs-passport-authenticate-wrap cf'>
<div class="pbs-passport-authenticate activate cf">
<div class='passport-middle'>
 
  
  <?php 
  if (!empty($defaults['station_passport_logo'])) {
	  echo '<div class="pp-logo-head"><img src="' . $defaults['station_passport_logo'] . '" alt="'.$station_nice_name.' Passport" /></div>'; 
	}
  ?>

<div class='pp-narrow'>
<h1>Enter your activation code:</h1>
<form action="" method="POST" class='gtm-activate-form cf'>
<input name="activation_token" type="text" value="<?php echo $activation_token; ?>" />
<button class="gtm-submit-activation-code"><i class="fa fa-arrow-circle-right"></i> <span>Enter Code</span></button>
</form>
<?php
if (!empty($return['errors'])){
  echo "<h3 class='" . $return['errors']['class'] . "'>" . $return['errors']['message'] . "</h3>";
}
?>

<h3>How do I find my activation code?</h3>

<p>If you are an active member of <?php echo $station_nice_name; ?> ($60+ annual, or $5 monthly), look for an email from "<?php echo $station_nice_name; ?> Passport" which contains your activation code.</p>  
<h3>Don't have an activation code?</h3>
<?php
$memberlookup_url = 'https://www.pbs.org/passport/lookup/" target="_new';   
if (class_exists('WNET_Passport_Already_Member')) {
  $memberlookup_url = site_url('pbsoauth/alreadymember/');
}
?>
<p>If you don't have an email from us,<br/> <a href="<?php echo $memberlookup_url; ?>">please click here</a>.</p>



</div><!-- .pp-narrow -->

<div class='service-options cf'>
	<ul>

	<li class="activate">
	<h4>Already activated?</h4>
	<a href="<?php echo site_url('pbsoauth/loginform/'); ?>" ><button class='pp-button-outline'><span>MEMBER SIGN IN</span></button></a>
	</li>
	
	<?php if (!empty($defaults['join_url'])) { ?>
	<li class="becomemember">
	<h4>Not a <?php echo $station_nice_name; ?> member?</h4>
	<a href="<?php echo $defaults['join_url']; ?>"><button class='pp-button-outline'>Become a Member <i class="fa fa-heart-o"></i></button></a></li>
	<?php } ?>

	</ul>
	</div>
<?php if (!empty($defaults['help_text'])) {echo "<p class='passport-help-text border'><i class='fa fa-info-circle'></i> " . $defaults['help_text'] . "</p>";} ?>  
<?php 
  // end opt in challenge else condition
?>
</div>
</div>
</div>
<?php get_footer();
