<?php

namespace Zeus\Mock\Mock;

use Closure;
use Throwable;
use Zeus\Mock\Exceptions\AtLeastMethodException;
use Zeus\Mock\Exceptions\AtMostMethodException;
use Zeus\Mock\Exceptions\NeverMethodException;
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
            if ($actualArgs === $arguments) {
                if ($response instanceof Closure) {
                    return $response(...$actualArgs);
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
            if ($argumentMatcher(...$args)) {
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
}
