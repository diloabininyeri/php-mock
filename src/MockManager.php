<?php

namespace Zeus\Mock;

use BadMethodCallException;

/**
 * @method static ScopedFunctionMocker function()
 * @method static MockObjectFactory object()
 */
class MockManager
{
    public static function __callStatic(string $name, array $arguments)
    {
        return match($name){
            'function' => new ScopedFunctionMocker(...$arguments),
            'object' => new MockObjectFactory(...$arguments),
            default => throw new BadMethodCallException("Method $name not found in MockManger.")
        };
    }
}
