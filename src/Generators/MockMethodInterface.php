<?php

namespace Zeus\Mock\Generators;


use Closure;
use Zeus\Mock\Mock\MockMethod;

/**
 *
 */
interface MockMethodInterface
{
    /**
     * @noinspection PhpUnused
     * @param string $methodName
     * @return bool
     */
    public function hasMethodMock(string $methodName): bool;

    /**
     * @noinspection PhpUnused
     * @param string $methodName
     * @param array $arguments
     * @return mixed
     */
    public function invokeMockedMethod(string $methodName, array $arguments): mixed;

    /**
     * @param string $methodName
     * @param Closure $response
     * @return $this
     */
    public function mockMethod(string $methodName, mixed $response): MockMethod;

    /**
     * @param string $methodName
     * @return Closure
     */
    public function getMockMethod(string $methodName): Closure;

    /**
     * @param string $methodName
     * @return int
     */
    public function getCallCount(string $methodName): int;

    /**
     * @return ?object
     */
    public function getMockInstance():?object;
}
