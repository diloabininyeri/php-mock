<?php

namespace Zeus\Mock\Tests\unit;

use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Zeus\Mock\MockObjectFactory;
use Zeus\Mock\Tests\stubs\Date;

class ThrowExceptionMethodTest extends TestCase
{


    /**
     * @throws ReflectionException
     */
    public function testThrowException(): void
    {
        $mockObjectFactory = new MockObjectFactory();
        $mockObjectFactory->throwsException('now',new Exception('An exception occurred'));

        $this->expectException(Exception::class);
        $mockObjectFactory->createMock(Date::class)->now();
    }
}