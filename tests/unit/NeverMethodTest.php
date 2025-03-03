<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zeus\Mock\Exceptions\NeverMethodException;
use Zeus\Mock\MockObjectFactory;
use Zeus\Mock\Tests\stubs\Date;

class NeverMethodTest extends TestCase
{


    #[Test]
    public function testNever():void
    {
        $mockFactory = new MockObjectFactory();
        $mockFactory->never('now');
        $dateInstance = $mockFactory->createMock(Date::class);
        $this->expectException(NeverMethodException::class);
        $dateInstance->now();
    }
}