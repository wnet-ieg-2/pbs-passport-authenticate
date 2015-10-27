<?php
show_admin_bar(false);
remove_all_actions('wp_footer',1);
remove_all_actions('wp_header',1);


$passport = new PBS_Passport_Authenticate();
$links = $passport->get_oauth_links();
// ADD A NONCE!!
$laas_client = $passport->get_laas_client();
$userinfo = $laas_client->check_pbs_login();
$membership_id = (!empty($_REQUEST['membership_id']) ? $_REQUEST['membership_id'] : false);


?>
<!DOCTYPE html>
<html>
<head>
<title>Login to Passport</title>
</head>
<body>
<div id = "login-block">
<h1>Login to Passport</h1>
<p>Get access to member-exclusive video on demand and more</p>
<ul>
<?php if (empty($userinfo)) { ?>
<li class = "service-login-link google"><a href="<?php echo($links['google']); ?>">Login using Google</a></li>
<li class = "service-login-link facebook"><a href="<?php echo($links['facebook']); ?>">Login using Facebook</a></li>
<li class = "service-login-link pbs"><a href="<?php echo($links['pbs']); ?>">Login using PBS</a></li>
<li><input type="checkbox" id="rememberme" name="rememberme" value="true" />Keep me logged in on this computer</li>
<!-- add jquery to make this checkbox a cookie -->
<?php }
if (! $membership_id){ ?>
<li class = "service-login-link activate"><a href="<?php echo site_url('pbsoauth/activate'); ?>">I have an activation code</a></li>
<?php } ?>
<li class = "service-login-link becomemember"><a href="<?php echo $join_url; ?>">Become a member</a></li>
</ul>
</div>

</body>
</html>
