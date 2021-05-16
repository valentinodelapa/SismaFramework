<?php

namespace Sisma\Core\Enumerators;

use Sisma\Core\ExtendedClasses\DataEnumerator;
use Sisma\Core\Interfaces\ExtendedEnumeratorInterface;

class Language extends DataEnumerator
{

    private const ITALIAN = 'it_IT';
    private const AMERICAN_ENGLISH = 'en_US';
    private const SPANISH = 'es_ES';
    private const GERMAN = 'de_DE';

    protected function setAdditionalData()
    {
        $additionalData = [
            self::ITALIAN => [
                self::ITALIAN()->__toString() => [
                    'name' => 'Italiano',
                ],
                self::AMERICAN_ENGLISH()->__toString() => [
                    'name' => 'Italian',
                ],
                self::SPANISH()->__toString() => [
                    'name' => 'Italiano',
                ],
                self::GERMAN()->__toString() => [
                    'name' => 'Italienisch',
                ],
            ],
            self::AMERICAN_ENGLISH => [
                self::ITALIAN()->__toString() => [
                    'name' => 'Inglese americano',
                ],
                self::AMERICAN_ENGLISH()->__toString() => [
                    'name' => 'American english',
                ],
                self::SPANISH()->__toString() => [
                    'name' => 'Inglés americano',
                ],
                self::GERMAN()->__toString() => [
                    'name' => 'Amerikanisches englisch',
                ],
            ],
            self::SPANISH => [
                self::ITALIAN()->__toString() => [
                    'name' => 'Spagnolo',
                ],
                self::AMERICAN_ENGLISH()->__toString() => [
                    'name' => 'Spanish',
                ],
                self::SPANISH()->__toString() => [
                    'name' => 'Español',
                ],
                self::GERMAN()->__toString() => [
                    'name' => 'Spanisch',
                ],
            ],
            self::GERMAN => [
                self::ITALIAN()->__toString() => [
                    'name' => 'Tedesco',
                ],
                self::AMERICAN_ENGLISH()->__toString() => [
                    'name' => 'Alemán',
                ],
                self::SPANISH()->__toString() => [
                    'name' => 'Alemán',
                ],
                self::GERMAN()->__toString() => [
                    'name' => 'German',
                ],
            ],
        ];
        return $additionalData;
    }

}
