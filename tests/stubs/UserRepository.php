<?php

namespace Zeus\Mock\Tests\stubs;

class UserRepository implements UserRepositoryInterface
{

    public function getById(int $id):int
    {
        return $id;
    }
}