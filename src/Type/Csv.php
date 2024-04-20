<?php

namespace ryunosuke\ltsv\Type;

class Csv extends AbstractType
{
    private $separator;

    public function __construct($option)
    {
        parent::__construct($option);

        $this->separator = $option['separator'];
    }

    public function head($column)
    {
        if ($this->comment_mode) {
            return $this->colorComment(implode($this->separator, $column)) . "\n";
        }
    }

    public function meta($file, $n) { }

    public function body($fields)
    {
        return $this->colorValue(implode($this->separator, $fields)) . "\n";
    }

    public function foot() { }
}
