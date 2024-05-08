<?php

namespace ryunosuke\ltsv\Type;

class Markdown extends AbstractType
{
    private function _line(array $column, string $padstr, string $color): string
    {
        $result = [];
        foreach ($column as $col) {
            $result[] = str_pad($col, 3, $padstr, STR_PAD_BOTH);
        }
        if ($color) {
            $result = array_map([$this, "color$color"], $result);
        }
        return "| " . implode(' | ', $result) . " |\n";
    }

    public function head(array $columns): string
    {
        if ($this->comment_mode) {
            return $this->_line($columns, ' ', 'comment') . $this->_line(array_fill(0, count($columns), '-'), '-', '');
        }
        return '';
    }

    public function meta(string $file, int $n): string
    {
        return '';
    }

    public function body(array $fields): string
    {
        return $this->_line($fields, ' ', 'value');
    }

    public function foot(): string
    {
        return '';
    }
}
