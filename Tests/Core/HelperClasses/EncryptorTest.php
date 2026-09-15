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
}
