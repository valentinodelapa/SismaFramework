<?php

namespace Sisma\Core\ExtendedClasses;

use Sisma\Core\ExtendedClasses\ReferencedEntity;
use Sisma\Core\ProprietaryTypes\SismaCollection;
use Sisma\Core\ObjectRelationalMapper\Adapter;

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
