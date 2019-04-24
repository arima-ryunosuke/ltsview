<?php

namespace ryunosuke\ltsv\Type;

class Tsv extends AbstractType
{
    public function head($column)
    {
        if ($this->comment_mode) {
            return $this->colorComment(implode("\t", $column)) . "\n";
        }
    }

    public function meta($file, $n) { }

    public function body($fields)
    {
        return $this->colorValue(implode("\t", $fields)) . "\n";
    }

    public function foot() { }
}
