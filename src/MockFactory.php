<?php


namespace Zeus\Mock;

use ReflectionException;
use Closure;

/**
 * @mixin MockMethodInterface
 */
class MockFactory
{


    /**
     * @param MockMethodInterface $mockMethod
     */
    public function __construct(private MockMethodInterface $mockMethod = new MockMethod())
    {
        $this->mockMethod->mockMethod('object.on.created', fn(...$args) => null);
    }

    /**
     * @param MockMethod $mockMethod
     * @return self
     */
    public static function from(MockMethod $mockMethod): self
    {
        return new self($mockMethod);
    }

    /**
     * @template T
     * @param string<T> $originalClass
     * @param bool $overrideConstruct
     * @param array $constructParameters
     * @return T of object
     * @throws ReflectionException
     */
    public function createMock(string $originalClass, array $constructParameters = [], bool $overrideConstruct = false): object
    {
        $mockClassName = $this->generateUniqueName($originalClass);
        eval($this->generateCode($originalClass, $mockClassName, $overrideConstruct));
        return new $mockClassName($this->mockMethod, $constructParameters);
    }

    /***
     * @param string $originalClass
     * @param string $mockClassName
     * @param bool $overrideConstruct
     * @return string
     * @throws ReflectionException
     */
    public function generateCode(string $originalClass, string $mockClassName, bool $overrideConstruct = false): string
    {
        if (interface_exists($originalClass)) {
            return new MockInterfaceGenerator()->generate($mockClassName, $originalClass);
        }
        return new MockClassGenerator()->generate($mockClassName, $originalClass, $overrideConstruct);
    }

    /**
     * @param string $originalClass
     * @return string
     */
    private function generateUniqueName(string $originalClass): string
    {
        return 'Mock_' .
            str_replace('\\', '_', $originalClass) . '_' .
            str_replace('.', '', uniqid(time(), true));
    }

    /**
     * @param Closure $closure
     * @return $this
     */
    public function onMockInstanceCreated(Closure $closure): self
    {
        $this->mockMethod->mockMethod('object.on.created', $closure);
        return $this;
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        return $this->mockMethod->$method(...$arguments);
    }

    /**
     * @param string $methodName
     * @return int
     */
    public function getCallCount(string $methodName): int
    {
        return $this->mockMethod->getCallCount($methodName);
    }

    /**
     * @return MockMethodInterface
     */
    public function getMockMethod(): MockMethodInterface
    {
        return $this->mockMethod;
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
     * @param Closure $mockMethodClosure
     * @return void
     */
    public function once(Closure $mockMethodClosure): void
    {
        $this->mockMethod->once($mockMethodClosure);
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

    /**
     * @param string $methodName
     * @return void
     */
    public function atMost(int $count, string $methodName, mixed $response): void
    {
        $this->mockMethod->add($methodName, function (...$args) use ($count, $response, $methodName) {
            static $callCount = 0;
            if ($callCount >= $count) {
                throw new AtMostMethodException("Method $methodName must be called at most $count times.");
            }
            if ($response instanceof Closure) {
                $callCount++;
                return $response(...$args);
            }
            $callCount++;
            return $response;
        });
    }
}
