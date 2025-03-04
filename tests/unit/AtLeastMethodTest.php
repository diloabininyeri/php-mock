<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Zeus\Mock\MockObjectFactory;
use Zeus\Mock\Tests\stubs\Date;

class AtLeastMethodTest extends TestCase
{


    /**
     * @throws ReflectionException
     */
    #[Test]
    public function atLeastMethod(): void
    {
        $mockObjectFactory = new MockObjectFactory();
        $mockObjectFactory->atLeast(3, 'now', '2012');

        $dateInstance = $mockObjectFactory->createMock(Date::class);

        $this->assertEquals('2012', $dateInstance->now());
        $this->assertEquals('2012', $dateInstance->now());
        $this->assertEquals('2012', $dateInstance->now());

    }
}