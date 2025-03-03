<?php

namespace Zeus\Mock\Table;

use JsonException;


class TableMockMethod
{
    const string RETURN_NAME = 'Return';
    const string METHOD = 'Method';
    const string ARGUMENTS = 'Arguments';
    const string TIMESTAMP = 'Timestamp';
    const string CALL_COUNT = 'Call';
    private array $debugLogs = [];
    private array $methodCallCounts = [];
    private static bool $isPrintedHeader = false;

    public function __construct(private readonly string $testName)
    {
    }

    /**
     * Debug method to log method calls and arguments
     *
     * @param string $methodName
     * @param array $arguments
     * @param mixed $returnValue
     * @return void
     * @throws JsonException
     */
    public function debug(string $methodName, array $arguments, mixed $returnValue): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $this->incrementCallCount($methodName);

        $this->debugLogs[] = [
            self::METHOD => $methodName,
            self::ARGUMENTS => $this->formatReturnValue($arguments),
            self::RETURN_NAME => $this->formatReturnValue($returnValue),
            self::TIMESTAMP => $timestamp,
            self::CALL_COUNT => $this->methodCallCounts[$methodName]
        ];
    }

    /**
     * Print all the debug logs in a formatted table
     *
     * @return void
     */
    public function printDebugLogs(): void
    {
        // Only print test name once
        echo str_repeat('=', 150) . "\n";
        echo "Debug Logs: $this->testName\n";
        echo "\n";

        if (static::$isPrintedHeader === false) {
            $this->printedHeader();
            static::$isPrintedHeader = true;
        }

        foreach ($this->debugLogs as $log) {
            echo $this->printRow($log);
        }
    }

    /**
     * Format each row of the debug logs for display
     *
     * @param array $log
     * @return string
     */
    private function printRow(array $log): string
    {
        // Get the length of each column content
        $method = $log[self::METHOD];
        $arguments = $log[self::ARGUMENTS];
        $return = $log[self::RETURN_NAME];
        $timestamp = $log[self::TIMESTAMP];
        $callCount = $log[self::CALL_COUNT];

        // Determine max lengths dynamically for each column
        $methodLength = strlen($method);
        $argumentsLength = strlen($arguments);
        $returnLength = strlen($return);
        $timestampLength = strlen($timestamp);
        $callCountLength = strlen($callCount);

        $maxMethodLength = max(30, $methodLength);
        $maxArgumentsLength = max(45, $argumentsLength);
        $maxReturnLength = max(45, $returnLength);
        $maxTimestampLength = max(30, $timestampLength);
        $maxCallCountLength = max(5, $callCountLength);

        // Adjust the padding for each column based on max length
        $paddedMethod = str_pad($method, $maxMethodLength);
        $paddedArguments = str_pad($arguments, $maxArgumentsLength);
        $paddedReturn = str_pad($return, $maxReturnLength);
        $paddedTimestamp = str_pad($timestamp, $maxTimestampLength);
        $paddedCallCount = str_pad($callCount, $maxCallCountLength);

        return $paddedMethod . $paddedArguments . $paddedReturn . $paddedTimestamp . $paddedCallCount . "\n";
    }

    /**
     * Format the return value (converts objects to class name and serializes others)
     *
     * @param mixed $returnValue
     * @return string
     * @throws JsonException
     */
    private function formatReturnValue(mixed $returnValue): string
    {
        if (is_object($returnValue)) {
            return get_class($returnValue);
        }
        return json_encode($returnValue, JSON_THROW_ON_ERROR);
    }

    /**
     * Increment the call count for a given method
     *
     * @param string $methodName
     * @return void
     */
    private function incrementCallCount(string $methodName): void
    {
        $this->methodCallCounts[$methodName] = ($this->methodCallCounts[$methodName] ?? 0) + 1;
    }

    /**
     * Print the table header for the debug logs
     *
     * @return void
     */
    public function printedHeader(): void
    {
        $headers = [self::METHOD, self::ARGUMENTS, self::RETURN_NAME, self::TIMESTAMP, self::CALL_COUNT];

        // Printing the headers with aligned padding
        echo str_pad($headers[0], 30) .
            str_pad($headers[1], 45) .
            str_pad($headers[2], 45) .
            str_pad($headers[3], 30) .
            str_pad($headers[4], 5) . "\n";

        echo str_repeat("-", 150) . "\n";
    }
}
