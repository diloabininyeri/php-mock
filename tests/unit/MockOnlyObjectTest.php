<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Zeus\Mock\MockObjectFactory;
use Zeus\Mock\Tests\stubs\User;

class MockOnlyObjectTest extends TestCase
{

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function userMethod():void
    {
        $mockFactory = new MockObjectFactory();
        $mockUser = $mockFactory->createMock(User::class);
        $mockFactory->mockMethod('getId', function () {
            return 10;
        });

        $this->assertEquals(10, $mockUser->getId());
    }
}