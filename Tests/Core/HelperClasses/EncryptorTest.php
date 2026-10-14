<?php

/*
 * The MIT License
 *
 * Copyright 2023 Valentino de Lapa.
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

namespace SismaFramework\Tests\Core\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\Encryptor;

/**
 * @author Valentino de Lapa
 */
class EncryptorTest extends TestCase
{

    private const DEFAULT_ITERATIONS = 600000;
    private const MINIMUM_ITERATIONS = 100000;
    private const RELEASED_VERSION_1_ENVELOPE = 'sisma-pw1$pbkdf2-sha256$100000$kAXNr/YDypI9oZ5tTeAoPw==$LbriCvVW3XRPbOAv$mcovN2nCI50GpKIQWiJxhQ==$WZH7Ck/TK2v5UGUlfGk=';

    private Config $configStub;

    public function setUp(): void
    {
        $this->configStub = $this->createConfigStub();
    }

    private function createConfigStub(array $customValues = []): Config
    {
        $values = array_merge([
            'simpleRandomTokenBytes' => 16,
            'blowfishHashWorkload' => 12,
            'encryptionAlgorithm' => 'AES-256-CBC',
            'encryptionPassphrase' => '',
            'initializationVectorBytes' => 16,
            'simpleHashAlgorithm' => 'sha256',
            'asymmetricKeyType' => OPENSSL_KEYTYPE_RSA,
            'asymmetricKeyBits' => 2048,
            'asymmetricDigestAlgorithm' => 'sha256',
            'certificateValidityDays' => 3650,
            'opensslConfigPath' => getenv('OPENSSL_CONFIG_PATH') ?: '',
            'passwordKeyDerivationDefaultIterations' => self::DEFAULT_ITERATIONS,
            'passwordKeyDerivationMinimumIterations' => self::MINIMUM_ITERATIONS,
            'passwordKeyDerivationMaximumIterations' => 10000000,
            'passwordKeyDerivationHashAlgorithm' => 'sha256',
            'passwordKeyDerivationSaltBytes' => 16,
            'passwordEncryptionAlgorithm' => 'aes-256-gcm',
            'passwordEncryptionInitializationVectorBytes' => 12,
            'passwordEncryptionTagBytes' => 16,
            'passwordEncryptionMinimumTagBytes' => 12,
            'passwordEnvelopeVersion' => 'sisma-pw2',
            'passwordEnvelopeSeparator' => '$',
            'passwordEnvelopeKeyDerivationPrefix' => 'pbkdf2-',
                ], $customValues);
        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')
                ->willReturnCallback(fn(string $name) => $values[$name]);
        $configStub->method('__isset')
                ->willReturn(true);
        return $configStub;
    }

    public function testGetSimpleRandomToken()
    {
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', Encryptor::getSimpleRandomToken($this->configStub));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', Encryptor::getSimpleRandomToken($this->createConfigStub(['simpleRandomTokenBytes' => 32])));
    }

    public function testGetVerifySimpleHash()
    {
        $testString = 'sample';
        $fakeTestString = 'fakeSample';
        $testStringHash = Encryptor::getSimpleHash($testString, $this->configStub);
        $this->assertTrue(Encryptor::verifySimpleHash($testString, $testStringHash, $this->configStub));
        $this->assertFalse(Encryptor::verifySimpleHash($fakeTestString, $testStringHash, $this->configStub));
    }

    public function testGetVerifyBlowfishHash()
    {
        $testString = 'sample';
        $fakeTestString = 'fakeSample';
        $testStringHash = Encryptor::getBlowfishHash($testString, $this->configStub);
        $this->assertTrue(Encryptor::verifyBlowfishHash($testString, $testStringHash));
        $this->assertFalse(Encryptor::verifyBlowfishHash($fakeTestString, $testStringHash));
    }

    public function testEncryptDecryptString()
    {
        $testString = 'sample';
        $fakeTestString = 'fakeSample';
        $initializationVector = Encryptor::createInitializationVector($this->configStub);
        $cryptTestString = Encryptor::encryptString($testString, $initializationVector, $this->configStub);
        $this->assertEquals($testString, Encryptor::decryptString($cryptTestString, $initializationVector, $this->configStub));
        $this->assertNotEquals($fakeTestString, Encryptor::decryptString($cryptTestString, $initializationVector, $this->configStub));
    }

    public function testGenerateAsymmetricKeyPair()
    {
        $keyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $this->assertArrayHasKey('privateKey', $keyPair);
        $this->assertArrayHasKey('publicKey', $keyPair);
        $this->assertStringContainsString('PRIVATE KEY', $keyPair['privateKey']);
        $this->assertStringContainsString('PUBLIC KEY', $keyPair['publicKey']);
    }

    public function testGenerateAsymmetricKeyPairGeneratesDistinctKeyPairsEachCall()
    {
        $keyPairOne = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $keyPairTwo = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $this->assertNotEquals($keyPairOne['privateKey'], $keyPairTwo['privateKey']);
        $this->assertNotEquals($keyPairOne['publicKey'], $keyPairTwo['publicKey']);
    }

    public function testGenerateCertificateSigningRequest()
    {
        $keyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $certificateSigningRequest = Encryptor::generateCertificateSigningRequest($keyPair['privateKey'], ['CN' => 'Test Subject'], $this->configStub);
        $this->assertStringContainsString('CERTIFICATE REQUEST', $certificateSigningRequest);
    }

    public function testGenerateSelfSignedCertificate()
    {
        $keyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $certificate = Encryptor::generateSelfSignedCertificate($keyPair['privateKey'], ['CN' => 'Test Subject'], true, $this->configStub);
        $this->assertStringContainsString('CERTIFICATE', $certificate);
        $parsedCertificate = openssl_x509_parse($certificate);
        $this->assertEquals('Test Subject', $parsedCertificate['subject']['CN']);
        $this->assertEquals('Test Subject', $parsedCertificate['issuer']['CN']);
    }

    public function testSignCertificateSigningRequestIssuesCertificateSignedByIssuer()
    {
        $issuerKeyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $issuerCertificate = Encryptor::generateSelfSignedCertificate($issuerKeyPair['privateKey'], ['CN' => 'Test Certification Authority'], true, $this->configStub);

        $subjectKeyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $certificateSigningRequest = Encryptor::generateCertificateSigningRequest($subjectKeyPair['privateKey'], ['CN' => 'Test Subject'], $this->configStub);

        $issuedCertificate = Encryptor::signCertificateSigningRequest($certificateSigningRequest, $issuerCertificate, $issuerKeyPair['privateKey'], false, $this->configStub);

        $parsedCertificate = openssl_x509_parse($issuedCertificate);
        $this->assertEquals('Test Subject', $parsedCertificate['subject']['CN']);
        $this->assertEquals('Test Certification Authority', $parsedCertificate['issuer']['CN']);
    }

    public function testSignDataAndVerifySignature()
    {
        $keyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $certificate = Encryptor::generateSelfSignedCertificate($keyPair['privateKey'], ['CN' => 'Test Subject'], true, $this->configStub);
        $data = 'sample document content';
        $fakeData = 'tampered document content';

        $signature = Encryptor::signData($data, $keyPair['privateKey'], $this->configStub);

        $this->assertTrue(Encryptor::verifySignature($data, $signature, $certificate, $this->configStub));
        $this->assertFalse(Encryptor::verifySignature($fakeData, $signature, $certificate, $this->configStub));
    }

    public function testVerifySignatureFailsWithUnrelatedCertificate()
    {
        $keyPairOne = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $keyPairTwo = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $unrelatedCertificate = Encryptor::generateSelfSignedCertificate($keyPairTwo['privateKey'], ['CN' => 'Unrelated Subject'], true, $this->configStub);

        $data = 'sample document content';
        $signature = Encryptor::signData($data, $keyPairOne['privateKey'], $this->configStub);

        $this->assertFalse(Encryptor::verifySignature($data, $signature, $unrelatedCertificate, $this->configStub));
    }

    public function testSignCertificateSigningRequestIssuedCertificateVerifiesSubjectSignature()
    {
        $issuerKeyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $issuerCertificate = Encryptor::generateSelfSignedCertificate($issuerKeyPair['privateKey'], ['CN' => 'Test Certification Authority'], true, $this->configStub);

        $subjectKeyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $certificateSigningRequest = Encryptor::generateCertificateSigningRequest($subjectKeyPair['privateKey'], ['CN' => 'Test Subject'], $this->configStub);
        $issuedCertificate = Encryptor::signCertificateSigningRequest($certificateSigningRequest, $issuerCertificate, $issuerKeyPair['privateKey'], false, $this->configStub);

        $data = 'sample document content';
        $signature = Encryptor::signData($data, $subjectKeyPair['privateKey'], $this->configStub);

        $this->assertTrue(Encryptor::verifySignature($data, $signature, $issuedCertificate, $this->configStub));
    }

    public function testGenerateSelfSignedCertificateDefaultsToCertificationAuthority()
    {
        $keyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $certificate = Encryptor::generateSelfSignedCertificate($keyPair['privateKey'], ['CN' => 'Default CA'], customConfig: $this->configStub);
        $parsedCertificate = openssl_x509_parse($certificate);
        $this->assertEquals('CA:TRUE', $parsedCertificate['extensions']['basicConstraints']);
    }

    public function testGenerateSelfSignedCertificateNotCertificationAuthority()
    {
        $keyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $certificate = Encryptor::generateSelfSignedCertificate($keyPair['privateKey'], ['CN' => 'Leaf'], false, $this->configStub);
        $parsedCertificate = openssl_x509_parse($certificate);
        $this->assertEquals('CA:FALSE', $parsedCertificate['extensions']['basicConstraints']);
    }

    public function testSignCertificateSigningRequestDefaultsToNotCertificationAuthority()
    {
        $issuerKeyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $issuerCertificate = Encryptor::generateSelfSignedCertificate($issuerKeyPair['privateKey'], ['CN' => 'Issuer'], true, $this->configStub);
        $subjectKeyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $certificateSigningRequest = Encryptor::generateCertificateSigningRequest($subjectKeyPair['privateKey'], ['CN' => 'Subject'], $this->configStub);

        $issuedCertificate = Encryptor::signCertificateSigningRequest($certificateSigningRequest, $issuerCertificate, $issuerKeyPair['privateKey'], customConfig: $this->configStub);

        $parsedCertificate = openssl_x509_parse($issuedCertificate);
        $this->assertEquals('CA:FALSE', $parsedCertificate['extensions']['basicConstraints']);
    }

    public function testSignCertificateSigningRequestCanIssueIntermediateCertificationAuthority()
    {
        $rootKeyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $rootCertificate = Encryptor::generateSelfSignedCertificate($rootKeyPair['privateKey'], ['CN' => 'Root CA'], true, $this->configStub);
        $intermediateKeyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $certificateSigningRequest = Encryptor::generateCertificateSigningRequest($intermediateKeyPair['privateKey'], ['CN' => 'Intermediate CA'], $this->configStub);

        $intermediateCertificate = Encryptor::signCertificateSigningRequest($certificateSigningRequest, $rootCertificate, $rootKeyPair['privateKey'], true, $this->configStub);

        $parsedCertificate = openssl_x509_parse($intermediateCertificate);
        $this->assertEquals('CA:TRUE', $parsedCertificate['extensions']['basicConstraints']);
    }

    public function testVerifyCertificateSignedByIssuer()
    {
        $issuerKeyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $issuerCertificate = Encryptor::generateSelfSignedCertificate($issuerKeyPair['privateKey'], ['CN' => 'Issuer'], true, $this->configStub);
        $subjectKeyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $certificateSigningRequest = Encryptor::generateCertificateSigningRequest($subjectKeyPair['privateKey'], ['CN' => 'Subject'], $this->configStub);
        $issuedCertificate = Encryptor::signCertificateSigningRequest($certificateSigningRequest, $issuerCertificate, $issuerKeyPair['privateKey'], false, $this->configStub);

        $this->assertTrue(Encryptor::verifyCertificateSignedByIssuer($issuedCertificate, $issuerCertificate));
    }

    public function testVerifyCertificateSignedByIssuerOnSelfSignedCertificate()
    {
        $keyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $certificate = Encryptor::generateSelfSignedCertificate($keyPair['privateKey'], ['CN' => 'Self Signed'], true, $this->configStub);

        $this->assertTrue(Encryptor::verifyCertificateSignedByIssuer($certificate, $certificate));
    }

    public function testVerifyCertificateSignedByIssuerFailsWithUnrelatedIssuer()
    {
        $issuerKeyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $issuerCertificate = Encryptor::generateSelfSignedCertificate($issuerKeyPair['privateKey'], ['CN' => 'Issuer'], true, $this->configStub);
        $subjectKeyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $certificateSigningRequest = Encryptor::generateCertificateSigningRequest($subjectKeyPair['privateKey'], ['CN' => 'Subject'], $this->configStub);
        $issuedCertificate = Encryptor::signCertificateSigningRequest($certificateSigningRequest, $issuerCertificate, $issuerKeyPair['privateKey'], false, $this->configStub);

        $unrelatedKeyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $unrelatedCertificate = Encryptor::generateSelfSignedCertificate($unrelatedKeyPair['privateKey'], ['CN' => 'Unrelated'], true, $this->configStub);

        $this->assertFalse(Encryptor::verifyCertificateSignedByIssuer($issuedCertificate, $unrelatedCertificate));
    }

    public function testEncryptDecryptWithAsymmetricKeysHandlesPayloadLargerThanDirectRsaLimit()
    {
        $keyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $certificate = Encryptor::generateSelfSignedCertificate($keyPair['privateKey'], ['CN' => 'Recipient'], true, $this->configStub);
        $plainTextLargerThanDirectRsaLimit = 'dati riservati ' . str_repeat('x', 1000);

        $envelope = Encryptor::encryptWithPublicKey($plainTextLargerThanDirectRsaLimit, $certificate, $this->configStub);

        $this->assertArrayHasKey('data', $envelope);
        $this->assertArrayHasKey('envelopeKey', $envelope);
        $this->assertArrayHasKey('initializationVector', $envelope);
        $this->assertNotEquals($plainTextLargerThanDirectRsaLimit, $envelope['data']);

        $decrypted = Encryptor::decryptWithPrivateKey($envelope, $keyPair['privateKey'], $this->configStub);
        $this->assertEquals($plainTextLargerThanDirectRsaLimit, $decrypted);
    }

    public function testDecryptWithPrivateKeyFailsWithWrongPrivateKey()
    {
        $keyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);
        $certificate = Encryptor::generateSelfSignedCertificate($keyPair['privateKey'], ['CN' => 'Recipient'], true, $this->configStub);
        $envelope = Encryptor::encryptWithPublicKey('dati riservati', $certificate, $this->configStub);

        $wrongKeyPair = Encryptor::generateAsymmetricKeyPair($this->configStub);

        $this->assertFalse(Encryptor::decryptWithPrivateKey($envelope, $wrongKeyPair['privateKey'], $this->configStub));
    }

    public function testEncryptDecryptWithPasswordUsesDefaultIterations()
    {
        $envelope = Encryptor::encryptWithPassword('chiave privata', '123456', null, $this->configStub);

        $this->assertStringStartsWith('sisma-pw2$aes-256-gcm$pbkdf2-sha256$' . self::DEFAULT_ITERATIONS . '$', $envelope);
        $this->assertEquals('chiave privata', Encryptor::decryptWithPassword($envelope, '123456', $this->configStub));
    }

    public function testDecryptWithPasswordRejectsReleasedVersion1Envelope()
    {
        $this->assertFalse(Encryptor::decryptWithPassword(self::RELEASED_VERSION_1_ENVELOPE, '123456', $this->configStub));
    }

    public function testEnvelopeStaysDecryptableAfterEncryptionParametersChange()
    {
        $customConfig = $this->createConfigStub([
            'passwordKeyDerivationHashAlgorithm' => 'sha512',
            'passwordKeyDerivationSaltBytes' => 32,
            'passwordEncryptionAlgorithm' => 'aes-128-gcm',
            'passwordEncryptionInitializationVectorBytes' => 16,
            'passwordEncryptionTagBytes' => 12,
        ]);

        $envelope = Encryptor::encryptWithPassword('chiave privata', '123456', self::MINIMUM_ITERATIONS, $customConfig);

        $this->assertStringStartsWith('sisma-pw2$aes-128-gcm$pbkdf2-sha512$', $envelope);
        $this->assertEquals('chiave privata', Encryptor::decryptWithPassword($envelope, '123456', $this->configStub));
    }

    public function testEnvelopeFormatIsReadOnlyWithSameFormatConfiguration()
    {
        $customConfig = $this->createConfigStub([
            'passwordEnvelopeVersion' => 'custom-v1',
            'passwordEnvelopeSeparator' => '|',
            'passwordEnvelopeKeyDerivationPrefix' => 'kdf-',
        ]);

        $envelope = Encryptor::encryptWithPassword('chiave privata', '123456', self::MINIMUM_ITERATIONS, $customConfig);

        $this->assertStringStartsWith('custom-v1|aes-256-gcm|kdf-sha256|', $envelope);
        $this->assertEquals('chiave privata', Encryptor::decryptWithPassword($envelope, '123456', $customConfig));
        $this->assertFalse(Encryptor::decryptWithPassword($envelope, '123456', $this->configStub));
    }

    public function testEncryptWithPasswordRejectsCipherWithoutAuthentication()
    {
        $this->expectException(\InvalidArgumentException::class);
        Encryptor::encryptWithPassword('dati', '123456', null, $this->createConfigStub(['passwordEncryptionAlgorithm' => 'aes-256-cbc']));
    }

    public function testEncryptWithPasswordRejectsUnsupportedHashAlgorithm()
    {
        $this->expectException(\InvalidArgumentException::class);
        Encryptor::encryptWithPassword('dati', '123456', null, $this->createConfigStub(['passwordKeyDerivationHashAlgorithm' => 'sha999']));
    }

    public function testEncryptWithPasswordRejectsTagShorterThanMinimum()
    {
        $this->expectException(\InvalidArgumentException::class);
        Encryptor::encryptWithPassword('dati', '123456', null, $this->createConfigStub(['passwordEncryptionTagBytes' => 8]));
    }

    public function testDecryptWithPasswordRejectsTruncatedTag()
    {
        $parts = explode('$', Encryptor::encryptWithPassword('dati', '123456', self::MINIMUM_ITERATIONS, $this->configStub));
        $parts[6] = base64_encode(substr(base64_decode($parts[6]), 0, 4));

        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', $parts), '123456', $this->configStub));
    }

    public function testEncryptDecryptWithPasswordPreservesBinaryAndUnicodeData()
    {
        $plainText = "-----BEGIN PRIVATE KEY-----\nÀèìòù €\0\xff" . random_bytes(64);

        $envelope = Encryptor::encryptWithPassword($plainText, 'pïn sègreto', self::MINIMUM_ITERATIONS, $this->configStub);

        $this->assertSame($plainText, Encryptor::decryptWithPassword($envelope, 'pïn sègreto', $this->configStub));
    }

    public function testEncryptWithPasswordProducesDifferentEnvelopesForSameInput()
    {
        $firstEnvelope = Encryptor::encryptWithPassword('dati', '123456', self::MINIMUM_ITERATIONS, $this->configStub);
        $secondEnvelope = Encryptor::encryptWithPassword('dati', '123456', self::MINIMUM_ITERATIONS, $this->configStub);

        $this->assertNotEquals($firstEnvelope, $secondEnvelope);
        $this->assertStringNotContainsString('dati', $firstEnvelope);
    }

    public function testDecryptWithPasswordFailsWithWrongPassword()
    {
        $envelope = Encryptor::encryptWithPassword('dati', '123456', self::MINIMUM_ITERATIONS, $this->configStub);

        $this->assertFalse(Encryptor::decryptWithPassword($envelope, '654321', $this->configStub));
        $this->assertFalse(Encryptor::decryptWithPassword($envelope, '', $this->configStub));
    }

    public function testDecryptWithPasswordFailsWithTamperedCipherText()
    {
        $parts = explode('$', Encryptor::encryptWithPassword('dati riservati', '123456', self::MINIMUM_ITERATIONS, $this->configStub));
        $cipherText = base64_decode($parts[7]);
        $cipherText[0] = chr(ord($cipherText[0]) ^ 1);
        $parts[7] = base64_encode($cipherText);

        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', $parts), '123456', $this->configStub));
    }

    public function testDecryptWithPasswordFailsWithTamperedHeader()
    {
        $parts = explode('$', Encryptor::encryptWithPassword('dati', '123456', self::MINIMUM_ITERATIONS, $this->configStub));
        $parts[3] = (string) (self::MINIMUM_ITERATIONS + 1);

        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', $parts), '123456', $this->configStub));
    }

    public function testDecryptWithPasswordFailsWithMalformedOrUnsupportedEnvelope()
    {
        $parts = explode('$', Encryptor::encryptWithPassword('dati', '123456', self::MINIMUM_ITERATIONS, $this->configStub));

        $this->assertFalse(Encryptor::decryptWithPassword('', '123456', $this->configStub));
        $this->assertFalse(Encryptor::decryptWithPassword('non è un involucro', '123456', $this->configStub));
        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', array_slice($parts, 0, 7)), '123456', $this->configStub));
        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', array_replace($parts, [0 => 'sisma-pw9'])), '123456', $this->configStub));
        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', array_replace($parts, [1 => 'aes-256-cbc'])), '123456', $this->configStub));
        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', array_replace($parts, [2 => 'scrypt-sha256'])), '123456', $this->configStub));
        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', array_replace($parts, [3 => 'molte'])), '123456', $this->configStub));
        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', array_replace($parts, [3 => '1000'])), '123456', $this->configStub));
        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', array_replace($parts, [3 => '999999999'])), '123456', $this->configStub));
        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', array_replace($parts, [6 => '!!!'])), '123456', $this->configStub));
    }

    public function testEncryptWithPasswordRejectsEmptyPassword()
    {
        $this->expectException(\InvalidArgumentException::class);
        Encryptor::encryptWithPassword('dati', '', null, $this->configStub);
    }

    public function testEncryptWithPasswordRejectsTooFewIterations()
    {
        $this->expectException(\InvalidArgumentException::class);
        Encryptor::encryptWithPassword('dati', '123456', self::MINIMUM_ITERATIONS - 1, $this->configStub);
    }
}
