<?php
/*
activate.php

*/
show_admin_bar(false);
remove_all_actions('wp_footer',1);
remove_all_actions('wp_header',1);

$passport = new PBS_Passport_Authenticate();

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
        $mvaultinfo = $mvault_client->activate($mvaultinfo['membership_id'], $pbs_uid);
        $userinfo["membership_info"] = $mvaultinfo;
        $success = $laas_client->validate_and_append_userinfo($userinfo);
        $login_referrer = site_url();
        if ( !empty($_COOKIE["pbsoauth_login_referrer"]) ){
          $login_referrer = $_COOKIE["pbsoauth_login_referrer"];
        }
        wp_redirect(site_url($login_referrer));
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
<html>
<head>
<title>Enter Your Passport Activation Code</title>
</head>
<body>
<h1>Enter Your Passport Activation Code</h1>
<?php if (!empty($return['errors'])){
  echo "<h2>" . $return['errors'] . "</h2>";
} ?>
<form action="">
<input name="activation_token" type="text" value="<?php echo $activation_token; ?>" />
<input name="submit" type="submit" value="Enter code"/></form>
</body>
</html>
