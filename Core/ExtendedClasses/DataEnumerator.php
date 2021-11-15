<?php

namespace Sisma\Core\ExtendedClasses;

use Sisma\Core\BaseClasses\BaseEnumerator;
use Sisma\Core\HelperClasses\Session;

abstract class DataEnumerator extends BaseEnumerator
{

    public function getAdditionalData(string $additionalDataName = 'name'): string|array|self
    {
        $additionalData = $this->setAdditionalData();
        if (isset($additionalData[strval($this->__toString())][$additionalDataName])) {
            return $additionalData[strval($this->__toString())][$additionalDataName];
        } elseif (isset($additionalData[strval($this->__toString())][Session::getItem('lang')][$additionalDataName])) {
            return $additionalData[strval($this->__toString())][Session::getItem('lang')][$additionalDataName];
        } else {
            return null;
        }
    }

    public function hasAdditionalData(): bool
    {
        if (isset($additionalData[strval($this->__toString())][$additionalDataName])) {
            return true;
        } elseif (isset($additionalData[strval($this->__toString())][Session::getItem('lang')][$additionalDataName])) {
            return true;
        } else {
            return false;
        }
    }

    public static function getChoiceFromEnum($additionalDataName = 'name'): array
    {
        $className = get_called_class();
        $enumArray = self::toArray();
        $choice = [];
        foreach ($enumArray as $value) {
            $enumObject = new $className($value);
            $choice[$enumObject->getAdditionalData($additionalDataName)] = $value;
        }
        return $choice;
    }

    abstract protected function setAdditionalData(): array;
}
