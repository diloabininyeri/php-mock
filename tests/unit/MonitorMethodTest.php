<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Zeus\Mock\MockObjectFactory;
use Zeus\Mock\Tests\stubs\Date;

class MonitorMethodTest extends TestCase
{

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function monitoring(): void
    {

        $mockObjectFactory = new MockObjectFactory();

        $mockObjectFactory->method('now',function (){
            return '2025';
        });

        $counter = 0;
        $mockObjectFactory->monitoringMethod('now', function (array $args) use (&$counter){

            $counter++;
            $this->assertEquals('now',$args['methodName']);
            $this->assertEquals('2025',$args['returnValue']);
            $this->assertEquals(Date::class,$args['mockInstance']);
        });

        $dateInstance = $mockObjectFactory->createMock(Date::class);

        $dateInstance->now();
        $dateInstance->now();
        $dateInstance->now();
        $this->assertEquals(3,$counter);
    }

}