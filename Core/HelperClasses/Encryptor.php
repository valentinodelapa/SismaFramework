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

/**
 *
 * @author Valentino de Lapa
 */
class Encryptor
{

    public static function getSimpleRandomToken(?Config $customConfig = null): string
    {
        $config = $customConfig ?? Config::getInstance();
        return bin2hex(random_bytes($config->simpleRandomTokenBytes));
    }

    public static function getSimpleHash(string $text, ?Config $customConfig = null): string
    {
        $config = $customConfig ?? Config::getInstance();
        return hash($config->simpleHashAlgorithm, $text);
    }

    public static function verifySimpleHash(string $text, string $hash, ?Config $customConfig = null): bool
    {
        $config = $customConfig ?? Config::getInstance();
        return hash($config->simpleHashAlgorithm, $text) === $hash;
    }

    public static function getBlowfishHash(string $text, ?Config $customConfig = null): string
    {
        $config = $customConfig ?? Config::getInstance();
        return password_hash($text, PASSWORD_BCRYPT, [
            'cost' => $config->blowfishHashWorkload,
        ]);
    }

    public static function verifyBlowfishHash(string $text, string $hash): bool
    {
        return password_verify($text, $hash);
    }

    public static function createInitializationVector(?Config $customConfig = null): string
    {
        $config = $customConfig ?? Config::getInstance();
        return openssl_random_pseudo_bytes($config->initializationVectorBytes);
    }

    public static function encryptString(string $plainText, string $initializationVector, ?Config $customConfig = null): string
    {
        $config = $customConfig ?? Config::getInstance();
        return openssl_encrypt($plainText, $config->encryptionAlgorithm, $config->encryptionPassphrase, 0, $initializationVector);
    }

    public static function decryptString(string $cipherText, string $initializationVector, ?Config $customConfig = null): string|false
    {
        $config = $customConfig ?? Config::getInstance();
        return openssl_decrypt($cipherText, $config->encryptionAlgorithm, $config->encryptionPassphrase, 0, $initializationVector);
    }

    public static function generateAsymmetricKeyPair(?Config $customConfig = null): array
    {
        $config = $customConfig ?? Config::getInstance();
        $options = self::buildOpensslOptions([
            'private_key_bits' => $config->asymmetricKeyBits,
            'private_key_type' => $config->asymmetricKeyType,
        ], $config);
        $resource = openssl_pkey_new($options);
        openssl_pkey_export($resource, $privateKey, null, $options);
        $details = openssl_pkey_get_details($resource);
        return [
            'privateKey' => $privateKey,
            'publicKey' => $details['key'],
        ];
    }

    public static function generateCertificateSigningRequest(string $privateKeyPem, array $distinguishedName, ?Config $customConfig = null): string
    {
        $config = $customConfig ?? Config::getInstance();
        $certificateSigningRequestResource = self::createCertificateSigningRequestResource($privateKeyPem, $distinguishedName, $config);
        openssl_csr_export($certificateSigningRequestResource, $certificateSigningRequestPem);
        return $certificateSigningRequestPem;
    }

    public static function generateSelfSignedCertificate(string $privateKeyPem, array $distinguishedName, bool $certificationAuthority = true, ?Config $customConfig = null): string
    {
        $config = $customConfig ?? Config::getInstance();
        $certificateSigningRequestResource = self::createCertificateSigningRequestResource($privateKeyPem, $distinguishedName, $config);
        $options = self::buildOpensslOptions([
            'digest_alg' => $config->asymmetricDigestAlgorithm,
            'x509_extensions' => $certificationAuthority ? 'v3_ca' : 'v3_leaf',
        ], $config);
        $privateKeyResource = openssl_pkey_get_private($privateKeyPem);
        $certificateResource = openssl_csr_sign($certificateSigningRequestResource, null, $privateKeyResource, $config->certificateValidityDays, $options);
        openssl_x509_export($certificateResource, $certificatePem);
        return $certificatePem;
    }

    public static function signCertificateSigningRequest(string $certificateSigningRequestPem, string $issuerCertificatePem, string $issuerPrivateKeyPem, bool $certificationAuthority = false, ?Config $customConfig = null): string
    {
        $config = $customConfig ?? Config::getInstance();
        $options = self::buildOpensslOptions([
            'digest_alg' => $config->asymmetricDigestAlgorithm,
            'x509_extensions' => $certificationAuthority ? 'v3_ca' : 'v3_leaf',
        ], $config);
        $issuerPrivateKeyResource = openssl_pkey_get_private($issuerPrivateKeyPem);
        $certificateResource = openssl_csr_sign($certificateSigningRequestPem, $issuerCertificatePem, $issuerPrivateKeyResource, $config->certificateValidityDays, $options);
        openssl_x509_export($certificateResource, $certificatePem);
        return $certificatePem;
    }

    public static function signData(string $data, string $privateKeyPem, ?Config $customConfig = null): string
    {
        $config = $customConfig ?? Config::getInstance();
        $privateKeyResource = openssl_pkey_get_private($privateKeyPem);
        openssl_sign($data, $signature, $privateKeyResource, $config->asymmetricDigestAlgorithm);
        return base64_encode($signature);
    }

    public static function verifySignature(string $data, string $base64Signature, string $certificateOrPublicKeyPem, ?Config $customConfig = null): bool
    {
        $config = $customConfig ?? Config::getInstance();
        $publicKeyResource = openssl_pkey_get_public($certificateOrPublicKeyPem);
        return openssl_verify($data, base64_decode($base64Signature), $publicKeyResource, $config->asymmetricDigestAlgorithm) === 1;
    }

    public static function verifyCertificateSignedByIssuer(string $certificatePem, string $issuerCertificateOrPublicKeyPem): bool
    {
        $certificateResource = openssl_x509_read($certificatePem);
        $issuerPublicKeyResource = openssl_pkey_get_public($issuerCertificateOrPublicKeyPem);
        return openssl_x509_verify($certificateResource, $issuerPublicKeyResource) === 1;
    }

    public static function encryptWithPublicKey(string $data, string $certificateOrPublicKeyPem, ?Config $customConfig = null): array
    {
        $config = $customConfig ?? Config::getInstance();
        $publicKeyResource = openssl_pkey_get_public($certificateOrPublicKeyPem);
        openssl_seal($data, $sealedData, $envelopeKeys, [$publicKeyResource], $config->encryptionAlgorithm, $initializationVector);
        return [
            'data' => base64_encode($sealedData),
            'envelopeKey' => base64_encode($envelopeKeys[0]),
            'initializationVector' => base64_encode($initializationVector),
        ];
    }

    public static function decryptWithPrivateKey(array $encryptedEnvelope, string $privateKeyPem, ?Config $customConfig = null): string|false
    {
        $config = $customConfig ?? Config::getInstance();
        $privateKeyResource = openssl_pkey_get_private($privateKeyPem);
        $success = openssl_open(
            base64_decode($encryptedEnvelope['data']),
            $decryptedData,
            base64_decode($encryptedEnvelope['envelopeKey']),
            $privateKeyResource,
            $config->encryptionAlgorithm,
            base64_decode($encryptedEnvelope['initializationVector']),
        );
        return $success ? $decryptedData : false;
    }

    public static function encryptWithPassword(string $plainText, string $password, ?int $iterations = null, ?Config $customConfig = null): string
    {
        $config = $customConfig ?? Config::getInstance();
        $iterations ??= $config->passwordKeyDerivationDefaultIterations;
        self::validatePasswordEncryptionParameters($password, $iterations, $config);
        $cipher = $config->passwordEncryptionAlgorithm;
        $hashAlgorithm = $config->passwordKeyDerivationHashAlgorithm;
        $salt = random_bytes($config->passwordKeyDerivationSaltBytes);
        $initializationVector = random_bytes($config->passwordEncryptionInitializationVectorBytes);
        $header = implode($config->passwordEnvelopeSeparator, [$config->passwordEnvelopeVersion, $cipher, $config->passwordEnvelopeKeyDerivationPrefix . $hashAlgorithm, $iterations, base64_encode($salt)]);
        $key = self::derivePasswordKey($password, $hashAlgorithm, $salt, $iterations, $cipher);
        $cipherText = openssl_encrypt($plainText, $cipher, $key, OPENSSL_RAW_DATA, $initializationVector, $tag, $header, $config->passwordEncryptionTagBytes);
        return implode($config->passwordEnvelopeSeparator, [$header, base64_encode($initializationVector), base64_encode($tag), base64_encode($cipherText)]);
    }

    private static function validatePasswordEncryptionParameters(string $password, int $iterations, Config $config): void
    {
        if ($password === '') {
            throw new \InvalidArgumentException('The password must not be empty.');
        }
        if (self::areIterationsAllowed($iterations, $config) === false) {
            throw new \InvalidArgumentException('The key derivation iterations must be between ' . $config->passwordKeyDerivationMinimumIterations . ' and ' . $config->passwordKeyDerivationMaximumIterations . '.');
        }
        if (self::isSupportedHashAlgorithm($config->passwordKeyDerivationHashAlgorithm) === false) {
            throw new \InvalidArgumentException('The key derivation hash algorithm ' . $config->passwordKeyDerivationHashAlgorithm . ' is not supported.');
        }
        if (self::isSupportedAuthenticatedCipher($config->passwordEncryptionAlgorithm) === false) {
            throw new \InvalidArgumentException('The password encryption algorithm ' . $config->passwordEncryptionAlgorithm . ' is not an authenticated cipher supported by OpenSSL.');
        }
        if ($config->passwordEncryptionTagBytes < $config->passwordEncryptionMinimumTagBytes) {
            throw new \InvalidArgumentException('The authentication tag must be at least ' . $config->passwordEncryptionMinimumTagBytes . ' bytes long.');
        }
    }

    public static function decryptWithPassword(string $envelope, string $password, ?Config $customConfig = null): string|false
    {
        $config = $customConfig ?? Config::getInstance();
        $fields = self::parsePasswordEnvelope($envelope, $config);
        if (($password === '') || ($fields === null) || (self::arePasswordEnvelopeFieldsAllowed($fields, $config) === false)) {
            return false;
        }
        $key = self::derivePasswordKey($password, $fields['hashAlgorithm'], $fields['salt'], $fields['iterations'], $fields['cipher']);
        return openssl_decrypt($fields['cipherText'], $fields['cipher'], $key, OPENSSL_RAW_DATA, $fields['initializationVector'], $fields['tag'], $fields['header']);
    }

    private static function parsePasswordEnvelope(string $envelope, Config $config): ?array
    {
        $parts = explode($config->passwordEnvelopeSeparator, $envelope);
        if ((count($parts) !== 8) || ($parts[0] !== $config->passwordEnvelopeVersion)) {
            return null;
        }
        [, $cipher, $keyDerivation, $iterations, $encodedSalt, $encodedInitializationVector, $encodedTag, $encodedCipherText] = $parts;
        $salt = base64_decode($encodedSalt, true);
        $initializationVector = base64_decode($encodedInitializationVector, true);
        $tag = base64_decode($encodedTag, true);
        $cipherText = base64_decode($encodedCipherText, true);
        $isKeyDerivationWellFormed = str_starts_with($keyDerivation, $config->passwordEnvelopeKeyDerivationPrefix) && ctype_digit($iterations);
        if (($isKeyDerivationWellFormed === false) || ($salt === false) || ($initializationVector === false) || ($tag === false) || ($cipherText === false)) {
            return null;
        }
        return [
            'cipher' => $cipher,
            'hashAlgorithm' => substr($keyDerivation, strlen($config->passwordEnvelopeKeyDerivationPrefix)),
            'iterations' => (int) $iterations,
            'salt' => $salt,
            'initializationVector' => $initializationVector,
            'tag' => $tag,
            'cipherText' => $cipherText,
            'header' => implode($config->passwordEnvelopeSeparator, array_slice($parts, 0, 5)),
        ];
    }

    private static function arePasswordEnvelopeFieldsAllowed(array $fields, Config $config): bool
    {
        return self::areIterationsAllowed($fields['iterations'], $config) &&
                self::isSupportedHashAlgorithm($fields['hashAlgorithm']) &&
                self::isSupportedAuthenticatedCipher($fields['cipher']) &&
                ($fields['initializationVector'] !== '') &&
                (strlen($fields['tag']) >= $config->passwordEncryptionMinimumTagBytes);
    }

    private static function areIterationsAllowed(int $iterations, Config $config): bool
    {
        return ($iterations >= $config->passwordKeyDerivationMinimumIterations) && ($iterations <= $config->passwordKeyDerivationMaximumIterations);
    }

    private static function isSupportedHashAlgorithm(string $hashAlgorithm): bool
    {
        return in_array($hashAlgorithm, hash_hmac_algos(), true);
    }

    private static function isSupportedAuthenticatedCipher(string $cipher): bool
    {
        $isAuthenticatedMode = preg_match('/^(aes-(128|192|256)-(gcm|ccm|ocb)|chacha20-poly1305)$/', $cipher) === 1;
        return $isAuthenticatedMode && in_array($cipher, openssl_get_cipher_methods(), true);
    }

    private static function derivePasswordKey(string $password, string $hashAlgorithm, string $salt, int $iterations, string $cipher): string
    {
        return hash_pbkdf2($hashAlgorithm, $password, $salt, $iterations, openssl_cipher_key_length($cipher), true);
    }

    private static function createCertificateSigningRequestResource(string $privateKeyPem, array $distinguishedName, Config $config): \OpenSSLCertificateSigningRequest
    {
        $options = self::buildOpensslOptions(['digest_alg' => $config->asymmetricDigestAlgorithm], $config);
        $privateKeyResource = openssl_pkey_get_private($privateKeyPem);
        return openssl_csr_new($distinguishedName, $privateKeyResource, $options);
    }

    private static function buildOpensslOptions(array $options, Config $config): array
    {
        $options['config'] = self::resolveOpensslConfigPath($config);
        return $options;
    }

    private static function resolveOpensslConfigPath(Config $config): string
    {
        if (empty($config->opensslConfigPath) === false) {
            return $config->opensslConfigPath;
        }
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'openssl.cnf';
    }
}
