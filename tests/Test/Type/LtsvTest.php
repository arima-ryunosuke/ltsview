<?php

namespace ryunosuke\test\Type;

class LtsvTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->decoder = function ($string) {
            $result = [];
            foreach (preg_split('#\\R#u', $string, -1, PREG_SPLIT_NO_EMPTY) as $line) {
                $row = [];
                foreach (explode("\t", trim($line)) as $e) {
                    [$label, $value] = explode(':', $e, 2) + [1 => null];
                    $row[$label] = $value;
                }
                $result[] = $row;
            }
            return $result;
        };
    }

    function test_parse()
    {
        $this->assertEquals(['a' => 'A', 'b' => 'B'], $this->type->parse("a:A\tb:B"));
        $this->assertEquals(['a' => 'A', 'b' => ''], $this->type->parse("a:A\tb:"));
        $this->assertEquals(['a' => 'A', 'b' => ''], $this->type->parse("a:A\tb"));
    }
}
