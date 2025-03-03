<?php
namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Zeus\Mock\MockObjectFactory;
use Zeus\Mock\Tests\stubs\UserRepository;
use Zeus\Mock\Tests\stubs\UserService;

class MockConstructTest extends TestCase
{


    /**
     * @throws ReflectionException
     */
    #[Test]
    public function parameterViaConstruct(): void
    {
        $mockFactory=new MockObjectFactory();
        $instance=$mockFactory->createMock(UserService::class,[
            'user'=>new UserRepository()
        ]);

        $this->assertEquals(1,$instance->getUserById(1));

        $mockFactory->mockMethod('getUserById', function ($id) {
            return 10;
        });

        $this->assertEquals(10, $instance->getUserById(1));

    }
}