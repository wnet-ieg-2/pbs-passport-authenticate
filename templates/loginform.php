<?php
get_header();
show_admin_bar(false);
$defaults = get_option('pbs_passport_authenticate');
$passport = new PBS_Passport_Authenticate(dirname(__FILE__));
$links = $passport->get_oauth_links();
$pluginImageDir = $passport->assets_url . 'img';
$station_nice_name = $defaults['station_nice_name'];
// ADD A NONCE!!
$laas_client = $passport->get_laas_client();
$userinfo = $laas_client->check_pbs_login();
$membership_id = (!empty($_REQUEST['membership_id']) ? $_REQUEST['membership_id'] : false);

?>
<div class='pbs-passport-authenticate-wrap cf'>
<div class="pbs-passport-authenticate login-block">
<div class='passport-middle'>
<h1>Login to Passport</h1>
<p>Get access to member-exclusive video on demand and more</p>
<ul>
<?php if (empty($userinfo)) { ?>
<li class = "service-section-label"><?php echo $station_nice_name; ?> Members who have activated their accounts, please sign in:</li>
<li class = "service-login-link google"><a href="<?php echo($links['google']); ?>"><img src="<?php echo $pluginImageDir; ?>/button-google.png" alt="Login using Google"/></a></li>
<li class = "service-login-link facebook"><a href="<?php echo($links['facebook']); ?>"><img src="<?php echo $pluginImageDir; ?>/button-facebook.png" alt="Login using Facebook"/></a></li>
<li class = "service-login-link pbs"><a href="<?php echo($links['pbs']); ?>"><img src="<?php echo $pluginImageDir; ?>/button-pbs.png" alt="Login using PBS"></a></li>
<li class="service-stay-logged-in"><input type="checkbox" id="rememberme" name="rememberme" value="true" /> Keep me logged in on this computer</li>
<!-- add jquery to make this checkbox a cookie -->
<?php }
if (! $membership_id){ ?>
<li class='service-section-label'><?php echo $station_nice_name; ?> Members who have not activated their accounts:</li>
<li class = "service-login-link activate"><a href="<?php echo site_url('pbsoauth/activate'); ?>"><img src="<?php echo $pluginImageDir; ?>/button-activate-account.png" alt="Activate Account"/></a></li>
<?php }
if (!empty($defaults['join_url'])) {
?>
<li class='service-section-label'>Not a Member?</li>
<li class = "service-login-link becomemember"><a href="<?php echo $defaults['join_url']; ?>"><img src="<?php echo $pluginImageDir; ?>/button-become-a-member.png" alt="Become a member"/></a></li>
<?php }
echo "</ul><p>" . $defaults['help_text'] . "</p>"; ?>
</div>
</div>
</div>
<?php get_footer();
