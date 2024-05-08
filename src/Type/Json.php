<?php

namespace ryunosuke\ltsv\Type;

class Json extends AbstractType
{
    private bool $first = true;
    private string $meta  = '';

    public function head(array $columns): string
    {
        return "[\n";
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
        $first = $this->first;
        $this->first = false;

        $prefix = '';
        if (!$first) {
            $prefix = ",\n";
        }

        end($fields);
        $lastkey = key($fields);
        $jopt = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRESERVE_ZERO_FRACTION;

        if ($this->compact_mode) {
            $result = [];
            if ($this->meta) {
                $result[] = $this->colorComment('"//":' . json_encode($this->meta, $jopt)) . ($fields ? "," : "");
            }
            foreach ($fields as $label => $value) {
                $hlabel = $this->colorLabel(json_encode($label, $jopt));
                $hvalue = $this->colorValue(json_encode($value, $jopt));
                $result[] = "$hlabel:$hvalue" . ($label !== $lastkey ? "," : "");
            }
            return "$prefix    {" . implode("", $result) . "}";
        }
        else {
            $result = [];
            if ($this->meta) {
                $result[] = "        " . $this->colorComment('"//": ' . json_encode($this->meta, $jopt)) . ($fields ? "," : "");
            }
            foreach ($fields as $label => $value) {
                $hlabel = $this->colorLabel(json_encode($label, $jopt));
                $hvalue = $this->colorValue(json_encode($value, $jopt));
                $result[] = "        $hlabel: $hvalue" . ($label !== $lastkey ? "," : "");
            }
            return "$prefix    {\n" . implode("\n", $result) . "\n    }";
        }
    }

    public function foot(): string
    {
        return "\n]\n";
    }
}
