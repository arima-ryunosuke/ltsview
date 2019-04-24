<?php

namespace ryunosuke\test\Type;

class TsvTest extends AbstractTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->decoder = function ($string) {
            $result = [];
            foreach (preg_split('#\\R#u', $string, -1, PREG_SPLIT_NO_EMPTY) as $line) {
                $fields = explode("\t", trim($line));
                if (!isset($columns)) {
                    $columns = $fields;
                    continue;
                }
                $result[] = array_combine($columns, $fields);
            }
            return $result;
        };
    }

    function test_meta()
    {
        $type = $this->getType([
            'comment' => false,
            'compact' => false,
            'color'   => false,
        ]);
        $fields = ['a' => 'A', 'b' => 'B', 'c' => 'C'];
        $buffer = '';
        $buffer .= $type->head(array_keys($fields));
        $buffer .= $type->meta('dummy', 1);
        $buffer .= $type->body($fields);
        $buffer .= $type->foot();
        $this->assertEquals("A	B	C\n", $buffer, "Actual:\n$buffer");
    }
}
