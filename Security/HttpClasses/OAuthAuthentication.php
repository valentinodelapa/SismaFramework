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

namespace SismaFramework\Security\HttpClasses;

use SismaFramework\Core\HelperClasses\Filter;
use SismaFramework\Core\HelperClasses\Session;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Security\BaseClasses\BaseAuthentication;
use SismaFramework\Security\Interfaces\Entities\AuthenticableInterface;
use SismaFramework\Security\Interfaces\Models\AuthenticableModelInterface;
use SismaFramework\Security\Interfaces\Wrappers\OAuthWrapperInterface;

/**
 * @author Valentino de Lapa
 */
class OAuthAuthentication extends BaseAuthentication
{
    private AuthenticableModelInterface $authenticableModelInterface;
    private OAuthWrapperInterface $oauthWrapperInterface;

    public function __construct(Request $request, Filter $filter = new Filter(), Session $session = new Session())
    {
        parent::__construct($request, $filter, $session);
    }

    public function setAuthenticableModelInterface(AuthenticableModelInterface $authenticableModelInterface): void
    {
        $this->authenticableModelInterface = $authenticableModelInterface;
    }

    public function setOAuthWrapperInterface(OAuthWrapperInterface $oauthWrapperInterface): void
    {
        $this->oauthWrapperInterface = $oauthWrapperInterface;
    }

    public function getAuthorizationUrl(): string
    {
        $state = bin2hex(random_bytes(16));
        $this->session->oauthState = $state;
        return $this->oauthWrapperInterface->getAuthorizationUrl($state);
    }

    public function checkCallback(): bool
    {
        if (array_key_exists('error', $this->request->query)) {
            return false;
        }
        if ($this->checkOAuthState() === false) {
            return false;
        }
        if (array_key_exists('code', $this->request->query) && $this->filter->isString($this->request->query['code'])) {
            $identifier = $this->oauthWrapperInterface->getAuthenticableIdentifier($this->request->query['code']);
            $this->authenticableInterface = $this->authenticableModelInterface->getValidAuthenticableInterfaceByIdentifier($identifier);
            return $this->authenticableInterface instanceof AuthenticableInterface;
        }
        return false;
    }

    private function checkOAuthState(): bool
    {
        if (isset($this->session->oauthState) === false) {
            return false;
        }
        if (array_key_exists('state', $this->request->query) === false) {
            return false;
        }
        if ($this->filter->isString($this->request->query['state']) === false) {
            return false;
        }
        return hash_equals($this->session->oauthState, $this->request->query['state']);
    }
}
