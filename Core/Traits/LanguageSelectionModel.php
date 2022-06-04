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

namespace SismaFramework\Core\Traits;

use SismaFramework\Core\Enumerations\Language;
use SismaFramework\Orm\Enumerations\Keyword;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\ProprietaryTypes\SismaCollection;

/**
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
trait LanguageSelectionModel
{

    public function getEntityCollectionByLanguage(?Language $language = null): SismaCollection
    {
        $class = get_class($this->entity);
        $query = $class::initQuery();
        $query->setWhere();
        $bindValues = $bindTypes = [];
        if ($language !== null){
            $query->appendCondition('language', ComparisonOperator::equal, Keyword::placeholder);
            $bindValues = [$language];
            $bindTypes = [DataType::typeEnumeration];
        }
        $query->close();
        $result = $class::find($query, $bindValues, $bindTypes);
        $collection = new SismaCollection;
        foreach ($result as $entity) {
            $collection->append($entity);
        }
        return $collection;
    }

}
