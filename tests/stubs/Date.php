<?php

namespace Zeus\Mock\Tests\stubs;

class Date
{


    public function now():string
    {
        return date('Y-m-d');
    }
}