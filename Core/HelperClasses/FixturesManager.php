<?php

namespace SismaFramework\Core\HelperClasses;

class FixturesManager
{

    private array $fixturesArray;
    private array $entitiesArray = [];

    public function __construct()
    {
        $this->getFixturesArray();
        $this->executeFixturesArray($this->fixturesArray);
    }

    private function getFixturesArray(): void
    {
        $fixturesFiles = scandir(\Config\FIXTURE_PATH);
        foreach ($fixturesFiles as $file) {
            if (($file != '.') && ($file != '..')) {
                $fixtureName = str_replace('.php', '', $file);
                $this->fixturesArray[\Config\FIXTURE_NAMESPACE . $fixtureName] = false;
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
        if ($this->fixturesArray[$fixture] == false) {
            $fixtureInstance = new $fixture();
            if (is_array($fixtureInstance->getDependencies())) {
                $this->executeFixturesArray(array_flip($fixtureInstance->getDependencies()));
            }
            $fixtureInstance->execute($this->entitiesArray);
            $this->fixturesArray[$fixture] = true;
        }
    }

}
