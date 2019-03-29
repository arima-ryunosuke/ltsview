<?php

namespace ryunosuke\ltsv\Type;

class Json extends AbstractType
{
    private $first = true;
    private $meta  = '';

    public function head($column)
    {
        return "[\n";
    }

    public function meta($file, $n)
    {
        if ($this->comment_mode) {
            $this->meta = "$file:$n";
        }
    }

    public function body($fields)
    {
        $first = $this->first;
        $this->first = false;

        $prefix = '';
        if (!$first) {
            $prefix = ",\n";
        }

        if ($this->meta) {
            $fields = ['//' => $this->meta] + $fields;
        }

        $json = json_encode([(object) $fields],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRESERVE_ZERO_FRACTION
        );
        return $prefix . trim($json, "[\r\n]");
    }

    public function foot()
    {
        return "\n]\n";
    }
}
