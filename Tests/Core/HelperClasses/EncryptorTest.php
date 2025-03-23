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
use SismaFramework\Core\BaseClasses\BaseConfig;
use SismaFramework\Core\HelperClasses\Encryptor;

/**
 * @author Valentino de Lapa
 */
class EncryptorTest extends TestCase
{

    private BaseConfig $configTest;

    public function setUp(): void
    {
        $this->configTest = new EncryptorConfigTest();
    }

    public function testGetSimpleRandomToken()
    {
        $this->assertIsString(Encryptor::getSimpleRandomToken());
    }

    public function testGetVerifySimpleHash()
    {
        $testString = 'sample';
        $fakeTestString = 'fakeSample';
        $testStringHash = Encryptor::getSimpleHash($testString, $this->configTest);
        $this->assertTrue(Encryptor::verifySimpleHash($testString, $testStringHash, $this->configTest));
        $this->assertFalse(Encryptor::verifySimpleHash($fakeTestString, $testStringHash, $this->configTest));
    }

    public function testGetVerifyBlowfishHash()
    {
        $testString = 'sample';
        $fakeTestString = 'fakeSample';
        $testStringHash = Encryptor::getBlowfishHash($testString, $this->configTest);
        $this->assertTrue(Encryptor::verifyBlowfishHash($testString, $testStringHash));
        $this->assertFalse(Encryptor::verifyBlowfishHash($fakeTestString, $testStringHash));
    }

    public function testEncryptDecryptString()
    {
        $testString = 'sample';
        $fakeTestString = 'fakeSample';
        $initializationVector = Encryptor::createInizializationVector($this->configTest);
        $cryptTestString = Encryptor::encryptString($testString, $initializationVector, $this->configTest);
        $this->assertEquals($testString, Encryptor::decryptString($cryptTestString, $initializationVector, $this->configTest));
        $this->assertNotEquals($fakeTestString, Encryptor::decryptString($cryptTestString, $initializationVector, $this->configTest));
    }
}

class EncryptorConfigTest extends BaseConfig
{

    #[\Override]
    protected function isInitialConfiguration(string $name): bool
    {
        return false;
    }

    #[\Override]
    protected function setFrameworkConfigurations(): void
    {
        $this->blowfishHashWorkload = 12;
        $this->encryptionAlgorithm = 'AES-256-CBC';
        $this->encryptionPassphrase = '';
        $this->initializationVectorBytes = 16;
        $this->simpleHashAlgorithm = 'sha256';
    }

    #[\Override]
    protected function setInitialConfiguration(): void
    {
        
    }
}
