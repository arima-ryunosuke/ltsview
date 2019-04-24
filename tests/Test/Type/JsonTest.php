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
        $type = $this->getType([
            'comment' => true,
            'compact' => false,
            'color'   => false,
        ]);
        $fields = ['a' => 'A', 'b' => 'B', 'c' => 'C'];
        $buffer = '';
        $buffer .= $type->head(array_keys($fields));
        $buffer .= $type->meta('dummy', 1);
        $buffer .= $type->body($fields);
        $buffer .= $type->foot();
        $this->assertEquals([['//' => 'dummy:1'] + $fields], ($this->decoder)($buffer), "Actual:\n$buffer");
    }

    function test_meta2()
    {
        $type = $this->getType([
            'comment' => true,
            'compact' => true,
            'color'   => false,
        ]);
        $fields = ['a' => 'A', 'b' => 'B', 'c' => 'C'];
        $buffer = '';
        $buffer .= $type->head(array_keys($fields));
        $buffer .= $type->meta('dummy', 1);
        $buffer .= $type->body($fields);
        $buffer .= $type->foot();
        $this->assertEquals([['//' => 'dummy:1'] + $fields], ($this->decoder)($buffer), "Actual:\n$buffer");
    }

    function test_compact()
    {
        $type = $this->getType([
            'comment' => false,
            'compact' => true,
            'color'   => false,
        ]);
        $fields = ['a' => 'A', 'b' => 'B', 'c' => 'C'];
        $buffer = $type->body($fields);
        $this->assertEquals($fields, ($this->decoder)($buffer), "Actual:\n$buffer");
        $this->assertEquals('    {"a":"A","b":"B","c":"C"}', $buffer, "Actual:\n$buffer");
    }
}
