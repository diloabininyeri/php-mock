<?php

use Zeus\Mock\MockFactory;

require_once 'vendor/autoload.php';


interface Logger {
    public function log(string $message): void;
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

// Mock the Logger interface
$mockLogger = $mockFactory->createMock(Logger::class);

// Mock the saveData method
$mockFactory->mockMethod('log', fn($message) =>print 'mocked log: ' . $message);

// Create the mock DatabaseService with the mocked Logger
$mockDatabaseService = $mockFactory->createMock(DatabaseService::class,[
    'logger' => $mockLogger,  // The Logger instance is passed to the DatabaseService constructor as a dependency injection.
]);

// Use the service with mocked behavior
$mockDatabaseService->saveData('Test Data');  // Will output 'mocked log: Data saved: Test Data'
