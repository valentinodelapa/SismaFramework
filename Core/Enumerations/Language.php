<?php

namespace SismaFramework\Core\Enumerations;

use SismaFramework\Core\HelperClasses\Session;

enum Language: string
{
    use \SismaFramework\Core\Traits\DataEnumeration;

    case italian = 'it_IT';
    case americanEnglish = 'en_US';
    case spanish = 'es_ES';
    case german = 'de_DE';
        
    private function getAdditionalData(?self $language = null):string
    {
        if($language === null){
            $language = self::from(\Config\LANGUAGE);
        }
        return match($this){
            self::italian => match($language){
                Language::italian => "Italiano",
                Language::americanEnglish => "Italian",
                Language::spanish => "Italiano",
                Language::german => "Italienisch",
            },
            self::americanEnglish => match($language){
                Language::italian => "Inglese americano",
                Language::americanEnglish => "American english",
                Language::spanish => "Inglés americano",
                Language::german => "Amerikanisches englisch",
            },
            self::spanish => match($language){
                Language::italian => "Spagnolo",
                Language::americanEnglish => "Spanish",
                Language::spanish => "Español",
                Language::german => "Spanisch",
            },
            self::german => match($language){
                Language::italian => "Tedesco",
                Language::americanEnglish => "Alemán",
                Language::spanish => "Alemán",
                Language::german => "German",
            },
        };
    }
    
    private function getFunctionalData():int|string|array|\UnitEnum
    {
        
    }

}
