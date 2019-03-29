<?php

namespace ryunosuke\ltsv\Type;

class Ltsv extends AbstractType
{
    public function head($column) { }

    public function meta($file, $n) { }

    public function body($fields)
    {
        return array_sprintf($fields, '%2$s:%1$s', "\t") . "\n";
    }

    public function foot() { }
}
