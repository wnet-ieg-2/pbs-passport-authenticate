<?php
$defaults = get_option('pbs_passport_authenticate');
$passport = new PBS_Passport_Authenticate(dirname(__FILE__));

wp_enqueue_script( 'pbs_passport_loginform_js' , $passport->assets_url . 'js/loginform_helpers.js', array('jquery'), $passport->version, true );

$links = $passport->get_oauth_links();
$pluginImageDir = $passport->assets_url . 'img';
$station_nice_name = $defaults['station_nice_name'];
// ADD A NONCE!!
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
      $jwt = $membership_id;
      $links[$type] = $link . "&state=" . $jwt; 
    }
  }
}

get_header();
?>
<div class='pbs-passport-authenticate-wrap <?php if (empty($userinfo)) {echo "wide"; }?> cf'>
<div class="pbs-passport-authenticate login-block">
<div class='passport-middle'>
<div class='before-login'>
<?php if (!empty($defaults['station_passport_logo'])) {
  echo '<img src="' . $defaults['station_passport_logo'] . '" />'; 
}
if ($membership_id){
  // this is an activation
  echo '<h2>Welcome ' . $mvaultinfo['first_name'] . ' ' . $mvaultinfo['last_name'] . '</h2>'; 
  echo '<p class="activation-text">To complete your activation, please choose a sign-in method below.  You can use this sign-in method whenever you visit <a href="' . get_bloginfo('url') . '">' . get_bloginfo('name') . '</a> in the future to enjoy members-only content.</p>';
} else {
  echo '<h2>Get access to member-exclusive video on demand and more</h2>';
}
 ?>
 </div>
 <div class='login-wrap cf'>
<ul class='float <?php if ($membership_id){ echo "single-column";} ?>'>
<?php if (empty($userinfo)) {
  if (!$membership_id){ ?>
<li class = "service-section-label">Already Activated? Please sign in below</li>
<?php } ?>
<li class = "service-login-link google"><a href="<?php echo($links['google']); ?>"><img src="<?php echo $pluginImageDir; ?>/button-google.png" alt="Login using Google"/></a></li>
<li class = "service-login-link facebook"><a href="<?php echo($links['facebook']); ?>"><img src="<?php echo $pluginImageDir; ?>/button-facebook.png" alt="Login using Facebook"/></a></li>
<li class = "service-login-link pbs"><a href="<?php echo($links['pbs']); ?>"><img src="<?php echo $pluginImageDir; ?>/button-pbs.png" alt="Login using PBS"></a></li>
<li class="service-stay-logged-in"><input type="checkbox" id="pbsoauth_rememberme" name="pbsoauth_rememberme" value="true" /> Keep me logged in on this computer</li>
</ul>

<ul class='float'>
<!-- add jquery to make this checkbox a cookie -->
<?php }
if (!$membership_id){ ?>
<li class='service-section-label'>Not Activated Yet?</li>
<li class = "service-login-link activate"><a href="<?php echo site_url('pbsoauth/activate'); ?>" class='passport-button'><span class='logo-button'>&nbsp;</span>Activate Now</a></li>
<?php 
if (!empty($defaults['join_url'])) {
?>
<li class='service-section-label'>Not a Member?</li>
<li class = "service-login-link becomemember"><a href="<?php echo $defaults['join_url']; ?>"  class='passport-button gray'>Become a Member</a></li>
<?php }
}
echo "</ul>";
echo "<div class='clear'></div>";

echo "<p class='passport-help-text'><i class='fa fa-info-circle'></i> " . $defaults['help_text'] . "</p>";

 ?>

</div><!-- .login-wrap -->

</div>
</div>
</div>
<?php get_footer();
