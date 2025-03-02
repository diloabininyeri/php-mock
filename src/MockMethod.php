<?php

namespace Zeus\Mock;

use Closure;
use JsonException;

/**
 *
 */
class MockMethod implements MockMethodInterface
{
    /**
     * @var array
     */
    private array $callCounts = [];

    /**
     * @var array
     */
    private array $methods = [];

    /**
     * @var TableMockMethod|null
     */
    private ?TableMockMethod $debug = null;

    /**
     * @var bool
     */
    private bool $onceMode = false;


    /**
     * @param string $mockTestName
     * @param bool $debug
     */
    public function __construct(string $mockTestName = 'default', bool $debug = false)
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
     * @throws JsonException
     */

    public function invokeMockedMethod(string $methodName, array $arguments): mixed
    {
        if (!$this->hasMethodMock($methodName)) {
            throw new MockMethodNotFoundException("Method $methodName not mocked.");
        }
        $returnValue = call_user_func_array($this->methods[$methodName], $arguments);
        $this->incrementCount($methodName);
        $this->debuggingMethod($methodName, $arguments, $returnValue);
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
        $onceMode = $this->onceMode;
        $this->methods[$methodName] = static function (...$args) use ($response, $onceMode, $methodName) {
            static $counter = 0;

            if ($onceMode && $counter > 0) {
                throw new OnceMockMethodException("Method $methodName called more than once.");
            }

            $returnValue = $response(...$args);
            if ($onceMode === true) {
                ++$counter;
            }
            return $returnValue;
        };
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $response
     * @return $this
     */
    public function add(string $name,mixed $response):self
    {
       return $this->mockMethod($name, $response);
    }

    /**
     * @param string $methodName
     * @return int
     */
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

    /**
     * @param string $methodName
     * @param array $arguments
     * @param $return
     * @return void
     * @throws JsonException
     */
    public function debuggingMethod(string $methodName, array $arguments, $return): void
    {
        $this->debug?->debug($methodName, $arguments, $return);
    }

    /**
     * @noinspection PhpUnused
     * @return void
     */
    public function printDebug(): void
    {
        $this->debug?->printDebugLogs();
    }

    /**
     * @param Closure $closure
     * @return void
     */
    public function once(Closure $closure): void
    {
        $this->onceMode = true;
        $closure($this);
        $this->onceMode = false;
    }
}

