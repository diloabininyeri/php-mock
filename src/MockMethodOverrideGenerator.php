<?php

namespace Zeus\Mock;

use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

abstract class MockMethodOverrideGenerator
{
    protected function generateMethodOverride(ReflectionMethod $method, bool $callParent = true): string
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
                $mockCode .= "        parent::$methodName($argList);\n"; // For static methods, use :
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
    protected function generateCallMethod(): string
    {
        return '    public function __call($methodName, array $arguments): mixed {
        if ($this->mockFactory->hasMethodMock($methodName)) {
            return $this->mockFactory->invokeMockedMethod($methodName, $arguments);
        }
        throw new \Zeus\Mock\MockMethodNotFoundException("Method $methodName not found or mocked.");
    }';
    }
}
