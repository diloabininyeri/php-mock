<?php

namespace Zeus\Mock;

use ReflectionClass;
use ReflectionException;

/**
 *
 */
class MockClassGenerator extends MockMethodOverrideGenerator
{

    /**
     * @param string $mockClassName
     * @param string $class
     * @return string
     * @throws ReflectionException
     */
    public function generate(string $mockClassName, string $class): string
    {
        $reflection = new ReflectionClass($class);
        $mockCode = "class $mockClassName extends $class {\n";
        $mockCode .= "    private \$mockFactory;\n";

        if ($reflection->hasMethod('__construct')) {
            $mockCode .= "    public function __construct(\$mockFactory, array \$params = []) {\n";
            $mockCode .= "        \$this->mockFactory = \$mockFactory;\n";
            $mockCode .= "        parent::__construct(...\$params);\n";
        } else {
            $mockCode .= "    public function __construct(\$mockFactory) {\n";
            $mockCode .= "        \$this->mockFactory = \$mockFactory;\n";
        }
        $mockCode .= "    }\n";

        foreach ($reflection->getMethods() as $method) {
            if ($method->getName() === '__construct') {
                continue;
            }
            $mockCode .= $this->generateMethodOverride($method);
        }

        $mockCode .= $this->generateCallMethod();
        $mockCode .= "}\n";
        return $mockCode;
    }
}
