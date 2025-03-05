<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zeus\Mock\Exceptions\TimeoutException;
use Zeus\Mock\MockObjectFactory;
use Zeus\Mock\Tests\stubs\Timeout;

class TimoutTest extends TestCase
{


    #[Test]
    public function withTimeout():void
    {

        $mockObjectFactory = new MockObjectFactory();
        $mockObjectFactory->withTimeout(1, 'now', function () {
            usleep(1000000);
            return 2;
        });

        $this->expectException(TimeoutException::class);

        $mockObjectFactory->createMock(Timeout::class)->now();
    }

    #[Test]
    public function withoutTimeout(): void
    {
        $mockObjectFactory = new MockObjectFactory();
        $mockObjectFactory->withTimeout(1, 'now', function () {
            usleep(100);
            return 2;
        });


        $timeout = $mockObjectFactory->createMock(Timeout::class);
        $this->assertEquals(
            2,
            $timeout->now()
        );
    }
}