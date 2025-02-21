<?php

namespace Zeus\Mock;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;


/**
 *
 */
class MockFactory
{
    /**
     * @var array
     */
    private array $methodMocks = [];

    /**
     * @template T
     * @param string $originalClass
     * @param array $constructParameters
     * @return object<T>
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
        $mockCode .= "    private \$mockFactory;\n";

        if ($reflection->hasMethod('__construct')) {
            $mockCode .= "    public function __construct(\$mockFactory, array \$params = []) {\n";
            $mockCode .= "        \$this->mockFactory = \$mockFactory;\n";
            $mockCode .= "        parent::__construct(...\$params);\n";
        } else {
            $mockCode .= "    public function __construct(\$mockFactory) {\n";
            $mockCode .= "        \$this->mockFactory = \$mockFactory;\n";
        }
        $mockCode .= "    }\n";

        foreach ($reflection->getMethods() as $method) {
            if ($method->getName() === '__construct') {
                continue;
            }
            $mockCode .= $this->generateMethodOverride($method);
        }

        $mockCode .= $this->generateCallMethod();
        $mockCode .= "}\n";
        return $mockCode;
    }

    /**
     * @throws ReflectionException
     */
    private function generateMockInterfaceClassCode(string $mockClassName, string $interfaceName): string
    {
        $reflection = new ReflectionClass($interfaceName);
        $mockCode = "class $mockClassName implements $interfaceName {\n";
        $mockCode .= "    private object \$mockFactory;\n";
        $mockCode .= "    public function __construct(\$mockFactory) { \$this->mockFactory = \$mockFactory; }\n";

        foreach ($reflection->getMethods() as $method) {
            $mockCode .= $this->generateMethodOverride($method, false);
        }

        $mockCode .= "}\n";
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
        $returnType = $this->getReturnTypeString($method);

        $isVoid = $method->hasReturnType() && $this->isVoidReturnType($method->getReturnType());

        [$paramList, $argList] = $this->generateParameterList($method);

        $isStatic = $method->isStatic();

        $mockCode = "    public " . ($isStatic ? 'static ' : '') . "function $methodName($paramList)$returnType {\n";
        $mockCode .= "        if (\$this->mockFactory->hasMethodMock('$methodName')) {\n";

        if ($isVoid) {
            $mockCode .= "            \$this->mockFactory->invokeMockedMethod('$methodName', [$argList]);\n";
            $mockCode .= "            return;\n";
        } else {
            $mockCode .= "            return \$this->mockFactory->invokeMockedMethod('$methodName', [$argList]);\n";
        }

        $mockCode .= "        }\n";
        if ($callParent) {
            if ($isStatic) {
                $mockCode .= "        parent::$methodName($argList);\n"; // For static methods, use ::
            } elseif ($isVoid) {
                $mockCode .= "        parent::$methodName($argList);\n";
            } else {
                $mockCode .= "        return parent::$methodName($argList);\n";
            }
        } else {
            $mockCode .= "        throw new \\Zeus\\Mock\\MockMethodNotFoundException('Method $methodName is not mocked.');\n";
        }

        $mockCode .= "    }\n";
        return $mockCode;
    }


    /**
     * @param ReflectionMethod $method
     * @return array
     */
    private function generateParameterList(ReflectionMethod $method): array
    {
        $parameters = [];
        $arguments = [];
        foreach ($method->getParameters() as $param) {
            if ($param->isVariadic()) {
                $parameters[] = $this->getParameterDeclaration($param);
                $arguments[] = "...\$" . $param->getName();
            } else {
                $parameters[] = $this->getParameterDeclaration($param);
                $arguments[] = '$' . $param->getName();
            }
        }

        return [
            implode(', ', $parameters),
            implode(', ', $arguments)
        ];
    }

    /**
     * @param ReflectionParameter $param
     * @return string
     */
    private function getParameterDeclaration(ReflectionParameter $param): string
    {
        $declaration = $this->getTypeDeclaration($param);
        $declaration .= $param->isPassedByReference() ? '&' : '';
        $declaration .= $param->isVariadic() ? '...' : '';
        $declaration .= '$' . $param->getName();

        if (!$param->isVariadic() && $param->isDefaultValueAvailable()) {
            $declaration .= ' = ' . $this->getDefaultValue($param);
        }

        return $declaration;
    }


    /**
     * @param ReflectionParameter $param
     * @return string
     */
    private function getTypeDeclaration(ReflectionParameter $param): string
    {
        return $param->hasType() ? $this->getTypeString($param->getType()) . ' ' : '';
    }

    /**
     * @param ReflectionParameter $param
     * @return string
     */
    private function getDefaultValue(ReflectionParameter $param): string
    {
        try {
            if ($param->isDefaultValueConstant()) {
                return $param->getDefaultValueConstantName();
            }
            return $this->formatDefaultValue($param->getDefaultValue());
        } catch (ReflectionException) {
            return '';
        }
    }

    /***
     * @param mixed $defaultValue
     * @return string
     */
    private function formatDefaultValue(mixed $defaultValue): string
    {
        return match (true) {
            is_array($defaultValue) => '[]',
            is_null($defaultValue) => 'null',
            is_bool($defaultValue) => $defaultValue ? 'true' : 'false',
            is_int($defaultValue) || is_float($defaultValue) => (string)$defaultValue,
            is_string($defaultValue) => var_export($defaultValue, true),
            default => ''
        };
    }


    /**
     * @param ReflectionMethod $method
     * @return string
     */
    private function getReturnTypeString(ReflectionMethod $method): string
    {
        if (!$method->hasReturnType()) {
            return '';
        }
        return ':' . $this->getTypeString($method->getReturnType());

    }


    /**
     * @param ReflectionType $type
     * @return string
     */
    private function getTypeString(ReflectionType $type): string
    {
        if ($type instanceof ReflectionUnionType) {
            $typeNames = array_map(
                static fn($t) => $t instanceof ReflectionNamedType ? $t->getName() : (string)$t,
                $type->getTypes()
            );
            return implode('|', $typeNames);
        }

        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();
            return $type->allowsNull() && $typeName !== 'mixed' ? '?' . $typeName : $typeName;
        }

        return (string)$type;
    }

    /**
     * @param ReflectionType|null $type
     * @return bool
     */
    private function isVoidReturnType(?ReflectionType $type): bool
    {
        if ($type instanceof ReflectionNamedType) {
            return $type->getName() === 'void';
        }
        return false;
    }

    /**
     * @return string
     */
    private function generateCallMethod(): string
    {
        return '    public function __call($methodName, array $arguments): mixed {
        if ($this->mockFactory->hasMethodMock($methodName)) {
            return $this->mockFactory->invokeMockedMethod($methodName, $arguments);
        }
        throw new \Zeus\Mock\MockMethodNotFoundException("Method $methodName not found or mocked.");
    }';
    }
}