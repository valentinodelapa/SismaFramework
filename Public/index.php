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

use SismaFramework\Core\Exceptions\PhpVersionException;
use SismaFramework\Core\HelperClasses\Debugger;
use SismaFramework\Core\HelperClasses\Dispatcher;
use SismaFramework\Core\HelperClasses\ErrorHandler;
use SismaFramework\Core\HelperClasses\PhpVersionChecker;
use SismaFramework\Core\HelperClasses\Session;
use SismaFramework\Security\BaseClasses\BaseException;
use SismaFramework\Security\ExtendedClasses\RedirectException;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Autoload' . DIRECTORY_SEPARATOR . 'autoload.php';

date_default_timezone_set('Europe/Rome');
ErrorHandler::disabligErrorDisplay();

try {
    ErrorHandler::handleNonThrowableError();
    Debugger::startExecutionTimeCalculation();
    PhpVersionChecker::checkPhpVersion();
    Session::start();
    header('Pragma: cache');
    header('Cache-control: public');
    $dispatcher = new Dispatcher();
    return $dispatcher->run();
} catch (PhpVersionException $exception) {
    echo $exception->getMessage();
} catch (RedirectException $exception) {
    return $exception->redirect();
} catch (BaseException $exception) {
    ErrorHandler::handleBaseException($exception);
} catch (\Throwable $throwable) {
    ErrorHandler::handleThrowableError($throwable);
} finally {
    ErrorHandler::showErrorInDevelopementEnvironment();
}
