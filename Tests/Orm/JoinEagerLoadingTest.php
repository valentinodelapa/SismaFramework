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

namespace SismaFramework\Tests\Orm;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\BaseClasses\BaseModel;
use SismaFramework\Orm\BaseClasses\BaseResultSet;
use SismaFramework\Orm\Enumerations\JoinType;
use SismaFramework\Orm\HelperClasses\Query;
use SismaFramework\TestsApplication\Entities\DependentEntityOne;
use SismaFramework\TestsApplication\Entities\EntityWithTwoCollection;
use SismaFramework\TestsApplication\Entities\SelfReferencedSample;
use SismaFramework\TestsApplication\Models\ReferencedSampleModel;
use SismaFramework\TestsApplication\Models\SelfReferencedSampleModel;

/**
 * Test per JOIN con eager loading e idratazione gerarchica
 *
 * @author Valentino de Lapa
 */
class JoinEagerLoadingTest extends TestCase
{

    private const JOINED_COLUMN_ID = 'entityWithTwoCollection.id AS entityWithTwoCollection__id';
    private const JOINED_COLUMN_NAME = 'entityWithTwoCollection.name AS entityWithTwoCollection__name';

    #[\Override]
    public function setUp(): void
    {
        $logDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('log_', true) . DIRECTORY_SEPARATOR;
        $referenceCacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('cache_', true) . DIRECTORY_SEPARATOR;

        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')
                ->willReturnMap([
                    ['defaultPrimaryKeyPropertyName', 'id'],
                    ['developmentEnvironment', false],
                    ['encryptionPassphrase', 'encryption-key'],
                    ['encryptionAlgorithm', 'AES-256-CBC'],
                    ['entityNamespace', 'TestsApplication\\Entities\\'],
                    ['entityPath', 'TestsApplication' . DIRECTORY_SEPARATOR . 'Entities' . DIRECTORY_SEPARATOR],
                    ['foreignKeySuffix', 'Collection'],
                    ['initializationVectorBytes', 16],
                    ['logDevelopmentMaxRow', 100],
                    ['logDirectoryPath', $logDirectoryPath],
                    ['logPath', $logDirectoryPath . 'log.txt'],
                    ['logProductionMaxRow', 2],
                    ['logVerboseActive', true],
                    ['moduleFolders', ['SismaFramework']],
                    ['ormCache', true],
                    ['parentPrefixPropertyName', 'parent'],
                    ['referenceCacheDirectory', $referenceCacheDirectory],
                    ['referenceCachePath', $referenceCacheDirectory . 'referenceCache.json'],
                    ['rootPath', dirname(__DIR__, 3) . DIRECTORY_SEPARATOR],
                    ['sonCollectionPropertyName', 'sonCollection'],
        ]);
        $configStub->method('__isset')
                ->willReturn(true);

        Config::setInstance($configStub);
    }

    public function testQueryWithJoinBuildsCorrectSQL(): void
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('escapeTable')
                ->with(DependentEntityOne::class)
                ->willReturn('dependent_entity_one');

        $baseAdapterMock->expects($this->once())
                ->method('allColumns')
                ->willReturn('*');

        $joinMetadata = [
            'type' => JoinType::left,
            'table' => 'entity_with_two_collection',
            'alias' => 'entityWithTwoCollection',
            'on' => 'dependent_entity_one.entity_with_two_collection_id = entityWithTwoCollection.id',
            'relatedEntityClass' => EntityWithTwoCollection::class
        ];

        $baseAdapterMock->expects($this->once())
                ->method('buildJoinOnForeignKey')
                ->with(
                        JoinType::left,
                        'entityWithTwoCollection',
                        EntityWithTwoCollection::class,
                        'dependent_entity_one'
                )
                ->willReturn($joinMetadata);

        $baseAdapterMock->expects($this->once())
                ->method('parseSelect')
                ->with(
                        false,
                        ['*', 'column_as_alias'],
                        'dependent_entity_one',
                        [],
                        [],
                        [],
                        [],
                        0,
                        0,
                        [$joinMetadata]
                )
                ->willReturn('SELECT * FROM dependent_entity_one LEFT JOIN entity_with_two_collection');

        $query = new Query($baseAdapterMock);
        $query->setTable(DependentEntityOne::class);
        $query->appendJoinOnForeignKey(
                JoinType::left,
                'entityWithTwoCollection',
                EntityWithTwoCollection::class
        );
        $query->appendColumn('column_as_alias');
        $query->close();

        $sql = $query->getCommandToExecute();

        $this->assertNotNull($sql);
        $this->assertStringContainsString('LEFT', $sql);
        $this->assertStringContainsString('JOIN', $sql);
        $this->assertStringContainsString('entity_with_two_collection', $sql);
    }

    public function testJoinedColumnsHaveSeparator(): void
    {
        $baseAdapterMock = $this->createMock(BaseAdapter::class);
        $baseAdapterMock->expects($this->once())
                ->method('buildJoinedColumns')
                ->with('entityWithTwoCollection', EntityWithTwoCollection::class)
                ->willReturn([
                    self::JOINED_COLUMN_ID,
                    self::JOINED_COLUMN_NAME
        ]);

        $columns = $baseAdapterMock->buildJoinedColumns(
                'entityWithTwoCollection',
                EntityWithTwoCollection::class
        );

        $this->assertIsArray($columns);
        $this->assertCount(2, $columns);

        foreach ($columns as $column) {
            $this->assertStringContainsString('AS', $column);
            $this->assertStringContainsString('__', $column);
        }
    }

    public function testBaseModelHasEagerLoadingMethods(): void
    {
        $reflection = new \ReflectionClass(\SismaFramework\Orm\BaseClasses\BaseModel::class);

        $this->assertTrue($reflection->hasMethod('getEntityCollectionWithRelations'));
        $this->assertTrue($reflection->hasMethod('getEntityByIdWithRelations'));
        $this->assertTrue($reflection->hasMethod('isCollectionRelation'));
        $this->assertTrue($reflection->hasMethod('eagerLoadCollections'));
    }

    public function testSeparatorConstant(): void
    {
        $reflection = new \ReflectionClass(BaseResultSet::class);
        $constant = $reflection->getConstant('COLUMN_SEPARATOR');

        $this->assertEquals('__', $constant);
    }

    public function testJoinTypeEnum(): void
    {
        $this->assertTrue(enum_exists(JoinType::class));

        $cases = JoinType::cases();
        $this->assertCount(4, $cases);

        $caseNames = array_map(fn($case) => $case->name, $cases);
        $this->assertContains('left', $caseNames);
        $this->assertContains('inner', $caseNames);
        $this->assertContains('right', $caseNames);
        $this->assertContains('cross', $caseNames);
    }

    public function testIsCollectionRelationDetectsCollections(): void
    {
        $model = new ReferencedSampleModel();

        $reflection = new \ReflectionClass(BaseModel::class);
        $method = $reflection->getMethod('isCollectionRelation');

        $this->assertFalse($method->invoke($model, 'baseSample'));
        $this->assertFalse($method->invoke($model, 'nonExistentProperty'));
    }

    public function testIsCollectionRelationDetectsSonCollection(): void
    {
        $model = new SelfReferencedSampleModel();

        $reflection = new \ReflectionClass(BaseModel::class);
        $method = $reflection->getMethod('isCollectionRelation');

        $this->assertTrue($method->invoke($model, 'sonCollection'));
    }

    public function testBaseModelHasCollectionEagerLoadingMethod(): void
    {
        $reflection = new \ReflectionClass(\SismaFramework\Orm\BaseClasses\BaseModel::class);

        $this->assertTrue($reflection->hasMethod('eagerLoadCollections'));
        $this->assertTrue($reflection->hasMethod('loadCollectionForEntities'));
    }

    public function testSelfReferencedEntityHasSonCollectionProperty(): void
    {
        $reflection = new \ReflectionClass(SelfReferencedSample::class);
        $parentReflection = new \ReflectionClass(\SismaFramework\Orm\ExtendedClasses\SelfReferencedEntity::class);

        $this->assertTrue($parentReflection->hasMethod('__get'));
        $this->assertTrue($parentReflection->hasMethod('__set'));
        $this->assertTrue($parentReflection->hasMethod('getForeignKeyReference'));
        $this->assertTrue($parentReflection->hasMethod('getForeignKeyName'));
    }

    public function testReferencedEntityHasCollectionSupport(): void
    {
        $reflection = new \ReflectionClass(\SismaFramework\Orm\ExtendedClasses\ReferencedEntity::class);
        $this->assertTrue($reflection->hasMethod('checkCollectionExists'));
        $this->assertTrue($reflection->hasMethod('forceCollectionPropertySet'));
        $this->assertTrue($reflection->hasMethod('getCollectionDataInformation'));
        $this->assertTrue($reflection->hasMethod('getCollectionNames'));
    }

    public function testFlattenRelationsDotNotation(): void
    {
        $model = new ReferencedSampleModel();
        $reflection = new \ReflectionClass(BaseModel::class);
        $method = $reflection->getMethod('flattenRelations');
        $result = $method->invoke($model, ['author.country.continent']);
        $this->assertContains('author.country.continent', $result);
    }

    public function testFlattenRelationsNestedArray(): void
    {
        $model = new ReferencedSampleModel();
        $reflection = new \ReflectionClass(BaseModel::class);
        $method = $reflection->getMethod('flattenRelations');
        $result = $method->invoke($model, [
            'author' => [
                'country' => ['continent'],
                'publisher'
            ]
        ]);
        $this->assertContains('author', $result);
        $this->assertContains('author.country', $result);
        $this->assertContains('author.country.continent', $result);
        $this->assertContains('author.publisher', $result);
    }

    public function testFlattenRelationsMixedSyntax(): void
    {
        $model = new ReferencedSampleModel();
        $reflection = new \ReflectionClass(BaseModel::class);
        $method = $reflection->getMethod('flattenRelations');
        $result = $method->invoke($model, [
            'author.country',
            'category' => ['parent']
        ]);
        $this->assertContains('author.country', $result);
        $this->assertContains('category', $result);
        $this->assertContains('category.parent', $result);
    }

    public function testBaseModelHasNestedRelationMethods(): void
    {
        $reflection = new \ReflectionClass(\SismaFramework\Orm\BaseClasses\BaseModel::class);
        $this->assertTrue($reflection->hasMethod('flattenRelations'));
        $this->assertTrue($reflection->hasMethod('appendNestedRelationJoin'));
    }

    public function testBaseResultSetHasNestedHydrationMethods(): void
    {
        $reflection = new \ReflectionClass(BaseResultSet::class);
        $this->assertTrue($reflection->hasMethod('hydrateNestedEntities'));
        $this->assertTrue($reflection->hasMethod('getEntityClassForAlias'));
    }

    public function testCustomQueryWithJoinAndConditionOnJoinedTable(): void
    {
        $adapterMock = $this->createStub(BaseAdapter::class);
        $adapterMock->method('escapeTable')->willReturnCallback(fn($table) => strtolower(basename(str_replace('\\', '/', $table))));
        $adapterMock->method('escapeIdentifier')->willReturnCallback(fn($id) => "`$id`");
        $adapterMock->method('escapeColumn')->willReturnCallback(fn($col, $withTable = false) => $withTable ? "`table`.`$col`" : "`$col`");
        $adapterMock->method('buildJoinOnForeignKey')->willReturn([
            'type' => JoinType::left,
            'table' => 'entitywithttwocollection',
            'alias' => 'entityWithTwoCollection',
            'on' => 'dependententityone.entity_with_two_collection_id = entityWithTwoCollection.id',
            'relatedEntityClass' => EntityWithTwoCollection::class
        ]);
        $query = new Query($adapterMock);
        $query->setTable(DependentEntityOne::class);
        $query->appendJoinOnForeignKey(JoinType::left, 'entityWithTwoCollection', EntityWithTwoCollection::class);
        $this->assertTrue($query->hasJoins());
        $joins = $query->getJoins();
        $this->assertCount(1, $joins);
        $this->assertSame(JoinType::left, $joins[0]['type']);
        $this->assertSame(EntityWithTwoCollection::class, $joins[0]['relatedEntityClass']);
    }

    public function testCustomQueryWithMultipleJoins(): void
    {
        $adapterMock = $this->createStub(BaseAdapter::class);
        $adapterMock->method('escapeTable')->willReturnCallback(fn($table) => strtolower(basename(str_replace('\\', '/', $table))));
        $adapterMock->method('escapeIdentifier')->willReturnCallback(fn($id) => "`$id`");
        $adapterMock->method('buildJoinOnForeignKey')->willReturnOnConsecutiveCalls(
                [
                    'type' => JoinType::inner,
                    'table' => 'entitywithttwocollection',
                    'alias' => 'entityWithTwoCollection',
                    'on' => 'dependententityone.entity_with_two_collection_id = entityWithTwoCollection.id',
                    'relatedEntityClass' => EntityWithTwoCollection::class
                ],
                [
                    'type' => JoinType::left,
                    'table' => 'basesample',
                    'alias' => 'baseSample',
                    'on' => 'referencesample.base_sample_id = baseSample.id',
                    'relatedEntityClass' => \SismaFramework\TestsApplication\Entities\BaseSample::class
                ]
        );
        $query = new Query($adapterMock);
        $query->setTable(DependentEntityOne::class);
        $query->appendJoinOnForeignKey(JoinType::inner, 'entityWithTwoCollection', EntityWithTwoCollection::class);
        $query->appendJoinOnForeignKey(JoinType::left, 'baseSample', \SismaFramework\TestsApplication\Entities\BaseSample::class);
        $joins = $query->getJoins();
        $this->assertCount(2, $joins);
        $this->assertSame(JoinType::inner, $joins[0]['type']);
        $this->assertSame(JoinType::left, $joins[1]['type']);
    }

    public function testCustomQueryWithManualJoinAndCustomCondition(): void
    {
        $adapterMock = $this->createStub(BaseAdapter::class);
        $adapterMock->method('escapeTable')->willReturnCallback(fn($table) => strtolower(basename(str_replace('\\', '/', $table))));
        $adapterMock->method('escapeIdentifier')->willReturnCallback(fn($id) => "`$id`");
        $query = new Query($adapterMock);
        $query->setTable(DependentEntityOne::class);
        $query->appendJoin(
                JoinType::inner,
                EntityWithTwoCollection::class,
                'custom_alias',
                'dependententityone.entity_with_two_collection_id = custom_alias.id AND custom_alias.active = 1',
                EntityWithTwoCollection::class
        );
        $joins = $query->getJoins();
        $this->assertCount(1, $joins);
        $this->assertSame('`custom_alias`', $joins[0]['alias']);
        $this->assertStringContainsString('custom_alias.active = 1', $joins[0]['on']);
        $this->assertSame(EntityWithTwoCollection::class, $joins[0]['relatedEntityClass']);
    }

    public function testCustomQuerySupportsCrossJoin(): void
    {
        $adapterMock = $this->createStub(BaseAdapter::class);
        $adapterMock->method('escapeTable')->willReturnCallback(fn($table) => strtolower(basename(str_replace('\\', '/', $table))));
        $adapterMock->method('escapeIdentifier')->willReturnCallback(fn($id) => "`$id`");
        $query = new Query($adapterMock);
        $query->setTable(DependentEntityOne::class);
        $query->appendJoin(
                JoinType::cross,
                EntityWithTwoCollection::class,
                'crossed',
                '',
                EntityWithTwoCollection::class
        );
        $joins = $query->getJoins();
        $this->assertCount(1, $joins);
        $this->assertSame(JoinType::cross, $joins[0]['type']);
        $this->assertEmpty($joins[0]['on']);
    }

    public function testQueryAppendColumnForJoinedTables(): void
    {
        $adapterMock = $this->createStub(BaseAdapter::class);
        $adapterMock->method('escapeTable')->willReturnCallback(fn($table) => strtolower(basename(str_replace('\\', '/', $table))));
        $adapterMock->method('allColumns')->willReturnCallback(fn($table = '') => $table ? $table . '.*' : '*');
        $query = new Query($adapterMock);
        $query->setTable(DependentEntityOne::class);
        $query->appendColumn(self::JOINED_COLUMN_ID);
        $query->appendColumn(self::JOINED_COLUMN_NAME);
        $columns = $query->getColumns();
        $this->assertCount(3, $columns);
        $this->assertSame('dependententityone.*', $columns[0]);
        $this->assertContains(self::JOINED_COLUMN_ID, $columns);
        $this->assertContains(self::JOINED_COLUMN_NAME, $columns);
    }

    public function testBaseAdapterHasBuildJoinedColumnsMethod(): void
    {
        $reflection = new \ReflectionClass(BaseAdapter::class);
        $this->assertTrue($reflection->hasMethod('buildJoinedColumns'));
        $this->assertTrue($reflection->hasMethod('buildJoinMetadata'));
    }

    public function testAllColumnsReturnsQualifiedNameWithTable(): void
    {
        $adapterMock = $this->createStub(BaseAdapter::class);
        $adapterMock->method('allColumns')->willReturnCallback(fn($table = '') => $table ? $table . '.*' : '*');
        $result = $adapterMock->allColumns('users');
        $this->assertSame('users.*', $result);
    }

    public function testAllColumnsReturnsAsteriskWithoutTable(): void
    {
        $adapterMock = $this->createStub(BaseAdapter::class);
        $adapterMock->method('allColumns')->willReturnCallback(fn($table = '') => $table ? $table . '.*' : '*');
        $result = $adapterMock->allColumns();
        $this->assertSame('*', $result);
    }
}
