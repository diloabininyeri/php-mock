<?php

namespace Zeus\Mock\Mock;

use Closure;
use JsonException;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use Zeus\Mock\Exceptions\MockMethodNotFoundException;
use Zeus\Mock\Exceptions\OnceMockMethodException;
use Zeus\Mock\Exceptions\SpyMethodException;
use Zeus\Mock\Generators\MockMethodInterface;
use Zeus\Mock\Table\TableMockMethod;

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
     * @var object|null
     */
    private ?object $mockedObjectInstance = null;


    /**
     * @var array
     */
    private array $alwaysMockMethods = [];

    /***
     * @var string|null
     */
    private ?string $originalClassName = null;


    /**
     * @var array<string,Closure[]>
     */
    private array $monitorMethods = [];


    /***
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
     * @param object $mockInstance
     * @return void
     */
    public function setMockInstance(object $mockInstance): void
    {
        $this->mockedObjectInstance = $mockInstance;
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
        if ($this->mockedObjectInstance) {
            $arguments[] = $this->mockedObjectInstance;
        }

        $returnValue = call_user_func_array($this->methods[$methodName], $arguments);
        $this->incrementCount($methodName);
        $this->debuggingMethod($methodName, $arguments, $returnValue);
        $this->invokeAlwaysMethods(
            compact('returnValue', 'arguments', 'methodName')
        );
        $this->invokeMonitorMethod($methodName, $arguments, $returnValue);
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
    public function add(string $name, mixed $response): self
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
     * @param string $methodName
     * @param mixed $return
     * @return void
     */
    public function addIfNotDefined(string $methodName, mixed $return): void
    {
        if (!$this->hasMethodMock($methodName)) {
            $this->mockMethod($methodName, $return);
        }
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

    /***
     * @return object|null
     */
    public function getMockInstance(): ?object
    {
        return $this->mockedObjectInstance;
    }

    /**
     * @param string $methodName
     * @param mixed ...$args
     * @return mixed
     * @throws ReflectionException
     */
    public function callOriginalMethod(string $methodName, array $args): mixed
    {
        $mockInstance = $this->getMockInstance();
        if (empty($mockInstance)) {
            throw new SpyMethodException("the $methodName doesn't support spy method,because its a method of the interface");
        }
        $reflection = new ReflectionClass($mockInstance);
        $parentClass = $reflection->getParentClass();

        if ($parentClass && $parentClass->hasMethod($methodName)) {
            return $parentClass->getMethod($methodName)->invoke($mockInstance, ...$args);
        }
        return null;
    }

    /***
     * @param Closure $callback
     * @return void
     */
    public function always(Closure $callback): void
    {
        $this->alwaysMockMethods[] = $callback;
    }

    /**
     * @param array $arguments
     * @return void
     */
    private function invokeAlwaysMethods(array $arguments): void
    {

        $args =& $arguments['arguments'];
        $arguments['class'] = $this->getParentClassName();
        array_pop($args);
        foreach ($this->alwaysMockMethods as $method) {
            $method($arguments);
        }
    }

    /***
     * @return string|null
     */
    private function getParentClassName(): ?string
    {
        if ($this->originalClassName) {
            return $this->originalClassName;
        }
        if (!$this->getMockInstance()) {
            return null;
        }
        $reflectionObject = new ReflectionObject($this->mockedObjectInstance);
        $this->originalClassName = $reflectionObject->getParentClass()->getName();
        return $this->originalClassName;
    }

    /**
     * @param string $methodName
     * @param Closure $closure
     * @return void
     */
    public function monitorMethod(string $methodName, Closure $closure): void
    {
        $this->monitorMethods[$methodName][] = $closure;
    }

    /**
     * @param string $methodName
     * @param array $arguments
     * @param mixed $returnValue
     * @return void
     */
    private function invokeMonitorMethod(string $methodName, array $arguments, mixed $returnValue): void
    {
        if (!isset($this->monitorMethods[$methodName])) {
            return;
        }

        $mockInstance = $this->getParentClassName();
        array_pop($arguments);
        foreach ($this->monitorMethods[$methodName] as $monitor) {
            $monitor(
                compact('mockInstance', 'methodName', 'arguments', 'returnValue')
            );
        }
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->callCounts = [];
        $this->methods = [
            'object.on.created' => $this->methods['object.on.created']
        ];
        $this->debug = null;
        $this->onceMode = false;
        $this->mockedObjectInstance = null;
        $this->alwaysMockMethods = [];
        $this->originalClassName = null;
        $this->monitorMethods = [];
    }
}

