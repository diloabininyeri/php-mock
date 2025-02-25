<?php

namespace Zeus\Mock;

use Closure;

/**
 *
 */
class MockMethod implements MockMethodInterface
{
    /**
     * @var array
     */
    private array $methods = [];

    /**
     * @noinspection PhpUnused
     * @param string $methodName
     * @return bool
     */
    public function hasMethodMock(string $methodName): bool
    {
        return isset($this->methods[$methodName]);
    }

    /**
     * @noinspection PhpUnused
     * @param string $methodName
     * @param array $arguments
     * @return mixed
     */

    public function invokeMockedMethod(string $methodName, array $arguments): mixed
    {
        if (!$this->hasMethodMock($methodName)) {
            throw new MockMethodNotFoundException("Method $methodName not mocked.");
        }
        return call_user_func_array($this->methods[$methodName], $arguments);
    }

    /**
     * @param string $methodName
     * @param Closure $closure
     * @return $this
     */
    public function mockMethod(string $methodName, Closure $closure): self
    {
        $this->methods[$methodName] = $closure;
        return $this;
    }

    /**
     * @param string $methodName
     * @return Closure
     */
    public function getMockMethod(string $methodName): Closure
    {
       return $this->methods[$methodName];
    }
}
