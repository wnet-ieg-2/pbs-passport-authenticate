jQuery(document).ready(function($) {

  // Helper functions for PKCE // 

  // Generate a secure random string using the browser crypto functions
  function generateRandomString() {
    var array = new Uint32Array(28);
    window.crypto.getRandomValues(array);
    return Array.from(array, dec => ('0' + dec.toString(16)).substr(-2)).join('');
  }

  // Calculate the SHA256 hash of the input text. 
  // Returns a promise that resolves to an ArrayBuffer
  function sha256(plain) {
    const encoder = new TextEncoder();
    const data = encoder.encode(plain);
    return window.crypto.subtle.digest('SHA-256', data);
  }

  // Base64-urlencodes the input string
  function base64urlencode(str) {
    // Convert the ArrayBuffer to string using Uint8 array to convert to what btoa accepts.
    // btoa accepts chars only within ascii 0-255 and base64 encodes them.
    // Then convert the base64 encoded to base64url encoded
    //   (replace + with -, replace / with _, trim trailing =)
    return btoa(String.fromCharCode.apply(null, new Uint8Array(str)))
        .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  }

  // Return the base64-urlencoded sha256 hash for the PKCE challenge
  async function pkceChallengeFromVerifier(v) {
    hashed = await sha256(v);
    return base64urlencode(hashed);
  }

  // if pmsso exists, just add the pkce code challenge etc and redirect them
  $(".passport-login-wrap a.pmsso").each(async function() {
  	document.cookie='pbsoauth_rememberme=true;domain=' + window.location.hostname + ';path=/';
	var code_verifier = generateRandomString();
	document.cookie='pkce_code_verifier=' + code_verifier + ';domain=' + window.location.hostname + ';path=/';
	var code_challenge = await pkceChallengeFromVerifier(code_verifier);
	var encoded_code_challenge = encodeURIComponent(code_challenge);
	appended_href =  $(this).attr('href') + "&code_challenge=" + encoded_code_challenge + "&code_challenge_method=S256";
	window.location.href = appended_href;
  });

});

