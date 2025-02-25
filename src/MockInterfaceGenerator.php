<?php

namespace Zeus\Mock;

use ReflectionClass;
use ReflectionException;

/**
 *
 */
class MockInterfaceGenerator extends MockMethodOverrideGenerator
{
    /**
     * @param string $mockClassName
     * @param string $interface
     * @return string
     * @throws ReflectionException
     */
    public function generate(string $mockClassName, string $interface): string
    {
        $reflection = new ReflectionClass($interface);
        $mockCode = "class $mockClassName implements $interface {\n";
        $mockCode .= "    private {$this->mockMethodInterface} \$mockFactory;\n";
        $mockCode .= "    public function __construct({$this->mockMethodInterface} \$mockFactory)
         {
          \$this->mockFactory = \$mockFactory;
          \$mockFactory->getMockMethod('object.on.created')(\$this);
        }\n";

        foreach ($reflection->getMethods() as $method) {
            $mockCode .= $this->generateMethodOverride($method, false);
        }
        $mockCode .= "}\n";
        return $mockCode;
    }
}
