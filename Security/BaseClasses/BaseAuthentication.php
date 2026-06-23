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

namespace SismaFramework\Security\BaseClasses;

use SismaFramework\Core\HelperClasses\Filter;
use SismaFramework\Core\HelperClasses\Session;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Security\Exceptions\AuthenticationException;
use SismaFramework\Security\Interfaces\Entities\AuthenticableInterface;

/**
 * @internal
 *
 * @author Valentino de Lapa
 */
abstract class BaseAuthentication
{
    protected Request $request;
    protected ?AuthenticableInterface $authenticableInterface = null;
    protected Filter $filter;
    protected Session $session;

    public function __construct(Request $request, Filter $filter = new Filter(), Session $session = new Session())
    {
        $this->request = $request;
        $this->filter = $filter;
        $this->session = $session;
    }

    public function getAuthenticableInterface(): AuthenticableInterface
    {
        if (isset($this->authenticableInterface) && ($this->authenticableInterface instanceof AuthenticableInterface)) {
            return $this->authenticableInterface;
        }
        throw new AuthenticationException();
    }
}
