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



echo '<style>@import url("' . $passport->assets_url . 'css/vppa.css");</style>';
if ($overriden_stylesheet = locate_template('passport-vppa.css')) {
  $second_stylesheet = trailingslashit(get_stylesheet_directory_uri()) . 'passport-vppa.css';
  echo "<style>@import url('" . $second_stylesheet . "');</style>";  
}
    
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

echo "<h1>Welcome back, " . $userinfo['first_name'] . "!</h1>"; 
            

//echo '<pre>';
//echo print_r($userinfo);
// echo '</pre>'; 

/* needs VPPA */
if ( $userinfo['vppa_status'] != 'valid') {
  

  //echo "<p>We're unable to display $station_nice_name Passport videos unless you accept our terms.</p>";
    
    echo "<p>Accessing $station_nice_name Passport requires your consent for PBS to share your viewing history directly with $station_nice_name and our respective service providers in accordance with our <a href='https://www.pbs.org/about/pbs-privacy-policy/' target=_new>Privacy Policy</a>.</p>";
    
    if ($userinfo['vppa_status'] == 'expired') {
        echo "<p>You accepted those terms previously, but we are required to renew your acceptance every two years.</p>";
    }
    
    echo "<p>Please click the link below to confirm and start watching video!</p>    ";

  $vppa_links = $passport->get_oauth_links(array('scope' => 'account vppa'));
  // We will now attempt to determine what the users current login_provider is
  // mvault is fallback
  $login_provider = $passport->get_login_provider($mvaultinfo);
  $vppa_link = $login_provider ? $vppa_links[$login_provider] : false;
  
  if ($vppa_link) {
    echo "<div class='link'><a href='" . $vppa_link . '&activation=true' . "' class='button'>Review and Accept Terms</a></div>";
      
    echo "<div class='link'><a href='/' class='txt-link'>BACK TO $station_nice_name <i class='fa fa-long-arrow-right'></i></a></div>";
      
  } else {
    echo "<p>Please log out and log back in and accept the terms</p>";
  }
} else {
  ?>
    <p>Your assent to sharing viewing history is current.</p>
  <?php
}

//echo "<p class='passport-help-text border'><i class='fa fa-info-circle'></i> " . $defaults['help_text'] . "</p>"; ?>

</article>
    </main>            
            
</body>
</html>
