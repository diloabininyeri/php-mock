<?php

namespace Zeus\Mock;

use Closure;

/**
 *
 */
class MockMethod implements MockMethodInterface
{
    private array $callCounts = [];

    /**
     * @var array
     */
    private array $methods = [];

    private ?TableMockMethod $debug=null;


    public function __construct(string $mockTestName='default',bool $debug=false)
    {
        if ($debug) {
            $this->debug = new TableMockMethod($mockTestName);
        }
    }

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
        $returnValue= call_user_func_array($this->methods[$methodName], $arguments);
        $this->incrementCount($methodName);
        $this->debuggingMethod($methodName,$arguments,$returnValue);
        return $returnValue;
    }

    /**
     * @param string $methodName
     * @param mixed $response
     * @return $this
     */
    public function mockMethod(string $methodName, mixed $response): self
    {
        if (!($response instanceof Closure)) {
            $response = static fn() => $response;
        }
        $this->methods[$methodName] = $response;
        return $this;
    }

    public function getCallCount(string $methodName): int
    {
        return $this->callCounts[$methodName] ?? 0;
    }

    /**
     * @param string $methodName
     * @return Closure
     */
    public function getMockMethod(string $methodName): Closure
    {
        return $this->methods[$methodName];
    }

    /**
     * @param string $methodName
     * @return void
     */
    private function incrementCount(string $methodName): void
    {
        $this->callCounts[$methodName] ??= 0;
        ++$this->callCounts[$methodName];
    }

    public function debuggingMethod(string $methodName, array $arguments,$return): void
    {
        $this->debug?->debug($methodName, $arguments,$return);
    }

    /**
     * @noinspection PhpUnused
     * @return void
     */
    public function printDebug(): void
    {
       $this->debug?->printDebugLogs();
    }
}

