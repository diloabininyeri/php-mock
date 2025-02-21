<?php


namespace Zeus\Mock;

use ReflectionException;

/**
 * @mixin MockMethod
 */
class MockFactory
{

    private MockMethod $mockMethod;


    public function __construct()
    {
        $this->mockMethod = new MockMethod();
    }


    /**
     * @template T
     * @param string<T> $originalClass
     * @param array $constructParameters
     * @return T of object
     * @throws ReflectionException
     */
    public function createMock(string $originalClass, array $constructParameters = []): object
    {
        $mockClassName = $this->generateUniqueName($originalClass);
        eval($this->generateCode($originalClass));
        return new $mockClassName($this->mockMethod, $constructParameters);
    }


    /**
     * @param string $originalClass
     * @return string
     * @throws ReflectionException
     */
    public function generateCode(string $originalClass): string
    {
        $mockClassName = $this->generateUniqueName($originalClass);
        if (interface_exists($originalClass)) {
            return new MockInterfaceGenerator()->generate($mockClassName, $originalClass);
        }
        return new MockClassGenerator()->generate($mockClassName, $originalClass);
    }

    private function generateUniqueName(string $originalClass): string
    {
        return 'Mock_' .
            str_replace('\\', '_', $originalClass) . '_' .
            str_replace('.', '', uniqid(time(), true));
    }


    public function __call(string $method, array $arguments)
    {
        return $this->mockMethod->$method(...$arguments);
    }
}
