<?php

namespace ryunosuke\ltsv\Type;

class Csv extends AbstractType
{
    private string $separator;

    public function __construct(array $option)
    {
        parent::__construct($option);

        $this->separator = $option['separator'];
    }

    public function parse(string $string): ?array
    {
        $fields = str_getcsv($string, $this->separator);
        return array_combine(array_map(fn($k) => "c$k", array_keys($fields)), $fields);
    }

    public function head(array $columns): string
    {
        if ($this->comment_mode) {
            return $this->colorComment(implode($this->separator, $columns)) . "\n";
        }
        return '';
    }

    public function meta(string $file, int $n): string
    {
        return '';
    }

    public function body(array $fields): string
    {
        return $this->colorValue(implode($this->separator, $fields)) . "\n";
    }

    public function foot(): string
    {
        return '';
    }
}
