<?php

namespace Zeus\Mock\Tests\stubs;

/**
 *
 */
class Args
{


    /**
     * @param int $number
     * @param int $number1
     * @return int
     */
    public function example(int $number, int $number1):int
    {
        return $number1 + $number;
    }
}