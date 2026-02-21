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

namespace SismaFramework\Console\Services\Fixtures;

use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\ModuleManager;
use SismaFramework\Orm\HelperClasses\DataMapper;

/**
 * @author Valentino de Lapa
 */
class FixturesManager
{

    private Config $config;
    private array $fixturesArray;
    private array $entitiesArray = [];
    private DataMapper $dataMapper;

    public function __construct(DataMapper $dataMapper = new DataMapper(), ?Config $customConfig = null)
    {
        $this->dataMapper = $dataMapper;
        $this->config = $customConfig ?? Config::getInstance();
    }

    public function run(): void
    {
        $this->getFixturesArray();
        $this->executeFixturesArray(array_keys($this->fixturesArray));
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
            $fixturesFiles = scandir($this->config->rootPath . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . $this->config->fixturePath);
            foreach ($fixturesFiles as $file) {
                if (($file != '.') && ($file != '..')) {
                    $fixtureName = str_replace('.php', '', $file);
                    $this->fixturesArray[$module . '\\' . $this->config->fixtureNamespace . $fixtureName] = false;
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
