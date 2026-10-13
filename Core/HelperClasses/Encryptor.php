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

use SismaFramework\Core\HelperClasses\Config;

/**
 *
 * @author Valentino de Lapa
 */
class Encryptor
{

    private const MINIMAL_OPENSSL_CONFIG = <<<'CNF'
        [req]
        distinguished_name = req_distinguished_name

        [req_distinguished_name]

        [v3_ca]
        basicConstraints = critical,CA:TRUE
        keyUsage = critical,keyCertSign,cRLSign

        [v3_leaf]
        basicConstraints = critical,CA:FALSE
        keyUsage = critical,digitalSignature,keyEncipherment,dataEncipherment
        CNF;

    public const PASSWORD_KEY_DERIVATION_DEFAULT_ITERATIONS = 600000;
    public const PASSWORD_KEY_DERIVATION_MINIMUM_ITERATIONS = 100000;
    private const PASSWORD_KEY_DERIVATION_MAXIMUM_ITERATIONS = 10000000;
    private const PASSWORD_ENVELOPE_VERSION = 'sisma-pw1';
    private const PASSWORD_ENVELOPE_KEY_DERIVATION = 'pbkdf2-sha256';
    private const PASSWORD_ENVELOPE_SEPARATOR = '$';
    private const PASSWORD_ENVELOPE_CIPHER = 'aes-256-gcm';
    private const PASSWORD_ENVELOPE_KEY_BYTES = 32;
    private const PASSWORD_ENVELOPE_SALT_BYTES = 16;
    private const PASSWORD_ENVELOPE_INITIALIZATION_VECTOR_BYTES = 12;
    private const PASSWORD_ENVELOPE_TAG_BYTES = 16;

    private static ?string $minimalOpensslConfigPath = null;

    public static function getSimpleRandomToken(): string
    {
        return bin2hex(random_bytes(16));
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
            'cost' => $config->blowfishHashWorkload
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
            base64_decode($encryptedEnvelope['initializationVector'])
        );
        return $success ? $decryptedData : false;
    }

    public static function encryptWithPassword(string $plainText, string $password, int $iterations = self::PASSWORD_KEY_DERIVATION_DEFAULT_ITERATIONS): string
    {
        if ($password === '') {
            throw new \InvalidArgumentException('The password must not be empty.');
        }
        if (($iterations < self::PASSWORD_KEY_DERIVATION_MINIMUM_ITERATIONS) || ($iterations > self::PASSWORD_KEY_DERIVATION_MAXIMUM_ITERATIONS)) {
            throw new \InvalidArgumentException('The key derivation iterations must be between ' . self::PASSWORD_KEY_DERIVATION_MINIMUM_ITERATIONS . ' and ' . self::PASSWORD_KEY_DERIVATION_MAXIMUM_ITERATIONS . '.');
        }
        $salt = random_bytes(self::PASSWORD_ENVELOPE_SALT_BYTES);
        $initializationVector = random_bytes(self::PASSWORD_ENVELOPE_INITIALIZATION_VECTOR_BYTES);
        $header = implode(self::PASSWORD_ENVELOPE_SEPARATOR, [self::PASSWORD_ENVELOPE_VERSION, self::PASSWORD_ENVELOPE_KEY_DERIVATION, $iterations, base64_encode($salt)]);
        $key = hash_pbkdf2('sha256', $password, $salt, $iterations, self::PASSWORD_ENVELOPE_KEY_BYTES, true);
        $cipherText = openssl_encrypt($plainText, self::PASSWORD_ENVELOPE_CIPHER, $key, OPENSSL_RAW_DATA, $initializationVector, $tag, $header, self::PASSWORD_ENVELOPE_TAG_BYTES);
        return implode(self::PASSWORD_ENVELOPE_SEPARATOR, [$header, base64_encode($initializationVector), base64_encode($tag), base64_encode($cipherText)]);
    }

    public static function decryptWithPassword(string $envelope, string $password): string|false
    {
        $parts = explode(self::PASSWORD_ENVELOPE_SEPARATOR, $envelope);
        if ((count($parts) !== 7) || ($password === '')) {
            return false;
        }
        [$version, $keyDerivation, $iterations, $encodedSalt, $encodedInitializationVector, $encodedTag, $encodedCipherText] = $parts;
        if (($version !== self::PASSWORD_ENVELOPE_VERSION) || ($keyDerivation !== self::PASSWORD_ENVELOPE_KEY_DERIVATION) || (ctype_digit($iterations) === false)) {
            return false;
        }
        if (((int) $iterations < self::PASSWORD_KEY_DERIVATION_MINIMUM_ITERATIONS) || ((int) $iterations > self::PASSWORD_KEY_DERIVATION_MAXIMUM_ITERATIONS)) {
            return false;
        }
        $salt = base64_decode($encodedSalt, true);
        $initializationVector = base64_decode($encodedInitializationVector, true);
        $tag = base64_decode($encodedTag, true);
        $cipherText = base64_decode($encodedCipherText, true);
        if (($salt === false) || ($initializationVector === false) || ($tag === false) || ($cipherText === false)) {
            return false;
        }
        if ((strlen($initializationVector) !== self::PASSWORD_ENVELOPE_INITIALIZATION_VECTOR_BYTES) || (strlen($tag) !== self::PASSWORD_ENVELOPE_TAG_BYTES)) {
            return false;
        }
        $header = implode(self::PASSWORD_ENVELOPE_SEPARATOR, [$version, $keyDerivation, $iterations, $encodedSalt]);
        $key = hash_pbkdf2('sha256', $password, $salt, (int) $iterations, self::PASSWORD_ENVELOPE_KEY_BYTES, true);
        return openssl_decrypt($cipherText, self::PASSWORD_ENVELOPE_CIPHER, $key, OPENSSL_RAW_DATA, $initializationVector, $tag, $header);
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
        return self::getMinimalOpensslConfigPath();
    }

    private static function getMinimalOpensslConfigPath(): string
    {
        if (self::$minimalOpensslConfigPath === null) {
            $path = tempnam(sys_get_temp_dir(), 'sisma_openssl_');
            file_put_contents($path, self::MINIMAL_OPENSSL_CONFIG);
            self::$minimalOpensslConfigPath = $path;
            register_shutdown_function(static function () use ($path): void {
                if (is_file($path)) {
                    @unlink($path);
                }
            });
        }
        return self::$minimalOpensslConfigPath;
    }
}
