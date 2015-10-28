<?php
/* Core functions to create endpoint handling 
*/
if ( ! defined( 'ABSPATH' ) ) exit;

class PBS_Passport_Authenticate {
	private $dir;
	private $file;
	private $assets_dir;
	public $assets_url;
  private $token;

	public function __construct($file) {
		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
    $this->token = 'pbs_passport_authenticate';

		// Load public-facing style sheet and JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

    // Setup the shortcode
    add_shortcode( 'pbs-passport-authenticate', array($this, 'do_shortcode') );

    // Setup the rewrite rules and query vars to make our endpoints work
    add_action( 'init', array($this, 'setup_rewrite_rules') );
    add_filter( 'query_vars', array($this, 'register_query_vars') );
    add_action( 'template_include', array($this, 'rewrite_templates') );
	}

  public function enqueue_scripts() {
    wp_register_script( 'pbs_passport_authenticate_js' , $this->assets_url . 'js/jquery.pids.js', array('jquery'), '0.1', true );
    wp_enqueue_script( 'pbs_passport_authenticate_js' );
  }

  // these next functions setup the custom endpoints

  public function setup_rewrite_rules() {
    add_rewrite_rule( 'pbsoauth/(authenticate|callback|loginform|activate)/?.*$', 'index.php?pbsoauth=$matches[1]', 'top');
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
    return $template;
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
    $args['laas_authenticate_script'] = site_url('pbsoauth/authenticate');
    $args['loginform'] = site_url('pbsoauth/loginform');
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

  public function get_oauth_links(){
    $defaults = get_option('pbs_passport_authenticate');
    $oauthroot = $defaults['oauth2_endpoint'];
    $redirect_uri = site_url('pbsoauth/callback/');
    $client_id = $defaults['laas_client_id'];
    
    $return = array();
    $return['pbs'] = $oauthroot . 'authorize/?scope=account&redirect_uri=' . $redirect_uri . '&response_type=code&client_id=' . $client_id; 
    $return['google'] = $oauthroot . 'social/login/google-oauth2/?scope=account&redirect_uri=' . $redirect_uri . '&response_type=code&client_id=' . $client_id;
    $return['facebook'] = $oauthroot . 'social/login/facebook/?scope=account&redirect_uri=' . $redirect_uri . '&response_type=code&client_id=' . $client_id;
    return $return;
  }

  public function get_laas_client(){
    $defaults = get_option('pbs_passport_authenticate');
    $laas_args = array(
      'client_id' => $defaults['laas_client_id'],
      'client_secret' => $defaults['laas_client_secret'],
      'oauthroot' => $defaults['oauth2_endpoint'],
      'redirect_uri' => site_url('/pbsoauth/callback/'),
      'tokeninfo_cookiename' => 'safdsafa',
      'userinfo_cookiename' => 'pbs_passport_userinfo',
      'cryptkey' => 'rioueqnfa2e',
      'encrypt_iv' => 'adsfafdsaafddsaf'
    );
    $laas_client = new PBS_LAAS_Client($laas_args);
    return $laas_client;
  }

  public function get_mvault_client(){
    $defaults = get_option('pbs_passport_authenticate');
    $mvault_client = new PBS_MVault_Client($defaults['mvault_client_id'], $defaults['mvault_client_secret'],$defaults['mvault_endpoint'], $defaults['station_call_letters']);
    return $mvault_client;
  }

  public function lookup_activation_token($activation_token) {
    $mvault_client = $this->get_mvault_client();
    $mvaultinfo = $mvault_client->lookup_activation_token($activation_token);
    return $mvaultinfo;
  }

}
