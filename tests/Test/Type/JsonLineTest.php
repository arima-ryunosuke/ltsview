<?php

namespace ryunosuke\test\Type;

class JsonLineTest extends AbstractTestCase
{
    protected $meta = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decoder = function ($string) {
            return json_decode('[' . implode(',', explode("\n", trim($string))) . ']', true);
        };
    }

    function test_parse()
    {
        $this->assertEquals(['a' => 'A', 'b' => 'B'], $this->type->parse('{"a":"A","b":"B"}'));
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
        $this->assertEquals([['//' => 'dummy:1'] + $fields], ($this->decoder)("$buffer"), "Actual:\n$buffer");
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
        $this->assertEquals([$fields], ($this->decoder)($buffer), "Actual:\n$buffer");
        $this->assertEquals('{"a":"A","b":"B","c":"C"}' . "\n", $buffer, "Actual:\n$buffer");
    }
}
