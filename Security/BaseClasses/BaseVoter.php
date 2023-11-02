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

use SismaFramework\Security\Enumerations\AccessControlEntry;
use SismaFramework\Security\Interfaces\Entities\AuthenticableInterface;

/**
 *
 * @author Valentino de Lapa
 */
abstract class BaseVoter
{

    private static BaseVoter $instance;
    protected mixed $subject;
    protected AccessControlEntry $accessControlEntry;
    protected ?AuthenticableInterface $authenticable = null;
    
    public function setSubject(mixed $subject):void
    {
        $this->subject = $subject;
    }
    
    public function setAccessControlEntry(AccessControlEntry $accessControlEntry):void
    {
        $this->accessControlEntry = $accessControlEntry;
    }
    
    public function setAuthenticable(?AuthenticableInterface $authenticable):void
    {
        $this->authenticable = $authenticable;
    }

    abstract protected function isInstancePermitted(): bool;

    abstract protected function checkVote(): bool;

    public function returnResult(): bool
    {
        return $this->isInstancePermitted() ? $this->checkVote() : false;
    }

    static public function isAllowed(mixed $subject, AccessControlEntry $accessControlEntry, ?AuthenticableInterface $authenticable = null):bool
    {
        $class = get_called_class();
        self::$instance = new $class($subject, $accessControlEntry, $authenticable);
        self::$instance->setSubject($subject);
        self::$instance->setAccessControlEntry($accessControlEntry);
        self::$instance->setAuthenticable($authenticable);
        return self::$instance->returnResult();
    }

}
