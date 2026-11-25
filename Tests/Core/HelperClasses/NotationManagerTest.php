<?php

/*
 * The MIT License
 *
 * Copyright 2022 Valentino de Lapa.
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

namespace SismaFramework\Tests\Core\HelperClasses;

use PHPUnit\Framework\TestCase;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\NotationManager;
use SismaFramework\Orm\BaseClasses\BaseAdapter;
use SismaFramework\Orm\HelperClasses\DataMapper;
use SismaFramework\Orm\HelperClasses\ProcessedEntitiesCollection;
use SismaFramework\TestsApplication\Entities\BaseSample;

/**
 * @author Valentino de Lapa
 */
class NotationManagerTest extends TestCase
{

    private \ReflectionClass $reflectionNotationManager;

    public function testConvertToStudlyCaps()
    {
        $result = NotationManager::convertToStudlyCaps('fake-fake-fake');
        $this->assertEquals('FakeFakeFake', $result);
        $this->assertEquals('Oauth2Client', NotationManager::convertToStudlyCaps('oauth2-client'));
        $this->assertEquals('Oauth2Client', NotationManager::convertToStudlyCaps('oauth2_client'));
        $this->assertEquals('B2bCustomer', NotationManager::convertToStudlyCaps('b2b_customer'));
    }

    public function testConvertToCamelCase()
    {
        $result = NotationManager::convertToCamelCase('fake-fake-fake');
        $this->assertEquals('fakeFakeFake', $result);
        $this->assertEquals('sha256Hash', NotationManager::convertToCamelCase('sha256-hash'));
        $this->assertEquals('sha256Hash', NotationManager::convertToCamelCase('sha256_hash'));
        $this->assertEquals('b2bCustomer', NotationManager::convertToCamelCase('b2b_customer'));
    }

    public function testConvertToKebabKase()
    {
        $result = NotationManager::convertToKebabCase('fakeFakeFake');
        $this->assertEquals('fake-fake-fake', $result);
        $this->assertEquals('oauth2-client', NotationManager::convertToKebabCase('oauth2Client'));
        $this->assertEquals('b2b-customer', NotationManager::convertToKebabCase('B2bCustomer'));
    }

    public function testConvertToSnakeCase()
    {
        $resultOne = NotationManager::convertToSnakeCase('StudlyCaps');
        $this->assertEquals('studly_caps', $resultOne);
        $resultTwo = NotationManager::convertToSnakeCase('camelCase');
        $this->assertEquals('camel_case', $resultTwo);
        $this->assertEquals('oauth2_client', NotationManager::convertToSnakeCase('Oauth2Client'));
        $this->assertEquals('sha256_hash', NotationManager::convertToSnakeCase('sha256Hash'));
        $this->assertEquals('b2b_customer', NotationManager::convertToSnakeCase('b2bCustomer'));
    }

    public function testConvertToUpperSnakeCase()
    {
        $this->assertEquals('STUDLY_CAPS', NotationManager::convertToUpperSnakeCase('StudlyCaps'));
        $this->assertEquals('SHA256_HASH', NotationManager::convertToUpperSnakeCase('sha256Hash'));
    }

    public function testConvertEntityToTableName()
    {
        $configStub = $this->createStub(Config::class);
        $configStub->method('__get')
                ->willReturnMap([
                    ['defaultPrimaryKeyPropertyName', 'id'],
                    ['ormCache', true],
        ]);
        Config::setInstance($configStub);
        $baseAdapterMock = $this->createStub(BaseAdapter::class);
        BaseAdapter::setDefault($baseAdapterMock);
        $processedEntitesCollectionMock = $this->createStub(ProcessedEntitiesCollection::class);
        $dataMapperMock = $this->createStub(DataMapper::class);
        $baseSample = new BaseSample($dataMapperMock, $processedEntitesCollectionMock, $configStub);
        $result = NotationManager::convertEntityToTableName($baseSample);
        $this->assertEquals('base_sample', $result);
    }

    public function testConvertEntityNameToTableName()
    {
        $result = NotationManager::convertEntityNameToTableName(BaseSample::class);
        $this->assertEquals('base_sample', $result);
        $this->assertEquals('oauth2_client', NotationManager::convertEntityNameToTableName('Module\\Entities\\Oauth2Client'));
    }

    public function testConvertColumnNameToPropertyName()
    {
        $result = NotationManager::convertColumnNameToPropertyName('referenced_entity_without_initialization_id');
        $this->assertEquals('referencedEntityWithoutInitialization', $result);
        $this->assertEquals('sha256Hash', NotationManager::convertColumnNameToPropertyName('sha256_hash'));
        $this->assertEquals('oauth2Client', NotationManager::convertColumnNameToPropertyName('oauth2_client_id'));
    }

    public function testConvertPropertyNameToColumnName()
    {
        $this->assertEquals('column_name', NotationManager::convertPropertyNameToColumnName('columnName'));
        $this->assertEquals('column_name_id', NotationManager::convertPropertyNameToColumnName('columnName', true));
        $this->assertEquals('sha256_hash', NotationManager::convertPropertyNameToColumnName('sha256Hash'));
        $this->assertEquals('oauth2_client_id', NotationManager::convertPropertyNameToColumnName('oauth2Client', true));
    }
}
