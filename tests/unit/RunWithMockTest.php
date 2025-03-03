<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zeus\Mock\ScopedFunctionMocker;
use Zeus\Mock\Tests\stubs\Date;

class RunWithMockTest extends TestCase
{

    #[Test]
    public function wrapObject():void
    {

        $mock = new ScopedFunctionMocker();

        $mock->add('date', function () {
            return '1971-12-10';
        });

        $date = $mock->runWithMock(new Date(), function (Date $date) {
            return $date->now();
        });

        $this->assertEquals('1971-12-10', $date);
    }

}