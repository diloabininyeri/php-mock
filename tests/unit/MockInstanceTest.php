<?php
namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Zeus\Mock\MockObjectFactory;
use Zeus\Mock\Tests\stubs\User;

class MockInstanceTest extends TestCase
{


    /**
     * @throws ReflectionException
     */
    #[Test]
    public function instanceOfTest(): void
    {
        $this->assertInstanceOf(
            User::class,
            new MockObjectFactory()->createMock(User::class)
        );
    }
}