<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zeus\Mock\MockFunction;

class MockFunctionTest extends TestCase
{


    #[Test]
    public function time(): void
    {

        $m = new MockFunction();
        $m->add('time', function () {
            return 100;
        });
        $m->scope();
        $this->assertEquals(100, time());

        $m->endScope();

        $this->assertNotEquals(100, time());

    }

    #[Test]
    public function sleep(): void
    {

        $m = new MockFunction();
        $m->add('sleep', function (int $m) {
            return $m;
        });
        $m->scope();
        $this->assertEquals(10, sleep(10));
        $m->endScope();
    }
}