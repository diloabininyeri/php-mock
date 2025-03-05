<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zeus\Mock\MockObjectFactory;
use Zeus\Mock\Tests\stubs\Date;

class AlwaysMethodTest extends TestCase
{


    #[Test]
    public function always(): void
    {

        $mockObjectFactory = new MockObjectFactory();
        $mockObjectFactory->method('now','122');

        $array = [];
        $mockObjectFactory->always(function ($args) use (&$array) {
            $array[] = $args;
        });
        $dateInstance = $mockObjectFactory->createMock(Date::class);
        $dateInstance->now();
        $dateInstance->now();

        $this->assertEquals(Date::class, $array[0]['class']);
        $this->assertEquals(Date::class, $array[1]['class']);
        $this->assertEquals('now', $array[0]['methodName']);
        $this->assertEquals('now', $array[1]['methodName']);
        $this->assertEquals('122', $array[0]['returnValue']);
        $this->assertEquals('122', $array[1]['returnValue']);

    }
}