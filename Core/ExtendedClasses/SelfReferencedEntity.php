<?php

namespace SismaFramework\Core\ExtendedClasses;

use SismaFramework\Core\ExtendedClasses\ReferencedEntity;
use SismaFramework\Core\ProprietaryTypes\SismaCollection;
use SismaFramework\Core\ObjectRelationalMapper\Adapter;

abstract class SelfReferencedEntity extends ReferencedEntity
{

    protected SismaCollection $sonCollection;

    private const SONS_PROPERTY_NAME = 'sonCollection';
    private const PARENT_PREFIX_PROPERTY_NAME = 'parent';

    public function __construct(Adapter &$adapter = null)
    {
        parent::__construct($adapter);
        $this->sonCollection = new SismaCollection();
    }

    public static function getCollectionDataInformation(string $collectionName, string $information): string
    {
        $calledClassNamePartes = explode("\\", static::class);
        self::addCollectionData(self::SONS_PROPERTY_NAME, static::class, self::PARENT_PREFIX_PROPERTY_NAME . end($calledClassNamePartes));
        return parent::getCollectionDataInformation($collectionName, $information);
    }

}
