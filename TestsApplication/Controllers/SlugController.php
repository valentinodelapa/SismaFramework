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

namespace SismaFramework\TestsApplication\Controllers;

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\HelperClasses\Render;
use SismaFramework\Core\HelperClasses\Router;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\Interfaces\Controllers\CallableController;

/**
 * Description of CallableController
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class SlugController extends BaseController implements CallableController
{

    public function index(): Response
    {
        return Render::generateData('controllerWithSlug/index', $this->vars);
    }

    #[\Override]
    public function __call(string $name, array $arguments)
    {
        $stringArguments = implode('/', $arguments);
        $this->vars['slug'] = Router::getControllerUrl() . '/' . $name . '/' . $stringArguments;
        return Render::generateView('controllerWithSlug/call', $this->vars);
    }

    #[\Override]
    public static function checkCompatibility(array $arguments): bool
    {
        if ((count($arguments) === 1) && ($arguments[0] === 'single-page-slug')) {
            return true;
        } elseif ((count($arguments) === 2) && ($arguments[0] === 'category-slug') && ($arguments[1] === 'child-page-slug')) {
            return true;
        } elseif ((count($arguments) === 3) && ($arguments[0] === 'category-slug') && ($arguments[1] === 'subcategory-slug') && ($arguments[2] === 'child-page-slug')) {
            return true;
        } else {
            return false;
        }
    }
}
