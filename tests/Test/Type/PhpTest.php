<?php

namespace ryunosuke\test\Type;

class PhpTest extends AbstractTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->decoder = function ($string) {
            return eval("return $string;");
        };
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
        $this->assertEquals([$fields], ($this->decoder)("[$buffer]"), "Actual:\n$buffer");
        $this->assertEquals("    ['a'=>'A','b'=>'B','c'=>'C'],\n", $buffer, "Actual:\n$buffer");
    }
}
