<?php
/*
 * Plugin Name: PBS Passport Authenticate
 * Version: 0.1.4.1
 * Plugin URI: https://github.com/tamw-wnet/pbs-passport-authenticate
 * Description: PBS Passport Authenticate
 * Author: William Tam
 * Author URI: http://ieg.wnet.org/
 * Requires at least: 4.0 
 * Tested up to: 4.2.2
 * 
 * @package WordPress
 * @author William Tam 
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// pre-define pluggable functions if needed
$plugpath = trailingslashit(dirname(__FILE__)) . 'pluggable.php';
if (file_exists($plugpath)) {
  require_once($plugpath);
}


// Include plugin class files
require_once( 'build-passport-player.php' );
require_once( 'classes/class-pbs-passport-authenticate.php' );
require_once( 'classes/class-pbs-passport-authenticate-settings.php' );
require_once('classes/class-PBS-LAAS-client.php');
if (!class_exists('PBS_MVault_Client')) {
  require_once('classes/class-PBS-MVault-client.php');
}


global $plugin_obj;
$plugin_obj = new PBS_Passport_Authenticate( __FILE__ );

if ( is_admin() ) {
  $plugin_settings_obj = new PBS_Passport_Authenticate_Settings( __FILE__ );
}

register_activation_hook(__FILE__, 'pbs_passport_authenticate_activation');

function pbs_passport_authenticate_activation() {
  // init the object, which will setup the object
  $plugin_obj = new PBS_Passport_Authenticate( __FILE__ );
  $plugin_obj->setup_rewrite_rules();
  flush_rewrite_rules();    
}

// always cleanup after yourself
register_deactivation_hook(__FILE__, 'pbs_passport_authenticate_deactivation');

function pbs_passport_authenticate_deactivation() {
  flush_rewrite_rules();
}


function pbs_passport_authenticate_render_video($vidid) {



}


