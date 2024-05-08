<?php

namespace ryunosuke\ltsv\Type;

use function ryunosuke\ltsv\str_array;

class Ltsv extends AbstractType
{
    public function parse(string $string): ?array
    {
        return str_array(explode("\t", $string), ':', true);
    }

    public function head(array $columns): string
    {
        return '';
    }

    public function meta(string $file, int $n): string
    {
        return '';
    }

    public function body(array $fields): string
    {
        $result = [];
        foreach ($fields as $label => $value) {
            $hlabel = $this->colorLabel($label);
            $hvalue = $this->colorValue($value);
            $result[] = "$hlabel:$hvalue";
        }
        return implode("\t", $result) . "\n";
    }

    public function foot(): string
    {
        return '';
    }
}
