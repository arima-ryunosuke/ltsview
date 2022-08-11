<?php

namespace ryunosuke\test\Type;

use ryunosuke\ltsv\Type\AbstractType;
use ryunosuke\ltsv\Type\Markdown;
use ryunosuke\ltsv\Type\Yaml;

class AbstractTypeTest extends \ryunosuke\test\AbstractTestCase
{
    function test_instance()
    {
        $this->assertInstanceOf(Yaml::class, AbstractType::instance('yml', [
            'comment' => false,
            'compact' => false,
            'color'   => false,
        ]));
        $this->assertInstanceOf(Markdown::class, AbstractType::instance('md', [
            'comment' => false,
            'compact' => false,
            'color'   => false,
        ]));
        $this->assertException('not supported', [AbstractType::class, 'instance'], 'hoge', []);
    }

    function test_color()
    {
        $mock = new Mock([
            'comment' => false,
            'compact' => false,
            'color'   => false,
        ]);
        $this->assertStringNotContainsString("<fg", $mock->colorComment('hoge'));
        $this->assertStringNotContainsString("<fg", $mock->colorLabel('hoge'));
        $this->assertStringNotContainsString("<fg", $mock->colorValue('hoge'));

        $mock = new Mock([
            'comment' => false,
            'compact' => false,
            'color'   => true,
        ]);
        $this->assertStringContainsString("<fg", $mock->colorComment('hoge'));
        $this->assertStringContainsString("<fg", $mock->colorLabel('hoge'));
        $this->assertStringContainsString("<fg", $mock->colorValue('hoge'));
    }
}

class Mock extends AbstractType
{
    public function head($column) { }

    public function meta($file, $n) { }

    public function body($fields) { }

    public function foot() { }

    public function colorComment($string) { return parent::colorComment($string); }

    public function colorLabel($string) { return parent::colorLabel($string); }

    public function colorValue($string) { return parent::colorValue($string); }
}
