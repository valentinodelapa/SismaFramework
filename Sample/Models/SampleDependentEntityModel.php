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

use SismaFramework\Orm\ExtendedClasses\DependentModel;
use SismaFramework\Orm\Enumerations\DataType;
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
        $query->where('title LIKE :searchKey OR content LIKE :searchKey');
        $bindValues[':searchKey'] = '%' . $searchKey . '%';
        $bindTypes[':searchKey'] = DataType::str;
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
     * @return SampleDependentEntity[]
     */
    public function getArticlesByAuthor(SampleReferencedEntity $author): array
    {
        // Modo 1: Usando getEntityCollectionByEntity (metodo ereditato da DependentModel)
        return $this->getEntityCollectionByEntity(['sampleReferencedEntity' => $author]);

        // Modo 2 (equivalente): Usando il metodo magico
        // return $this->getBySampleReferencedEntity($author);
    }

    /**
     * Esempio avanzato: recupera articoli pubblicati di un autore con JOIN
     *
     * Questo evita il problema N+1 caricando anche i dati dell'autore
     */
    public function getPublishedArticlesByAuthorWithJoin(SampleReferencedEntity $author): array
    {
        $query = new Query();
        $query->select('a.*')  // Solo colonne della tabella articoli
              ->from($this->getTableName() . ' a')
              ->join('sample_referenced_entity are', 'a.sample_referenced_entity_id = are.id')
              ->where('a.sample_referenced_entity_id = :authorId AND a.status = :status')
              ->orderBy(['a.created_at' => 'DESC']);

        $bindValues = [
            ':authorId' => $author->getId(),
            ':status' => ArticleStatus::PUBLISHED->value
        ];
        $bindTypes = [
            ':authorId' => DataType::int,
            ':status' => DataType::str
        ];

        return $this->getEntityCollectionByQuery($query, $bindValues, $bindTypes);
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
    public function getMostViewedArticles(int $limit = 10): array
    {
        $query = new Query();
        $query->select('*')
              ->from($this->getTableName())
              ->where('status = :status')
              ->orderBy(['views' => 'DESC'])
              ->limit($limit);

        $bindValues = [':status' => ArticleStatus::PUBLISHED->value];
        $bindTypes = [':status' => DataType::str];

        return $this->getEntityCollectionByQuery($query, $bindValues, $bindTypes);
    }
}
