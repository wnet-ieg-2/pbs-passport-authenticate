<?php
/* Core functions such as post and taxonomy definition and a basic interface for 
*  retrieving pledge premiums in PHP or via an AJAX call
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

    // make calls to pledge premium content use our custom AJAX templates
    add_filter( 'archive_template', array( $this, 'use_custom_template' ) );

   // Setup the shortcode
    add_shortcode( 'pledge_premiums', array($this, 'do_shortcode') );

	}

  public function enqueue_scripts() {
    // colorbox is a common script so lets avoid conflicts and use whatever version is registered if one is already
    if (! wp_script_is( 'colorbox', 'registered' ) ) {
      wp_register_script( 'colorbox', $this->assets_url . 'js/jquery.colorbox-min.js', array('jquery'), '1.6.3', true );
      // base colorbox styling 
      wp_enqueue_style( 'colorbox_css', $this->assets_url . 'css/colorbox.css' );
    }
    wp_enqueue_script( 'colorbox' );
    // our custom front-facing script to include everywhere since its called in a plugin

    wp_register_script( 'pbs_passport_authenticate_js' , $this->assets_url . 'js/pbs_passport.js', array('colorbox', 'jquery'), '0.1', true );
    wp_enqueue_script( 'pbs_passport_authenticate_js' );
    // custom styling
    //wp_enqueue_style( 'pbs_passport_authenticate_css', $this->assets_url . 'css/pledge_premiums.css' );
  }


  public function use_custom_template($template) {
    global $post;

    if ($post->post_type == 'pbs_passport_authenticate' || is_post_type_archive( 'pbs_passport_authenticate') ) {
      $template = trailingslashit($this->dir) . 'pledge-premiums-template.php';
    }
    return $template;
  }

  public function do_shortcode( $atts ) {
    $allowed_args = array('display_programs'=>null, 'featured_premium'=>null, 'featured_program'=>null, 'form_type'=>'sustainer', 'api_endpoint' => site_url('pledge_premiums'), 'render' => 'all' );
    $args = array();
    if (is_array($atts)) {
      $args = shortcode_atts($allowed_args, $atts, 'pledge_premiums');
    }
    $render = $args['render'];
    $json_args = json_encode($args);
    $button = '<div id="pbs_passport_authenticate"><button class="launch">Select a premium <i class="fa fa-plus-circle"></i></button><div class="messages"></div><input type="hidden" name="pcode" id="pcode" value="" /><input type="hidden" name="req_amt" id="req_amt" value=0 /></div>';
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
