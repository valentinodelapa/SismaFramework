<?php

/*
 * The MIT License
 *
 * Copyright 2025 Valentino de Lapa <valentino.delapa@gmail.com>.
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

namespace SismaFramework\Orm\HelperClasses;

use SismaFramework\Orm\BaseClasses\BaseEntity;

/**
 * Description of ProcessedEntityCollection
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class ProcessedEntitiesCollection extends \ArrayObject
{

    private static self $instance;

    public static function getInstance(): self
    {
        if (isset(self::$instance) === false) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function has(BaseEntity $entity): bool
    {
        return in_array($entity, $this->getArrayCopy(), true);
    }
    
    public function remove(BaseEntity $entity):void
    {
        $key = array_search($entity, $this->getArrayCopy(), true);
        if($key !== false){
            $this->offsetUnset($key);
        }
    }
    
    public function clear():void
    {
        $this->exchangeArray([]);
    }
}
