<?php

namespace Zeus\Mock\Tests\feature;

use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Zeus\Mock\MockFactory;

/**
 *
 */
class MockWithPdoWithoutConnectTest extends TestCase
{

    /**
     * @return void
     * @throws ReflectionException
     */
    #[Test]
    public function pdoWithoutConnect():void
    {
        $mockFactory = new MockFactory();

        $mockStatement = $mockFactory->createMock(PDOStatement::class);
        $mockFactory->mockMethod('execute', fn($params) => true);
        $mockFactory->mockMethod('fetch', fn($fetchMode) => ['id' => 1, 'name' => 'Dilo Surucu']);

        $mockPdo = $mockFactory->createMock(PDO::class,[],true);
        $mockFactory->mockMethod('prepare', fn($query) => $mockStatement);

        $statement=$mockPdo->prepare('select * from users where id=1');

        $this->assertEquals($statement,$mockStatement);
        $this->assertTrue($mockPdo->execute([]));
        $this->assertEquals(['id' => 1, 'name' => 'Dilo Surucu'], $mockPdo->fetch(PDO::FETCH_ASSOC));
    }
}