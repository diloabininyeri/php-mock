<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zeus\Mock\MockFunction;

class MockOverrideAbleFunctionTest extends TestCase
{


    #[Test]
    public function override():void
    {

        $mock = new MockFunction();
        $mock->add('time', function () {
            return 100;
        });

        $mock->scope();

        $this->assertEquals(100, time());

        $mock->add('time', function () {
            return 200;
        });

        $this->assertEquals(200, time());
        $mock->endScope();


        $mock1 = new MockFunction();
        $mock1->add('time', function () {
            return 99;
        });

        $mock1->scope();
        $this->assertEquals(99, time());
        $mock1->endScope();
    }
}