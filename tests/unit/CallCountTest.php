<?php

namespace Zeus\Mock\Tests\unit;


use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zeus\Mock\Mock\MockMethod;
use Zeus\Mock\MockObjectFactory;
use Zeus\Mock\Tests\stubs\Date;

/**
 *
 */
class CallCountTest extends TestCase
{

    /**
     * @return void
     * @throws \ReflectionException
     */
    #[Test]
    public function callCount(): void
    {
        $mockMethod = new MockMethod();
        $mockMethod->mockMethod('getDate', '2024');
        $dateInstance=MockObjectFactory::from($mockMethod)->createMock(Date::class);
        $dateInstance->getDate(12, 2012);
        $dateInstance->getDate(12, 2015);
        $this->assertEquals(2, $mockMethod->getCallCount('getDate'));
    }
}