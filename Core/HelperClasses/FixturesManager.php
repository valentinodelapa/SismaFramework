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

use SismaFramework\Orm\BaseClasses\BaseAdapter;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class FixturesManager
{

    private array $fixturesArray;
    private array $entitiesArray = [];
    private ?BaseAdapter $customAdapter = null;
    
    public function __construct(?BaseAdapter $customAdapter = null)
    {
        $this->customAdapter = $customAdapter;
    }

    public function run()
    {
        $this->getFixturesArray();
        $this->executeFixturesArray($this->fixturesArray);
    }

    private function getFixturesArray(): void
    {
        foreach (ModuleManager::getModuleList() as $module) {
            $fixturesFiles = scandir(\Config\ROOT_PATH . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . \Config\FIXTURE_PATH);
            foreach ($fixturesFiles as $file) {
                if (($file != '.') && ($file != '..')) {
                    $fixtureName = str_replace('.php', '', $file);
                    $this->fixturesArray[$module.'\\'.\Config\FIXTURE_NAMESPACE . $fixtureName] = false;
                }
            }
        }
    }

    private function executeFixturesArray(array $fixturesArray): void
    {
        foreach (array_keys($fixturesArray) as $fixture) {
            $this->executeFixture($fixture);
        }
    }

    private function executeFixture($fixture): void
    {
        if ($this->fixturesArray[$fixture] === false) {
            $fixtureInstance = new $fixture($this->customAdapter);
            if (count($fixtureInstance->getDependencies()) > 0) {
                $this->executeFixturesArray($fixtureInstance->getDependencies());
            }
            $this->entitiesArray[$fixture] = $fixtureInstance->execute($this->entitiesArray);
            $this->fixturesArray[$fixture] = true;
        }
    }

}
