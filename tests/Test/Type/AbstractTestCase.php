<?php

namespace ryunosuke\test\Type;

use ryunosuke\ltsv\Type\AbstractType;
use ryunosuke\ltsv\Type\Markdown;
use ryunosuke\ltsv\Type\Yaml;

abstract class AbstractTestCase extends \ryunosuke\test\AbstractTestCase
{
    /** @var AbstractType */
    protected $type;

    protected $meta = true;

    /** @var callable */
    protected $decoder;

    protected function getType($meta)
    {
        $name = preg_replace('#Test$#', '', array_slice(explode("\\", get_class($this)), -1)[0]);
        return AbstractType::instance(strtolower($name), $meta);
    }

    protected function setUp()
    {
        parent::setUp();

        $this->type = $this->getType($this->meta);
    }

    function test_instance()
    {
        $this->assertInstanceOf(Yaml::class, AbstractType::instance('yml', false));
        $this->assertInstanceOf(Markdown::class, AbstractType::instance('md', false));
        $this->assertException('not supported', [AbstractType::class, 'instance'], 'hoge');
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
