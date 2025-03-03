<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zeus\Mock\Exceptions\OnceMockMethodException;
use Zeus\Mock\Mock\MockMethod;
use Zeus\Mock\MockObjectFactory;
use Zeus\Mock\Tests\stubs\Date;

class OnceMockMethodTest extends TestCase
{

    #[Test]
    public function onceMockMethod(): void
    {
        $mockFactory = new MockObjectFactory();
        $mockFactory->once(function (MockMethod $method) {
            $method->add('now', '2012');
        });

        $dateInstance = $mockFactory->createMock(Date::class);
        $this->assertEquals('2012', $dateInstance->now());

        $this->expectException(OnceMockMethodException::class);
        $dateInstance->now();

    }
}