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
        $type = $this->getType(false);
        $fields = ['a' => 'A', 'b' => 'B', 'c' => 'C'];
        $buffer = '';
        $buffer .= $type->head(array_keys($fields));
        $buffer .= $type->meta('dummy', 1);
        $buffer .= $type->body($fields);
        $buffer .= $type->foot();
        $this->assertEquals([$fields], ($this->decoder)($buffer));
    }
}
