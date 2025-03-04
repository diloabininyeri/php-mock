<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Zeus\Mock\Exceptions\AtMostMethodException;
use Zeus\Mock\MockObjectFactory;
use Zeus\Mock\Tests\stubs\Date;

class AtMostMethodTest extends TestCase
{


    /**
     * @throws ReflectionException
     */
    #[Test]
    public function atMostTest():void
    {

        $mockFactory = new MockObjectFactory();
        $mockFactory->atMost(3, 'now','2012');
        $dateInstance=$mockFactory->createMock(Date::class);

        $this->assertEquals('2012', $dateInstance->now());
        $this->assertEquals('2012', $dateInstance->now());
        $this->assertEquals('2012', $dateInstance->now());

        $this->expectException(AtMostMethodException::class);
        $dateInstance->now();
    }
}