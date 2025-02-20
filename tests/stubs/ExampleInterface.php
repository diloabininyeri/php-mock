<?php

namespace Zeus\Mock\Tests\stubs;

interface ExampleInterface
{


    public function hello(): string;

    public function welcome(string $message): string;

    public function goodbye(): void;
}