<?php

namespace ryunosuke\test\Type;

use ryunosuke\ltsv\Type\AbstractType;

abstract class AbstractTestCase extends \ryunosuke\test\AbstractTestCase
{
    /** @var AbstractType */
    protected $type;

    protected $meta = true;

    /** @var callable */
    protected $decoder;

    protected function getType($option)
    {
        $name = preg_replace('#Test$#', '', array_slice(explode("\\", get_class($this)), -1)[0]);
        return AbstractType::instance(strtolower($name), $option);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = $this->getType([
            'comment' => $this->meta,
            'compact' => false,
            'color'   => false,
        ]);
    }

    function test_parse()
    {
        $this->expectException(\DomainException::class);
        $this->type->parse('');
    }

    function test_syntax()
    {
        $fields = ['a' => 'A', 'b' => 'B', 'c' => 'C'];
        $buffer = '';
        $buffer .= $this->type->head(array_keys($fields));
        foreach (['hoge', 'fuga'] as $from) {
            foreach (range(1, 5) as $n) {
                $buffer .= $this->type->meta($from, $n);
                $buffer .= $this->type->body($fields);
            }
        }
        $buffer .= $this->type->foot();
        $this->assertEquals(array_fill(0, 10, $fields), ($this->decoder)($buffer), "Actual:\n$buffer");
    }
}
