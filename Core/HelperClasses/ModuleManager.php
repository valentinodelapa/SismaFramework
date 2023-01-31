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

namespace SismaFramework\Core\HelperClasses;

use SismaFramework\Core\Enumerations\Resource;
use SismaFramework\Core\Exceptions\ModuleException;

/**
 * 
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class ModuleManager
{

    private static string $applicationModule = '';
    private static ?string $customVisualizationModule = null;
    private static bool $customVisualizationFileExists;

    public static function getModuleList(): array
    {
        return \Config\MODULE_FOLDERS;
    }

    public static function setApplicationModule(string $module): void
    {
        self::$applicationModule = $module;
    }

    public static function getApplicationModule(): string
    {
        return self::$applicationModule;
    }

    public static function setCustomVisualizationModule(string $module): void
    {
        self::$customVisualizationModule = $module;
    }

    public static function unsetCustomVisualizationModule(): void
    {
        self::$customVisualizationModule = null;
    }

    public static function getExistingFilePath(string $path, Resource $resource): string
    {
        if ((empty(self::$customVisualizationModule) === false) && file_exists(\Config\ROOT_PATH . self::$customVisualizationModule . DIRECTORY_SEPARATOR . $path . '.' . $resource->value)) {
            self::$customVisualizationFileExists = true;
            return \Config\ROOT_PATH . self::$customVisualizationModule . DIRECTORY_SEPARATOR . $path . '.' . $resource->value;
        } elseif (file_exists(\Config\ROOT_PATH . self::$applicationModule . DIRECTORY_SEPARATOR . $path . '.' . $resource->value)) {
            self::$customVisualizationFileExists = false;
            return \Config\ROOT_PATH . self::$applicationModule . DIRECTORY_SEPARATOR . $path . '.' . $resource->value;
        } else {
            throw new ModuleException('File non trovato');
        }
    }

    public static function getConsequentFilePath(string $path, Resource $resource): string
    {
        if (self::$customVisualizationFileExists && file_exists(\Config\ROOT_PATH . self::$customVisualizationModule . DIRECTORY_SEPARATOR . $path . '.' . $resource->value)) {
            return \Config\ROOT_PATH . self::$customVisualizationModule . DIRECTORY_SEPARATOR . $path . '.' . $resource->value;
        } elseif ((self::$customVisualizationFileExists === false) && file_exists(\Config\ROOT_PATH . self::$applicationModule . DIRECTORY_SEPARATOR . $path . '.' . $resource->value)) {
            return \Config\ROOT_PATH . self::$applicationModule . DIRECTORY_SEPARATOR . $path . '.' . $resource->value;
        } else {
            throw new ModuleException('File non trovato');
        }
    }

}
