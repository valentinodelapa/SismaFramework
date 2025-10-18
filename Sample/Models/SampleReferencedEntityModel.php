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

namespace SismaFramework\Sample\Models;

use SismaFramework\Orm\BaseClasses\BaseModel;
use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\Enumerations\Placeholder;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Sample\Entities\SampleReferencedEntity;

/**
 * Model per SampleReferencedEntity - Gestione Autori
 *
 * Questo model mostra:
 * - Come usare BaseModel per entità senza dipendenze
 * - Ricerca per nome completo
 * - Query su autori verificati
 * - L'entity è ReferencedEntity per supportare collezioni inverse, ma il Model è BaseModel
 *
 * @author Valentino de Lapa
 */
class SampleReferencedEntityModel extends BaseModel
{
    /**
     * Implementa la ricerca testuale su nome completo
     */
    #[\Override]
    protected function appendSearchCondition(Query &$query, string $searchKey, array &$bindValues, array &$bindTypes): void
    {
        $query->appendCondition('fullName', ComparisonOperator::like, Placeholder::placeholder);
        $bindValues[] = '%' . $searchKey . '%';
        $bindTypes[] = DataType::typeString;
    }

    #[\Override]
    protected function getEntityName(): string
    {
        return SampleReferencedEntity::class;
    }

    /**
     * Recupera solo autori verificati
     *
     * @return SismaCollection<SampleReferencedEntity>
     */
    public function getVerifiedAuthors(): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere()
              ->appendCondition('verified', ComparisonOperator::equal, Placeholder::placeholder);
        $query->setOrderBy(['fullName' => 'ASC']);

        $bindValues = [true];
        $bindTypes = [DataType::typeBoolean];

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }
}
