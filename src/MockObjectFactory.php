<?php


namespace Zeus\Mock;

use Closure;
use ReflectionException;
use Zeus\Mock\Generators\MockClassGenerator;
use Zeus\Mock\Generators\MockInterfaceGenerator;
use Zeus\Mock\Generators\MockMethodInterface;
use Zeus\Mock\Mock\MethodCountRules;
use Zeus\Mock\Mock\MockMethod;

/**
 * @mixin MockMethodInterface
 * @mixin MethodCountRules
 */
readonly class MockObjectFactory
{
    private MethodCountRules $methodCountRules;
    /**
     * @param MockMethodInterface $mockMethod
     */
    public function __construct(private MockMethodInterface $mockMethod = new MockMethod())
    {
        $this->mockMethod->mockMethod('object.on.created', fn(...$args) => null);
        $this->methodCountRules = new MethodCountRules($this->mockMethod);
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
        if (method_exists($this->mockMethod, $method)) {
            return $this->mockMethod->$method(...$arguments);
        }
        return $this->methodCountRules->$method(...$arguments);
    }

    /**
     * @noinspection PhpUnused
     * @param string $methodName
     * @return int
     */
    public function getCallCount(string $methodName): int
    {
        return $this->mockMethod->getCallCount($methodName);
    }

    /**
     * @noinspection PhpUnused
     * @return MockMethodInterface
     */
    public function getMockMethod(): MockMethodInterface
    {
        return $this->mockMethod;
    }

    /**
     * @param string $methodName
     * @param mixed $response
     * @return void
     */
    public function method(string $methodName, mixed $response): void
    {
        $this->mockMethod($methodName, $response);
    }
}
