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
    if (\Config\DEVELOPMENT_ENVIRONMENT) {
        echo $exception->getCode().' - '.$exception->getMessage().' - '.$exception->getFile().'(' . $exception->getLine() . ')'."<br />";
        foreach ($exception->getTrace() as $call) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;" . ($call['class'] ?? '') . ($call['type'] ?? '') . ($call['function'] ?? '') . "<br />";
            echo isset($call['file']) ? "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $call['file'] . '(' . $call['line'] . ')' . "<br />" : '';
        }
    } else {
        $sampleController = new SampleController();
        $sampleController->error('');
    }
} catch (\Throwable $throwable) {
    $response = new Response();
    $response->setResponseType(ResponseType::httpInternalServerError);
    Logger::saveLog($throwable->getMessage(), $throwable->getCode(), $throwable->getFile(), $throwable->getLine());
    if (\Config\DEVELOPMENT_ENVIRONMENT) {
        Logger::saveTrace($throwable->getTrace());
        echo $throwable->getCode().' - '.$throwable->getMessage().' - '.$throwable->getFile().'(' . $throwable->getLine() . ')'."<br />";
        foreach ($throwable->getTrace() as $call) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;" . ($call['class'] ?? '') . ($call['type'] ?? '') . ($call['function'] ?? '') . "<br />";
            echo isset($call['file']) ? "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $call['file'] . '(' . $call['line'] . ')' . "<br />" : '';
        }
    } else {
        Router::setActualCleanUrl('cms', 'error');
        $sampleController = new SampleController();
        $sampleController->error('');
    }
}
