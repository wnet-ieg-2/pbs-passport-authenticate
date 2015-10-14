<?php
/* credit to John Conde
* http://stackoverflow.com/questions/2448256/encrypting-decrypting-file-with-mcrypt/2448441#2448441
* usage:
* $key =  CRYPTOGRAPHIC!!! key ;
* $crypt = new Encryption($key);
* $encrypted_string = $crypt->encrypt('this is a test');
* $decrypted_string = $crypt->decrypt($encrypted_string); 
*/

class Encryption
{
    const CIPHER = MCRYPT_RIJNDAEL_128; // Rijndael-128 is AES
    const MODE   = MCRYPT_MODE_CBC;

    /* Cryptographic key of length 16, 24 or 32. NOT a password! */
    private $key;
    public function __construct($key) {
        $this->key = $key;
    }

    public function encrypt($plaintext) {
        $ivSize = mcrypt_get_iv_size(self::CIPHER, self::MODE);
        $iv = mcrypt_create_iv($ivSize, MCRYPT_DEV_RANDOM);
        $ciphertext = mcrypt_encrypt(self::CIPHER, $this->key, $plaintext, self::MODE, $iv);
        return base64_encode($iv.$ciphertext);
    }

    public function decrypt($ciphertext) {
        $ciphertext = base64_decode($ciphertext);
        $ivSize = mcrypt_get_iv_size(self::CIPHER, self::MODE);
        if (strlen($ciphertext) < $ivSize) {
            throw new Exception('Missing initialization vector');
        }

        $iv = substr($ciphertext, 0, $ivSize);
        $ciphertext = substr($ciphertext, $ivSize);
        $plaintext = mcrypt_decrypt(self::CIPHER, $this->key, $ciphertext, self::MODE, $iv);
        return rtrim($plaintext, "\0");
    }
}
