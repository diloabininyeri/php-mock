<?php

namespace Zeus\Mock;

use Closure;
use ReflectionObject;

/**
 *
 */
class MockFunction
{
    /**
     * @var array
     */
    private array $functions = [];
    /**
     * @var bool
     */
    public bool $isScoped = false;
    /**
     * @var bool
     */
    private bool $isBuilt = false;
    /**
     * @var MockFunction
     */
    private static self $instances;

    /**
     * @var array
     */
    private static array $createdFunctions = [];

    /**
     * @var array<string, array{in_scope: int, out_scope: int}> $calledCount
     */
    private array $calledCount = [];

    /**
     *
     */
    public function __construct()
    {
        static::$instances = $this;
    }

    /**
     * @param string $name
     * @param Closure $function
     * @return void
     */
    public function add(string $name, Closure $function): void
    {
        $this->functions[$name] = $function;
    }

    /**
     * @param string $name
     * @param array $args
     * @return mixed
     */
    private function call(string $name, array $args): mixed
    {
        $func = $this->functions[$name];
        $returnValue = $func(...$args);
        $this->incrementCount($name);
        return $returnValue;
    }

    /**
     * @param string $name
     * @return void
     */
    private function incrementCount(string $name): void
    {
        $this->calledCount[$name]['in_scope'] ??= 0;
        $this->calledCount[$name]['out_scope'] ??= 0;
        if ($this->isScoped === true) {
            ++$this->calledCount[$name]['in_scope'];
            return;
        }
        ++$this->calledCount[$name]['out_scope'];
    }

    /**
     * @param string $namespace
     * @return string
     */
    private function generate(string $namespace): string
    {
        $code = "namespace $namespace;\n\n";

        foreach ($this->functions as $name => $_) {
            if (in_array($name, static::$createdFunctions, true)) {
                continue;
            }
            static::$createdFunctions[] = $name;

            $code .= "function $name() {\n";
            $code .= "    if (\Zeus\Mock\MockFunction::runningScope()) {\n";
            $code .= "        return \Zeus\Mock\MockFunction::callMockFunction('$name', func_get_args());\n";
            $code .= "    }\n";
            $code .= "    return \Zeus\Mock\MockFunction::callRealFunction('$name', ...func_get_args());\n";

            $code .= "}\n";
        }

        return $code;
    }

    private function callGlobalFunction(string $name, mixed ...$args): mixed
    {
        $returnValue = $name(...$args);
        $this->incrementCountOutOfScope($name);
        return $returnValue;
    }

    /**
     * @param string|null $namespace
     * @return void
     */
    public function scope(?string $namespace = null): void
    {
        $this->isScoped = true;
        if (!$this->isBuilt) {
            $namespace ??= $this->getNamespaceFromTrace(debug_backtrace());
            eval($this->generate($namespace));
            $this->isBuilt = true;
        }
    }

    /**
     * @param array $trace
     * @return string
     */
    private function getNamespaceFromTrace(array $trace): string
    {
        $file = $trace[0]['file'];
        if (preg_match('/\bnamespace\s+([^;]+);/', file_get_contents($file), $matches)) {
            return trim($matches[1]);
        }
        throw new NamespaceNotFound('the namespace keyword could not find');
    }


    /**
     * @return void
     */
    public function endScope(): void
    {
        $this->isScoped = false;
    }

    /**
     * @param string $functionName
     * @return array{in_scope:int,out_scope:int}
     */

    public function getCalledCount(string $functionName): array
    {
        return $this->calledCount[$functionName] ?? ['in_scope' => 0, 'out_scope' => 0];
    }

    /**
     * @noinspection PhpUnused
     * @return void
     */
    public function resetCounts(): void
    {
        $this->calledCount = [];
    }

    /**
     * @param string $functionName
     * @return int
     */
    public function getCalledCountOutScope(string $functionName): int
    {
        return $this->getCalledCount($functionName)['out_scope'];
    }

    public function getTotalCount(string $functionName): int
    {
        $calledCount = $this->getCalledCount($functionName);
        return $calledCount['in_scope'] + $calledCount['out_scope'];
    }

    /**
     * @noinspection PhpUnused
     * @param string $name
     * @return int
     */
    public function getCalledCountInScope(string $name): int
    {
        return $this->getCalledCount($name)['in_scope'];
    }

    /**
     * @noinspection PhpUnused
     * @param object $object
     * @param Closure $closure
     * @return mixed
     */
    public function runWithMock(object $object, Closure $closure): mixed
    {
        $reflectionObject = new ReflectionObject($object);

        if ($reflectionObject->isInternal()) {
            throw new InternalObjectException("Can't mock internal classes: " . $object::class);
        }

        if (empty($reflectionObject->getNamespaceName())) {
            throw new NamespaceNotFound('The namespace keyword could not find in object: ');
        }
        $this->scope($reflectionObject->getNamespaceName());
        $result = $closure($object);
        $this->endScope();
        return $result;
    }

    /**
     * @param string $name
     * @return void
     */
    private function incrementCountOutOfScope(string $name): void
    {
        $this->calledCount[$name][$name] ??= 0;
        ++$this->calledCount[$name]['out_scope'];
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed|void
     */
    public static function __callStatic(string $method, array $arguments)
    {
        return match ($method) {
            'callMockFunction' => static::$instances->call(...$arguments),
            'runningScope' => static::$instances->isScoped,
            'callRealFunction' => static::$instances->callGlobalFunction(...$arguments),
            default => null,
        };
    }
}
