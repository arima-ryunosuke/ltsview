<?php

namespace ryunosuke\ltsv\Type;

class Ltsv extends AbstractType
{
    public function parse(string $string): ?array
    {
        $result = [];
        foreach (explode("\t", $string) as $line) {
            $parts = explode(':', $line, 2);
            $result[trim($parts[0])] = trim($parts[1] ?? '');
        }
        return $result;
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
