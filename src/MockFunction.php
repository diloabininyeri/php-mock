<?php

namespace Zeus\Mock;

use Closure;

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

    public function scope(?string $namespace = null): void
    {
        $this->isScoped = true;
        if (!$this->isBuilt) {
            $namespace ??=$this->getNamespaceFromTrace(debug_backtrace());
            eval($this->generate($namespace));
            $this->isBuilt = true;
        }
    }

    private function getNamespaceFromTrace(array $trace): string
    {
        $file = $trace[0]['file'];
        $content = file_get_contents($file);
        if (preg_match('/\bnamespace\s+([^;]+);/', $content, $matches)) {
            return trim($matches[1]);
        }

        throw new NamespaceNotFound('the namespace keyword could not find');
    }


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
}
