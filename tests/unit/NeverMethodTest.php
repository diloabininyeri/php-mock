<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zeus\Mock\MockFactory;
use Zeus\Mock\NeverMethodException;
use Zeus\Mock\Tests\stubs\Date;

class NeverMethodTest extends TestCase
{


    #[Test]
    public function testNever():void
    {
        $mockFactory = new MockFactory();
        $mockFactory->never('now');
        $dateInstance = $mockFactory->createMock(Date::class);
        $this->expectException(NeverMethodException::class);
        $dateInstance->now();
    }
}