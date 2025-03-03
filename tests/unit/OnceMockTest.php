<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zeus\Mock\Exceptions\OnceMockFunctionException;
use Zeus\Mock\ScopedFunctionMocker;

class OnceMockTest extends TestCase
{


    #[Test]
    public function onceMock(): void
    {

        $mockFunction = new ScopedFunctionMocker();
        $mockFunction->once(function (ScopedFunctionMocker $function) {
            $function->add('time', function () {
                return 100;
            });
        });

        $mockFunction->scope();
        $this->assertEquals(100, time());
        $this->expectException(OnceMockFunctionException::class);
        time();
        $mockFunction->endScope();
        $this->assertNotEquals(100, time());
    }
}