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

namespace SismaFramework\Orm\Exceptions;

use SismaFramework\Orm\BaseClasses\BaseEntity;
use SismaFramework\Core\BaseClasses\BaseException;
use SismaFramework\Core\HelperClasses\Router;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class ReferencedEntityDeletionException extends BaseException
{

    private BaseEntity $entity;

    public function __construct(BaseEntity $entity, string $message = "", int $code = 0, \Throwable $previous = NULL)
    {
        $this->entity = $entity;
        parent::__construct($message, $code, $previous);
    }

    protected function errorRedirect()
    {
        $reflectionEntity = new \ReflectionClass($this->entity);
        $entityShortNameParts = array_diff(preg_split('/(?=[A-Z])/',$reflectionEntity->getShortName()), ['']);
        array_walk($entityShortNameParts, function(&$value){
            $value = strtolower($value);
        });
        $entityShortName = implode('-', $entityShortNameParts);
        return Router::redirect($entityShortName.'/view-relations/'.$entityShortName.'/'.$this->entity->id.'/');
    }

}