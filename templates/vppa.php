<html>
<head>
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
    
echo "<style>@import url('".$passport->assets_url."css/vppa.css');</style>";    
    
?>
</head>
<body>
    
  <main>
        <article>    
    
<?php
    // logo
    if (!empty($defaults['station_passport_logo_reverse'])) {$logo = $defaults['station_passport_logo_reverse'];}
    elseif (!empty($defaults['station_passport_logo'])) {$logo = $defaults['station_passport_logo'];} 

    if (!empty($logo)) {$logo = '<img src="'.$logo.'" alt="'. $defaults['station_nice_name'].' Passport"></a>';}
    else {$logo = "<h1>" . $defaults['station_nice_name']. ' Passport' . "</h1>";}  
    echo $logo;
?>            
            
      

<?php 

echo "<h1>Welcome back, " $userinfo['first_name'] . "!</h1>"; 
            

echo '<pre>';
echo print_r($userinfo);
 echo '</pre>'; 

/* needs VPPA */
if ( $userinfo['vppa_status'] != 'valid') {
  echo "$station_nice_name Passport";

  echo "<p>We're unable to display $station_nice_name Passport videos unless you accept our terms of service.</p>";
  if ($userinfo['vppa_status'] == 'expired') {
    echo "<p>You accepted those terms previously, but we are required to renew your acceptance every two years.</p>";
  }

  $vppa_links = $passport->get_oauth_links(array('scope' => 'account vppa'));
  // We will now attempt to determine what the users current login_provider is
  // mvault is fallback
  $login_provider = !empty($mvaultinfo["pbs_profile"]["login_provider"]) ? strtolower($mvaultinfo["profile"]["pbs_login_provider"]) : false;
  if ( !in_array($login_provider, array("pbs", "google", "facebook") ) ) {
    $login_provider = "pbs";
  }
  // what they last used on the website is better option
  $login_provider = !empty($_COOKIE['pbsoauth_loginprovider']) ? $_COOKIE['pbsoauth_loginprovider'] : $login_provider;
  $vppa_link = $login_provider ? $vppa_links[$login_provider] : false;
  $userinfo["vppa_link"] = $vppa_link;
  
  if ($vppa_link) {
    echo "<a href='" . $vppa_link . '&activation=true' . "'><button class='pp-button-outline'>Accept Terms of Service</button></a>";
    echo "<p>Or, <a href='/'>continue without access to THIRTEEN Passport video</a>.  You can accept the terms at any time to get access.</p>";
  } else {
    echo "<p>Please log out and log back in and accept the terms of service</p>";
  }
} else {
  ?>
    Your VPPA assent is current.
  <?php
}

echo "<p class='passport-help-text border'><i class='fa fa-info-circle'></i> " . $defaults['help_text'] . "</p>"; ?>

</article>
    </main>            
            
</body>
</html>