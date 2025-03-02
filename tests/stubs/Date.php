<?php

namespace Zeus\Mock\Tests\stubs;

class Date
{


    public function now(int $a):string
    {
        return date('Y-m-d');
    }
}