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
    private ScaffoldingManager $scaffoldingManagerStub;

    protected function setUp(): void
    {        
        $this->scaffoldingManagerStub = $this->createStub(ScaffoldingManager::class);
        $this->command = new ScaffoldCommand($this->scaffoldingManagerStub);
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
        $this->command->setArguments(['0' => 'User']);

        ob_start();
        $result = $this->command->run();
        $output = ob_get_clean();

        $this->assertFalse($result);
        $this->assertStringContainsString('Error: Module name is required', $output);
    }

    public function testSuccessfulExecution(): void
    {
        $this->command->setArguments([
            '0' => 'MockEntity',
            '1' => 'TestModule'
        ]);
        $this->command->setOptions(['force' => true]);

        $this->scaffoldingManagerStub
            ->method('setForce')
            ->with(true)
            ->willReturnSelf();

        $this->scaffoldingManagerStub
            ->method('setCustomType')
            ->with(null)
            ->willReturnSelf();

        $this->scaffoldingManagerStub
            ->method('setCustomTemplatePath')
            ->with(null)
            ->willReturnSelf();

        $this->scaffoldingManagerStub
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
