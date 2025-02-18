# MockFactory

The `MockFactory` class allows for the dynamic creation of mock objects for classes or interfaces, which can be useful in unit testing or when you want to simulate the behavior of an object. This is achieved through method mocking, where you can override methods to return predefined results for testing purposes.

## Features

- **Mock any class or interface:** Create mock objects for both classes and interfaces dynamically.
- **Method mocking:** Override methods with custom behaviors, defined via closures.
- **Supports class inheritance:** The mock objects will inherit from the original class, allowing you to test behavior as if the real object was used.
- **Supports interface implementation:** If the target is an interface, it will generate a mock implementing the interface.
- **Flexible mock behavior:** Customize method returns and behaviors for specific testing scenarios.

## Installation

This class does not require any additional installations other than the usual PHP setup. Ensure your environment supports `eval()` and dynamic class generation.

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
