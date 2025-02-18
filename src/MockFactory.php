<?php

namespace Zeus\Mock;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 *
 */
class MockFactory
{
    /**
     * @var array<string, Closure>
     */
    private array $methodMocks = [];

    /**
     * @template T
     * @param class-string<T> $originalClass
     * @return T
     * @throws ReflectionException
     */
    public function createMock(string $originalClass): object
    {
        $mockClassName = $this->generateMockClassName($originalClass);

        if (interface_exists($originalClass)) {
            eval($this->generateMockInterfaceClassCode($mockClassName, $originalClass));
        } else {
            eval($this->generateMockClassCode($mockClassName, $originalClass));
        }

        return new $mockClassName($this);
    }

    /**
     * @param string $methodName
     * @param Closure $closure
     * @return $this
     */
    public function mockMethod(string $methodName, Closure $closure): self
    {
        $this->methodMocks[$methodName] = $closure;
        return $this;
    }

    /**
     * @param string $methodName
     * @return bool
     */
    public function hasMethodMock(string $methodName): bool
    {
        return isset($this->methodMocks[$methodName]);
    }

    /**
     * @param string $methodName
     * @param array $args
     * @return mixed
     */
    public function invokeMockedMethod(string $methodName, array $args): mixed
    {
        return call_user_func_array($this->methodMocks[$methodName], $args);
    }

    /**
     * @param string $originalClass
     * @return string
     */
    private function generateMockClassName(string $originalClass): string
    {
        return 'Mock_' . str_replace('\\', '_', $originalClass) . '_' . uniqid();
    }

    /**
     * @param string $mockClassName
     * @param string $originalClass
     * @return string
     * @throws ReflectionException
     */
    private function generateMockClassCode(string $mockClassName, string $originalClass): string
    {
        $reflection = new ReflectionClass($originalClass);
        $mockCode = "class $mockClassName extends $originalClass {\n";
        $mockCode .= "private \$mockFactory;\n";
        $mockCode .= "public function __construct(\$mockFactory) { \$this->mockFactory = \$mockFactory; }\n";

        foreach ($reflection->getMethods() as $method) {
            $mockCode .= $this->generateMethodOverride($method);
        }

        $mockCode .= "}";
        return $mockCode;
    }

    /**
     * @param string $mockClassName
     * @param string $interfaceName
     * @return string
     * @throws ReflectionException
     */
    private function generateMockInterfaceClassCode(string $mockClassName, string $interfaceName): string
    {
        $reflection = new ReflectionClass($interfaceName);
        $mockCode = "class $mockClassName implements $interfaceName {\n";
        $mockCode .= "private object \$mockFactory;\n";
        $mockCode .= "public function __construct(\$mockFactory) { \$this->mockFactory = \$mockFactory; }\n";

        foreach ($reflection->getMethods() as $method) {
            $mockCode .= $this->generateMethodOverride($method, false);
        }

        $mockCode .= "}";
        return $mockCode;
    }

    /**
     * @param ReflectionMethod $method
     * @param bool $callParent
     * @return string
     */
    private function generateMethodOverride(ReflectionMethod $method, bool $callParent = true): string
    {
        $methodName = $method->getName();
        $returnType = $method->hasReturnType() ? ': ' . $method->getReturnType()->getName() : '';

        $parameters = [];
        $arguments = [];
        foreach ($method->getParameters() as $param) {
            $type = $param->hasType() ? $param->getType()->getName() . ' ' : '';
            $name = '$' . $param->getName();
            $parameters[] = $type . $name;
            $arguments[] = $name;
        }
        $paramList = implode(', ', $parameters);
        $argList = implode(', ', $arguments);

        $mockCode = "public function $methodName($paramList)$returnType {\n";
        $mockCode .= "    if (\$this->mockFactory->hasMethodMock('$methodName')) {\n";
        $mockCode .= "        return \$this->mockFactory->invokeMockedMethod('$methodName', [$argList]);\n";
        $mockCode .= "    }\n";

        if ($callParent) {
            $mockCode .= "    return parent::$methodName($argList);\n";
        } else {
            $mockCode .= "    throw new \BadMethodCallException('Method $methodName is not mocked.');\n";
        }

        $mockCode .= "}\n";
        return $mockCode;
    }
}
