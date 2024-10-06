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

namespace SismaFramework\Structural\Controllers;

use SismaFramework\Core\Enumerations\ResponseType;
use SismaFramework\Core\HelperClasses\Render;
use SismaFramework\Core\HelperClasses\Router;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\Interfaces\Controllers\StructuralControllerInterface;
use SismaFramework\Security\BaseClasses\BaseException;

/**
 * @author Valentino de Lapa
 */
class FrameworkController implements StructuralControllerInterface
{
    protected array $vars;
    
    public function __construct()
    {
        $this->vars['controllerUrl'] = Router::getControllerUrl();
        $this->vars['actionUrl'] = Router::getActionUrl();
        $this->vars['metaUrl'] = Router::getMetaUrl();
        $this->vars['actualCleanUrl'] = Router::getActualCleanUrl();
        $this->vars['rootUrl'] = Router::getRootUrl();
    }
    
    #[\Override]
    public function internalServerError():Response
    {
        Render::setStructural();
        return Render::generateData('framework/internalServerError', $this->vars, ResponseType::httpInternalServerError);
    }

    #[\Override]
    public function throwableError(\Throwable $throwable): Response
    {
        $this->vars['project'] = \Config\PROJECT;
        $this->vars['error'] = [
            'message' => $throwable->getMessage(),
            'type' => $throwable->getCode(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
        ];
        $this->vars['backtrace'] = $throwable->getTrace();
        Render::setStructural();
        return Render::generateData('framework/visibleError', $this->vars, ($throwable instanceof BaseException) ? $throwable->getResponseType() : ResponseType::httpInternalServerError);
    }

    #[\Override]
    public function nonThrowableError(array $error, array $backtrace): Response
    {
        $this->vars['project'] = \Config\PROJECT;
        $this->vars['error'] = $error;
        $this->vars['backtrace'] = $backtrace;
        Render::setStructural();
        return Render::generateData('framework/visibleError', $this->vars, ResponseType::httpInternalServerError);
    }
}
