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

    private Config $configStub;

    public function setUp(): void
    {
        $this->configStub = $this->createStub(Config::class);
        $this->configStub->method('__get')
                ->willReturnMap([
                    ['blowfishHashWorkload', 12],
                    ['encryptionAlgorithm', 'AES-256-CBC'],
                    ['encryptionPassphrase', ''],
                    ['initializationVectorBytes', 16],
                    ['simpleHashAlgorithm', 'sha256'],
                    ['asymmetricKeyType', OPENSSL_KEYTYPE_RSA],
                    ['asymmetricKeyBits', 2048],
                    ['asymmetricDigestAlgorithm', 'sha256'],
                    ['certificateValidityDays', 3650],
                    ['opensslConfigPath', getenv('OPENSSL_CONFIG_PATH') ?: ''],
        ]);
        $this->configStub->method('__isset')
                ->willReturn(true);
    }

    public function testGetSimpleRandomToken()
    {
        $this->assertIsString(Encryptor::getSimpleRandomToken());
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
        $envelope = Encryptor::encryptWithPassword('chiave privata', '123456');

        $this->assertStringStartsWith('sisma-pw1$pbkdf2-sha256$' . Encryptor::PASSWORD_KEY_DERIVATION_DEFAULT_ITERATIONS . '$', $envelope);
        $this->assertEquals('chiave privata', Encryptor::decryptWithPassword($envelope, '123456'));
    }

    public function testEncryptDecryptWithPasswordPreservesBinaryAndUnicodeData()
    {
        $plainText = "-----BEGIN PRIVATE KEY-----\nÀèìòù €\0\xff" . random_bytes(64);

        $envelope = Encryptor::encryptWithPassword($plainText, 'pïn sègreto', Encryptor::PASSWORD_KEY_DERIVATION_MINIMUM_ITERATIONS);

        $this->assertSame($plainText, Encryptor::decryptWithPassword($envelope, 'pïn sègreto'));
    }

    public function testEncryptWithPasswordProducesDifferentEnvelopesForSameInput()
    {
        $firstEnvelope = Encryptor::encryptWithPassword('dati', '123456', Encryptor::PASSWORD_KEY_DERIVATION_MINIMUM_ITERATIONS);
        $secondEnvelope = Encryptor::encryptWithPassword('dati', '123456', Encryptor::PASSWORD_KEY_DERIVATION_MINIMUM_ITERATIONS);

        $this->assertNotEquals($firstEnvelope, $secondEnvelope);
        $this->assertStringNotContainsString('dati', $firstEnvelope);
    }

    public function testDecryptWithPasswordFailsWithWrongPassword()
    {
        $envelope = Encryptor::encryptWithPassword('dati', '123456', Encryptor::PASSWORD_KEY_DERIVATION_MINIMUM_ITERATIONS);

        $this->assertFalse(Encryptor::decryptWithPassword($envelope, '654321'));
        $this->assertFalse(Encryptor::decryptWithPassword($envelope, ''));
    }

    public function testDecryptWithPasswordFailsWithTamperedCipherText()
    {
        $parts = explode('$', Encryptor::encryptWithPassword('dati riservati', '123456', Encryptor::PASSWORD_KEY_DERIVATION_MINIMUM_ITERATIONS));
        $cipherText = base64_decode($parts[6]);
        $cipherText[0] = chr(ord($cipherText[0]) ^ 1);
        $parts[6] = base64_encode($cipherText);

        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', $parts), '123456'));
    }

    public function testDecryptWithPasswordFailsWithTamperedHeader()
    {
        $parts = explode('$', Encryptor::encryptWithPassword('dati', '123456', Encryptor::PASSWORD_KEY_DERIVATION_MINIMUM_ITERATIONS));
        $parts[2] = (string) (Encryptor::PASSWORD_KEY_DERIVATION_MINIMUM_ITERATIONS + 1);

        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', $parts), '123456'));
    }

    public function testDecryptWithPasswordFailsWithMalformedOrUnsupportedEnvelope()
    {
        $parts = explode('$', Encryptor::encryptWithPassword('dati', '123456', Encryptor::PASSWORD_KEY_DERIVATION_MINIMUM_ITERATIONS));

        $this->assertFalse(Encryptor::decryptWithPassword('', '123456'));
        $this->assertFalse(Encryptor::decryptWithPassword('non è un involucro', '123456'));
        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', array_slice($parts, 0, 6)), '123456'));
        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', array_replace($parts, [0 => 'sisma-pw9'])), '123456'));
        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', array_replace($parts, [1 => 'pbkdf2-md5'])), '123456'));
        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', array_replace($parts, [2 => 'molte'])), '123456'));
        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', array_replace($parts, [2 => '1000'])), '123456'));
        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', array_replace($parts, [2 => '999999999'])), '123456'));
        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', array_replace($parts, [5 => '!!!'])), '123456'));
        $this->assertFalse(Encryptor::decryptWithPassword(implode('$', array_replace($parts, [4 => base64_encode('corto')])), '123456'));
    }

    public function testEncryptWithPasswordRejectsEmptyPassword()
    {
        $this->expectException(\InvalidArgumentException::class);
        Encryptor::encryptWithPassword('dati', '');
    }

    public function testEncryptWithPasswordRejectsTooFewIterations()
    {
        $this->expectException(\InvalidArgumentException::class);
        Encryptor::encryptWithPassword('dati', '123456', Encryptor::PASSWORD_KEY_DERIVATION_MINIMUM_ITERATIONS - 1);
    }
}
