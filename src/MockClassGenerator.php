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
     * @param bool $overrideConstruct
     * @return string
     * @throws ReflectionException
     */
    public function generate(string $mockClassName, string $class, bool $overrideConstruct = false): string
    {
        $reflection = new ReflectionClass($class);
        $mockCode = "class $mockClassName extends $class {\n";
        $mockCode .= "    private {$this->mockMethodInterface} \$mockFactory;\n";

        $defineMockFactory = "        \$this->mockFactory = \$mockFactory;\n";

        if ($overrideConstruct) {
            $mockCode .= "    public function __construct({$this->mockMethodInterface} \$mockFactory, array \$params = []) {\n";
            $mockCode .= $defineMockFactory;
        } elseif ($reflection->hasMethod('__construct')) {
            $mockCode .= "    public function __construct(\$mockFactory, array \$params = []) {\n";
            $mockCode .= $defineMockFactory;
            $mockCode .= "        parent::__construct(...\$params);\n";
        } else {
            $mockCode .= "    public function __construct(\$mockFactory) {\n";
            $mockCode .= $defineMockFactory;
        }
        $mockCode.="\$mockFactory->getMockMethod('object.on.created')(\$this,\$params=[]);\n";
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
