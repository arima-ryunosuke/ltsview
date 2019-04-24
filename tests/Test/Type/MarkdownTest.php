<?php

namespace ryunosuke\test\Type;

class MarkdownTest extends AbstractTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->decoder = function ($string) {
            $result = [];
            $n = 0;
            foreach (preg_split('#\\R#u', $string, -1, PREG_SPLIT_NO_EMPTY) as $line) {
                $fields = array_map('trim', preg_split('#\\|#u', $line, -1, PREG_SPLIT_NO_EMPTY));
                if (!isset($columns)) {
                    $columns = $fields;
                    continue;
                }
                if ($n++ >= 1) {
                    $result[] = array_combine($columns, $fields);
                }
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
        $this->assertEquals("|  A  |  B  |  C  |\n", $buffer, "Actual:\n$buffer");
    }
}
