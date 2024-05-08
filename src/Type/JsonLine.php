<?php

namespace ryunosuke\ltsv\Type;

class JsonLine extends AbstractType
{
    private string $meta = '';

    public function parse(string $string): ?array
    {
        return json_decode($string, true);
    }

    public function head(array $columns): string
    {
        return '';
    }

    public function meta(string $file, int $n): string
    {
        if ($this->comment_mode) {
            $this->meta = "$file:$n";
        }
        return '';
    }

    public function body(array $fields): string
    {
        end($fields);
        $lastkey = key($fields);
        $jopt = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRESERVE_ZERO_FRACTION;
        $space = $this->compact_mode ? '' : ' ';

        $result = [];
        if ($this->meta) {
            $result[] = $this->colorComment('"//":' . $space . json_encode($this->meta, $jopt)) . ($fields ? ",$space" : "");
        }
        foreach ($fields as $label => $value) {
            $hlabel = $this->colorLabel(json_encode($label, $jopt));
            $hvalue = $this->colorValue(json_encode($value, $jopt));
            $result[] = "$hlabel:$space$hvalue" . ($label !== $lastkey ? ",$space" : "");
        }
        return "{" . implode("", $result) . "}\n";
    }

    public function foot(): string
    {
        return '';
    }
}
