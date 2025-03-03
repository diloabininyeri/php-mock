<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Zeus\Mock\MockObjectFactory;
use Zeus\Mock\Tests\stubs\ExampleInterface;

class MockInterfaceTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    #[Test]
    public function onlyImplementedInterface(): void
    {
        $mockFactory = new MockObjectFactory();
        $mockFactory->mockMethod('hello', function () {
            return 'Hello';
        });
        $mockObject = $mockFactory->createMock(ExampleInterface::class);
        $this->assertEquals('Hello', $mockObject->hello());


        $mockFactory1 = new MockObjectFactory();
        $mockFactory1->mockMethod('welcome', function (string $message) {
            return "Welcome $message";
        });

        $mock = $mockFactory1->createMock(ExampleInterface::class);
        $this->assertEquals('Welcome dilo', $mock->welcome('dilo'));


        $mockFactory2 = new MockObjectFactory();
        $mockFactory2->mockMethod('goodbye', function () {
            echo 'Goodbye';
        });
        $mock = $mockFactory2->createMock(ExampleInterface::class);

        $this->assertNull($mock->goodbye());
    }
}