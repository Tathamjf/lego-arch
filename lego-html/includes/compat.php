<?php
// =====================================================================
// Compatibilidade com PHP antigo (5.3+): cria as funções que faltam.
// Em PHP 5.5/5.6 ou mais novo, nada disso é usado (as nativas valem).
// =====================================================================
if (!defined('PASSWORD_DEFAULT')) { define('PASSWORD_DEFAULT', 1); }
if (!defined('PASSWORD_BCRYPT'))  { define('PASSWORD_BCRYPT', 1); }

if (!function_exists('password_hash')) {
    function password_hash($password, $algo = 1, $options = array()) {
        $cost = isset($options['cost']) ? (int)$options['cost'] : 10;
        if (function_exists('openssl_random_pseudo_bytes')) {
            $raw = openssl_random_pseudo_bytes(16);
        } else {
            $raw = '';
            for ($i = 0; $i < 16; $i++) { $raw .= chr(mt_rand(0, 255)); }
        }
        $salt   = substr(strtr(base64_encode($raw), '+', '.'), 0, 22);
        $prefix = (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 50307) ? '$2y$' : '$2a$';
        $hash   = crypt($password, $prefix . sprintf('%02d', $cost) . '$' . $salt);
        return (is_string($hash) && strlen($hash) === 60) ? $hash : false;
    }
}

if (!function_exists('hash_equals')) {
    function hash_equals($a, $b) {
        $a = (string)$a; $b = (string)$b;
        if (strlen($a) !== strlen($b)) { return false; }
        $diff = 0;
        for ($i = 0; $i < strlen($a); $i++) { $diff |= ord($a[$i]) ^ ord($b[$i]); }
        return $diff === 0;
    }
}

if (!function_exists('password_verify')) {
    function password_verify($password, $hash) {
        $r = crypt($password, $hash);
        if (!is_string($r) || strlen($r) < 60) { return false; }
        return hash_equals($hash, $r);
    }
}

if (!function_exists('http_response_code')) {
    function http_response_code($code = null) {
        if ($code) { header('X-PHP-Response-Code: ' . $code, true, $code); }
        return $code;
    }
}

// Sem a extensão mbstring: versões simples (suficientes para o projeto)
if (!function_exists('mb_strlen')) {
    function mb_strlen($s, $enc = null) { return strlen(utf8_decode($s)); }
}
if (!function_exists('mb_substr')) {
    function mb_substr($s, $start, $len = null, $enc = null) {
        $a = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
        return implode('', $len === null ? array_slice($a, $start) : array_slice($a, $start, $len));
    }
}
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper($s, $enc = null) { return strtoupper($s); }
}
