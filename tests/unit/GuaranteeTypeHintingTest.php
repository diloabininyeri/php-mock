<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Zeus\Mock\MockObjectFactory;
use Zeus\Mock\Tests\stubs\User;
use Zeus\Mock\Tests\stubs\UserRepositoryInterface;

/**
 *
 */
class GuaranteeTypeHintingTest extends TestCase
{

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function testUserTypeHinting(): void
    {
        $mockFactory = new MockObjectFactory();
        $mockFactory->mockMethod('getId', function () {
            return 999;
        });

        $mockUser = $mockFactory->createMock(User::class);

        $this->assertEquals(999, $this->getId($mockUser));
    }

    /**
     * @param User $user
     * @return int
     */
    private function getId(User $user): int
    {
        return $user->getId();
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function testInterfaceTypeHinting(): void
    {
        $mockFactory = new MockObjectFactory();

        $mockFactory->mockMethod('getById', function () {
            return 999;
        });

        $mockUserRepository = $mockFactory->createMock(UserRepositoryInterface::class);
        $this->assertEquals(999, $this->getIdByInterface($mockUserRepository));
    }

    /**
     * @param UserRepositoryInterface $userRepository
     * @return int
     */
    private function getIdByInterface(UserRepositoryInterface $userRepository): int
    {
        return $userRepository->getById(1);
    }
}