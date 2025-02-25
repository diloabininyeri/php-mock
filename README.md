# MockFactory

The `MockFactory` class allows for the dynamic creation of mock objects for classes or interfaces, which can be useful in unit testing or when you want to simulate the behavior of an object. This is achieved through method mocking, where you can override methods to return predefined results for testing purposes.

## Features

- **Mock any class or interface:** Create mock objects for both classes and interfaces dynamically.
- **Method mocking:** Override methods with custom behaviors, defined via closures.
- **Supports class inheritance:** The mock objects will inherit from the original class, allowing you to test behavior as if the real object was used.
- **Supports interface implementation:** If the target is an interface, it will generate a mock implementing the interface.
- **Flexible mock behavior:** Customize method returns and behaviors for specific testing scenarios.

## Installation
```console
composer require zeus/mock
```

## Example Usage

```php
use Zeus\Mock\MockFactory;

$mockFactory = new MockFactory();

// Create a mock instance of a class
$mockObject = $mockFactory->createMock(SomeClass::class);

// Mock a specific method on the class
$mockFactory->mockMethod('someMethod', fn($arg) => 'mocked result');

// Use the mock object
echo $mockObject->someMethod('test');  // Outputs 'mocked result'
```

**Mocking an Interface**

```php
interface Logger {
    public function log(string $message): void;
}

$mockFactory = new MockFactory();

// Create a mock instance of an interface
$mockLogger = $mockFactory->createMock(Logger::class);

// Mock the log method
$mockFactory->mockMethod('log', fn($message) =>print 'mocked log: ' . $message);

// Use the mock interface
echo $mockLogger->log('Test');  // Outputs 'mocked log: Test'

```
**Mocking a Service Class with Dependencies**
```php

class DatabaseService {
    private Logger $logger;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    public function saveData(string $data): void {
        // Simulate saving data and logging the action
        $this->logger->log('Data saved: ' . $data);
    }
}

$mockFactory = new MockFactory();

// Mock the Logger interface
$mockLogger = $mockFactory->createMock(Logger::class);

// Mock the saveData method
$mockFactory->mockMethod('log', fn($message) =>print 'mocked log: ' . $message);

// Create the mock DatabaseService with the mocked Logger
$mockDatabaseService = $mockFactory->createMock(DatabaseService::class,[
   'logger' => $mockLogger
]);

// Use the service with mocked behavior
$mockDatabaseService->saveData('Test Data');  // Will output 'mocked log: Data saved: Test Data'

```

**Type Hinting and instanceof Check**

The MockFactory ensures that type hinting is respected in the mock classes.
For example, if a method expects a specific type, the mock will follow that expectation.
```php
class SomeService {
    public function processData(array $data): int {
        return count($data);
    }
}

$mockFactory = new MockFactory();

// Create a mock instance of SomeService
$mockService = $mockFactory->createMock(SomeService::class);

// Mock the processData method
$mockFactory->mockMethod('processData', fn($data) => 42);

// Use the mock object
echo $mockService->processData([1, 2, 3]);  // Outputs '42'

// Check the instance type
if ($mockService instanceof SomeService) {
    echo "It's an instance of SomeService!";
}

```

**Benefits of Type Hinting in Mocks**

**Avoid type-related issues**: Ensures the mock correctly implements method signatures, avoiding errors caused by incorrect argument types.

**Seamless integration**: Your tests will integrate with the actual system as expected without concerns for type mismatch.
Method Mocking and Custom Behavior

**Method Mocking and Custom Behavior**

You can mock specific methods using closures that define the behavior you need for testing.
```php
$mockFactory->mockMethod('methodName', fn($arg1, $arg2) => 'custom result');

// Call the method
echo $mockObject->methodName($arg1, $arg2);  // Outputs 'custom result'

```

Example Class
Here is a basic example class that demonstrates how to use the `MockFactory` in different scenarios:

```php
<?php
use Zeus\Mock\MockFactory;
interface Logger {
    public function log(string $message): void;
}

class LoggerService implements Logger {
    public function log(string $message): void {
        echo $message;
    }
}

class DatabaseService {
    private Logger $logger;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    public function saveData(string $data): void {
        // Simulate saving data and logging the action
        $this->logger->log('Data saved: ' . $data);
    }
}


$mockFactory = new MockFactory();

// Create a mock Logger interface
$mockLogger = $mockFactory->createMock(Logger::class);
$mockFactory->mockMethod('log', fn($message) =>print 'mocked log: ' . $message);

// Create a mock DatabaseService with the mocked Logger
$mockDatabaseService = $mockFactory->createMock(DatabaseService::class,[
    'logger'=>new LoggerService()
]);

// Use the service with mocked behavior
$mockDatabaseService->saveData('Test Data');  // Outputs 'mocked log: Data saved: Test Data'


```
This example class shows how to create mocks for both interfaces and concrete classes, including the use of mocked methods, 
type hinting, and checking instance types using `instanceof`.

```php
class Person
{
    public function getName(): string
    {
        return 'Dilo surucu';
    }
}

function get_name(PersonInterface $person): string
{
    return $person->getName();
}


$mockFactory = new MockFactory();
$mockPerson = $mockFactory->createMock(PersonInterface::class);
$mockFactory->mockMethod('getName', function () {
    return 'Mocked Person';
});
echo get_name(new PersonInterface());//Dilo surucu
echo get_name($mockPerson);//Mocked Person
var_dump($mockPerson instanceof PersonInterface); //true
```
### MockFactory - Mocking Interfaces Example

### Overview

MockFactory is a powerful tool for mocking interfaces in PHP, allowing you to create mock instances and customize their behavior for unit testing. It ensures that mocked objects adhere to the expected type and guarantees compatibility with type hinting and `instanceof` checks.

### Example Usage - Mocking Interfaces

### Example

```php
use Zeus\Mock\MockFactory;

interface PersonInterface
{
    public function getName(): string;
}

$mockFactory = new MockFactory();

// Create a mock instance of PersonInterface
$personService = $mockFactory->createMock(PersonInterface::class);

// Mock the getName method
$mockFactory->mockMethod('getName', fn(): string => 'dilo surucu');

function get_person(PersonInterface $person): string
{
    return $person->getName();
}

echo get_person($personService); // dilo surucu
```
### Debug The code
There is a usage to see what kind of code the MockFactory class produces, this also allows you to debug it
```php
interface MYInterface
{
    public function foo():void;
}
$mockFactory = new MockFactory();

echo $mockFactory->generateCode(MYInterface::class,'CustomClassName');

/**
class CustomClassName implements MYInterface {
    private object $mockFactory;
    public function __construct($mockFactory) { $this->mockFactory = $mockFactory; }
    public function foo():void {
        if ($this->mockFactory->hasMethodMock('foo')) {
            $this->mockFactory->invokeMockedMethod('foo', []);
            return;
        }
        throw new \Zeus\Mock\MockMethodNotFoundException('Method foo is not mocked.');
    }
}*/
```

### Mock functions
Yes, this section allows you to mock php in-built functions that will allow you to do amazing things,
for example, let's mock the sleep function in the example.

```php

namespace Foo;

use Zeus\Mock\MockFunction;

$mockFunction=new MockFunction();

$mockFunction->add('sleep',function (int $seconds){
    return "seconds: $seconds";
});

$mockFunction->add('time',function(){
    return 100;
});

$mockFunction->scope();

echo sleep(10);//it'll return 'seconds: 10',it won't wait
echo time();//100

$mockFunction->endScope();

sleep(1);//it'll do wait for 1 second because out of the scope
echo time();//it'll return the real time because it's out of the scope
```
### Scope management
Sometimes we may want to use mock function for objects. Here is an example; we can determine the scope area with the scope method.
```php

namespace Foo\Bar;

class Date
{
    public function now(): int
    {
        return time();
    }
}


//using Foo\Bar scope for mocking functions

namespace App;

use Foo\Bar\Date;
use Zeus\Mock\MockFunction;

$mock=new MockFunction()
$mock->add('time',fn()=>100);

$mock->scope('\\Foo\\Bar');

echo new Date()->now(); //100

$mock->endScope();

echo new Date()->now(); //not 100,its return now
```
### the runWithMock method in the MockFunction object
This method defines functions in your mock object and overrides them with functions, like pulling a rabbit out of a hat.
```php
namespace Foo\Bar;

class Date
{
    public function now(): int
    {
        return time();
    }
}


//using

namespace App;

use Foo\Bar\Date;
use Zeus\Mock\MockFunction;

$mock=new MockFunction()
$mock->add('time',fn()=>100);

echo $mock->runWithmock(new Date(), function (Date $date) {
    return $date->now();
}); //100
```
### Test with PDO object

without database connection, Yes, you are a little surprised.
PDO object will work without database connection, do not worry.

```php

use Zeus\Mock\MockFactory;


$mockFactory = new MockFactory();

$mockStatement = $mockFactory->createMock(PDOStatement::class);
$mockFactory->mockMethod('execute', fn($params) => true);
$mockFactory->mockMethod('fetch', fn($fetchMode) => ['id' => 1, 'name' => 'Dilo Surucu']);

$mockPdo = $mockFactory->createMock(PDO::class, [], true);//avoid connecting to the database,it won't throw errors
$mockFactory->mockMethod('prepare', fn($query) => $mockStatement);

function fetch_data(PDO $PDO): array
{
    $query = $PDO->prepare('select * from users where id=1');
    $query->execute([]);
    return $query->fetch(PDO::FETCH_ASSOC);
}

$data = fetch_data($mockPdo);

print_r($data);//['id' => 1, 'name' => 'Dilo Surucu']

```

With the database connection, This part actually requires a database connection.
```php
use Zeus\Mock\MockFactory;


$dsn = 'mysql:host=127.0.0.1;dbname=test;port=3306';
$username = 'root';
$password = 'my-secret-pw';
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];


$mockFactory = new MockFactory();
$mockStatement = $mockFactory->createMock(PDOStatement::class);
$mockFactory->mockMethod('execute', fn($params) => true);
$mockFactory->mockMethod('fetch', fn($fetchMode) => ['id' => 1, 'name' => 'Dilo Surucu']);

$mockPdo = $mockFactory->createMock(PDO::class,[
    'dsn' => $dsn,
    'username' => $username,
    'password' => $password,
    'options' => $options
]);

$mockFactory->mockMethod('prepare', fn($query) => $mockStatement);

$statement=$mockPdo->prepare('select * from users where id=1');


function fetch_data(PDO $PDO):array
{
    $query=$PDO->prepare('select * from users where id=1');
    $query->execute([]);
    return $query->fetch(PDO::FETCH_ASSOC);

}

$data=fetch_data($mockPdo);
print_r($data);////['id' => 1, 'name' => 'Dilo Surucu']
```
**onMockInstanceCreated**

This method is triggered when the object is instantiated.
```php

$mockMethod = new MockMethod();

$mockMethod->mockMethod('test', function () {
    return 'test foo';
});
$mockMethod->mockMethod('now', function (int $a) {
    return $a;
});


$mockFactory = new MockFactory($mockMethod);
$mockFactory
    ->onMockInstanceCreated(function (Date $date) {
        echo $date->test(); //test foo
        
        //$date->__construct(); for custom constructor
    })
    ->createMock(Date::class, ['a' => 1]);

```
You can get the parameters sent to the constructor as soon as it is instanced.
```php

$mockMethod = new MockMethod();


$mockFactory = new MockFactory($mockMethod);
$mockFactory->onMockInstanceCreated(function (Date $dateInstance, string $date) {
    //$dateInstance
    //$date 2022-12-12

});
$dateInstance = $mockFactory->createMock(Date::class, ['date' => '2022-12-12'], true);
```