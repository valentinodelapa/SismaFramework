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

namespace SismaFramework\Core\BaseClasses;

use SismaFramework\Core\Enumerations\PermissionAttribute;
use SismaFramework\Core\Exceptions\AccessDeniedException;
use SismaFramework\Security\Interfaces\Entities\AuthenticableInterface;

/**
 *
 * @author Valentino de Lapa
 */
abstract class BasePermission
{

    private static BasePermission $instance;
    protected mixed $subject;
    protected PermissionAttribute $attribute;
    protected ?AuthenticableInterface $authenticable;
    protected bool $result = true;

    public function __construct(mixed $subject, PermissionAttribute $attribute, ?AuthenticableInterface $authenticable = null)
    {
        $this->subject = $subject;
        $this->attribute = $attribute;
        $this->authenticable = $authenticable;
        $this->callParentPermissions();
        $this->result = ($this->isInstancePermitted() === false) ? false : $this->result;
        $this->checkResult();
        $this->result = ($this->checkPermmisions() === false) ? false : $this->result;
        $this->checkResult();
    }
    
    abstract protected function callParentPermissions():void;

    abstract protected function isInstancePermitted(): bool;

    abstract protected function checkPermmisions(): bool;

    protected function checkResult(): void
    {
        if ($this->result === false) {
            throw new AccessDeniedException();
        }
    }

    static public function isAllowed(mixed $subject, PermissionAttribute $attribute, ?AuthenticableInterface $authenticable = null):void
    {
        $class = get_called_class();
        self::$instance = new $class($subject, $attribute, $authenticable);
    }

}
