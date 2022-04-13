<?php

/*
 * The MIT License
 *
 * Copyright 2020 Valentino de Lapa <valentino.delapa@gmail.com>.
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

namespace SismaFramework\Core\ExtendedClasses;

use SismaFramework\Core\Exceptions\MethodNotFoundException;
use SismaFramework\Core\ProprietaryTypes\SismaCollection;
use SismaFramework\Core\ProprietaryTypes\SismaStandardClass;

/**
 * Description of StandardEntity
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class StandardEntity extends SismaStandardClass
{
    public function __call($methodName, $arguments)
    {
        $methodType = substr($methodName, 0, 3);
        $propertyName = lcfirst(substr($methodName, 3));
        switch ($methodType) {
            case 'get':
                return isset($this->$propertyName) ? new SismaCollection($this->$propertyName) : new SismaCollection();
            default:
                throw new MethodNotFoundException('Metodo non trovato');
        }
    }
}