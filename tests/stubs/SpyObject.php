<?php

namespace Zeus\Mock\Tests\stubs;

use Zeus\Mock\Exceptions\SpyMethodException;

class SpyObject
{

    public function example(): string
    {
        throw new SpyMethodException(__METHOD__);
    }
}