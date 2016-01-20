<?php
/*
activate.php

*/
show_admin_bar(false);
get_header();

$passport = new PBS_Passport_Authenticate(dirname(__FILE__));
$pluginImageDir = $passport->assets_url . 'img';
// ADD A NONCE


$defaults = get_option('pbs_passport_authenticate');
$station_nice_name = $defaults['station_nice_name'];

// this script only takes one possible argument

$activation_token = (!empty($_REQUEST['activation_token']) ? $_REQUEST['activation_token'] : '');


if ($activation_token){
  $mvaultinfo = $passport->lookup_activation_token($activation_token);
  $return = array();
  if (empty($mvaultinfo['membership_id'])){
    $return['errors'] = 'This activation code is invalid';
  } else {
    // this is a theoretically valid token.  

    if ($mvaultinfo['status']!='On') {
      $return['errors'] = 'This account has been disabled';
    }
    if (!empty($mvaultinfo['activation_date'])) {
      $return['errors'] = 'This activation code has already been used. <br />You only need to activate once for access.';
    }
    if (empty($return['errors'])){ 
      // nothing wrong with this account, so
      // see if we're already logged in
      $laas_client = $passport->get_laas_client();
      $userinfo = $laas_client->check_pbs_login();
      if ($userinfo){
        // the user is logged in already.  Activate them!
        $pbs_uid = $userinfo["pid"];
        $mvault_client = $passport->get_mvault_client();
        $mvaultinfo = $mvault_client->activate($mvaultinfo['membership_id'], $pbs_uid);
        $userinfo["membership_info"] = $mvaultinfo;
        $success = $laas_client->validate_and_append_userinfo($userinfo);
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
	  echo '<img src="' . $defaults['station_passport_logo'] . '" alt="'.$station_nice_name.' Passport" />'; 
	}
  ?>
  
  
<h1>Enter your activation code:</h1>
<form action="" method="POST" class='cf'>
<input name="activation_token" type="text" value="<?php echo $activation_token; ?>" />
<button><i class="fa fa-arrow-circle-right"></i> <span>Enter Code</span></button>
</form>
<?php
if (!empty($return['errors'])){
  echo "<h3 class='error'>" . $return['errors'] . "</h3>";
}
?>

<h2>How do I find my activation code?</h2>

<p>If you are an active member of <?php echo $station_nice_name; ?> ($60+ annual, or $5 monthly), look for an email from "<?php echo $station_nice_name; ?> Passport" which contains your activation code.</p>  
<h3>Don't have an activation code?</h3>
<p>If you don't have an email from us, <a href="<?php echo site_url('pbsoauth/alreadymember/'); ?>">please click here</a>.</p>
<h3>I already activated.</h3>
<p>If you have already activated your <?php echo $station_nice_name; ?> Passport account, <a href="<?php echo site_url('pbsoauth/loginform/'); ?>" >click here to sign in</a>.</p>
<h3>Not a member?</h3>
<p>If you are not a current member, <a href="<?php echo $defaults['join_url']; ?>">click here to sign in.</a></p>
<p>&nbsp;</p>
<p class='passport-help-text'><i class='fa fa-info-circle'></i> <?php echo $defaults['help_text']; ?></p>
</div>
</div>
</div>
<?php get_footer();
