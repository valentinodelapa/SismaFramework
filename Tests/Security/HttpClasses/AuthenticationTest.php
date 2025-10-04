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

namespace SismaFramework\Tests\Security\HttpClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\Filter;
use SismaFramework\Core\HelperClasses\Session;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HelperClasses\Encryptor;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Security\Exceptions\AuthenticationException;
use SismaFramework\Security\HttpClasses\Authentication;
use SismaFramework\Security\Interfaces\Entities\AuthenticableInterface;
use SismaFramework\Security\Interfaces\Entities\MultiFactorInterface;
use SismaFramework\Security\Interfaces\Entities\MultiFactorRecoveryInterface;
use SismaFramework\Security\Interfaces\Models\AuthenticableModelInterface;
use SismaFramework\Security\Interfaces\Models\MultiFactorModelInterface;
use SismaFramework\Security\Interfaces\Models\MultiFactorRecoveryModelInterface;
use SismaFramework\Security\Interfaces\Models\PasswordModelInterface;
use SismaFramework\Security\Interfaces\Wrappers\MultiFactorWrapperInterface;
use SismaFramework\TestsApplication\Entities\MultiFactorRecovery;
use SismaFramework\TestsApplication\Entities\Password;

/**
 * Description of AuthenticationTest
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class AuthenticationTest extends TestCase
{

    private Config $configMock;
    private DataMapper $dataMapperMock;
    private Authentication $authentication;
    private Filter $filterMock;
    private Session $sessionMock;
    private AuthenticableInterface $authenticableInterfaceMock;
    private MultiFactorInterface $multiFactorInterfaceMock;
    private MultiFactorRecovery $multiFactorRecoveryMock;
    private Password $passwordMock;
    private AuthenticableModelInterface $authenticableModelInterfaceMock;
    private MultiFactorModelInterface $multiFactorModelInterfaceMock;
    private MultiFactorRecoveryModelInterface $multiFactorRecoveryModelInterfaceMock;
    private MultiFactorWrapperInterface $multiFactorWrapperInterfaceMock;
    private PasswordModelInterface $passwordModelInterfaceMock;
    private Request $requestMock;

    public function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->configMock->expects($this->any())
                ->method('__get')
                ->willReturnMap([
                    ['blowfishHashWorkload', 10],
        ]);
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $this->dataMapperMock = $this->getMockBuilder(DataMapper::class)
                ->disableOriginalConstructor()
                ->getMock();
        $this->sessionMock = $this->createMock(Session::class);
        $this->filterMock = $this->createMock(Filter::class);
        $this->sessionMock = $this->createMock(Session::class);
        $this->authenticableInterfaceMock = $this->createMock(AuthenticableInterface::class);
        $this->multiFactorInterfaceMock = $this->createMock(MultiFactorInterface::class);
        $this->multiFactorRecoveryMock = $this->createMock(MultiFactorRecovery::class);
        $this->passwordMock = $this->createMock(Password::class);
        $this->authenticableModelInterfaceMock = $this->createMock(AuthenticableModelInterface::class);
        $this->multiFactorModelInterfaceMock = $this->createMock(MultiFactorModelInterface::class);
        $this->multiFactorRecoveryModelInterfaceMock = $this->createMock(MultiFactorRecoveryModelInterface::class);
        $this->passwordModelInterfaceMock = $this->createMock(PasswordModelInterface::class);
        $this->multiFactorWrapperInterfaceMock = $this->createMock(MultiFactorWrapperInterface::class);
        $this->requestMock = $this->createMock(Request::class);
        $this->requestMock->server['REQUEST_METHOD'] = 'POST';
        $this->requestMock->input = [];
        $this->authentication = new Authentication($this->requestMock, $this->filterMock, $this->sessionMock);
        $this->authentication->setAuthenticableModelInterface($this->authenticableModelInterfaceMock);
        $this->authentication->setPasswordModelInterface($this->passwordModelInterfaceMock);
        $this->authentication->setMultiFactorModelInterface($this->multiFactorModelInterfaceMock);
        $this->authentication->setMultiFactorRecoveryModelInterface($this->multiFactorRecoveryModelInterfaceMock);
        $this->authentication->setMultiFactorWrapperInterface($this->multiFactorWrapperInterfaceMock);
    }

    public function testCheckAuthenticable()
    {
        $matcherOne = $this->exactly(5);
        $this->filterMock->expects($matcherOne)
                ->method('isString')
                ->willReturnCallback(function ($name) use ($matcherOne) {
                    switch ($matcherOne->numberOfInvocations()) {
                        case 5:
                            $this->assertEquals('password-test', $name);
                            break;
                        default:
                            $this->assertEquals('identifier-test', $name);
                            break;
                    }
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            return false;
                        default:
                            return true;
                    }
                });
        $matcherTwo = $this->exactly(3);
        $this->authenticableModelInterfaceMock->expects($matcherTwo)
                ->method('getValidAuthenticableInterfaceByIdentifier')
                ->willReturnCallback(function ($authenticableInterface) use ($matcherTwo) {
                    $this->assertEquals('identifier-test', $authenticableInterface);
                    switch ($matcherTwo->numberOfInvocations()) {
                        case 1:
                            return null;
                        default:
                            return $this->authenticableInterfaceMock;
                    }
                });

        $this->passwordModelInterfaceMock->expects($this->once())
                ->method('getPasswordByAuthenticableInterface')
                ->with($this->authenticableInterfaceMock)
                ->willReturn($this->passwordMock);
        $this->passwordMock->expects($this->once())
                ->method('__get')
                ->with('password')
                ->willReturn(Encryptor::getBlowfishHash('password-test', $this->configMock));
        $this->requestMock->input['csrfToken'] = 'csfr-token-test';
        $this->assertFalse($this->authentication->checkAuthenticable(true));
        $this->assertFalse($this->authentication->checkAuthenticable(true));
        $this->requestMock->input['identifier'] = 'identifier-test';
        $this->assertFalse($this->authentication->checkAuthenticable(false));
        $this->assertFalse($this->authentication->checkAuthenticable(false));
        $this->assertFalse($this->authentication->checkAuthenticable(false));
        $this->requestMock->input['password'] = 'password-test';
        $this->assertTrue($this->authentication->checkAuthenticable(false));
    }

    public function testCheckCsrfToken()
    {

        $matcherOne = $this->exactly(5);
        $this->sessionMock->expects($matcherOne)
                ->method('__isset')
                ->willReturnCallback(function ($name) use ($matcherOne) {
                    $this->assertEquals('csrfToken', $name);
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            return false;
                        default:
                            return true;
                    }
                });
        $matcherTwo = $this->exactly(3);
        $this->filterMock->expects($matcherTwo)
                ->method('isString')
                ->willReturnCallback(function ($name) use ($matcherTwo) {
                    $this->assertEquals('csfr-token-test', $name);
                    switch ($matcherTwo->numberOfInvocations()) {
                        case 1:
                            return false;
                        default:
                            return true;
                    }
                });
        $matcherThree = $this->exactly(2);
        $this->sessionMock->expects($matcherThree)
                ->method('__get')
                ->willReturnCallback(function ($name) use ($matcherThree) {
                    $this->assertEquals('csrfToken', $name);
                    switch ($matcherThree->numberOfInvocations()) {
                        case 1:
                            return 'fake-csfr-token-test';
                        case 2:
                            return 'csfr-token-test';
                    }
                });
        $this->assertFalse($this->authentication->checkCsrfToken($this->sessionMock));
        $this->requestMock->input = [];
        $this->assertFalse($this->authentication->checkCsrfToken($this->sessionMock));
        $this->requestMock->input['csrfToken'] = 'csfr-token-test';
        $this->assertFalse($this->authentication->checkCsrfToken($this->sessionMock));
        $this->assertFalse($this->authentication->checkCsrfToken($this->sessionMock));
        $this->assertTrue($this->authentication->checkCsrfToken($this->sessionMock));
    }

    public function testCheckPassword()
    {
        $matcherOne = $this->exactly(4);
        $this->filterMock->expects($matcherOne)
                ->method('isString')
                ->willReturnCallback(function ($name) use ($matcherOne) {
                    $this->assertEquals('password-test', $name);
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            return false;
                        default:
                            return true;
                    }
                });
        $matcherTwo = $this->exactly(3);
        $this->passwordModelInterfaceMock->expects($matcherTwo)
                ->method('getPasswordByAuthenticableInterface')
                ->willReturnCallback(function ($authenticableInterface) use ($matcherTwo) {
                    $this->assertEquals($this->authenticableInterfaceMock, $authenticableInterface);
                    switch ($matcherTwo->numberOfInvocations()) {
                        case 1:
                            return null;
                        default:
                            return $this->passwordMock;
                    }
                });
        $matcherThree = $this->exactly(2);
        $this->passwordMock->expects($matcherThree)
                ->method('__get')
                ->willReturnCallback(function ($name) use ($matcherThree) {
                    $this->assertEquals('password', $name);
                    switch ($matcherThree->numberOfInvocations()) {
                        case 1:
                            return Encryptor::getBlowfishHash('fake-password-test', $this->configMock);
                        default :
                            return Encryptor::getBlowfishHash('password-test', $this->configMock);
                    }
                });
        $this->requestMock->input = [];
        $this->assertFalse($this->authentication->checkPassword($this->authenticableInterfaceMock));
        $this->requestMock->input['password'] = 'password-test';
        $this->assertFalse($this->authentication->checkPassword($this->authenticableInterfaceMock));
        $this->assertFalse($this->authentication->checkPassword($this->authenticableInterfaceMock));
        $this->assertFalse($this->authentication->checkPassword($this->authenticableInterfaceMock));
        $this->assertTrue($this->authentication->checkPassword($this->authenticableInterfaceMock));
    }

    public function testCheckMultiFactor()
    {

        $matcherOne = $this->exactly(5);
        $this->filterMock->expects($matcherOne)
                ->method('isString')
                ->willReturnCallback(function ($name) use ($matcherOne) {
                    $this->assertEquals('code-test', $name);
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            return false;
                        default:
                            return true;
                    }
                });
        $matcherTwo = $this->exactly(4);
        $this->multiFactorModelInterfaceMock->expects($matcherTwo)
                ->method('getLastActiveMultiFactorByAuthenticableInterface')
                ->willReturnCallback(function ($authenticableInterface) use ($matcherTwo) {
                    $this->assertEquals($this->authenticableInterfaceMock, $authenticableInterface);
                    switch ($matcherTwo->numberOfInvocations()) {
                        case 1:
                            return null;
                        default:
                            return $this->multiFactorInterfaceMock;
                    }
                });
        $matcherThree = $this->exactly(3);
        $this->multiFactorWrapperInterfaceMock->expects($matcherThree)
                ->method('testCodeForLogin')
                ->willReturnCallback(function ($multiFactorInterface, $code) use ($matcherThree) {
                    $this->assertEquals($multiFactorInterface, $this->multiFactorInterfaceMock);
                    $this->assertEquals('code-test', $code);
                    switch ($matcherThree->numberOfInvocations()) {
                        case 1:
                            return true;
                        default:
                            return false;
                    }
                });
        $matcherFour = $this->exactly(2);
        $this->multiFactorRecoveryModelInterfaceMock->expects($matcherFour)
                ->method('getMultiFactorRecoveryInterfaceCollectionByMultiFactorInterface')
                ->willReturnCallback(function ($multiFactorInterface) use ($matcherFour) {
                    $this->assertEquals($multiFactorInterface, $this->multiFactorInterfaceMock);
                    switch ($matcherFour->numberOfInvocations()) {
                        case 1:
                            $multiFactorRecoveryCollection = new SismaCollection(MultiFactorRecoveryInterface::class);
                            $multiFactorRecoveryCollection->append($this->multiFactorRecoveryMock);
                            return $multiFactorRecoveryCollection;
                        default:
                            return new SismaCollection(MultiFactorRecoveryInterface::class);
                    }
                });
        $this->multiFactorRecoveryMock->expects($this->once())
                ->method('__get')
                ->with('token')
                ->willReturn(Encryptor::getBlowfishHash('code-test', $this->configMock));
        $this->dataMapperMock->expects($this->once())
                ->method('delete')
                ->with($this->multiFactorRecoveryMock)
                ->willReturn(true);
        $this->requestMock->input = [];
        $this->assertFalse($this->authentication->checkMultiFactor($this->authenticableInterfaceMock, $this->dataMapperMock));
        $this->requestMock->input['code'] = 'code-test';
        $this->assertFalse($this->authentication->checkMultiFactor($this->authenticableInterfaceMock, $this->dataMapperMock));
        $this->assertFalse($this->authentication->checkMultiFactor($this->authenticableInterfaceMock, $this->dataMapperMock));
        $this->assertTrue($this->authentication->checkMultiFactor($this->authenticableInterfaceMock, $this->dataMapperMock));
        $this->multiFactorRecoveryMock->token = Encryptor::getBlowfishHash('code-test', $this->configMock);
        $this->assertTrue($this->authentication->checkMultiFactor($this->authenticableInterfaceMock, $this->dataMapperMock));
        $this->assertFalse($this->authentication->checkMultiFactor($this->authenticableInterfaceMock, $this->dataMapperMock));
    }

    public function testCheckMultiFactorRecovery()
    {

        $matcherOne = $this->exactly(3);
        $this->multiFactorRecoveryModelInterfaceMock->expects($matcherOne)
                ->method('getMultiFactorRecoveryInterfaceCollectionByMultiFactorInterface')
                ->willReturnCallback(function ($multiFactorInterface) use ($matcherOne) {
                    $this->assertEquals($multiFactorInterface, $this->multiFactorInterfaceMock);
                    switch ($matcherOne->numberOfInvocations()) {
                        case 1:
                            return new SismaCollection(MultiFactorRecoveryInterface::class);
                        default:
                            $multiFactorRecoveryCollection = new SismaCollection(MultiFactorRecoveryInterface::class);
                            $multiFactorRecoveryCollection->append($this->multiFactorRecoveryMock);
                            return $multiFactorRecoveryCollection;
                    }
                });
        $matcherTwo = $this->exactly(2);
        $this->multiFactorRecoveryMock->expects($matcherTwo)
                ->method('__get')
                ->willReturnCallback(function ($name) use ($matcherTwo) {
                    $this->assertEquals('token', $name);
                    switch ($matcherTwo->numberOfInvocations()) {
                        case 1:
                            return Encryptor::getBlowfishHash('fake-code-test', $this->configMock);
                        case 2:
                            return Encryptor::getBlowfishHash('code-test', $this->configMock);
                    }
                });
        $this->dataMapperMock->expects($this->once())
                ->method('delete')
                ->with($this->multiFactorRecoveryMock)
                ->willReturn(true);
        $this->assertFalse($this->authentication->checkMultiFactorRecovery($this->multiFactorInterfaceMock, 'code-test', $this->dataMapperMock));
        $this->assertFalse($this->authentication->checkMultiFactorRecovery($this->multiFactorInterfaceMock, 'code-test', $this->dataMapperMock));
        $this->assertTrue($this->authentication->checkMultiFactorRecovery($this->multiFactorInterfaceMock, 'code-test', $this->dataMapperMock));
    }

    public function testGetAuthenticableInterfaceWithException()
    {
        $this->expectException(AuthenticationException::class);
        $this->assertEquals($this->authenticableInterfaceMock, $this->authentication->getAuthenticableInterface());
    }

    public function testGetAuthenticableInterface()
    {
        $matcher = $this->exactly(2);
        $this->filterMock->expects($matcher)
                ->method('isString')
                ->willReturnCallback(function ($name) use ($matcher) {
                    switch ($matcher->numberOfInvocations()) {
                        case 1:
                            $this->assertEquals('identifier-test', $name);
                            break;
                        default:
                            $this->assertEquals('password-test', $name);
                            break;
                    }
                    return true;
                });
        $this->authenticableModelInterfaceMock->expects($this->once())
                ->method('getValidAuthenticableInterfaceByIdentifier')
                ->with('identifier-test')
                ->willReturn($this->authenticableInterfaceMock);
        $this->passwordModelInterfaceMock->expects($this->once())
                ->method('getPasswordByAuthenticableInterface')
                ->with($this->authenticableInterfaceMock)
                ->willReturn($this->passwordMock);
        $this->passwordMock->expects($this->once())
                ->method('__get')
                ->with('password')
                ->willReturn(Encryptor::getBlowfishHash('password-test', $this->configMock));
        $this->requestMock->input['csrfToken'] = 'csfr-token-test';
        $this->requestMock->input['identifier'] = 'identifier-test';
        $this->requestMock->input['password'] = 'password-test';
        $this->assertTrue($this->authentication->checkAuthenticable(false));
        $this->assertEquals($this->authenticableInterfaceMock, $this->authentication->getAuthenticableInterface());
    }
}
