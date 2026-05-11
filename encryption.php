<?php
// encryption.php - AES-256 encryption/decryption class

class Encryption {
    private static $algorithm = 'aes-256-cbc';
    
    // Encrypt data
    public static function encrypt($data, $masterKey) {
        $iv = random_bytes(openssl_cipher_iv_length(self::$algorithm));
        $encrypted = openssl_encrypt($data, self::$algorithm, hash('sha256', $masterKey), 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    // Decrypt data
    public static function decrypt($encryptedData, $masterKey) {
        $data = base64_decode($encryptedData);
        $ivLength = openssl_cipher_iv_length(self::$algorithm);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        return openssl_decrypt($encrypted, self::$algorithm, hash('sha256', $masterKey), 0, $iv);
    }
    
    // Generate secure master key hash
    public static function hashMasterKey($masterKey) {
        return password_hash($masterKey, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    // Verify master key
    public static function verifyMasterKey($masterKey, $hash) {
        return password_verify($masterKey, $hash);
    }
}
?>