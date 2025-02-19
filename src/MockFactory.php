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
    public function createMock(string $originalClass, array $constructParameters = []): object
    {
        $mockClassName = $this->generateMockClassName($originalClass);
        if (interface_exists($originalClass)) {
            eval($this->generateMockInterfaceClassCode($mockClassName, $originalClass));
        } else {
            eval($this->generateMockClassCode($mockClassName, $originalClass));
        }
        return new $mockClassName($this, $constructParameters);
    }

    /**
     * @noinspection PhpUnused
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
     * @noinspection PhpUnused
     * @param string $methodName
     * @param array $args
     * @return mixed
     */
    public function invokeMockedMethod(string $methodName, array $args = []): mixed
    {
        if ($this->hasMethodMock($methodName)) {
            return call_user_func_array($this->methodMocks[$methodName], $args);
        }
        throw new MockMethodNotFoundException("Method $methodName is not mocked.");
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

        if ($reflection->hasMethod('__construct')) {
            $mockCode .= "public function __construct(\$mockFactory, array \$params = []) {\n";
            $mockCode .= "    \$this->mockFactory = \$mockFactory;\n";
            $mockCode .= "    parent::__construct(...\$params);\n";
        } else {
            $mockCode .= "public function __construct(\$mockFactory) {\n";
            $mockCode .= "    \$this->mockFactory = \$mockFactory;\n";
        }
        $mockCode .= "}\n";

        foreach ($reflection->getMethods() as $method) {
            if ($method->getName() === '__construct') {
                continue;
            }
            $mockCode .= $this->generateMethodOverride($method);
        }

        $mockCode .= $this->generateCallMethod();

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
        $returnType = $method->hasReturnType() ? ': ' . $method->getReturnType()?->getName() : '';

        $isVoid = $method->hasReturnType() && $method->getReturnType()?->getName() === 'void';

        $parameters = [];
        $arguments = [];
        foreach ($method->getParameters() as $param) {
            $type = $param->hasType() ? $param->getType()?->getName() . ' ' : '';
            $name = '$' . $param->getName();
            $parameters[] = $type . $name;
            $arguments[] = $name;
        }
        $paramList = implode(', ', $parameters);
        $argList = implode(', ', $arguments);

        $mockCode = "public function $methodName($paramList)$returnType {\n";
        $mockCode .= "    if (\$this->mockFactory->hasMethodMock('$methodName')) {\n";

        if ($isVoid) {
            $mockCode .= "        \$this->mockFactory->invokeMockedMethod('$methodName', [$argList]);\n";
            $mockCode .= "        return;\n";
        } else {
            $mockCode .= "        return \$this->mockFactory->invokeMockedMethod('$methodName', [$argList]);\n";
        }

        $mockCode .= "    }\n";
        if ($callParent) {
            if ($isVoid) {
                $mockCode .= "    parent::$methodName($argList);\n";
            } else {
                $mockCode .= "    return parent::$methodName($argList);\n";
            }
        } else {
            $mockCode .= "    throw new MockMethodNotFoundException('Method $methodName is not mocked.');\n";
        }

        $mockCode .= "}\n";
        return $mockCode;
    }

    private function generateCallMethod(): string
    {
        return 'public function __call($methodName,array $arguments):mixed {
        if ($this->mockFactory->hasMethodMock($methodName)) {
            return $this->mockFactory->invokeMockedMethod($methodName, $arguments);
        }
        throw new Zeus\Mock\MockMethodNotFoundException("Method $methodName not found or mocked.");
    }';
    }

}
