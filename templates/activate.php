<?php
/*
activate.php

*/
show_admin_bar(false);
get_header();

$passport = new PBS_Passport_Authenticate(dirname(__FILE__));
$pluginImageDir = $passport->assets_url . 'img';
// ADD A NONCE

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
      $return['errors'] = 'This activation code has already been used';
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
  <img src="http://thirteen-mvod-dev.vc2.wnet.org/files/2015/11/thirteen-passport.png" alt="Thirteen Passport">
<h1>Enter your activation code:</h1>
<?php 
if (!empty($return['errors'])){
  echo "<h3 class='error'>" . $return['errors'] . "</h3>";
} 
?>
<form action="" method="POST" class='cf'>
<input name="activation_token" type="text" value="<?php echo $activation_token; ?>" />
<button><i class="fa fa-arrow-circle-right"></i> <span>Enter Code</span></button>
</form>

<h2>What is an activation code and how do I find mine?</h2>


<p>Your activation code will be four English words with dashes in between them that was sent to you from THIRTEEN.  This code is only used once, and we use it to connect your login account -- your Google, Facebook, or email sign-in --  with your membership information.</p>  

<p>If you have already used your activation code, you will never need to use it again.  Just sign in with the Google, Facebook, or email sign-in that you used when you activated before.  This is true even if you activated on a different computer or device.</p>

<p>If you are an up-to-date member of THIRTEEN at the $60 per year or higher level and we have your email address on file, we sent you an email with your activation code in it.</p>

<p>If you can't find that email, or if THIRTEEN doesn't have your email address on file, we can send your activation code to you by email if you visit <a href="<?php echo site_url('pbsoauth/alreadymember'); ?>">this form</a>.</p>
 

</div>
</div>
</div>
<?php get_footer();
