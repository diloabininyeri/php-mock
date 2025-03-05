<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Zeus\Mock\Exceptions\SpyMethodException;
use Zeus\Mock\MockObjectFactory;
use Zeus\Mock\Tests\stubs\SpyObject;

/**
 *
 */
class SpyMethodTest extends TestCase
{


    /**
     * @return void
     * @throws ReflectionException
     */
    #[Test]
    public function spyMethod(): void
    {
        $mock = new MockObjectFactory();

        $mock->spyMethod('example', function (SpyObject $spyObject) {
            return 'foo';
        });
        $this->expectException(SpyMethodException::class);

        $this->assertEquals(2025,$mock->createMock(SpyObject::class)->example());
    }
}