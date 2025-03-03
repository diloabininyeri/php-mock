<?php

namespace Zeus\Mock;

use Closure;
use ReflectionObject;
use Zeus\Mock\Exceptions\InternalObjectException;
use Zeus\Mock\Exceptions\NamespaceNotFoundException;
use Zeus\Mock\Exceptions\OnceMockFunctionException;
use function call_user_func;

/**
 *
 */
class ScopedFunctionMocker
{
    /**
     * @var array<string,Closure>
     */
    private array $functions = [];

    /**
     * @var array<string, Closure> $originalFunctions
     */
    private array $originalFunctions = [];
    /**
     * @var bool
     */
    public bool $isScoped = false;
    /**
     * @var bool
     */
    private bool $isBuilt = false;
    /**
     * @var ScopedFunctionMocker
     */
    private static self $instances;

    /**
     * @var array
     */
    private static array $createdFunctions = [];


    private array $onceFunctions = [];


    /**
     * @var bool $onceMode
     */
    private bool $onceMode = false;

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
     * @param Closure $returnValue
     * @return void
     */
    public function add(string $name, mixed $returnValue): void
    {
        if ($this->onceMode && !in_array($name, $this->onceFunctions, true)) {
             $this->onceFunctions[] = $name;
        }
        if (!isset($this->originalFunctions[$name]) && function_exists($name)) {
            $this->originalFunctions[$name] = $name(...);
        }

        if (!($returnValue instanceof Closure)) {
            $returnValue = static fn() => $returnValue;
        }

        $this->functions[$name] = $returnValue;
    }

    /***
     * @noinspection PhpUnused
     * @param string $name
     * @return void
     */
    public function restoreOriginalFunction(string $name): void
    {
        if (isset($this->originalFunctions[$name])) {
            $this->functions[$name] = $this->originalFunctions[$name];
        }
    }

    /**
     * @noinspection PhpUnused
     * @param string $name
     * @param mixed $returnValue
     * @return void
     */
    public function addIfNotDefined(string $name, mixed $returnValue): void
    {
        if (!$this->has($name)) {
            $this->add($name, $returnValue);
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->functions[$name]);
    }

    /**
     * @param string $name
     * @param array $args
     * @return mixed
     */
    private function call(string $name, array $args): mixed
    {
        if (in_array($name, $this->onceFunctions, true)) {
            $calledCount = $this->getCalledCountInScope($name);
            if ($calledCount !== 0) {
                throw new OnceMockFunctionException("$name cant be called more than once ");
            }
        }
        return $this->executeWithEffect(
            call_user_func($this->functions[$name], ...$args),
            fn() => $this->incrementCount($name)
        );
    }

    /**
     * @param string $name
     * @param mixed ...$args
     * @return mixed
     */
    private function callGlobalFunction(string $name, mixed ...$args): mixed
    {
        return $this->executeWithEffect(
            $name(...$args),
            fn() => $this->incrementCount($name)
        );
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
            $code .= "    if (\Zeus\Mock\ScopedFunctionMocker::runningScope()) {\n";
            $code .= "        return \Zeus\Mock\ScopedFunctionMocker::callMockFunction('$name', func_get_args());\n";
            $code .= "    }\n";
            $code .= "    return \Zeus\Mock\ScopedFunctionMocker::callRealFunction('$name', ...func_get_args());\n";

            $code .= "}\n";
        }

        return $code;
    }

    /**
     * @param mixed $returnValue
     * @param Closure $effect
     * @return mixed
     */
    private function executeWithEffect(mixed $returnValue, Closure $effect): mixed
    {
        $effect();
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
        if (preg_match('/\bnamespace\s+([^;]+);/', file_get_contents($trace[0]['file']), $matches)) {
            return trim($matches[1]);
        }
        throw new NamespaceNotFoundException('the namespace keyword could not find');
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

    /**
     * @param string $functionName
     * @return int
     */
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
            throw new NamespaceNotFoundException('The namespace keyword could not find in object: ');
        }
        $this->scope($reflectionObject->getNamespaceName());
        $result = $closure($object);
        $this->endScope();
        return $result;
    }


    public function once(Closure $closure):void
    {
        $this->onceMode = true;
        $closure($this);
        $this->onceMode = false;
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

    public function addConsecutive(string $name, array $array):void
    {
        $this->add($name,function () use (&$array):mixed{
            return array_shift($array);
        });
    }
}
