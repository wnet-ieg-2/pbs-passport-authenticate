<?php
/*
PBS_LAAS_Client
developed by William Tam, WNET/Thirteen

This class provides methods for working with PBS's Login As A Service (LAAS)

Create a new client:

$client = new PBS_LAAS_Client($client_id, $client_secret, $oauthroot, $redirect_uri, $tokeninfo_cookiename, $userinfo_cookiename, $cryptkey, $encrypt_iv, $encrypt_method);

arguments:
$client_id, $client_secret -- access keys from pbs.  The client_secret must be kept private.

$oauthroot -- the base URI of PBS's endpoint.  This is configurable so you can point at staging/dev URIs.

$cryptkey -- unique key for encrypt/decrypt operations for the user's info.  This key must be kept private. 

$redirect_uri -- this is the callback URI registered with PBS for this client. After an oAuth grant is made, 
PBS's endpoint will redirect to this redirect_uri with the grant code appended in a query string 
(eg http://thirteen.org/callback?code=2kl3j40k9w90djwkewwer )

$tokeninfo_cookiename, $userinfo_cookiename -- The tokeninfo cookie stores the access and refresh tokens, 
and is encrypted, but it is possible to break any encryption. Giving it some random non-obvious name provides 
some additional security through obscurity.  The userinfo cookie contains only the name and email and is not 
encrypted, but should also be given a random name.

$cryptkey, $encrypt_iv -- unique key and iv(salt) for encrypt/decrypt operations for the token info.  
They should both be very random, at least 16 chars long, and must be kept private.

$encrypt_method -- Encryption cypher for the token info.  The available methods are whatever is available to 
the openssl_encrypt function.  Default is AES-256-CBC, but there are many choices.  This must be kept private.

Public Methods:

authenticate($code, $rememberme)
Takes an oAuth grant code and use it to get access and refresh tokens from PBS 
then stores tokens and userinfo in encrypted session variables.
the second arg determines if this info is stored in encryped cookies for longer term.
returns true on success
example:
$success = $client->authenticate($code, true);
if ($success){ print 'you have authenticated'; };


check_pbs_login()
Takes no arguments.  It looks for access/refresh token info and user info in the session
and cookies, and refreshes that data from PBS's endpoints, then updates the stored data.  
Returns the userinfo in an array on success, false otherwise.
example:
$userinfo = $client->check_pbs_login();
if ($userinfo) { print 'your email is ' . $userinfo['email'];};


logout()
Takes no arguments. Clears the values of the session and cookies of both userinfo and tokeninfo.
Returns nothing.
example:
$client->logout();


prompt_for_pbs_login()
NOT YET IMPLEMENTED. Takes no arguments. It looks for the user info, finds the login provider
(google/facebook/openid), and highlights the corresponding endpoint link for the user.  
It will probably depend on browser local storage to get that value, or maybe a separate cookie. 



*/

class PBS_LAAS_Client {
  private $client_id;
  private $client_secret;
  private $oauthroot;
  private $redirect_uri;
  private $tokeninfo_cookiename;
  private $userinfo_cookiename;
  private $rememberme;
  private $checknonce;
  private $cryptkey;
  private $encrypt_method;
  private $encrypt_iv;
  private $domain;
  public $nonce;
  

  public function __construct($args){
    $this->client_id = $args['client_id'];
    $this->client_secret = $args['client_secret'];
    $this->oauthroot = $args['oauthroot'];
    $this->redirect_uri = $args['redirect_uri'];

    // cookie stuff
    $this->tokeninfo_cookiename = $args['tokeninfo_cookiename'];
    $this->userinfo_cookiename = $args['userinfo_cookiename'];
    $this->domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : false;

    // encryption stuff
    $this->cryptkey = $args['cryptkey'];
    $this->encrypt_iv = $args['encrypt_iv'];
    $this->encrypt_method = (!empty($args['encrypt_method']) ? $args['encrypt_method'] : 'AES-256-CBC');




    if( !isset( $_SESSION ) ) {
      session_start();
    }
    $this->generate_nonce();
    $this->set_rememberme_state();
    date_default_timezone_set('UTC');
  } 

  private function generate_nonce(){
    if ( isset( $_SESSION ) ) {
      $this->nonce = $_SESSION;
      // encryption 
    }
  }

  private function set_rememberme_state(){
    // this function checks for the previous existence 
    // of the tokeninfo cookie and sets rememberme=true if exists
    if (isset($_COOKIE[$this->tokeninfo_cookiename]) ){
      $this->rememberme = true;
    }
  }

  private function build_curl_handle($url) {
    if (!function_exists('curl_init')){
      die('the curl library is required for this client to work');
    }
    $ch = curl_init();
    if (!$ch) {
      die('could not initialize curl');
    }
    curl_setopt($ch, CURLOPT_URL,$url);

    // method and headers can be different, but these are always the same
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    if (function_exists('mvault_curl_extras')) {
      // this allows the user to specify extra curl options for this specific environment
      // usually to do with ssl cert issues
      $ch = mvault_curl_extras($ch);
    }

    return $ch;
  }



  public function authenticate($code= '', $rememberme='', $nonce=''){

    $this->checknonce = $nonce;

    $this->rememberme = $rememberme;

    $tokeninfo = $this->get_code_response($code);
    if (! isset($tokeninfo["access_token"]) ) {
      $tokeninfo['messages'] = 'broke on code response';
      return $tokeninfo;
    }
    
    $tokeninfo = $this->update_pbs_tokeninfo($tokeninfo);
     
    $access_token = $tokeninfo['access_token'];
    if (! isset($tokeninfo["access_token"]) ) {
      return $tokeninfo;
    }

    $this->save_encrypted_tokeninfo($tokeninfo);

    $userinfo = $this->get_latest_pbs_userinfo($access_token);
    if (! isset($userinfo["pid"])){
      $tokeninfo['messages'] = 'broke on getting userinfo';
      $tokeninfo['userinfodata'] = $userinfo;
      return $tokeninfo;
    } 
    $this->store_pbs_userinfo($userinfo);
    
    return $userinfo;

  }

  public function code_exchange($code= ''){
    $tokeninfo = $this->get_code_response($code);
    if (! isset($tokeninfo["access_token"]) ) {
      $tokeninfo['messages'] = 'broke on code exchange';
      return $tokeninfo;
    }
    $tokeninfo = $this->update_pbs_tokeninfo($tokeninfo);
    return $tokeninfo;
  }


  public function check_pbs_login() {

    $userinfo = $this->retrieve_pbs_userinfo();

    // if the user has logged out, or the session or cookie has expired
    if (! $userinfo) {
      // use access tokens to get the most recent info

      $tokeninfo = $this->retrieve_encrypted_tokeninfo();
      if (! $tokeninfo) {
        // they're not logged in
        return false;
      }
      $tokeninfo = $this->update_pbs_tokeninfo($tokeninfo);

      $access_token = $tokeninfo['access_token'];

      if (! $access_token) {
        // they're not logged in
        return false;
      }
      $this->save_encrypted_tokeninfo($tokeninfo);

      $userinfo = $this->get_latest_pbs_userinfo($access_token);
      if (! isset($userinfo["pid"])){
        $tokeninfo['userinfodata'] = $userinfo;
        return $tokeninfo;
      }


      $this->store_pbs_userinfo($userinfo);

    }
    // can be false at this point, which is fine
    return $userinfo;

  }

  public function validate_and_append_userinfo($userinfo) {
    /*
    * This function takes the userinfo array given by PBS
    * compares it to the same data passed into the function
    * and then appends any additional fields, such as mvault
    * stuff, and saves it into the userinfo session/cookie
    */ 
    // are we logged into PBS? are the session/cookie still valid? 
    $current_userinfo = $this->check_pbs_login();
  
    if (! $current_userinfo) {
      return false;
    } 
    // compare the PBS provided fields with the corresponding fields passed
    foreach ($userinfo as $key => $value) {
      if (isset ($current_userinfo[$key]) && $current_userinfo[$key] != $value) {
        return false;
      }
    }
    return $this->store_pbs_userinfo($userinfo); 
  }


  public function prompt_for_pbs_login() {

    $userinfo = $this->retrieve_pbs_userinfo();

    // if we can get the the login provider, use it to prompt a specific provider

  }

  public function logout() {
     unset($_SESSION[$this->userinfo_cookiename]);
     setcookie($this->userinfo_cookiename, NULL, -1, "/", $this->domain, false, true);
     unset($_SESSION[$this->tokeninfo_cookiename]);
     setcookie($this->tokeninfo_cookiename, NULL, -1, "/", $this->domain, false, true);
  }


  private function get_code_response($code=''){
    $url = $this->oauthroot . 'token/';
    $postfields = array(
      'code' => $code,
      'redirect_uri' => $this->redirect_uri,
      'client_id' => $this->client_id,
      'client_secret' => $this->client_secret,
      'grant_type' => 'authorization_code'
    );
    $ch = $this->build_curl_handle($url);
    //construct the curl request

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    $response_json = curl_exec($ch);
    $info = curl_getinfo($ch);
    $errors = curl_error($ch);
    curl_close($ch);
    $code_response = json_decode($response_json, true);
    if (isset($code_response["access_token"])){
      return $code_response;
    } else { 
      $return = array();
      $return['response'] = $code_response;
      $return['curlerrors'] = $errors;
      $return['curlinfo'] = $info;
      $return['rawjson'] = $response_json;
      return $return;
    }
  }



  public function validate_pbs_access_token($access_token = ''){
    // this function hits the tokeninfo endpoint and checks its validity
    $url = $this->oauthroot . 'token-info/?access_token=' . $access_token;
    //construct the curl request
    $ch = $this->build_curl_handle($url);
    $response_json = curl_exec($ch);
    $info = curl_getinfo($ch);
    $errors = curl_error($ch);
    $response = json_decode($response_json, true);
    if (isset($response['access_token'])) {
      return $response;
    } else {
      $response['rawjson'] = $response_json;
      $response['curlerrors'] = $errors;
      $response['curlinfo'] = $info;
      return $response;
    }
  }

  public function generate_pbs_access_token_from_refresh_token($refresh_token =''){
    $url = $this->oauthroot . 'token/';
    $postfields = array(
      'refresh_token' => $refresh_token,
      'client_id' => $this->client_id,
      'client_secret' => $this->client_secret,
      'grant_type' => 'refresh_token'
    );
    //construct the curl request
    $ch = $this->build_curl_handle($url);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    $response_json = curl_exec($ch);
    $info = curl_getinfo($ch);
    $errors = curl_error($ch);
    curl_close($ch);
    $code_response = json_decode($response_json, true);
    if (isset($code_response["access_token"])){
      // calculate the expiration date and add to array
      if (isset($code_response['expires_in']) ){
        $code_response['expires_timestamp'] = strtotime("+" . $code_response['expires_in'] . " seconds");
      }
      return $code_response;
    } else {
      $code_response['curlerrors'] = $errors;
      $code_response['curlinfo'] = $info;
      return $code_response;
    }
  }

  private function encrypt($plaintext=''){
    if (! function_exists('openssl_encrypt')){
      return false;
    }
    $key = hash('sha256', $this->cryptkey);
    $iv = substr(hash('sha256', $this->encrypt_iv), 0, 16);
    $output = openssl_encrypt($plaintext, $this->encrypt_method, $key, 0, $iv);
    $output = base64_encode($output);
    return $output;
  }

  private function decrypt($cyphertext=''){
    if (! function_exists('openssl_encrypt')){
      return false;
    }
    $key = hash('sha256', $this->cryptkey);
    $iv = substr(hash('sha256', $this->encrypt_iv), 0, 16);
    $output = openssl_decrypt(base64_decode($cyphertext), $this->encrypt_method, $key, 0, $iv);
    return $output;
  }


  private function retrieve_encrypted_tokeninfo() {

    // check for encrypted tokeninfo in session or cookie
    if (isset( $_SESSION ) && isset($_SESSION[$this->tokeninfo_cookiename])) {
      $tokeninfo = $_SESSION[$this->tokeninfo_cookiename];
    } elseif (isset($_COOKIE[$this->tokeninfo_cookiename])){
       $tokeninfo = $_COOKIE[$this->tokeninfo_cookiename];
    }
  
    // decrypt encrypted tokeninfo
    $decrypted = $this->decrypt($tokeninfo);
    if ($decrypted) {
      $tokeninfo = $decrypted;
    } 
 
    $tokeninfo = json_decode($tokeninfo, true);
    return $tokeninfo;
  }



  private function save_encrypted_tokeninfo($tokeninfo) {

    $tokeninfo = json_encode($tokeninfo);

    // encrypt tokeninfo
    $encrypted = $this->encrypt($tokeninfo);
    if ($encrypted){
      // THIS IS A BAD INSECURE TEMPORARY HACK.  FIND A BETTER WAY TO FAIL AND NOTIFY THAT WE HAVE NO ENCRYPTION
      $tokeninfo = $encrypted;
    }

    // save encrypted tokeninfo in session

    if (isset( $_SESSION )) {
      $_SESSION[$this->tokeninfo_cookiename] = $tokeninfo;
    }
    if ($this->rememberme) {
      // save encrypted tokeninfo in cookie if it exists or the user checked the remember me box
      return setcookie($this->tokeninfo_cookiename, $tokeninfo, strtotime("+1 year"), "/", $this->domain, false, true);
    } else {
      // remember me is only false at this point if the checkbox is user-cleared, so clear out that cookie if it was previously set
      if (isset($_COOKIE[$this->tokeninfo_cookiename])) { 
        setcookie($this->tokeninfo_cookiename, NULL, -1, "/", $this->domain, false, true); 
      }
      return false;
    }
  }

  private function update_pbs_tokeninfo($tokeninfo) {
    // We get a new access token if the current token has less than 5% of its life left

    // default lifespan of token is 10 hours, so 30 minutes.
    $token_expire_window = strtotime("+30 minute");
    if ( isset( $tokeninfo['expires_in'] ) && ( $tokeninfo['expires_in'] < 36000 ) ){
      // in case PBS decides to give the access token a shorter lifespan, change the expire window to 5% of that. 
      // so a 1 hr lifespan has a 3 minute grace period
      $seconds = round($tokeninfo['expires_in'] / 20);
      $token_expire_window = strtotime("+" . $seconds . " seconds");
    }

    // is the access token expired or going to expire soon?
    if ( isset( $tokeninfo['expires_timestamp'] ) && ( $tokeninfo['expires_timestamp'] < $token_expire_window ) ){


      //  use the refresh token to get a new access token
      $newtokeninfo = $this->generate_pbs_access_token_from_refresh_token($tokeninfo['refresh_token']);
      if (! isset($newtokeninfo['refresh_token'])) {
        $newtokeninfo['messages'] = 'broke on generateing an access token from a refresh token';
        return $newtokeninfo;
      }

      $tokeninfo = $newtokeninfo;

    }

    // validate the access token on general priniciple
    /*
    $validate = $this->validate_pbs_access_token($tokeninfo['access_token']);
    if (! isset($validate['access_token'])){
      return $validate;
    }
    */
    // calculate the expiration date and add to tokeninfo array if not previously set
    if (! isset($tokeninfo['expires_timestamp']) ){
      $tokeninfo['expires_timestamp'] = strtotime("+" . $tokeninfo['expires_in'] . " seconds");
    }

    // return the tokeninfo
    return $tokeninfo;
  }




  private function retrieve_pbs_userinfo() {
    // check for profile info in session or cookie
    if (isset($_SESSION) && isset($_SESSION[$this->userinfo_cookiename])){
      $userinfo_json = $_SESSION[$this->userinfo_cookiename];
    } elseif (isset($_COOKIE[$this->userinfo_cookiename])) {
      $userinfo_json = $_COOKIE[$this->userinfo_cookiename];
    }
    $userinfo = base64_decode(json_decode($userinfo_json));
    if (isset($userinfo->email)){
      return $userinfo;
    } else {
      return false;
    }
  }

  public function get_latest_pbs_userinfo($access_token = '') {

    // get the profile info from PBS
    $url = $this->oauthroot . 'user/info/';
    $customheaders = array('Authorization: Bearer ' . $access_token);
    $ch = $this->build_curl_handle($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $customheaders);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    $userinfo_json = curl_exec($ch);
    $info = curl_getinfo($ch);
    $errors = curl_error($ch);
    curl_close($ch);
    $userinfo = json_decode($userinfo_json, true);
    if (isset($userinfo['email'])){
      //for debugging ONLY
      return $userinfo;
    } else {
      $userinfo['curlinfo'] = $info;
      $userinfo['curlerrors'] = $errors;
      return $userinfo;
    }
  }

  private function store_pbs_userinfo($userinfo) {
    $userinfo_json = json_encode($userinfo);
    $userinfo_json_64=base64_encode($userinfo_json);
    if (isset($userinfo['email'])){
      // store the profile info in the session
      if (isset( $_SESSION )) {
        $_SESSION[$this->userinfo_cookiename] = $userinfo_json_64;
      }

      // optionally store profile info in a cookie
      if ($this->rememberme) {
        setcookie($this->userinfo_cookiename, $userinfo_json_64, strtotime("+1 hour"), "/", $this->domain, false, false);
      } else {
        setcookie($this->userinfo_cookiename, NULL, -1, "/", $this->domain, false, false);
      }
      // return the profile info if there was any
      return true;
    } else {
      //no email in userinfo means no data from userinfo, so we're not authenticated
      unset($_SESSION[$this->userinfo_cookiename]);
      setcookie($this->userinfo_cookiename, NULL, -1, "/", $this->domain, false, false);
      return false;
    }
  }

}
