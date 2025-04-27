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

    private BaseConfig $configMock;

    public function setUp(): void
    {
        $this->configMock = $this->createMock(BaseConfig::class);
        $this->configMock->expects($this->any())
                ->method('__get')
                ->willReturnMap([
                    ['blowfishHashWorkload', 12],
                    ['encryptionAlgorithm', 'AES-256-CBC'],
                    ['encryptionPassphrase', ''],
                    ['initializationVectorBytes', 16],
                    ['simpleHashAlgorithm', 'sha256'],
        ]);
    }

    public function testGetSimpleRandomToken()
    {
        $this->assertIsString(Encryptor::getSimpleRandomToken());
    }

    public function testGetVerifySimpleHash()
    {
        $testString = 'sample';
        $fakeTestString = 'fakeSample';
        $testStringHash = Encryptor::getSimpleHash($testString, $this->configMock);
        $this->assertTrue(Encryptor::verifySimpleHash($testString, $testStringHash, $this->configMock));
        $this->assertFalse(Encryptor::verifySimpleHash($fakeTestString, $testStringHash, $this->configMock));
    }

    public function testGetVerifyBlowfishHash()
    {
        $testString = 'sample';
        $fakeTestString = 'fakeSample';
        $testStringHash = Encryptor::getBlowfishHash($testString, $this->configMock);
        $this->assertTrue(Encryptor::verifyBlowfishHash($testString, $testStringHash));
        $this->assertFalse(Encryptor::verifyBlowfishHash($fakeTestString, $testStringHash));
    }

    public function testEncryptDecryptString()
    {
        $testString = 'sample';
        $fakeTestString = 'fakeSample';
        $initializationVector = Encryptor::createInizializationVector($this->configMock);
        $cryptTestString = Encryptor::encryptString($testString, $initializationVector, $this->configMock);
        $this->assertEquals($testString, Encryptor::decryptString($cryptTestString, $initializationVector, $this->configMock));
        $this->assertNotEquals($fakeTestString, Encryptor::decryptString($cryptTestString, $initializationVector, $this->configMock));
    }
}
