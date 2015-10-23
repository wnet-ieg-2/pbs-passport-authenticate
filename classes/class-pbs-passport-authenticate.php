<?php
/* Core functions to create endpoint handling 
*/
if ( ! defined( 'ABSPATH' ) ) exit;

class PBS_Passport_Authenticate {
	private $dir;
	private $file;
	private $assets_dir;
	private $assets_url;
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
    wp_register_script( 'pbs_passport_authenticate_js' , $this->assets_url . 'js/pbs_passport.js', array('jquery'), '0.1', true );
    wp_enqueue_script( 'pbs_passport_authenticate_js' );
  }

  // these next functions setup the custom endpoints

  public function setup_rewrite_rules() {
    add_rewrite_rule( 'pbsoauth/(authenticate|callback|loginform)/?.*$', 'index.php?pbsoauth=$matches[1]', 'top');
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
    $json_args = json_encode($args);
    $button = '<div class="pbs_passport_authenticate"><button class="launch">' . $args['login_text'] .  '<i class="fa fa-plus-circle"></i></button><div class="messages"></div></div>';
    $jsonblock = '<script language="javascript">var pbs_passport_authenticate_args = ' . $json_args . ' </script>';
    $style = '<style>' . file_get_contents($this->assets_dir . '/css/pledge_premiums.css') . '</style>';
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
}
