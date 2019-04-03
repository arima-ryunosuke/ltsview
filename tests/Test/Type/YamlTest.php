<?php

namespace ryunosuke\test\Type;

class YamlTest extends AbstractTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->decoder = function ($string) {
            // symfony yaml: multiple documents are not supported
            return yaml_parse($string, -1);
        };
    }

    function test_meta()
    {
        $type = $this->getType(false, false);
        $fields = ['a' => 'A', 'b' => 'B', 'c' => 'C'];
        $buffer = '';
        $buffer .= $type->head(array_keys($fields));
        $buffer .= $type->meta('dummy', 1);
        $buffer .= $type->body($fields);
        $buffer .= $type->foot();
        $this->assertEquals([$fields], ($this->decoder)($buffer, "Actual:\n$buffer"));
    }

    function test_compact()
    {
        $type = $this->getType(false, true);
        $fields = ['a' => 'A', 'b' => 'B', 'c' => 'C'];
        $buffer = $type->body($fields);
        $this->assertEquals([$fields], ($this->decoder)($buffer, "Actual:\n$buffer"));
        $this->assertEquals("{ a: A, b: B, c: C }\n", $buffer, "Actual:\n$buffer");
    }
}
