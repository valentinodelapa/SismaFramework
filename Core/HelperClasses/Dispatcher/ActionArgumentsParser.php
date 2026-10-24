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

namespace SismaFramework\Core\HelperClasses\Dispatcher;

use SismaFramework\Core\Exceptions\BadRequestException;
use SismaFramework\Core\HelperClasses\NotationManager;
use SismaFramework\Core\HelperClasses\Parser;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Security\HttpClasses\Authentication;

/**
 * @internal
 *
 * @author Valentino de Lapa
 */
class ActionArgumentsParser
{

    private Request $request;
    private DataMapper $dataMapper;

    public function __construct(Request $request, DataMapper $dataMapper)
    {
        $this->request = $request;
        $this->dataMapper = $dataMapper;
    }

    public function parseActionArguments(array $urlArguments, array $reflectionParameters): array
    {
        $queryArguments = [];
        foreach ($this->request->query ?? [] as $key => $value) {
            $queryArguments[NotationManager::convertToCamelCase($key)] = $value;
        }
        $associativeArguments = array_merge($queryArguments, $this->convertUrlArgumentsToAssociative($urlArguments));
        $this->request->query = $associativeArguments;
        if (count($reflectionParameters) === 0) {
            return [];
        }
        return $this->mapArgumentsToParameters($associativeArguments, $reflectionParameters);
    }

    private function convertUrlArgumentsToAssociative(array $urlArguments): array
    {
        $keys = [];
        $values = [];
        $nestedQueryParts = [];
        $this->removeLeadingEmptyArguments($urlArguments);
        while (count($urlArguments) > 1) {
            $key = $this->convertArgumentKey(urldecode(array_shift($urlArguments)));
            $value = urldecode(array_shift($urlArguments));
            if (str_contains($key, '[')) {
                $nestedQueryParts[] = urlencode($key) . '=' . urlencode($value);
            } else {
                $keys[] = $key;
                $values[] = $value;
            }
            $this->removeLeadingEmptyArguments($urlArguments);
        }
        parse_str(implode('&', $nestedQueryParts), $nestedArguments);
        return array_merge($this->combineArrayToAssociativeArray($keys, $values), $nestedArguments);
    }

    private function removeLeadingEmptyArguments(array &$urlArguments): void
    {
        while (reset($urlArguments) === '') {
            array_shift($urlArguments);
        }
    }

    private function convertArgumentKey(string $key): string
    {
        $bracketPosition = strpos($key, '[');
        if (($bracketPosition === false) || ($bracketPosition === 0)) {
            return NotationManager::convertToCamelCase($key);
        } else {
            return NotationManager::convertToCamelCase(substr($key, 0, $bracketPosition)) . substr($key, $bracketPosition);
        }
    }

    private function combineArrayToAssociativeArray(array $keys, array $values): array
    {
        $result = [];
        foreach ($keys as $index => $key) {
            if (array_key_exists($key, $result)) {
                if (!is_array($result[$key])) {
                    $result[$key] = [$result[$key]];
                }
                $result[$key][] = $values[$index];
            } else {
                $result[$key] = $values[$index];
            }
        }
        return $result;
    }

    private function mapArgumentsToParameters(array $associativeArguments, array $reflectionParameters): array
    {
        $result = [];
        foreach ($reflectionParameters as $parameter) {
            $paramType = $parameter->getType()->getName();
            $paramName = $parameter->name;
            if ($paramType === Request::class) {
                $result[$paramName] = $this->request;
            } elseif ($paramType === Authentication::class) {
                $result[$paramName] = new Authentication($this->request);
            } elseif (array_key_exists($paramName, $associativeArguments)) {
                $result[$paramName] = Parser::parseValue($parameter->getType(), $associativeArguments[$paramName], true, $this->dataMapper);
            } elseif ($parameter->isDefaultValueAvailable()) {
                $result[$paramName] = $parameter->getDefaultValue();
            } else {
                throw new BadRequestException("Missing required parameter: " . $paramName);
            }
        }
        return $result;
    }
}
