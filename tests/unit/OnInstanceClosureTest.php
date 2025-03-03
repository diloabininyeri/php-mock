<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Zeus\Mock\MockObjectFactory;
use Zeus\Mock\Tests\stubs\ExampleForOnInstanced;

class OnInstanceClosureTest extends TestCase
{

    #[Test]
    /**
     *
     * @throws ReflectionException
     */
    public function onInstanceClosure(): void
    {
        $mockFactory = new MockObjectFactory();
        $mockFactory->onMockInstanceCreated(function (ExampleForOnInstanced $example, string $message, bool $status) {

            $this->assertEquals('hello', $message);
            $this->assertTrue($status);
            $this->assertInstanceOf(ExampleForOnInstanced::class, $example);
        });

        $mockFactory->createMock(ExampleForOnInstanced::class,
            [
                'message' => 'hello',
                'status' => true
            ]);
    }
}