<?php

namespace Zeus\Mock\Tests\stubs;

interface UserRepositoryInterface
{

    public function getById(int $id):int;
}