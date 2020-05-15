<?php

class PBS_MVault_Client {
  private $client_id;
  private $client_secret;
  private $mvault_url;
  private $station_call_letters;
  private $auth_string;

  public function __construct($client_id = '', $client_secret = '', $mvault_url ='', $station_id = ''){
    $this->client_id = $client_id;
    $this->client_secret = $client_secret;
    $this->station = $station_id; // note that call letters are a supported legacy id
    $this->mvault_url = $mvault_url . $this->station;
    $this->auth_string = $this->client_id . ":" . $this->client_secret;
    date_default_timezone_set('UTC');
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
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, $this->auth_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    if (function_exists('mvault_curl_extras')) {
      // this allows the user to specify extra curl options for this specific environment
      // usually to do with ssl cert issues
      $ch = mvault_curl_extras($ch);
    }

    return $ch;
  }


  public function get_membership($membership_id) {
    $return = array();
    $MVAULT_URL = $this->mvault_url . '/memberships/' . $membership_id . '/';
    $ch = $this->build_curl_handle($MVAULT_URL);
    $result=curl_exec($ch);
    $info = curl_getinfo($ch);
    $errors = curl_error($ch);
    curl_close ($ch);
    if (empty($result)) {
      return false;
    }
    $json = $result;
    $return = json_decode($json, true);
    return $return;
  }

  public function get_membership_by_uid($uid) {
    $return = array();
    $MVAULT_URL = $this->mvault_url . '/memberships/filter/uid/' . $uid . '/';
    $ch = $this->build_curl_handle($MVAULT_URL);
    $result=curl_exec($ch);
    $info = curl_getinfo($ch);
    $errors = curl_error($ch);
    curl_close ($ch);
    if (empty($result)) {
      return false;
    }
    $json = $result;
    $return = json_decode($json, true);
    if (isset($return[0])) {
      return $return[0]; // note: this PBS endpoint on success returns an array of arrays, unlike non-filtered endpoints
    }
    return $return;
  }

  public function get_membership_by_email($email) {
    $return = array();
    $MVAULT_URL = $this->mvault_url . '/memberships/filter/email/' . $email . '/';
    $ch = $this->build_curl_handle($MVAULT_URL);
    $result=curl_exec($ch);
    $info = curl_getinfo($ch);
    $errors = curl_error($ch);
    curl_close ($ch);
    if (empty($result)) {
      $return['errors'] = $errors;
      return $return;
    }
    $json = $result;
    $returnobj = json_decode($json, true);
    if (isset($returnobj['errors'])){
      return $returnobj;
    }
    // this PBS endpoint returns a different array of arrays than the other ones.
    // It is possible for it to return multiple memberships.
    return $returnobj['objects'];
  }



  public function activate($membership_id, $uid) {
    $return = array();
    // first check if this membership is already activated
    $membercheck = $this->get_membership($membership_id);
    if (isset($membercheck['pbs_profile']['email'])){
      $return['errors'] = 'Membership is already activated';
      return $return;
    }
    // then check if this $uid is already associated with a membership
    $uidcheck = $this->get_membership_by_uid($uid);
    if (isset($uidcheck['membership_id'])){
      $return['errors'] = 'Profile is already associated with a membership';
      return $return;
    }
    $activation = $this->patch_membershipid_or_pbsuid($membership_id, $membership_id, $uid);
    if (! isset($activation['success'])){
      return $activation;
    }
    return $this->get_membership($membership_id);
  }


  public function lookup_activation_token($token) {
    $return = array();
    $MVAULT_URL = $this->mvault_url . '/memberships/filter/token/' . $token . '/';
    $ch = $this->build_curl_handle($MVAULT_URL);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $result=curl_exec($ch);
    $info = curl_getinfo($ch);
    $errors = curl_error($ch);
    curl_close ($ch);
    if (empty($result)) {
      //return false;
      return $info;
    }
    $json = $result;
    $return = json_decode($json, true);
    return $return;
  }




  public function create_or_update_membership($membership_id, $last_name, $first_name, $start_date, $expire_date, $offer, $email = null, $provisional = null, $status = null, $additional_metadata = array() ) {
    $memberinfo = array(
      "last_name" => $last_name, 
      "first_name" => $first_name, 
      "offer" => $offer,
      "start_date" => $start_date, 
      "expire_date" => $expire_date
    );

    // these args aren't required, and if are null shouldn't be passed.  

    // empty string for an email actually will delete the email in the mvault
    if (! is_null($email)) {
      $memberinfo['email'] = $email;
    }
    
    // null is different from false for provisional
    if (! is_null($provisional)) {
      $memberinfo['provisional'] = $provisional;
    }
   
    // status can only be the strings On and Off
    if (! is_null($status)) { 
      $memberinfo['status'] = $status;
    }

    // additional_metadata can have any array in it or be a string.
    if (! empty($additional_metadata) ) {
      $memberinfo['additional_metadata'] = json_encode($additional_metadata, JSON_UNESCAPED_UNICODE);
    }


    $memberinfo_json = json_encode($memberinfo, JSON_UNESCAPED_UNICODE);

    $return = array();
    $MVAULT_URL = $this->mvault_url . '/memberships/' . $membership_id . '/';

    $ch = $this->build_curl_handle($MVAULT_URL);

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS,$memberinfo_json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($memberinfo_json)));

    $result=curl_exec($ch);
    $info = curl_getinfo($ch);
    $errors = curl_error($ch);
    curl_close ($ch);
    $return['errors'] = $errors;
    $return['request_info'] = $info;
    if (empty($result)) {
      // problem with the request of some sort -- mvault down etc
      $return['response'] = false;
      if (!$errors) {
        $return['errors'] = $result;
      }
      return $return; 
    }
    $jsonobj = json_decode($result, true);
    if (! $jsonobj) {
      // bad json 
      $return['errors'] = 'invalid json returned from mvault';
      $return['response'] = false;
      return $return;
    }
    // note -- this could still return an error array from data validation
    $return = $jsonobj;
    $return['response'] = true;
    $return['request_info'] = $info;
    return $return;
  }


  public function patch_membershipid_or_pbsuid($orig_membership_id, $new_membership_id, $pbs_uid="noupdate") {
  /*
  * This function is used to change an MVault membership_id
  * it returns the URL for the updated record
  */
    $return = array();
    if (($orig_membership_id == $new_membership_id) && $pbs_uid=="noupdate"){
      //nothing to do
      $return['errors'] = 'membership ids identical and no new uid';
      return $return;
    } 

    $MVAULT_URL = $this->mvault_url . '/memberships/' . $orig_membership_id . '/';
    $ch = curl_init();
    if (!$ch) {
      $return['errors'] = "Couldn't initialize a cURL handle";
      return $return;
    }


    $memberinfo = array();
    if ($orig_membership_id != $new_membership_id){
      $memberinfo["membership_id"] = $new_membership_id;
    } 

    if ($pbs_uid != "noupdate"){
      $memberinfo["uid"] = $pbs_uid;
    }

    $memberinfo_json = json_encode($memberinfo, JSON_UNESCAPED_UNICODE);

    $ch = $this->build_curl_handle($MVAULT_URL);

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_POSTFIELDS,$memberinfo_json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($memberinfo_json)));
    $result=curl_exec($ch);
    $errors=curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close ($ch);
    if ($info['http_code'] == 200){
      $return['url'] = $info['url'];
      $return['success'] = true;
      return $return;
    } else {
      $resultobj = json_decode($result);
      $info['membership_id'] = $orig_membership_id;
      $info['new_membership_id'] = $new_membership_id;
      if ($errors) {
        $info['errors'] = $errors;
      } elseif (isset($resultobj->errors)) {
        $info['errors'] = $resultobj->errors; 
      } else {
        $info['errors'] = $info['http_code'];
      }
      return $info;
    }
  }

  public function delete_membership($membership_id) {
  /*
  * This function is used to delete an MVault membership_id
  */
    $return = array();

    $MVAULT_URL = $this->mvault_url . '/memberships/' . $membership_id . '/';
    $ch = curl_init();
    if (!$ch) {
      $return['errors'] = "Couldn't initialize a cURL handle";
      return $return;
    }

    $ch = $this->build_curl_handle($MVAULT_URL);

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    $result=curl_exec($ch);
    $errors=curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close ($ch);
    if ($info['http_code'] == 204){
      $return['url'] = $info['url'];
      $return['success'] = true;
      return $return;
    } else {
      $resultobj = json_decode($result);
      $info['membership_id'] = $membership_id;
      if ($errors) {
        $info['errors'] = $errors;
      } elseif (isset($resultobj->errors)) {
        $info['errors'] = $resultobj->errors;
      } else {
        $info['errors'] = $info['http_code'];
      }
      return $info;
    }
  }



  public function get_recordset($recordset_url = null) {
  /*  
  * This function retrieves up to 50 records at a time from 
  * the Membership Vault, and returns those records and 
  * the URL for the next set of records 
  */

    $return = array();
    if (empty($recordset_url)){
      $recordset_url = $this->mvault_url . '/memberships/';
    }

    $ch = $this->build_curl_handle($recordset_url);

    $result=curl_exec($ch);
    $info = curl_getinfo($ch);
    $errors = curl_error($ch);
    curl_close ($ch);

    if (empty($result)) {
      $return['errors'] = $errors;
      $return['request_info'] = $info;
    } else {
      $json = $result;
      $return = json_decode($json, true);
    }
    return $return;
  }


  public function normalize_login_provider($providerstring) {
    /* PBS has used many different strings for the "login_provider"
     * returned in the pbs_profile block.   This matches those variants
     * and returns either "google", "facebook", "apple", or "pbs" */
    $providerstring = strtolower(trim($providerstring));
    $google = array('google', 'googleplus', 'google-oauth2');
    $facebook = array('facebook');
    $apple = array('apple');
    $pbs = array('pbs', 'openid');
    if (in_array($providerstring, $google)) {
      $providerstring = 'google';
    } else if (in_array($providerstring, $facebook)) {
      $providerstring = 'facebook';
    } else if (in_array($providerstring, $apple)) {
      $providerstring = 'apple';
    } else if (in_array($providerstring, $pbs)) {
      $providerstring = 'pbs';
    } else {
      $providerstring = 'unknown';
    }
    return $providerstring;
  }

}

?>
