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

namespace SismaFramework\Core\HelperClasses;

use SismaFramework\Orm\BaseClasses\BaseEntity;

/**
 *
 * @author Valentino de Lapa
 */
class NotationManager
{

    public static function convertToStudlyCaps(string $kebabCaseOrSnakeCaseString): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $kebabCaseOrSnakeCaseString)));
    }

    public static function convertToCamelCase(string $kebabCaseOrSnakeCaseString): string
    {
        return lcfirst(self::convertToStudlyCaps($kebabCaseOrSnakeCaseString));
    }

    public static function convertToKebabCase(string $studlyCapsOrCamelCaseString): string
    {
        return implode('-', array_map(function ($value) {
                    return strtolower($value);
                }, array_filter(preg_split('/(?=[A-Z])/', $studlyCapsOrCamelCaseString))));
    }

    public static function convertToSnakeCase(string $studlyCapsOrCamelCaseString): string
    {
        return implode('_', array_map(function ($value) {
                    return strtolower($value);
                }, array_filter(preg_split('/(?=[A-Z])/', $studlyCapsOrCamelCaseString))));
    }

    public static function convertEntityToTableName(BaseEntity $entity): string
    {
        return self::convertEntityNameToTableName(get_class($entity));
    }

    public static function convertEntityNameToTableName(string $entityName): string
    {
        $entityNameParts = explode('\\', $entityName);
        return self::convertToSnakeCase(array_pop($entityNameParts));
    }
    
    public static function convertColumnNameToPropertyName(string $columnName):string
    {
        $parsedColumnName = (substr($columnName, -3, 3) == '_id') ? substr($columnName, 0, -3) : $columnName;
        return self::convertToCamelCase($parsedColumnName);
    }
}
