<?php

namespace ryunosuke\ltsv\Type;

class Markdown extends AbstractType
{
    private function _line($column, $padstr)
    {
        $result = [];
        foreach ($column as $col) {
            $result[] = str_pad($col, 3, $padstr, STR_PAD_BOTH);
        }
        return "| " . implode(' | ', $result) . " |\n";
    }

    public function head($column)
    {
        if ($this->comment_mode) {
            return $this->_line($column, ' ') . $this->_line(array_fill(0, count($column), '-'), '-');
        }
    }

    public function meta($file, $n) { }

    public function body($fields)
    {
        return $this->_line($fields, ' ');
    }

    public function foot() { }
}
