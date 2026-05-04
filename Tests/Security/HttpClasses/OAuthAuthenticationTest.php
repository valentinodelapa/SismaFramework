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
use SismaFramework\Core\HelperClasses\Filter;
use SismaFramework\Core\HelperClasses\Session;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Security\HttpClasses\OAuthAuthentication;
use SismaFramework\Security\Interfaces\Entities\AuthenticableInterface;
use SismaFramework\Security\Interfaces\Models\AuthenticableModelInterface;
use SismaFramework\Security\Interfaces\Wrappers\OAuthWrapperInterface;

/**
 * @author Valentino de Lapa
 */
class OAuthAuthenticationTest extends TestCase
{
    private Request $requestStub;
    private Session $sessionStub;
    private Filter $filterStub;
    private OAuthAuthentication $oauthAuthentication;
    private OAuthWrapperInterface $oauthWrapperStub;
    private AuthenticableModelInterface $authenticableModelStub;
    private AuthenticableInterface $authenticableInterfaceStub;

    #[\Override]
    public function setUp(): void
    {
        $this->requestStub = $this->createStub(Request::class);
        $this->requestStub->query = [];
        $this->requestStub->server = [];

        $this->sessionStub = $this->createStub(Session::class);

        $this->filterStub = $this->createStub(Filter::class);
        $this->filterStub->method('isString')->willReturn(true);

        $this->oauthWrapperStub = $this->createStub(OAuthWrapperInterface::class);
        $this->authenticableModelStub = $this->createStub(AuthenticableModelInterface::class);
        $this->authenticableInterfaceStub = $this->createStub(AuthenticableInterface::class);

        $this->oauthAuthentication = new OAuthAuthentication($this->requestStub, $this->filterStub, $this->sessionStub);
        $this->oauthAuthentication->setOAuthWrapperInterface($this->oauthWrapperStub);
        $this->oauthAuthentication->setAuthenticableModelInterface($this->authenticableModelStub);
    }

    public function testGetAuthorizationUrl(): void
    {
        $expectedUrl = 'https://provider.example.com/oauth/authorize';

        $sessionMock = $this->createMock(Session::class);
        $wrapperMock = $this->createMock(OAuthWrapperInterface::class);

        $sessionMock->expects($this->once())
            ->method('__set')
            ->with('oauthState', $this->anything());

        $wrapperMock->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with($this->anything())
            ->willReturn($expectedUrl);

        $oauthAuthentication = new OAuthAuthentication($this->requestStub, $this->filterStub, $sessionMock);
        $oauthAuthentication->setOAuthWrapperInterface($wrapperMock);
        $oauthAuthentication->setAuthenticableModelInterface($this->authenticableModelStub);

        $this->assertEquals($expectedUrl, $oauthAuthentication->getAuthorizationUrl());
    }

    public function testCheckCallbackWithProviderError(): void
    {
        $this->requestStub->query = ['error' => 'access_denied'];
        $this->assertFalse($this->oauthAuthentication->checkCallback());
    }

    public function testCheckCallbackWithMissingSessionState(): void
    {
        $this->requestStub->query = ['state' => 'some_state', 'code' => 'some_code'];
        $this->assertFalse($this->oauthAuthentication->checkCallback());
    }

    public function testCheckCallbackWithMissingRequestState(): void
    {
        $this->requestStub->query = ['code' => 'some_code'];
        $this->sessionStub->method('__isset')->willReturn(true);
        $this->assertFalse($this->oauthAuthentication->checkCallback());
    }

    public function testCheckCallbackWithMismatchedState(): void
    {
        $this->requestStub->query = ['state' => 'wrong_state', 'code' => 'some_code'];
        $this->sessionStub->method('__isset')->willReturn(true);
        $this->sessionStub->method('__get')->willReturn('correct_state');
        $this->assertFalse($this->oauthAuthentication->checkCallback());
    }

    public function testCheckCallbackWithMissingCode(): void
    {
        $this->requestStub->query = ['state' => 'test_state'];
        $this->sessionStub->method('__isset')->willReturn(true);
        $this->sessionStub->method('__get')->willReturn('test_state');
        $this->assertFalse($this->oauthAuthentication->checkCallback());
    }

    public function testCheckCallbackWithUserNotFound(): void
    {
        $this->requestStub->query = ['state' => 'test_state', 'code' => 'valid_code'];
        $this->sessionStub->method('__isset')->willReturn(true);
        $this->sessionStub->method('__get')->willReturn('test_state');
        $this->oauthWrapperStub->method('getAuthenticableIdentifier')->willReturn('user@example.com');
        $this->authenticableModelStub->method('getValidAuthenticableInterfaceByIdentifier')->willReturn(null);
        $this->assertFalse($this->oauthAuthentication->checkCallback());
    }

    public function testCheckCallbackSuccess(): void
    {
        $this->requestStub->query = ['state' => 'test_state', 'code' => 'valid_code'];
        $this->sessionStub->method('__isset')->willReturn(true);
        $this->sessionStub->method('__get')->willReturn('test_state');
        $this->oauthWrapperStub->method('getAuthenticableIdentifier')->willReturn('user@example.com');
        $this->authenticableModelStub->method('getValidAuthenticableInterfaceByIdentifier')->willReturn($this->authenticableInterfaceStub);
        $this->assertTrue($this->oauthAuthentication->checkCallback());
        $this->assertEquals($this->authenticableInterfaceStub, $this->oauthAuthentication->getAuthenticableInterface());
    }
}
