<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use RuntimeException;
use Zeus\Mock\Exceptions\MaxRetriedException;
use Zeus\Mock\MockObjectFactory;
use Zeus\Mock\Tests\stubs\Date;

class MaxRetryTest extends TestCase
{

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function retryWithoutAnyException(): void
    {

        $mockObjectFactory = new MockObjectFactory();
        $counter = 0;
        $mockObjectFactory->retry(3, 'now', function () use (&$counter) {
            ++$counter;
            if ($counter < 3) {
                throw new RuntimeException();
            }
            return $counter;
        });

        $dateInstance = $mockObjectFactory->createMock(Date::class);


        $this->assertEquals(3, $dateInstance->now());

    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function retryCatchException(): void
    {
        $mockObjectFactory = new MockObjectFactory();
        $mockObjectFactory->retry(3, 'now', fn() => 5 / 0);

        $dateInstance = $mockObjectFactory->createMock(Date::class);
        $this->expectException(MaxRetriedException::class);

        $dateInstance->now();
    }
}