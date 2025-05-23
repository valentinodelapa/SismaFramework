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

use SismaFramework\Core\AbstractClasses\Submittable;
use SismaFramework\Core\Enumerations\RequestType;
use SismaFramework\Core\HelperClasses\Encryptor;
use SismaFramework\Core\HelperClasses\Filter;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Security\Interfaces\Entities\MultiFactorInterface;
use SismaFramework\Security\Interfaces\Entities\AuthenticableInterface;
use SismaFramework\Security\Interfaces\Models\MultiFactorModelInterface;
use SismaFramework\Security\Interfaces\Models\MultiFactorRecoveryModelInterface;
use SismaFramework\Security\Interfaces\Models\PasswordModelInterface;
use SismaFramework\Security\Interfaces\Models\AuthenticableModelInterface;
use SismaFramework\Security\Interfaces\Wrappers\MultiFactorWrapperInterface;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Core\HelperClasses\Session;

/**
 *
 * @author Valentino de Lapa
 */
class Authentication extends Submittable
{

    private MultiFactorModelInterface $multiFactorModelInterface;
    private MultiFactorRecoveryModelInterface $multiFactorRecoveryModelInterface;
    private MultiFactorWrapperInterface $multiFactorWrapperInterface;
    private RequestType $requestType;
    private PasswordModelInterface $passwordModelInterface;
    private ?AuthenticableInterface $authenticableInterface;
    private AuthenticableModelInterface $authenticableModelInterface;
    private Filter $filter;

    public function __construct(Request $request)
    {
        parent::__construct();
        $this->request = $request;
        $this->requestType = RequestType::from($request->server['REQUEST_METHOD']);
        $this->filter = new Filter();
    }

    public function setAuthenticableModelInterface(AuthenticableModelInterface $authenticableModelInterface): void
    {
        $this->authenticableModelInterface = $authenticableModelInterface;
    }

    public function setPasswordModelInterface(PasswordModelInterface $passwordModelInterface): void
    {
        $this->passwordModelInterface = $passwordModelInterface;
    }

    public function setMultiFactorModelInterface(MultiFactorModelInterface $multiFactorModelInterface): void
    {
        $this->multiFactorModelInterface = $multiFactorModelInterface;
    }

    public function setMultiFactorRecoveryModelInterface(MultiFactorRecoveryModelInterface $multiFactorRecoveryModelInterface): void
    {
        $this->multiFactorRecoveryModelInterface = $multiFactorRecoveryModelInterface;
    }

    public function setMultiFactorWrapperInterface(MultiFactorWrapperInterface $multiFactorWrapperInterface): void
    {
        $this->multiFactorWrapperInterface = $multiFactorWrapperInterface;
    }

    public function checkAuthenticable(bool $withCsrfToken = true): bool
    {
        if ($withCsrfToken && ($this->checkCsrfToken() === false)) {
            return false;
        }
        if (array_key_exists('identifier', $this->request->request) && $this->filter->isString($this->request->request['identifier'])) {
            $this->authenticableInterface = $this->authenticableModelInterface->getValidAuthenticableInterfaceByIdentifier($this->request->request['identifier']);
            if (($this->authenticableInterface instanceof AuthenticableInterface) && $this->checkPassword($this->authenticableInterface)) {
                return true;
            } else {
                $this->formFilterError->passwordError = true;
            }
        }
        $this->formFilterError->usernameError = true;
        return false;
    }

    public function checkCsrfToken(): bool
    {
        if (Session::hasItem('csrfToken') === false) {
            return false;
        } elseif (array_key_exists('csrfToken', $this->request->request) === false) {
            return false;
        } else {
            return $this->request->request['csrfToken'] === Session::getItem('csrfToken');
        }
    }

    public function checkPassword(AuthenticableInterface $authenticableInterface): bool
    {
        if (array_key_exists('password', $this->request->request) && $this->filter->isString($this->request->request['password'])) {
            $passwordInterface = $this->passwordModelInterface->getPasswordByAuthenticableInterface($authenticableInterface);
            return Encryptor::verifyBlowfishHash($this->request->request['password'], $passwordInterface->password);
        }
        return false;
    }

    public function checkMultiFactor(AuthenticableInterface $authenticableInterface): bool
    {
        if (array_key_exists('code', $this->request->request) && $this->filter->isString($this->request->request['code'])) {
            $multiFactorInterface = $this->multiFactorModelInterface->getLastActiveMultiFactorByAuthenticableInterface($authenticableInterface);
            if ($this->multiFactorWrapperInterface->testCodeForLogin($multiFactorInterface, $this->request->request['code'])) {
                return true;
            } elseif ($this->checkMultiFactorRecovery($multiFactorInterface, $this->request->request['code'])) {
                return true;
            } else {
                $this->formFilterError->codeError = true;
            }
        }
        $this->formFilterError->codeError = true;
        return false;
    }

    public function checkMultiFactorRecovery(MultiFactorInterface $multiFactorInterface, string $code, DataMapper $dataMapper = new DataMapper()): bool
    {
        $result = false;
        $multiFactorRecoveryInterfaceCollection = $this->multiFactorRecoveryModelInterface->getMultiFactorRecoveryInterfaceCollectionByMultiFactorInterface($multiFactorInterface);
        foreach ($multiFactorRecoveryInterfaceCollection as $multiFactorRecoveryInterface) {
            if (Encryptor::verifyBlowfishHash($code, $multiFactorRecoveryInterface->token)) {
                $dataMapper->delete($multiFactorRecoveryInterface);
                $result = true;
            }
        }
        return $result;
    }

    public function getAuthenticableInterface(): AuthenticableInterface
    {
        return $this->authenticableInterface;
    }
}