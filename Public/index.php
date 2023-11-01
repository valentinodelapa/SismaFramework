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

namespace SismaFramework\Public;

use SismaFramework\BaseClasses\BaseException;
use SismaFramework\Core\Enumerations\ResponseType;
use SismaFramework\Core\HelperClasses\Dispatcher;
use SismaFramework\Core\HelperClasses\Logger;
use SismaFramework\Core\HelperClasses\Session;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\ExtendedClasses\RedirectException;
use SismaFramework\Sample\Controllers\SampleController;

try {

    require_once(__DIR__ . '/../Config/config.php');
    require_once(__DIR__ . '/../Autoload/autoload.php');

    if (\Config\DEVELOPMENT_ENVIRONMENT) {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL | E_STRICT);
    }

    Session::start();
    $dispatcher = new Dispatcher();
    $dispatcher->run();
} catch (RedirectException $exception) {
    $exception->redirect();
} catch (BaseException $exception) {
    $cmsController = new CmsController();
    $cmsController->error('');
} catch (\Throwable $throwable) {
    Logger::saveLog($throwable->getMessage(), $throwable->getCode());
    if (\Config\DEVELOPMENT_ENVIRONMENT) {
        Logger::saveTrace($throwable->getTrace());
    }
    $response = new Response();
    $response->setResponseType(ResponseType::httpInternalServerError);
    $sampleController = new SampleController();
    $sampleController->error('');
}
