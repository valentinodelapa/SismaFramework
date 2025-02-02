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

use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Orm\HelperClasses\DataMapper;

/**
 *
 * @author Valentino de Lapa
 */
class FixturesManager
{

    private array $fixturesArray;
    private array $entitiesArray = [];
    private DataMapper $dataMapper;
    
    private static bool $developmentEnvironment = \Config\DEVELOPMENT_ENVIRONMENT;
    private static string $fixtures = \Config\FIXTURES;
    private static string $fixtureNamespace = \Config\FIXTURE_NAMESPACE;
    private static string $fixturePath = \Config\FIXTURE_PATH;
    private static string $rootPath = \Config\ROOT_PATH;

    public function __construct(DataMapper $dataMapper = new DataMapper())
    {
        $this->dataMapper = $dataMapper;
    }

    public function isFixtures(array $pathParts): bool
    {
        if (count($pathParts) === 1) {
            return ($pathParts[0] === strtolower(self::$fixtures)) && self::$developmentEnvironment;
        } else {
            return false;
        }
    }

    public function run(): Response
    {
        $this->getFixturesArray();
        $this->executeFixturesArray(array_keys($this->fixturesArray));
        return new Response();
    }

    public function extecuted(): bool
    {
        $result = true;
        foreach ($this->fixturesArray as $fixture) {
            if ($fixture === false) {
                $result = false;
            }
        }
        return $result;
    }

    private function getFixturesArray(): void
    {
        foreach (ModuleManager::getModuleList() as $module) {
            $fixturesFiles = scandir(self::$rootPath . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . self::$fixturePath);
            foreach ($fixturesFiles as $file) {
                if (($file != '.') && ($file != '..')) {
                    $fixtureName = str_replace('.php', '', $file);
                    $this->fixturesArray[$module . '\\' . self::$fixtureNamespace . $fixtureName] = false;
                }
            }
        }
    }

    private function executeFixturesArray(array $fixturesArray): void
    {
        foreach ($fixturesArray as $fixture) {
            $this->executeFixture($fixture);
        }
    }

    private function executeFixture($fixture): void
    {
        if ($this->fixturesArray[$fixture] === false) {
            $fixtureInstance = new $fixture($this->dataMapper);
            if (count($fixtureInstance->getDependencies()) > 0) {
                $this->executeFixturesArray($fixtureInstance->getDependencies());
            }
            $this->entitiesArray[$fixture] = $fixtureInstance->execute($this->entitiesArray);
            $this->fixturesArray[$fixture] = true;
        }
    }
}
