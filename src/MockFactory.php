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
        if (interface_exists($originalClass)) {
            eval(new MockInterfaceGenerator()->generate($mockClassName, $originalClass));
        } else {
            eval(new MockClassGenerator()->generate($mockClassName, $originalClass));
        }
        return new $mockClassName($this->mockMethod, $constructParameters);
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
