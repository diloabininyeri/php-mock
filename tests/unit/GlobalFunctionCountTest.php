<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zeus\Mock\MockFunction;

class GlobalFunctionCountTest extends TestCase
{
    #[Test]
    public function time(): void
    {
        $mockFunction = new MockFunction();


        $mockFunction->add('time', function () {
            return 100;
        });


        $mockFunction->scope();


        $this->assertEquals(100, time());
        $this->assertEquals(100, time());
        $this->assertEquals(2, $mockFunction->getCalledCountInScope('time'));
        $this->assertEquals(0, $mockFunction->getCalledCountOutScope('time'));


        $mockFunction->endScope();

        time();
        time();
        time();
        $this->assertEquals(3, $mockFunction->getCalledCountOutScope('time'));

        $this->assertNotEquals(100, time());

        $this->assertEquals(6, $mockFunction->getTotalCount('time'));
    }
}
