<?php


namespace Zeus\Mock;

use ReflectionException;

/**
 * @mixin MockMethod
 */
class MockFactory
{
    /**
     * @var MockMethod
     */
    private MockMethod $mockMethod;

    /**
     *
     */
    public function __construct()
    {
        $this->mockMethod = new MockMethod();
    }
    /**
     * @template T
     * @param string<T> $originalClass
     * @param bool $overrideConstruct
     * @param array $constructParameters
     * @return T of object
     * @throws ReflectionException
     */
    public function createMock(string $originalClass, array $constructParameters = [],bool $overrideConstruct=false): object
    {
        $mockClassName = $this->generateUniqueName($originalClass);
        eval($this->generateCode($originalClass,$mockClassName,$overrideConstruct));
        return new $mockClassName($this->mockMethod, $constructParameters);
    }
    /***
     * @param string $originalClass
     * @param string $mockClassName
     * @param bool $overrideConstruct
     * @return string
     * @throws ReflectionException
     */
    public function generateCode(string $originalClass,string $mockClassName,bool $overrideConstruct=false): string
    {
        if (interface_exists($originalClass)) {
            return new MockInterfaceGenerator()->generate($mockClassName, $originalClass);
        }
        return new MockClassGenerator()->generate($mockClassName, $originalClass,$overrideConstruct);
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
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        return $this->mockMethod->$method(...$arguments);
    }
}
