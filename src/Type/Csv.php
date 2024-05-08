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

    public function parse($string)
    {
        $fields = str_getcsv($string, $this->separator);
        return array_combine(array_map(fn($k) => "c$k", array_keys($fields)), $fields);
    }

    public function head($column)
    {
        if ($this->comment_mode) {
            return $this->colorComment(implode($this->separator, $column)) . "\n";
        }
        return '';
    }

    public function meta($file, $n)
    {
        return '';
    }

    public function body($fields)
    {
        return $this->colorValue(implode($this->separator, $fields)) . "\n";
    }

    public function foot()
    {
        return '';
    }
}
