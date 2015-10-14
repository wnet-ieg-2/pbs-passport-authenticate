<?php
require('classes/class-PBS-MVault-client.php');
require('classes/ConvioOpenAPI.php');
require('initvars.php');


// enable CORS, change * to a specific server soon.

header("Access-Control-Allow-Origin: *");

// tk -- require inbound SSL connection
/*
if($_SERVER["HTTPS"] != "on") { echo "SSL required"; exit(); }
*/

$trans_id = ( isset( $_REQUEST['trans_id'] ) ) ? $_REQUEST['trans_id'] : '';
$first_name = ( isset( $_REQUEST['first_name'] ) ) ? $_REQUEST['first_name'] : '';
$last_name = ( isset( $_REQUEST['last_name'] ) ) ? $_REQUEST['last_name'] : '';
$email = ( isset( $_REQUEST['email'] ) ) ? $_REQUEST['email'] : '';
$station = ( isset( $_REQUEST['station'] ) ) ? $_REQUEST['station'] : '';

// instantiate our Convio object, which we'll use globally
$convioAPI = new ConvioOpenAPI;
$convioAPI->host       = $convio_host;
$convioAPI->short_name = $convio_short_name;
$convioAPI->api_key    = $convio_api_key;
$convioAPI->login_name     = $convio_login_name;
$convioAPI->login_password = $convio_login_password;



function check_for_valid_convio_trans($trans_id, $email) {
  global $convioAPI;
  
  $convioparams = array('primary_email' => $email);
  $convioparams['transaction_type'] = 'DONATION';
  $convioresponse = $convioAPI->call('SRConsAPI_getUserTransactions', $convioparams);
  $transmatched = false;

  // only allow transactions in the last day
  $thisday = strtotime("-1 day");

  // only allow transactions of $60 or more
  $minamount = 60;
  foreach($convioresponse->getConsTransactionsResponse as $trans){
    if ($thisday < strtotime($trans->timestamp)){
      $ary = explode("-", $trans->confirmation_code);
      if ($ary[4] == $trans_id){
        if ($trans->amount->decimal >= $minamount) {
          // VALID TRANSACTION! 
          $transmatched = 'valid';
          // NOW lets see if there's a member_id available
          $convioparams = array('primary_email' => $email);
          $conviomember = $convioAPI->call('SRConsAPI_getUser', $convioparams);
          if (isset($conviomember->getConsResponse->member_id)) {
            $transmatched = $conviomember->getConsResponse->member_id;
          }
        } else {
          $transmatched = 'invalid';
        }
      }
    }
  }
  return $transmatched;
}


$returnobj = array();


if ($trans_id && $first_name && $last_name && $email) {
  // first: check trans_id + email vs luminate
  $transmatched = check_for_valid_convio_trans($trans_id, $email);

  if (! $transmatched){
    // false is not found, so wait 2 seconds and retry in case there was a delay
    sleep(2);
    $transmatched = check_for_valid_convio_trans($trans_id, $email);
  } 
  
  if (! $transmatched){ 
    // trying twice is enough
    echo json_encode(array("errors" => "no transaction found"));
    die();
  }
  if ($transmatched == 'invalid') {
    // wasn't at our threshold
    echo json_encode(array("errors" => "no valid transaction found"));
    die();
  }


  // at this point we've died if we couldn't find a valid transaction

  $account_id = 'LO_' . $trans_id;

  $MVAULT_USERNAME = $MVAULT_CREDS[$station]['MVAULT_USERNAME'];
  $MVAULT_SECRET = $MVAULT_CREDS[$station]['MVAULT_SECRET'];
  $offer = $MVAULT_CREDS[$station]['DEFAULT_OFFER'];

  $client = new PBS_MVault_Client($MVAULT_USERNAME, $MVAULT_SECRET, $MVAULT_URL, $station);
  
  // first: check if convio member id (transmatched) is a number and has a record in the mvault
  if (is_int($transmatched)) {
    $existing_account = $client->get_membership($transmatched);
  }


  if (!isset($existing_account['token'])) {
    // No existing account found by member_id. check if email address already in use in mvault
    $mvault_possibles = $client->get_membership_by_email($email);

    if ( isset($mvault_possibles[0]['token']) ){
      // found at least one record, so don't create a new one

      // it is possible that there are multiple accounts with this email. 
      if (count($mvault_possibles) === 1) {
        $existing_account = $mvault_possibles[0];
      } else {
        // multiple accounts in mvault with this email.  Do nothing but send a nice error message
        $account_id = false;
        $existing_account['errors'] = 'multiple accounts';
      }
    }
  }
  // at this point, our existing_account array either has a membership_id element, or is null because there wasn't anything to find

  if (isset($existing_account['membership_id'])) {
    $account_id = $existing_account['membership_id'];
    
    // if provisional or still has access because expiration is less than 2 months ago, null the account_id and do not update record in mvault 
    if ( isset($existing_account['provisional']) || ( strtotime($existing_account['expire_date']) > strtotime('-60 days') ) ) {
      $account_id = false;
    } else {
      $memberarray = $existing_account;
    }
  } else {
    // never found an existing account so set the memberarray values to the input values plus some defaults
    $memberarray = array(
      'membership_id' => $account_id, 
      'first_name' => $first_name, 
      'last_name' => $last_name, 
      'start_date' => date('Y-m-d') . "T" . date('H:i:s') . "Z", 
      'offer' => $offer
    );
  }

  //do the create/update if there's an account_id still set
  if ($account_id) {
    // set the expiration date to 14 days from now to reconcile with team approach no matter what
    $memberarray['expire_date'] = date('Y-m-d', strtotime('+14 days')) . "T" . date('H:i:s') . "Z";
    $jsonobj = $client->create_or_update_membership($account_id, $last_name, $first_name, $memberarray['start_date'], $memberarray['expire_date'], $offer, $email, true);
    $existing_account = json_decode($jsonobj, true);
    $returnobj['token'] = $existing_account['token'];
  }

  
  // only return the minimum necessary data
  if (isset($existing_account['errors'])) {
    $returnobj['errors'] = $existing_account['errors'];
  } elseif ( isset($existing_account['activation_date']) ) {
     $returnobj['activated'] = true;
  } elseif (isset($existing_account['token'])) {
    $returnobj['token'] = $existing_account['token'];
  }
} else {
  $returnobj['errors'] = "missing fields";
}

echo json_encode($returnobj);
die();


?>
