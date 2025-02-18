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
$mockFactory->mockMethod('log', fn($message) => 'mocked log: ' . $message);

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
$mockFactory->mockMethod('log', fn($message) => 'mocked log: ' . $message);

// Create the mock DatabaseService with the mocked Logger
$mockDatabaseService = $mockFactory->createMock(DatabaseService::class);

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

namespace App;

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

use Zeus\Mock\MockFactory;

$mockFactory = new MockFactory();

// Create a mock Logger interface
$mockLogger = $mockFactory->createMock(Logger::class);
$mockFactory->mockMethod('log', fn($message) => 'mocked log: ' . $message);

// Create a mock DatabaseService with the mocked Logger
$mockDatabaseService = $mockFactory->createMock(DatabaseService::class);

// Use the service with mocked behavior
$mockDatabaseService->saveData('Test Data');  // Outputs 'mocked log: Data saved: Test Data'

// Type hinting in mocks
$mockService = $mockFactory->createMock(SomeService::class);
$mockFactory->mockMethod('processData', fn($data) => 42);
echo $mockService->processData([1, 2, 3]);  // Outputs '42'

```
This example class shows how to create mocks for both interfaces and concrete classes, including the use of mocked methods, 
type hinting, and checking instance types using `instanceof`.

