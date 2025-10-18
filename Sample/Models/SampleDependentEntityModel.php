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

use SismaFramework\Orm\CustomTypes\SismaCollection;
use SismaFramework\Orm\ExtendedClasses\DependentModel;
use SismaFramework\Orm\Enumerations\ComparisonOperator;
use SismaFramework\Orm\Enumerations\DataType;
use SismaFramework\Orm\Enumerations\Placeholder;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\Sample\Entities\SampleDependentEntity;
use SismaFramework\Sample\Entities\SampleReferencedEntity;
use SismaFramework\Sample\Enumerations\ArticleStatus;

/**
 * Model per SampleDependentEntity - Articoli con Relazioni
 *
 * Questo model mostra:
 * - Come usare DependentModel per gestire relazioni
 * - Metodi magici getBy...() per query su relazioni
 * - Query con JOIN per ottimizzare le performance
 * - Filtraggio basato su entità referenziate
 *
 * @author Valentino de Lapa
 */
class SampleDependentEntityModel extends DependentModel
{
    /**
     * Implementa la ricerca testuale su titolo e contenuto
     */
    #[\Override]
    protected function appendSearchCondition(Query &$query, string $searchKey, array &$bindValues, array &$bindTypes): void
    {
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
        return SampleDependentEntity::class;
    }

    /**
     * Esempio di metodo magico: recupera tutti gli articoli di un autore
     *
     * Puoi chiamare questo metodo anche così:
     * $articles = $model->getBySampleReferencedEntity($author);
     *
     * @param SampleReferencedEntity $author
     * @return SismaCollection<SampleDependentEntity>
     */
    public function getArticlesByAuthor(SampleReferencedEntity $author): SismaCollection
    {
        // Modo 1: Usando getEntityCollectionByEntity (metodo ereditato da DependentModel)
        return $this->getEntityCollectionByEntity(['sampleReferencedEntity' => $author]);

        // Modo 2 (equivalente): Usando il metodo magico
        // return $this->getBySampleReferencedEntity($author);
    }

    /**
     * Esempio avanzato: recupera articoli pubblicati di un autore
     *
     * Nota: Il framework gestisce automaticamente il JOIN attraverso il lazy loading.
     * Per query complesse con JOIN espliciti, usa direttamente il DataMapper.
     */
    public function getPublishedArticlesByAuthor(SampleReferencedEntity $author): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere()
              ->appendCondition('sampleReferencedEntity', ComparisonOperator::equal, Placeholder::placeholder, true)
              ->appendAnd()
              ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder);
        $query->setOrderBy(['createdAt' => 'DESC']);

        $bindValues = [
            $author->getId(),
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
     * Esempio: conta articoli di un autore
     */
    public function countArticlesByAuthor(SampleReferencedEntity $author): int
    {
        // Modo 1: Usando countEntityCollectionByEntity
        return $this->countEntityCollectionByEntity(['sampleReferencedEntity' => $author]);

        // Modo 2 (equivalente): Usando il metodo magico
        // return $this->countBySampleReferencedEntity($author);
    }

    /**
     * Esempio: recupera gli articoli più visti
     */
    public function getMostViewedArticles(int $limit = 10): SismaCollection
    {
        $query = $this->initQuery();
        $query->setWhere()
              ->appendCondition('status', ComparisonOperator::equal, Placeholder::placeholder);
        $query->setOrderBy(['views' => 'DESC']);
        $query->setLimit($limit);

        $bindValues = [ArticleStatus::PUBLISHED->value];
        $bindTypes = [DataType::typeString];

        $query->close();
        return $this->dataMapper->find($this->entityName, $query, $bindValues, $bindTypes);
    }
}
