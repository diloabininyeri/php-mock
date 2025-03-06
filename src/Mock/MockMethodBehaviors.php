<?php

namespace Zeus\Mock\Mock;

use Closure;
use RuntimeException;
use Throwable;
use Zeus\Mock\Exceptions\AtLeastMethodException;
use Zeus\Mock\Exceptions\AtMostMethodException;
use Zeus\Mock\Exceptions\MaxRetriedException;
use Zeus\Mock\Exceptions\NeverMethodException;
use Zeus\Mock\Exceptions\TimeoutException;
use Zeus\Mock\Exceptions\WithArgsMethodException;
use Zeus\Mock\Exceptions\WithArgumentMismatchException;
use function microtime;

/**
 *
 */
readonly class MockMethodBehaviors
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
     * @param mixed $return
     * @return void
     */
    public function atMost(int $count, string $methodName, mixed $return): void
    {
        $this->mockMethod->add($methodName, function (...$args) use ($count, $return, $methodName) {
            static $callCount = 0;
            if ($callCount >= $count) {
                throw new AtMostMethodException("Method $methodName must be called at most $count times.");
            }
            $callCount++;
            return $this->resolveResponseWithSideEffect(
                return: $return,
                args: $args
            );
        });
    }

    /**
     * @noinspection PhpUnused
     * @param int $delay
     * @param string $methodName
     * @param mixed $return
     * @return void
     */
    public function afterDelay(int $delay, string $methodName, mixed $return): void
    {
        $this->mockMethod->add($methodName, function (...$args) use ($delay, $return) {
            return $this->resolveResponseWithSideEffect($return, fn() => sleep($delay), $args);
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
            return $this->resolveResponseWithSideEffect(return: $response, args: $args);
        });
    }

    /**
     * @param string $methodName
     * @param array $arguments
     * @param mixed $return
     * @return void
     */
    public function withArgs(string $methodName, array $arguments, mixed $return): void
    {
        $this->mockMethod->add($methodName, function (...$actualArgs) use ($arguments, $return, $methodName) {

            $mockInstance = null;
            if ($this->mockMethod->getMockInstance()) {
                $mockInstance = array_pop($actualArgs);
            }
            if ($actualArgs === $arguments) {
                if ($return instanceof Closure) {
                    return $this->applyMockInstanceToResponse($mockInstance, $return, $actualArgs);
                }
                return $return;
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

            return $this->resolveResponseWithSideEffect(
                $mainResponseClosure,
                fn($return) => $afterClosure($return),
                $args
            );
        });
    }

    /**
     * @noinspection PhpUnused
     * @param array<string,mixed> $return
     * @return void
     */
    public function applyDefaultMockMethods(array $return): void
    {
        foreach ($return as $key => $response) {
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
     * @param mixed $return
     * @return void
     */
    public function withArgumentsMatching(string $methodName, callable $argumentMatcher, mixed $return): void
    {
        $this->mockMethod->add($methodName, function (...$args) use ($argumentMatcher, $return, $methodName) {
            $mockInstance = $this->mockMethod->getMockInstance();
            if ($mockInstance) {
                array_pop($args);
            }
            if ($argumentMatcher(...$args)) {
                if (is_callable($return)) {
                    return $this->applyMockInstanceToResponse($mockInstance, $return, $args);
                }
                return $return;
            }
            throw new WithArgumentMismatchException("Arguments don't match for method $methodName.");
        });
    }

    /**
     * @param int $count
     * @param string $methodName
     * @param mixed $return
     * @return void
     */
    public function atLeast(int $count, string $methodName, mixed $return): void
    {
        $this->mockMethod->add($methodName, function (...$args) use ($count, $return, $methodName) {
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

            return $this->resolveResponseWithSideEffect(
                return: $return,
                args: $args
            );
        });
    }

    /**
     * @param string $methodName
     * @param mixed $return
     * @return void
     */
    public function spyMethod(string $methodName, mixed $return): void
    {
        $this->mockMethod->add($methodName, function (...$args) use ($return, $methodName) {
            $this->mockMethod->callOriginalMethod($methodName, $args);
            return $this->resolveResponseWithSideEffect(return: $return, args: $args);
        });
    }

    /**
     * @param bool $condition
     * @param Closure $ifStatement
     * @param Closure $elseStatement
     * @return mixed
     */
    private function ifElseCondition(bool $condition, Closure $ifStatement, Closure $elseStatement): mixed
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
    private function applyMockInstanceToResponse(object $mockInstance, Closure $response, array $actualArgs): mixed
    {
        return $this->ifElseCondition(
            !empty($mockInstance),
            fn() => $response([...$actualArgs, $mockInstance]),
            fn() => $response(...$actualArgs),
        );
    }

    /**
     * @param mixed $return
     * @param Closure|null $sideEffect
     * @param array $args
     * @return mixed
     */
    private function resolveResponseWithSideEffect(mixed $return, ?Closure $sideEffect = null, array $args = []): mixed
    {
        $returnValue = is_callable($return) ? $return(...$args) : $return;
        if ($sideEffect) {
            $sideEffect($returnValue);
        }
        return $returnValue;
    }

    /**
     * @param int $timeout
     * @param string $methodName
     * @param mixed $return
     * @return void
     */
    public function withTimeout(int $timeout, string $methodName, mixed $return): void
    {
        $this->mockMethod->add($methodName, function (...$args) use ($timeout, $return, $methodName) {
            $startTime = microtime(true);
            $response = $this->resolveResponseWithSideEffect($return, args: $args);
            $elapsedTime = microtime(true) - $startTime;
            if ($elapsedTime > $timeout) {
                throw new TimeoutException("Method $methodName timed out.");
            }
            return $response;

        });
    }

    /**
     * @param Closure $closure
     * @return void
     */
    public function always(Closure $closure): void
    {
        $this->mockMethod->always($closure);
    }

    /**
     * @param string $methodName
     * @param Closure $closure
     * @return void
     */
    public function monitoringMethod(string $methodName, Closure $closure): void
    {
        $this->mockMethod->monitorMethod($methodName, $closure);
    }

    /**
     * @param array $methods
     * @param Closure $handle
     * @return void
     */
    public function monitoringMethods(array $methods,Closure $handle): void
    {
        foreach ($methods as $method) {
            $this->monitoringMethod($method, $handle);
        }
    }
    /**
     * @param string $logFile
     * @return void
     */
    public function log(string $logFile): void
    {
        $this->always(function (array $args) use ($logFile) {

            $logMessage = sprintf(
                "[%s] %s::%s called with arguments: %s and returned: %s\n",
                date('Y-m-d H:i:s'),
                $args['class'] ?? 'interface',
                $args['methodName'],
                json_encode($args['arguments'], JSON_THROW_ON_ERROR),
                json_encode($args['returnValue'], JSON_THROW_ON_ERROR)
            );

            $logDirectory = dirname($logFile);

            if (!is_dir($logDirectory) && !mkdir($logDirectory, 0755, true) && !is_dir($logDirectory)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $logDirectory)); //NOSONAR
            }

            file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        });
    }

    /**
     * @param int $maxAttempts
     * @param string $methodName
     * @param mixed $return
     * @return void
     */
    public function retry(int $maxAttempts,string $methodName,mixed $return):void
    {
        $this->mockMethod->add($methodName,function (...$args) use ($methodName,$maxAttempts,$return){
            $counter = 0;
            while($counter < $maxAttempts){
                try{
                    return $this->resolveResponseWithSideEffect($return,args: $args);
                } catch(Throwable){
                    $counter++;
                    usleep(1000);
                }
            }
            throw new MaxRetriedException("Method '$methodName' failed after $maxAttempts attempts.");

        });
    }

}
