<?php
/*
 * Plugin Name: PBS Passport Authenticate
 * Version: 0.2.8.3 -- support added for Login with Apple
 * Plugin URI: https://github.com/tamw-wnet/pbs-passport-authenticate
 * Description: PBS Passport Authenticate
 * Author: William Tam, Brian Santalone
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

function pbs_passport_authenticate_icon_svg($icon='') {
	if ($icon == 'compass') {
		return '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 155 155"><path d="M78.32632414102554,146.02631702423096 l-15.9,-50.8 l-50.8,-15.9 l50.8,-15.9 l15.9,-50.8 L94.22632414102554,63.52631702423096 l50.8,15.9 L94.22632414102554,95.22631702423095 L78.32632414102554,146.02631702423096 zM100.82632414102554,56.82631702423096 l5.5,1.7000000000000002 L119.52632414102555,38.12631702423096 l-20.5,13.2 L100.82632414102554,56.82631702423096 zM55.82632414102554,56.82631702423096 l1.7000000000000002,-5.5 L37.02632414102554,38.12631702423096 l13.2,20.4 L55.82632414102554,56.82631702423096 zM100.82632414102554,101.92631702423097 l-1.7000000000000002,5.5 l20.5,13.2 l-13.2,-20.5 L100.82632414102554,101.92631702423097 zM55.82632414102554,101.92631702423097 l-5.5,-1.7000000000000002 l-13.2,20.5 l20.4,-13.2 L55.82632414102554,101.92631702423097 zM156.12632414102555,79.42631702423095 c0,42.9 -34.8,77.8 -77.8,77.8 c-42.9,0 -77.8,-34.8 -77.8,-77.8 c0,-43 34.8,-77.8 77.8,-77.8 C121.22632414102554,1.626317024230957 156.12632414102555,36.426317024230954 156.12632414102555,79.42631702423095 L156.12632414102555,79.42631702423095 zM145.02632414102555,79.42631702423095 c0,-36.8 -29.9,-66.7 -66.7,-66.7 c-36.8,0 -66.7,29.9 -66.7,66.7 c0,36.8 29.9,66.7 66.7,66.7 C115.12632414102555,146.02631702423096 145.02632414102555,116.12631702423096 145.02632414102555,79.42631702423095 L145.02632414102555,79.42631702423095 zM89.42632414102555,-9.473682975769044 "/></svg>';
	}
}


