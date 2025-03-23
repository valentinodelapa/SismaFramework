<?php

/*
 * The MIT License
 *
 * Copyright (c) 2020-present Valentino de Lapa.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace SismaFramework\Core\HelperClasses;

use SismaFramework\Core\BaseClasses\BaseConfig;

/**
 *
 * @author Valentino de Lapa
 */
class Encryptor
{

    public static function getSimpleRandomToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    public static function getSimpleHash(string $text, ?BaseConfig $customConfig = null): string
    {
        $config = $customConfig ?? BaseConfig::getInstance();
        return hash($config->simpleHashAlgorithm, $text);
    }

    public static function verifySimpleHash(string $text, string $hash, ?BaseConfig $customConfig = null): bool
    {
        $config = $customConfig ?? BaseConfig::getInstance();
        return hash($config->simpleHashAlgorithm, $text) === $hash;
    }

    public static function getBlowfishHash(string $text, ?BaseConfig $customConfig = null): string
    {
        $config = $customConfig ?? BaseConfig::getInstance();
        return password_hash($text, PASSWORD_BCRYPT, [
            'cost' => $config->blowfishHashWorkload
        ]);
    }

    public static function verifyBlowfishHash(string $text, string $hash): bool
    {
        return password_verify($text, $hash);
    }

    public static function createInizializationVector(?BaseConfig $customConfig = null): string
    {
        $config = $customConfig ?? BaseConfig::getInstance();
        return openssl_random_pseudo_bytes($config->initializationVectorBytes);
    }

    public static function encryptString(string $plainText, string $initializationVector, ?BaseConfig $customConfig = null): string
    {
        $config = $customConfig ?? BaseConfig::getInstance();
        return openssl_encrypt($plainText, $config->encryptionAlgorithm, $config->encryptionPassphrase, 0, $initializationVector);
    }

    public static function decryptString(string $cipherText, string $initializationVector, ?BaseConfig $customConfig = null): string|false
    {
        $config = $customConfig ?? BaseConfig::getInstance();
        return openssl_decrypt($cipherText, $config->encryptionAlgorithm, $config->encryptionPassphrase, 0, $initializationVector);
    }
}
