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
use SismaFramework\Sample\Entities\SampleBaseEntity;
use SismaFramework\Sample\Enumerations\ArticleStatus;

/**
 * Model per SampleBaseEntity - Gestione Articoli
 *
 * Questo model mostra:
 * - Come implementare la ricerca testuale
 * - Come creare metodi custom per query specifiche
 * - Come utilizzare il Query Builder
 * - Come gestire filtri per enum
 *
 * @author Valentino de Lapa
 */
class SampleBaseEntityModel extends BaseModel
{
    /**
     * Implementa la logica di ricerca testuale
     *
     * Questo metodo viene chiamato automaticamente quando usi getEntityCollection() con un searchKey.
     * La ricerca viene eseguita su titolo e contenuto.
     */
    #[\Override]
    protected function appendSearchCondition(Query &$query, string $searchKey, array &$bindValues, array &$bindTypes): void
    {
        // Cerca nel titolo O nel contenuto
        $query->appendOpenBlock()
              ->appendCondition('title', ComparisonOperator::like, Placeholder::placeholder)
              ->appendOr()
              ->appendCondition('content', ComparisonOperator::like, Placeholder::placeholder)
              ->appendCloseBlock();
        $bindValues[] = '%' . $searchKey . '%';
        $bindTypes[] = DataType::typeString;
        $bindValues[] = '%' . $searchKey . '%';
        $bindTypes[] = DataType::typeString;
    }

    #[\Override]
    protected function getEntityName(): string
    {
        return SampleBaseEntity::class;
    }

    /**
     * Esempio di metodo custom: recupera solo articoli pubblicati
     *
     * @return SismaCollection<SampleBaseEntity>
     */
    public function getPublishedArticles(?int $limit = null): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere()
              ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder);
        $query->setOrderBy(['publishedAt' => 'DESC']);

        if ($limit !== null) {
            $query->setLimit($limit);
        }

        $bindValues = [ArticleStatus::PUBLISHED->value];
        $bindTypes = [DataType::typeString];

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }

    /**
     * Esempio: recupera articoli in evidenza
     *
     * @return SismaCollection<SampleBaseEntity>
     */
    public function getFeaturedArticles(): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere()
              ->appendCondition('featured', ComparisonOperator::equal, Placeholder::placeholder)
              ->appendAnd()
              ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder);
        $query->setOrderBy(['rating' => 'DESC']);

        $bindValues = [
            1,
            ArticleStatus::PUBLISHED->value
        ];
        $bindTypes = [
            DataType::typeInteger,
            DataType::typeString
        ];

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }

    /**
     * Esempio: conta articoli per stato
     */
    public function countByStatus(ArticleStatus $status): int
    {
        $query = $this->initQuery();
        $query->setWhere()
              ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder);

        $bindValues = [$status->value];
        $bindTypes = [DataType::typeString];

        $query->close();
        return $this->dataMapper->getCount($query, $bindValues, $bindTypes);
    }
}
