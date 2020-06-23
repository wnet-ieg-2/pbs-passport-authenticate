<?php
/* Core functions to create endpoint handling 
*/
if ( ! defined( 'ABSPATH' ) ) exit;

// needed for addition of JWT functionality
use \Firebase\JWT\JWT;
require_once(__DIR__ . '/../libs/php-jwt/autoload.php');

class PBS_Passport_Authenticate {
	private $dir;
	private $file;
	private $assets_dir;
  private $token;
  private $defaults;
	public $assets_url;
  public $version;

	public function __construct($file) {
		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
    $this->token = 'pbs_passport_authenticate';
    $this->defaults = get_option($this->token);
    $this->version = '0.2.9.5';

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

    // Setup the shortcode
    add_shortcode( 'pbs-passport-authenticate', array($this, 'do_shortcode') );

    // Setup the rewrite rules and query vars to make our endpoints work
    add_action( 'init', array($this, 'setup_rewrite_rules') );
    add_filter( 'query_vars', array($this, 'register_query_vars') );
    add_action( 'template_include', array($this, 'rewrite_templates') );
		
	add_filter( 'body_class', array($this, 'ppa_body_classes') );	
	}

  public function enqueue_scripts() {
    wp_register_script( 'js_cookie_js',  $this->assets_url . 'js/js.cookie.js', null, '2.0.4', true);
    wp_enqueue_script( 'js_cookie_js');
    wp_register_script( 'jquery.pids' , $this->assets_url . 'js/jquery.pids.js', array('jquery', 'js_cookie_js'), $this->version, true );
    wp_enqueue_script( 'jquery.pids' );
    //only register this one, we'll enqueue it on just the loginform
    wp_register_script( 'pbs_passport_loginform_js' , $this->assets_url . 'js/loginform_helpers.js', array('jquery'), $this->version, true );

    //required styles
    wp_enqueue_style( 'pbs_passport_css', $this->assets_url . 'css/passport_styles.css', null, $this->version);
  }

  // these next functions setup the custom endpoints

  public function setup_rewrite_rules() {
    add_rewrite_rule( 'pbsoauth/(authenticate|callback|loginform|activate|userinfo|vppa)/?.*$', 'index.php?pbsoauth=$matches[1]', 'top');
  }

  public function register_query_vars( $vars ) {
    $vars[] = 'pbsoauth';
    return $vars;
  }

  public function rewrite_templates($template) {
    if ( get_query_var('pbsoauth')== 'authenticate' ) {
      $template = trailingslashit($this->dir) . 'templates/authenticate.php';
    }
    if ( get_query_var('pbsoauth')=='callback' )  {
      $template = trailingslashit($this->dir) . 'templates/oauthcallback.php';
    }
    if ( get_query_var('pbsoauth')=='loginform' )  {
      $template = trailingslashit($this->dir) . 'templates/loginform.php';
    }
    if ( get_query_var('pbsoauth')=='activate' )  {
      $template = trailingslashit($this->dir) . 'templates/activate.php';
    }
    if ( get_query_var('pbsoauth')=='userinfo' )  {
      $template = trailingslashit($this->dir) . 'templates/userinfo.php';
    }
    if ( get_query_var('pbsoauth')=='vppa' )  {
      $template = trailingslashit($this->dir) . 'templates/vppa.php';
    }
    return $template;
  }

	
	
	// add pbsoauth to body classes.
	public function ppa_body_classes( $classes ) {
		if (!empty(get_query_var('pbsoauth'))) {
			if (in_array(get_query_var('pbsoauth'), array('authenticate','callback','loginform','activate','userinfo','vppa' ))) {
				$classes[] = 'pbsoauth';	
				if ($classes[0] == 'home') { unset($classes[0]); }
			}
		}
    	return $classes;
	}
	
	
	
	
  public function do_shortcode( $atts ) {
    $allowed_args = array('login_text' => 'Sign in', 'render' => 'all' );
    $args = array();
    if (is_array($atts)) {
      $args = shortcode_atts($allowed_args, $atts, 'pbs_passport_authenticate');
    } else {
      $args = $allowed_args;
    }
    $render = $args['render'];
    $args['laas_authenticate_script'] = site_url('pbsoauth/authenticate/');
    $args['loginform'] = site_url('pbsoauth/loginform/');
    $defaults = $this->defaults;
    $args['joinurl'] = $defaults['join_url'];
    $args['activatelink'] =  site_url('pbsoauth/activate/');
    $args['userinfolink'] =  site_url('pbsoauth/userinfo/');
    $args['vppalink'] =  site_url('pbsoauth/vppa/');
    $args['station_call_letters_lc'] = strtolower($defaults['station_call_letters']);
    $json_args = json_encode($args);
    $button = '<div class="pbs_passport_authenticate"><button class="launch">' . $args['login_text'] .  '</button><div class="messages"></div></div>';
    $jsonblock = '<script language="javascript">var pbs_passport_authenticate_args = ' . $json_args . ' </script>';
    $style = '';
    $return = '';
    if ($render == 'all'){
      $return = $button . $jsonblock . $style;
    } else {
      if (strpos($render, 'button') !== false) {
        $return .= $button; 
      }
      if (strpos($render, 'jsonargs') !== false) {
        $return .= $jsonblock; 
      }
      if (strpos($render, 'css') !== false) {
        $return .= $style; 
      }
    }
    return $return;
  }

  public function get_oauth_links($args = null){
    $defaults = $this->defaults;
    
    $oauthroot = ( !empty($args['oauth2_endpoint']) ? $args['oauth2_endpoint'] : $defaults['oauth2_endpoint'] );
    $redirect_uri = ( !empty($args['redirect_uri']) ? $args['redirect_uri'] : site_url('pbsoauth/callback/') );
    $client_id = ( !empty($args['laas_client_id']) ? $args['laas_client_id'] : $defaults['laas_client_id'] );

    $return = array();

    /* complex possibilities for scope */
    $scope =  ( !empty($args['scope']) ? $args['scope'] : ( !empty($defaults['scope']) ? $defaults['scope'] : '' ) );

    $scopestring = ( $scope ? '&scope=' . urlencode($scope) : '' );

    $return['pbs'] = $oauthroot . 'authorize/?redirect_uri=' . $redirect_uri . '&response_type=code&client_id=' . $client_id . $scopestring; 
    $return['google'] = $oauthroot . 'social/login/google-oauth2/?redirect_uri=' . $redirect_uri . '&response_type=code&client_id=' . $client_id . $scopestring;
    $return['facebook'] = $oauthroot . 'social/login/facebook/?redirect_uri=' . $redirect_uri . '&response_type=code&client_id=' . $client_id . $scopestring;
    $return['apple'] = $oauthroot . 'social/login/apple/?redirect_uri=' . $redirect_uri . '&response_type=code&client_id=' . $client_id . $scopestring;
    $return['create_pbs'] = $oauthroot . 'register/?next=' . urlencode('/oauth2/authorize/?redirect_uri=' . $redirect_uri . '&response_type=code&client_id=' . $client_id . $scopestring);
    return $return;
  }

  public function get_laas_client($args = null){
    $defaults = $this->defaults;

    $oauthroot = ( !empty($args['oauth2_endpoint']) ? $args['oauth2_endpoint'] : $defaults['oauth2_endpoint'] );
    $redirect_uri = ( !empty($args['redirect_uri']) ? $args['redirect_uri'] : site_url('pbsoauth/callback/') );
    $client_id = ( !empty($args['laas_client_id']) ? $args['laas_client_id'] : $defaults['laas_client_id'] );
    $client_secret = ( !empty($args['laas_client_secret']) ? $args['laas_client_secret'] : $defaults['laas_client_secret'] );

    $laas_args = array(
      'client_id' => $client_id,
      'client_secret' => $client_secret,
      'oauthroot' => $oauthroot,
      'redirect_uri' => $redirect_uri,
      'tokeninfo_cookiename' => $defaults['tokeninfo_cookiename'],
      'userinfo_cookiename' => 'pbs_passport_userinfo',
      'cryptkey' => $defaults['cryptkey']
    );
    $laas_client = new PBS_LAAS_Client($laas_args);
    return $laas_client;
  }

  public function get_mvault_client(){
    $defaults = $this->defaults;
    $station_id = ( !empty($defaults['station_id']) ? $defaults['station_id'] : $defaults['station_call_letters'] );
    $mvault_client = new PBS_MVault_Client($defaults['mvault_client_id'], $defaults['mvault_client_secret'],$defaults['mvault_endpoint'], $station_id);
    return $mvault_client;
  }

  public function lookup_activation_token($activation_token) {
    $mvault_client = $this->get_mvault_client();
    $mvaultinfo = $mvault_client->lookup_activation_token($activation_token);
    return $mvaultinfo;
  }

  public function create_jwt($payload = array()) {
    $defaults = $this->defaults;
    $allowed_algo = 'HS256'; // in the future allow for RS256 etc
    if (empty($defaults['jwt_secret'])) {
      $defaults['jwt_secret'] = bin2hex(openssl_random_pseudo_bytes(32));
      update_option($this->token, $defaults);
      error_log("jwt_secret not set so generating one and updating in options table");
    }
    if (empty($payload)) {
      // got to have something in there
      return;
    }
    // issuer could theoretically be passed in some cases but will normally be this site
    $payload['iss'] = !empty($payload['iss']) ? $payload['iss'] : get_site_url(null, null, 'https');
    $payload['aud'] = $payload['iss']; // audience will always be the same as issuer in this application
    $payload['iat'] = time();
    $payload['nbf'] = $payload['iat'];
    $payload['exp'] = $payload['iat'] + 600; // it is possible for a person to take 10 minutes to create a new account with an OAuth2 provider
    return JWT::encode($payload, $defaults['jwt_secret'], $allowed_algo);
  }

  public function read_jwt($token) {
    $defaults = $this->defaults;
    $allowed_algo = 'HS256';
    if (empty($defaults['jwt_secret'])) {
      error_log("jwt_secret not set -- go to PBS Passport Authenticate settings and save!");
      return false;
    }
    try {
      ($result = JWT::decode($token, $defaults['jwt_secret'], array($allowed_algo)));
    } catch (\Exception $e) {
      error_log("error decoding token: " . json_encode($e));
      return false;
    }
    // convert the returned object to an array since thats what was submitted
    return (array)$result;
  }



  public function obscured_login_account($mvaultinfo) {
    $profile_email = !empty($mvaultinfo['pbs_profile']['email']) ? $mvaultinfo['pbs_profile']['email'] : false;
    if ($profile_email) {
       return preg_replace("/(.)(.*@)(.)(.*)\.(\w)\w+$/", "$1****@$3****.$5**", $profile_email);
    } 
    return false;
  } 
  
  public function get_login_provider($mvaultinfo) {
    /* this function looks at the mvaultinfo either returns 'pbs', 'google', 'facebook', 'apple' or if unknown, FALSE 
     * it prioritizes the local cookie over what is in the mvault */
    $login_provider = FALSE;
    if (!empty($mvaultinfo['pbs_profile']['login_provider'])) {
      $mvault_client = $this->get_mvault_client();
      $login_provider = $mvault_client->normalize_login_provider($mvaultinfo['pbs_profile']['login_provider']);
      if ( !in_array($login_provider, array("pbs", "google", "facebook", "apple") ) ) {
        $login_provider = FALSE;
      }
    }
    // what they last used on the website is better option anyway
    $login_provider = !empty($_COOKIE['pbsoauth_loginprovider']) ? $_COOKIE['pbsoauth_loginprovider'] : $login_provider;
    return $login_provider;
  }

  public function get_membership_from_access_token($access_token = '', $client_args = array()){
    /* this function consolidates mvault and laas lookups to return a combined/useinfo membership object
     * should have been built years ago! */
    if (!$access_token) {
      return array('errors' => 'no access token provided');
    }
    $laas_client = $this->get_laas_client($client_args);
    $userinfo = $laas_client->get_latest_pbs_userinfo($access_token);
    $pid = !empty($userinfo["pid"]) ? $userinfo["pid"] : false;
    if (!$pid) {
      return array('errors' => 'bad login');
    }

    // preset these for later cleanup
    $userinfo["membership_info"] = array("offer" => null, "status" => "Off");

    // get the full membership info if available
    $mvault_client = $this->get_mvault_client();
    $mvaultinfo = $mvault_client->get_membership_by_uid($pid);
    if (isset ($mvaultinfo["pbs_profile"]["email"])) {
      $mvaultinfo["pbs_profile"]["login_provider"] = $mvault_client->normalize_login_provider($mvaultinfo["pbs_profile"]["login_provider"]);
      $userinfo["membership_info"] = $mvaultinfo;
    }
    return $userinfo; 
  }
}
