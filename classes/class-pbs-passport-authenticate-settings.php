<?php
/* This class handles the settings page for the plugin
*/
if ( ! defined( 'ABSPATH' ) ) exit;

class PBS_Passport_Authenticate_Settings { 
  private $dir;
 	private $file;
	private $assets_dir;
	private $assets_url;
  private $token;

	public function __construct( $file ) {
		$this->dir = dirname( $file );
		$this->file = $file;
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
    $this->token = 'pbs_passport_authenticates';

		// Register plugin settings
		add_action( 'admin_init' , array( $this , 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu' , array( $this , 'add_menu_item' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->file ) , array( $this , 'add_settings_link' ) );

	}

	
	public function add_menu_item() {
		$hook_suffix = add_options_page( 'PBS Passport Authenticate Settings' , 'PBS Passport Authenticate Settings' , 'manage_options' , 'pbs_passport_authenticate_settings' ,  array( $this , 'settings_page' ) );
    // adds the scripts to only the options page
    add_action('admin_print_scripts-' . $hook_suffix, array( $this, 'setup_admin_scripts'));
	}

	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=pbs_passport_authenticate_settings">Settings</a>';
  		array_push( $links, $settings_link );
  		return $links;
	}

  public function setup_admin_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('suggest');
    wp_register_script('pbs-passport-authenticate-settings-admin', $this->assets_url . 'js/settings_admin_functions.js', array('jquery','jquery-ui-autocomplete'));
    wp_enqueue_script('pbs-passport-authenticate-settings-admin');
  }


	public function register_settings() {
    register_setting( 'pbs_passport_authenticate_group', 'pbs_passport_authenticate' );

    add_settings_section('general_settings', 'General settings', array( $this, 'settings_section_callback'), 'pbs_passport_authenticate');

    add_settings_field( 'station_call_letters', 'Station Call Letters', array( $this, 'settings_field'), 'pbs_passport_authenticate', 'general_settings', array('setting' => 'pbs_passport_authenticate', 'field' => 'station_call_letters', 'class' => 'regular-text', 'label' => 'ID as used by PBS to identify the station, eg "WNET" or "WNJT" not "Thirteen" or "NJTV".  Upper/lower-case unimportant, we automatically transform as needed.'  ) );
    add_settings_field( 'station_nice_name', 'Station "Nice" Name', array( $this, 'settings_field'), 'pbs_passport_authenticate', 'general_settings', array('setting' => 'pbs_passport_authenticate', 'field' => 'station_nice_name', 'class' => 'regular-text', 'label' => 'Preferred name for your station, eg "Thirteen" or "Minnesota PTV"'  ) );
    add_settings_field( 'station_passport_logo', 'Station Passport Logo', array( $this, 'settings_field'), 'pbs_passport_authenticate', 'general_settings', array('setting' => 'pbs_passport_authenticate', 'field' => 'station_passport_logo', 'class' => 'regular-text', 'label' => 'URL to your logo file that has your station name "locked up" with the Passport logo.  The image will scale as necessary, and should be set for use on a white background.'  ) );
    add_settings_field( 'help_text', 'Visitor Help Text', array( $this, 'settings_field'), 'pbs_passport_authenticate', 'general_settings', array('setting' => 'pbs_passport_authenticate', 'field' => 'help_text', 'class' => 'large-text', 'label' => 'Help text to display to users below login forms.  Can include HTML for links.'  ) );
    add_settings_field( 'join_url', 'Join/Donate URL', array( $this, 'settings_field'), 'pbs_passport_authenticate', 'general_settings', array('setting' => 'pbs_passport_authenticate', 'field' => 'join_url', 'class' => 'regular-text', 'label' => 'Link to the specific donate form people should be directed to from the login screen.'  ) );
     add_settings_field( 'watch_url', 'Watch Video URL', array( $this, 'settings_field'), 'pbs_passport_authenticate', 'general_settings', array('setting' => 'pbs_passport_authenticate', 'field' => 'watch_url', 'class' => 'regular-text', 'label' => 'Link to you watch video landing page.'  ) );

    add_settings_section('pbslaas_settings', 'PBS LAAS settings', array( $this, 'settings_section_callback'), 'pbs_passport_authenticate');

    add_settings_field( 'oauth2_endpoint', 'oAuth2 Endpoint', array( $this, 'settings_field'), 'pbs_passport_authenticate', 'pbslaas_settings', array('setting' => 'pbs_passport_authenticate', 'field' => 'oauth2_endpoint', 'class' => 'regular-text', 'label' => 'Root path for PBS-provided oAuth endpoints to PBS etc. This should only change if authenticating against a dev endpoint.', 'default' => 'https://account.pbs.org/oauth2/' ) );
    add_settings_field( 'laas_client_id', 'LAAS Client ID', array( $this, 'settings_field'), 'pbs_passport_authenticate', 'pbslaas_settings', array('setting' => 'pbs_passport_authenticate', 'field' => 'laas_client_id', 'class' => 'regular-text', 'label' => 'Client ID for PIDS/LAAS.  Provided by PBS.') );
    add_settings_field( 'laas_client_secret', 'LAAS Client secret', array( $this, 'settings_field'), 'pbs_passport_authenticate', 'pbslaas_settings', array('setting' => 'pbs_passport_authenticate', 'field' => 'laas_client_secret', 'class' => 'large-text', 'label' => 'Client Secret for PIDS/LAAS.  Provided by PBS.') );

    add_settings_section('mvault_settings', 'Membership Vault settings', array( $this, 'settings_section_callback'), 'pbs_passport_authenticate');

    add_settings_field( 'mvault_endpoint', 'MVault API Endpoint', array( $this, 'settings_field'), 'pbs_passport_authenticate', 'mvault_settings', array('setting' => 'pbs_passport_authenticate', 'field' => 'mvault_endpoint', 'class' => 'regular-text', 'label' => 'Membership Vault API URL. This should only change if authenticating against a dev endpoint.', 'default' => 'https://mvault.services.pbs.org/api/' ) );
    add_settings_field( 'mvault_client_id', 'MVault API Client ID', array( $this, 'settings_field'), 'pbs_passport_authenticate', 'mvault_settings', array('setting' => 'pbs_passport_authenticate', 'field' => 'mvault_client_id', 'class' => 'regular-text', 'label' => 'MVault API Client ID. Provided by PBS.') );
    add_settings_field( 'mvault_client_secret', 'MVault API Client Secret', array( $this, 'settings_field'), 'pbs_passport_authenticate', 'mvault_settings', array('setting' => 'pbs_passport_authenticate', 'field' => 'mvault_client_secret', 'class' => 'regular-text', 'label' => 'MVault API Client Secret. Provided by PBS.') );
	}

	public function settings_section_callback() { echo ' '; }

	public function settings_field( $args ) {
    // This is the default processor that will handle standard text input fields.  Because it accepts a class, it can be styled or even have jQuery things (like a calendar picker) integrated in it.  Pass in a 'default' argument only if you want a non-empty default value.
    $settingname = esc_attr( $args['setting'] );
    $setting = get_option($settingname);
    $field = esc_attr( $args['field'] );
    $label = esc_attr( $args['label'] );
    $class = esc_attr( $args['class'] );
    $default = ($args['default'] ? esc_attr( $args['default'] ) : '' );
    $value = (($setting[$field] && strlen(trim($setting[$field]))) ? $setting[$field] : $default);
    echo '<input type="text" name="' . $settingname . '[' . $field . ']" id="' . $settingname . '[' . $field . ']" class="' . $class . '" value="' . $value . '" /><p class="description">' . $label . '</p>';
	}


	public function settings_page() {
    if (!current_user_can('manage_options')) {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }

    ?>
    <div class="wrap">
      <h2>PBS Passport Authenticate Settings</h2>
      <form action="options.php" method="POST">
        <?php settings_fields( 'pbs_passport_authenticate_group' ); ?>
        <?php do_settings_sections( 'pbs_passport_authenticate' ); ?>
        <?php submit_button(); ?>
      </form>
    </div>
    <?php
  }
}
