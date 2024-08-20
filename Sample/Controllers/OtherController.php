<?php

/*
 * The MIT License
 *
 * Copyright 2023 Valentino de Lapa.
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

namespace SismaFramework\Sample\Controllers;

use SismaFramework\Core\BaseClasses\BaseController;
use SismaFramework\Core\Enumerations\ResponseType;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\HelperClasses\Render;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Security\HttpClasses\Authentication;

/**
 * @author Valentino de Lapa
 */
class OtherController extends BaseController
{

    private Request $request;

    public function __construct(Request $request, DataMapper $dataMapper = new DataMapper())
    {
        parent::__construct($dataMapper);
        $this->request = $request;
    }

    public function index(string $parameter): Response
    {
        $this->vars['parameter'] = urldecode($parameter);
        return Render::generateView('other/index', $this->vars, ResponseType::httpOk);
    }

    public function actionWithRequest(Request $request): Response
    {
        $this->vars['request'] = $request;
        return Render::generateView('other/actionWithRequest', $this->vars, ResponseType::httpOk);
    }

    public function actionWithAuthentication(Authentication $authentication): Response
    {
        $this->vars['authentication'] = $authentication->isSubmitted();
        return Render::generateView('other/actionWithAuthentication', $this->vars, ResponseType::httpOk);
    }

    public function actionWithDefaultValue(bool $isDefault = true): Response
    {
        $this->vars['isDefault'] = $isDefault;
        return Render::generateView('other/actionWithDefaultValue', $this->vars, ResponseType::httpOk);
    }

    public function actionWithArray(array $array): Response
    {
        $this->vars['array'] = $array;
        return Render::generateView('other/actionWithArray', $this->vars, ResponseType::httpOk);
    }

}
