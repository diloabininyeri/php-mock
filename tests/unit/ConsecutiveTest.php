<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zeus\Mock\ScopedFunctionMocker;
use Zeus\Mock\Tests\stubs\Date;

class ConsecutiveTest extends TestCase
{


    #[Test]
    public function date():void
    {
        $mockFunction = new ScopedFunctionMocker();
        $mockFunction->addConsecutive('date',
            [
                '2022-01-01',
                '2022-01-02',
                '2022-01-03'
            ]);

        $mockFunction->runWithMock(new Date(),function (Date $date)
        {
            $this->assertEquals('2022-01-01', $date->now());
            $this->assertEquals('2022-01-02', $date->now());
            $this->assertEquals('2022-01-03', $date->now());
        });
    }
}