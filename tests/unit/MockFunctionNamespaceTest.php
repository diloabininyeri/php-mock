<?php

namespace Zeus\Mock\Tests\unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Zeus\Mock\MockFunction;

class MockFunctionNamespaceTest extends TestCase
{


    #[Test]
    public function scopeWithoutNamespace1(): void
    {

        $mk = new MockFunction();
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