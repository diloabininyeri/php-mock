<?php

namespace Zeus\Mock\Tests\feature;

use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Zeus\Mock\MockFactory;

class MockWithPdoTest extends TestCase
{

    #[Test]
    /**
     * @throws ReflectionException
     */
    public function fetch():void
    {
        $dsn = 'mysql:host=127.0.0.1;dbname=test;port=3306';
        $username = 'root';
        $password = 'my-secret-pw';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];



        $mockFactory = new MockFactory();

        $mockStatement = $mockFactory->createMock(PDOStatement::class);
        $mockFactory->mockMethod('execute', fn($params) => true);
        $mockFactory->mockMethod('fetch', fn($fetchMode) => ['id' => 1, 'name' => 'Dilo Surucu']);

        $mockPdo = $mockFactory->createMock(PDO::class,[
            'dsn' => $dsn,
            'username' => $username,
            'password' => $password,
            'options' => $options
        ],true);
        $mockFactory->mockMethod('prepare', fn($query) => $mockStatement);

        $statement=$mockPdo->prepare('select * from users where id=1');

        $this->assertEquals($statement,$mockStatement);
        $this->assertTrue($mockPdo->execute([]));
        $this->assertEquals(['id' => 1, 'name' => 'Dilo Surucu'], $mockPdo->fetch(PDO::FETCH_ASSOC));
    }
}