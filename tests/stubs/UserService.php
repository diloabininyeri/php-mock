<?php

namespace Zeus\Mock\Tests\stubs;

class UserService
{


    public function __construct(private UserRepositoryInterface $user)
    {
    }


    public function getUserById(int $id): int
    {
        return $this->user->getById($id);
    }

}