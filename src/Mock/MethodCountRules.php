<?php

namespace Zeus\Mock\Mock;

use Closure;
use ReflectionClass;
use Throwable;
use Zeus\Mock\Exceptions\AtLeastMethodException;
use Zeus\Mock\Exceptions\AtMostMethodException;
use Zeus\Mock\Exceptions\NeverMethodException;
use Zeus\Mock\Exceptions\SpyMethodException;
use Zeus\Mock\Exceptions\WithArgsMethodException;
use Zeus\Mock\Exceptions\WithArgumentMismatchException;

/**
 *
 */
readonly class MethodCountRules
{

    /**
     * @param MockMethod $mockMethod
     */
    public function __construct(private MockMethod $mockMethod)
    {
    }

    /**
     * @param Closure $mockMethodClosure
     * @return void
     */
    public function once(Closure $mockMethodClosure): void
    {
        $this->mockMethod->once($mockMethodClosure);
    }

    /**
     * @param string $methodName
     * @param array $returns
     * @return void
     */
    public function addConsecutive(string $methodName, array $returns): void
    {
        $this->mockMethod->mockMethod($methodName, function () use (&$returns) {
            return array_shift($returns);
        });
    }

    /**
     * @param string $methodName
     * @return void
     */
    public function never(string $methodName): void
    {
        $this->mockMethod->add(
            $methodName, fn() => throw new NeverMethodException("Unexpected method call: $methodName. This method should never be invoked.")
        );
    }

    /***
     * @param int $count
     * @param string $methodName
     * @param mixed $response
     * @return void
     */
    public function atMost(int $count, string $methodName, mixed $response): void
    {
        $this->mockMethod->add($methodName, function (...$args) use ($count, $response, $methodName) {
            static $callCount = 0;
            if ($callCount >= $count) {
                throw new AtMostMethodException("Method $methodName must be called at most $count times.");
            }
            $callCount++;
            if ($response instanceof Closure) {
                return $response(...$args);
            }
            return $response;
        });
    }

    /**
     * @noinspection PhpUnused
     * @param int $delay
     * @param string $methodName
     * @param mixed $response
     * @return void
     */
    public function afterDelay(int $delay, string $methodName, mixed $response): void
    {
        $this->mockMethod->add($methodName, function (...$args) use ($delay, $response) {
            if ($response instanceof Closure) {
                $returnValue = $response(...$args);
                sleep($delay);
                return $returnValue;
            }
            $returnValue = $response;
            sleep($delay);
            return $returnValue;
        });
    }

    /**
     * @noinspection PhpUnused
     * @param int $delay
     * @param string $methodName
     * @param mixed $response
     * @return void
     */
    public function beforeDelay(int $delay, string $methodName, mixed $response): void
    {
        $this->mockMethod->add($methodName, function (...$args) use ($response, $delay) {
            sleep($delay);
            if ($response instanceof Closure) {
                return $response(...$args);
            }
            return $response;
        });
    }

    /**
     * @param string $methodName
     * @param array $arguments
     * @param mixed $response
     * @return void
     */
    public function withArgs(string $methodName, array $arguments, mixed $response): void
    {
        $this->mockMethod->add($methodName, function (...$actualArgs) use ($arguments, $response, $methodName) {

            $mockInstance = null;
            if ($this->mockMethod->getMockInstance()) {
                $mockInstance = array_pop($actualArgs);
            }
            if ($actualArgs === $arguments) {
                if ($response instanceof Closure) {
                    return $this->bindMockInstanceToResponseClosure($mockInstance, $response, $actualArgs);
                }
                return $response;
            }
            throw new WithArgsMethodException(
                "Unexpected arguments for method $methodName. Expected: " .
                json_encode($arguments, JSON_THROW_ON_ERROR) . ", got: " . json_encode($actualArgs, JSON_THROW_ON_ERROR)
            );
        });
    }

    /**
     * @param string $methodName
     * @param Closure $afterClosure
     * @return void
     */
    public function after(string $methodName, Closure $afterClosure): void
    {
        $mainResponseClosure = $this->mockMethod->getMockMethod($methodName);
        $this->mockMethod->add($methodName, function (...$args) use ($afterClosure, $mainResponseClosure) {
            $returnValue = $mainResponseClosure(...$args);
            $afterClosure($returnValue);
            return $returnValue;
        });
    }

    /**
     * @noinspection PhpUnused
     * @param array<string,mixed> $responses
     * @return void
     */
    public function applyDefaultMockMethods(array $responses): void
    {
        foreach ($responses as $key => $response) {
            $this->mockMethod->addIfNotDefined($key, $response);
        }
    }

    /**
     * @param string $methodName
     * @param Throwable $exception
     * @return void
     */
    public function throwsException(string $methodName, Throwable $exception): void
    {
        $this->mockMethod->add($methodName, fn(...$args) => throw $exception);
    }

    /**
     * @noinspection PhpUnused
     * @param string $methodName
     * @param callable $argumentMatcher
     * @param mixed $response
     * @return void
     */
    public function withArgumentsMatching(string $methodName, callable $argumentMatcher, mixed $response): void
    {
        $this->mockMethod->add($methodName, function (...$args) use ($argumentMatcher, $response, $methodName) {
            $mockInstance = $this->mockMethod->getMockInstance();
            if ($mockInstance) {
                array_pop($args);
            }
            if ($argumentMatcher(...$args)) {
                if (is_callable($response)) {
                    return $this->bindMockInstanceToResponseClosure($mockInstance, $response, $args);
                }
                return $response;
            }
            throw new WithArgumentMismatchException("Arguments don't match for method $methodName.");
        });
    }

    /**
     * @param int $count
     * @param string $methodName
     * @param mixed $response
     * @return void
     */
    public function atLeast(int $count, string $methodName, mixed $response): void
    {
        $this->mockMethod->add($methodName, function (...$args) use ($count, $response, $methodName) {
            static $callCount = 0;
            static $isRegistered = false;
            $callCount++;
            if (false === $isRegistered) {
                register_shutdown_function(static function () use (&$callCount, $count, $methodName) {
                    if ($callCount < $count) {
                        throw new AtLeastMethodException("$methodName should be called at least $count times");
                    }
                });
                $isRegistered = true;
            }

            if (is_callable($response)) {
                return $response(...$args);
            }
            return $response;
        });
    }

    /**
     * @param string $methodName
     * @param mixed $response
     * @return void
     */
    public function spyMethod(string $methodName, mixed $response): void
    {
        $this->mockMethod->add($methodName, function (...$args) use ($response, $methodName) {
            if (empty($this->mockMethod->getMockInstance())) {
                throw new SpyMethodException("the $methodName doesn't support spy method,because its a method of the interface");
            }
            $reflection = new ReflectionClass($this->mockMethod->getMockInstance());
            $parentClass = $reflection->getParentClass();

            if ($parentClass && $parentClass->hasMethod($methodName)) {
                $method = $parentClass->getMethod($methodName);
                $method->invoke($this->mockMethod->getMockInstance(), ...$args);
            }

            if (is_callable($response)) {
                return $response(...$args);
            }
            return $response;
        });

    }

    private function when(bool $condition, Closure $ifStatement, Closure $elseStatement): mixed
    {
        if ($condition) {
            return $ifStatement();
        }
        return $elseStatement();
    }

    /**
     * @param object $mockInstance
     * @param Closure $response
     * @param array $actualArgs
     * @return mixed
     */
    private function bindMockInstanceToResponseClosure(object $mockInstance, Closure $response, array $actualArgs): mixed
    {
        return $this->when(
            !empty($mockInstance),
            fn() => $response([...$actualArgs, $mockInstance]),
            fn() => $response(...$actualArgs),
        );
    }
}
