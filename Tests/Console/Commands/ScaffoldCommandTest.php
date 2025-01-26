<?php

namespace SismaFramework\Tests\Console\Commands;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Commands\ScaffoldCommand;
use SismaFramework\Console\Services\Scaffolding\ScaffoldingManager;
use SismaFramework\Orm\BaseClasses\BaseEntity;

// Prima creiamo una classe mock completa per l'entità
class MockEntity extends BaseEntity
{
    protected string $name;
    protected ?int $age;
    
    public static function getTableName(): string
    {
        return 'mock_entities';
    }
    
    public static function getPrimaryKeyName(): string
    {
        return 'id';
    }

    protected function setEncryptedProperties(): void
    {
        // Non necessita di implementazione per il test
    }

    protected function setPropertyDefaultValue(): void
    {
        // Non necessita di implementazione per il test
    }
}

class ScaffoldCommandTest extends TestCase
{
    private ScaffoldCommand $command;
    private ScaffoldingManager $mockScaffoldingManager;

    protected function setUp(): void
    {        
        $this->mockScaffoldingManager = $this->createMock(ScaffoldingManager::class);
        $this->command = new ScaffoldCommand($this->mockScaffoldingManager);
    }



    public function testCheckCompatibility(): void
    {
        $this->assertTrue($this->command->checkCompatibility('scaffold'));
        $this->assertFalse($this->command->checkCompatibility('other'));
    }

    public function testExecuteWithMissingEntity(): void
    {
        $this->command->setArguments([]);

        ob_start();
        $result = $this->command->run();
        $output = ob_get_clean();

        $this->assertFalse($result);
        $this->assertStringContainsString('Error: Entity name is required', $output);
    }

    public function testExecuteWithMissingModule(): void
    {
        $this->command->setArguments(['entity' => 'User']);

        ob_start();
        $result = $this->command->run();
        $output = ob_get_clean();

        $this->assertFalse($result);
        $this->assertStringContainsString('Error: Module name is required', $output);
    }

    public function testSuccessfulExecution(): void
    {
        $this->command->setArguments([
            'entity' => 'MockEntity',
            'module' => 'TestModule'
        ]);
        $this->command->setOptions(['force' => true]);

        $this->mockScaffoldingManager
            ->expects($this->once())
            ->method('setForce')
            ->with(true)
            ->willReturnSelf();

        $this->mockScaffoldingManager
            ->expects($this->once())
            ->method('setCustomType')
            ->with(null)
            ->willReturnSelf();

        $this->mockScaffoldingManager
            ->expects($this->once())
            ->method('setCustomTemplatePath')
            ->with(null)
            ->willReturnSelf();

        $this->mockScaffoldingManager
            ->expects($this->once())
            ->method('generateScaffolding')
            ->with('MockEntity', 'TestModule')
            ->willReturn(true);

        ob_start();
        $result = $this->command->run();
        $output = ob_get_clean();

        $this->assertTrue($result);
        $this->assertStringContainsString('Scaffolding generated successfully', $output);
    }


    public function testHelpOutput(): void
    {
        ob_start();
        $this->command->run();
        $output = ob_get_clean();

        $expectedStrings = [
            'Usage: php SismaFramework/Console/sisma scaffold <entity> <module> [options]',
            'Arguments:',
            'entity    The entity name',
            'module    The module name',
            'Options:',
            '--force',
            '--type=TYPE',
            '--template=PATH'
        ];

        foreach ($expectedStrings as $string) {
            $this->assertStringContainsString($string, $output);
        }
    }

}
