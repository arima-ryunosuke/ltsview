<?php

namespace ryunosuke\ltsv\Type;

use function ryunosuke\ltsv\str_array;

class Ltsv extends AbstractType
{
    public function parse($string)
    {
        return str_array(explode("\t", $string), ':', true);
    }

    public function head($column) { }

    public function meta($file, $n) { }

    public function body($fields)
    {
        $result = [];
        foreach ($fields as $label => $value) {
            $hlabel = $this->colorLabel($label);
            $hvalue = $this->colorValue($value);
            $result[] = "$hlabel:$hvalue";
        }
        return implode("\t", $result) . "\n";
    }

    public function foot() { }
}
