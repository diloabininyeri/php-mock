<?php

use Zeus\Mock\MockFactory;

require_once 'vendor/autoload.php';
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

// Mock PDO for the "prepare" method
$mockPdo = $mockFactory->createMock(PDO::class,[
    'dsn' => $dsn,
    'username' => $username,
    'password' => $password,
    'options' => $options
]);
$mockFactory->mockMethod('prepare', fn($query) => $mockStatement);


$a= $mockPdo->prepare('g');
var_dump($a->execute());
var_dump($a->fetch());