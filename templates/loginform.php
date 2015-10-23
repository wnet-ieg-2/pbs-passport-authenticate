<?php
show_admin_bar(false);
remove_all_actions('wp_footer',1);
remove_all_actions('wp_header',1);

$defaults = get_option('pbs_passport_authenticate');

$oauthroot = $defaults['oauth2_endpoint'];
$redirect_uri = site_url('/pbsoauth/callback/');
$client_id = $defaults['laas_client_id'];
// initvars
$pbs_auth_endpoint = $oauthroot . 'authorize/?scope=account&redirect_uri=' . $redirect_uri . '&response_type=code&client_id=' . $client_id;
$facebook_auth_endpoint = $oauthroot . 'social/login/facebook/?scope=account&redirect_uri=' . $redirect_uri . '&response_type=code&client_id=' . $client_id;
$google_auth_endpoint = $oauthroot . 'social/login/google-oauth2/?scope=account&redirect_uri=' . $redirect_uri . '&response_type=code&client_id=' . $client_id;

?>
<!DOCTYPE html>
<html>
<head>
<title>Login to Thirteen Passport</title>
<script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
<script src="<?php echo plugins_url( 'pbs-passport-authenticate/assets/' ); ?>js/jquery.pids.js"></script>
<script type="text/javascript">
  var laas_authenticate_script="<?php echo site_url("pbsoauth/authenticate/"); ?>";
<?php if (isset($_REQUEST["membership_id"])) { echo 'var membership_id="' . $_REQUEST["membership_id"] . '";';} ?>
</script>
</head>
<body>
<div id = "login-block">
<h1>Thirteen Passport</h1>
<p>Choose a service from the list below to login with and get access to member-exclusive video on demand and more</p>
<ul>
<li><a class = "service-login-link" href="<?php echo($google_auth_endpoint); ?>">Login using Google</a></li>
<li><a class = "service-login-link" href="<?php echo($facebook_auth_endpoint); ?>">Login using Facebook</a></li>
<li><a class = "service-login-link" href="<?php echo($pbs_auth_endpoint); ?>">Login using PBS</a></li>
<li><input type="checkbox" id="rememberme" name="rememberme" value="true" />Keep me logged in on this computer</li>
</ul>
</div>

<div id = "statusdiv"></div>
<div id = "logout-block"><a> Click here to logout </a><iframe name="PBSAuthIFrame" id="PBSAuthIFrame" style="display:none"></iframe></div>

</body>
</html>
