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

namespace SismaFramework\Core\ExtendedClasses;

use SismaFramework\Core\ExtendedClasses\ReferencedEntity;
use SismaFramework\Core\ProprietaryTypes\SismaCollection;
use SismaFramework\Core\ObjectRelationalMapper\Adapter;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
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
