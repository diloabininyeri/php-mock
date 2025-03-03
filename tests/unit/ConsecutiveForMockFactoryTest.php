<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Zeus\Mock\MockObjectFactory;
use Zeus\Mock\Tests\stubs\Date;

class ConsecutiveForMockFactoryTest extends TestCase
{

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function consecutive():void
    {
        $mockFactory = new MockObjectFactory();
        $mockFactory->addConsecutive('now', ['2012-10-9', '2012-10-10', '2012-10-11']);
        $dateInstance = $mockFactory->createMock(Date::class);
        $this->assertEquals('2012-10-9', $dateInstance->now());
        $this->assertEquals('2012-10-10', $dateInstance->now());
        $this->assertEquals('2012-10-11', $dateInstance->now());

    }
}