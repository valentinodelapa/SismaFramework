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

use SismaFramework\Core\HelperClasses\Debugger;
use SismaFramework\Core\Services\RouterService;
use SismaFramework\Core\Services\RenderService;
use SismaFramework\Orm\HelperClasses\DataMapper;

/**
 * @author Valentino de Lapa
 */
abstract class BaseController
{
    protected DataMapper $dataMapper;
    protected Debugger $debugger;
    protected RouterService $router;
    protected RenderService $render;
    protected array $vars;

    public function __construct(DataMapper $dataMapper = new DataMapper(), Debugger $debugger = new Debugger())
    {
        $this->dataMapper = $dataMapper;
        $this->debugger = $debugger;
        $this->router = RouterService::getInstance();
        $this->render = RenderService::getInstance();
        $this->vars['controllerUrl'] = $this->router->getControllerUrl();
        $this->vars['actionUrl'] = $this->router->getActionUrl();
        $this->vars['metaUrl'] = $this->router->getMetaUrl();
        $this->vars['actualCleanUrl'] = $this->router->getActualCleanUrl();
        $this->vars['rootUrl'] = $this->router->getRootUrl();
    }
}
