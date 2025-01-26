<?php

namespace SismaFramework\Tests\Console\Enumerations;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Enumerations\ModelType;

class ModelTypeTest extends TestCase
{
	public function testGetNamespace(): void
	{
		$this->assertEquals('SismaFramework\\Orm\\BaseClasses', ModelType::baseModel->getNamespace());
		$this->assertEquals('SismaFramework\\Orm\\ExtendedClasses', ModelType::dependentModel->getNamespace());
		$this->assertEquals('SismaFramework\\Orm\\ExtendedClasses', ModelType::selfReferencedModel->getNamespace());
	}

	public function testEnumValues(): void
	{
		$this->assertEquals('BaseModel', ModelType::baseModel->value);
		$this->assertEquals('DependentModel', ModelType::dependentModel->value);
		$this->assertEquals('SelfReferencedModel', ModelType::selfReferencedModel->value);
	}
}