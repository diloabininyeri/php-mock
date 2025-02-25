<?php

namespace Zeus\Mock;

use Closure;
use ReflectionObject;

/**
 * @method static callFunction(string $name, array $args)
 * @method static bool runningScope()
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
    public function call(string $name, array $args): mixed
    {
        $func = $this->functions[$name];
        return $func(...$args);
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
            $code .= "        return \Zeus\Mock\MockFunction::callFunction('$name',func_get_args());\n";
            $code .= "    }\n";
            $code .= "    return \\$name(...func_get_args());\n";
            $code .= "}\n";
        }

        return $code;
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
     * @param string $method
     * @param array $arguments
     * @return mixed|void
     */
    public static function __callStatic(string $method, array $arguments)
    {
        return match ($method) {
            'callFunction' => static::$instances->call(...$arguments),
            'runningScope' => static::$instances->isScoped,
            default => null,
        };
    }

    /**
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
}
