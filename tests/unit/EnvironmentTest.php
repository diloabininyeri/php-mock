<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Zeus\Mock\MockObjectFactory;
use Zeus\Mock\Tests\stubs\User;

/**
 *
 */
class EnvironmentTest extends TestCase
{

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function development():void
    {

        $factory = new MockObjectFactory();

        $factory->addEnvironment('development',$this->developmentFactory(...));

        $factory->addEnvironment('production',$this->productionFactory(...));
        $factory->setEnvironment('development');

        $user=$factory->createMock(User::class);

        $this->assertEquals(100, $user->getId());

        $factory->setEnvironment('production');

        $this->assertEquals(400, $user->getId());

        $factory->setEnvironment('development');

        $this->assertEquals(100, $user->getId());
    }

    /**
     * @param MockObjectFactory $factory
     * @return void
     */
    private function developmentFactory(MockObjectFactory $factory):void
    {
        $factory->method('getId',100);
    }

    /**
     * @param MockObjectFactory $factory
     * @return void
     */
    private function productionFactory(MockObjectFactory $factory):void
    {
        $factory->method('getId',400);
    }
}