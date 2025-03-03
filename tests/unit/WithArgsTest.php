<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Zeus\Mock\Exceptions\WithArgsMethodException;
use Zeus\Mock\MockObjectFactory;
use Zeus\Mock\Tests\stubs\Args;

/**
 *
 */
class WithArgsTest extends TestCase
{


    /**
     * @throws ReflectionException
     */
    #[Test]
    public function withArgsMatch(): void
    {
        $mockObjectFactory = new MockObjectFactory();
        $mockObjectFactory->withArgs('example', [1, 2], 10);
        $args = $mockObjectFactory->createMock(Args::class);
        $this->assertEquals(10, $args->example(1, 2));
    }


    /**
     * @return void
     * @throws ReflectionException
     */
    #[Test]
    public function withArgsNotMatch(): void
    {
        $mockObjectFactory = new MockObjectFactory();
        $mockObjectFactory->withArgs('example', [1, 3], 10);
        $this->expectException(WithArgsMethodException::class);
        $mockObjectFactory->createMock(Args::class)->example(1, 2);
    }
}