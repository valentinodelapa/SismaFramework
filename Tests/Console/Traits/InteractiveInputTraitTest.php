<?php

namespace SismaFramework\Tests\Console\Traits;

use PHPUnit\Framework\TestCase;
use SismaFramework\Console\Traits\InteractiveInputTrait;

class InteractiveInputTraitTest extends TestCase
{
    private object $traitObject;

    protected function setUp(): void
    {
        $this->traitObject = new class {
            use InteractiveInputTrait;

            public function publicAsk(string $question, ?string $default = null): string
            {
                return $this->ask($question, $default);
            }

            public function publicAskConfirmation(string $question, bool $default = true): bool
            {
                return $this->askConfirmation($question, $default);
            }

            public function publicAskSecret(string $question): string
            {
                return $this->askSecret($question);
            }
        };
        ob_start();
    }

    protected function tearDown(): void
    {
        ob_end_clean();
    }

    public function testTraitCanBeUsed(): void
    {
        $this->assertIsObject($this->traitObject);
    }

    public function testAskMethodExists(): void
    {
        $this->assertTrue(method_exists($this->traitObject, 'publicAsk'));
    }

    public function testAskConfirmationMethodExists(): void
    {
        $this->assertTrue(method_exists($this->traitObject, 'publicAskConfirmation'));
    }

    public function testAskSecretMethodExists(): void
    {
        $this->assertTrue(method_exists($this->traitObject, 'publicAskSecret'));
    }

    public function testTraitMethodsAreProtected(): void
    {
        $reflection = new \ReflectionClass($this->traitObject);
        
        $traits = $reflection->getTraits();
        $traitReflection = new \ReflectionClass(InteractiveInputTrait::class);
        
        $askMethod = $traitReflection->getMethod('ask');
        $this->assertTrue($askMethod->isProtected());
        
        $askConfirmationMethod = $traitReflection->getMethod('askConfirmation');
        $this->assertTrue($askConfirmationMethod->isProtected());
        
        $askSecretMethod = $traitReflection->getMethod('askSecret');
        $this->assertTrue($askSecretMethod->isProtected());
    }

    public function testAskMethodSignature(): void
    {
        $traitReflection = new \ReflectionClass(InteractiveInputTrait::class);
        $method = $traitReflection->getMethod('ask');
        
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        
        $this->assertEquals('question', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
        
        $this->assertEquals('default', $parameters[1]->getName());
        $this->assertTrue($parameters[1]->allowsNull());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertNull($parameters[1]->getDefaultValue());
        
        $this->assertEquals('string', $method->getReturnType()->getName());
    }

    public function testAskConfirmationMethodSignature(): void
    {
        $traitReflection = new \ReflectionClass(InteractiveInputTrait::class);
        $method = $traitReflection->getMethod('askConfirmation');
        
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        
        $this->assertEquals('question', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
        
        $this->assertEquals('default', $parameters[1]->getName());
        $this->assertEquals('bool', $parameters[1]->getType()->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertTrue($parameters[1]->getDefaultValue());
        
        $this->assertEquals('bool', $method->getReturnType()->getName());
    }

    public function testAskSecretMethodSignature(): void
    {
        $traitReflection = new \ReflectionClass(InteractiveInputTrait::class);
        $method = $traitReflection->getMethod('askSecret');
        
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        
        $this->assertEquals('question', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
        
        $this->assertEquals('string', $method->getReturnType()->getName());
    }

    private function withInjectedInput(string $content): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);
        $this->traitObject->setInputStream($stream);
    }

    public function testAskReturnsTrimmedInput(): void
    {
        $this->withInjectedInput("localhost\n");

        $result = $this->traitObject->publicAsk('Host');

        $this->assertEquals('localhost', $result);
    }

    public function testAskReturnsDefaultWhenInputEmpty(): void
    {
        $this->withInjectedInput("\n");

        $result = $this->traitObject->publicAsk('Host', '127.0.0.1');

        $this->assertEquals('127.0.0.1', $result);
    }

    public function testAskReturnsEmptyStringWhenInputEmptyAndNoDefault(): void
    {
        $this->withInjectedInput("\n");

        $result = $this->traitObject->publicAsk('Name');

        $this->assertEquals('', $result);
    }

    public function testAskConfirmationReturnsDefaultWhenInputEmpty(): void
    {
        $this->withInjectedInput("\n");

        $this->assertTrue($this->traitObject->publicAskConfirmation('Continue?', true));
    }

    public function testAskConfirmationReturnsFalseDefaultWhenInputEmpty(): void
    {
        $this->withInjectedInput("\n");

        $this->assertFalse($this->traitObject->publicAskConfirmation('Continue?', false));
    }

    public static function affirmativeAnswerProvider(): array
    {
        return [
            ['y'],
            ['Y'],
            ['yes'],
            ['YES'],
            ['si'],
            ['s'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('affirmativeAnswerProvider')]
    public function testAskConfirmationReturnsTrueForAffirmativeAnswers(string $answer): void
    {
        $this->withInjectedInput($answer . "\n");

        $this->assertTrue($this->traitObject->publicAskConfirmation('Continue?', false));
    }

    public function testAskConfirmationReturnsFalseForNegativeAnswer(): void
    {
        $this->withInjectedInput("n\n");

        $this->assertFalse($this->traitObject->publicAskConfirmation('Continue?', true));
    }

    public function testAskSecretReadsInputWhenStreamIsNotATty(): void
    {
        $this->withInjectedInput("my-secret\n");

        $result = $this->traitObject->publicAskSecret('Password');

        $this->assertEquals('my-secret', $result);
    }

    public function testMultipleAsksShareTheSameInjectedStream(): void
    {
        $this->withInjectedInput("y\nlocalhost\n3306\n");

        $this->assertTrue($this->traitObject->publicAskConfirmation('Configure?', false));
        $this->assertEquals('localhost', $this->traitObject->publicAsk('Host'));
        $this->assertEquals('3306', $this->traitObject->publicAsk('Port'));
    }

    public function testSetInputStreamIsPublic(): void
    {
        $traitReflection = new \ReflectionClass(InteractiveInputTrait::class);
        $method = $traitReflection->getMethod('setInputStream');

        $this->assertTrue($method->isPublic());
    }
}
