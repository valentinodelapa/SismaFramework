<?php

namespace Sisma\Core\HelperClasses;

class Encryptor
{

    public static function getBlowfishHash(string $text, string $workload): string
    {
        $salt = substr(str_replace('+', '.', base64_encode(openssl_random_pseudo_bytes(16))), 0, 22);
        $result = crypt($text, '$2a$' . $workload . '$' . $salt);
        return $result;
    }

    public static function verifyBlowfishHash(string $text, string $hash): bool
    {
        return ($hash == crypt($text, $hash));
    }

}
