<?php

namespace ryunosuke\test\Type;

class JsonTest extends AbstractTestCase
{
    protected $meta = false;

    protected function setUp()
    {
        parent::setUp();

        $this->decoder = function ($string) {
            return json_decode($string, true);
        };
    }

    function test_meta()
    {
        $type = $this->getType(true);
        $fields = ['a' => 'A', 'b' => 'B', 'c' => 'C'];
        $buffer = '';
        $buffer .= $type->head(array_keys($fields));
        $buffer .= $type->meta('dummy', 1);
        $buffer .= $type->body($fields);
        $buffer .= $type->foot();
        $this->assertEquals([['//' => 'dummy:1'] + $fields], ($this->decoder)($buffer));
    }
}
