<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zeus\Mock\ScopedFunctionMocker;

class MockFunctionNamespaceTest extends TestCase
{


    #[Test]
    public function scopeWithoutNamespace1(): void
    {

        $mk = new ScopedFunctionMocker();
        $mk->add('exec', function (string $command) {
            return [
                'a', 'b', 'c'
            ];
        });
        $mk->scope();

        $this->assertEquals(['a', 'b', 'c'], exec('ls -l'));

        $mk->endScope();
    }
}