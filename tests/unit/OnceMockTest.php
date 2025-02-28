<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zeus\Mock\MockFunction;
use Zeus\Mock\OnceMockFunction;

class OnceMockTest extends TestCase
{


    #[Test]
    public function onceMock(): void
    {

        $mockFunction = new MockFunction();
        $mockFunction->once(function (MockFunction $function) {
            $function->add('time', function () {
                return 100;
            });
        });

        $mockFunction->scope();
        $this->assertEquals(100, time());
        $this->expectException(OnceMockFunction::class);
        time();
        $mockFunction->endScope();
        $this->assertNotEquals(100, time());
    }
}