<?php

namespace ryunosuke\ltsv\Type;

class Php extends AbstractType
{
    public function head(array $columns): string
    {
        return "[\n";
    }

    public function meta(string $file, int $n): string
    {
        if ($this->comment_mode) {
            return $this->colorComment("    // $file:$n\n");
        }
        return '';
    }

    public function body(array $fields): string
    {
        if ($this->compact_mode) {
            $result = [];
            foreach ($fields as $label => $value) {
                $hlabel = $this->colorLabel(var_export($label, true));
                $hvalue = $this->colorValue(var_export($value, true));
                $result[] = "$hlabel=>$hvalue";
            }
            return "    [" . implode(",", $result) . "],\n";
        }
        else {
            $result = [];
            foreach ($fields as $label => $value) {
                $hlabel = $this->colorLabel(var_export($label, true));
                $hvalue = $this->colorValue(var_export($value, true));
                $result[] = "        $hlabel => $hvalue,";
            }
            return "    [\n" . implode("\n", $result) . "\n    ],\n";
        }
    }

    public function foot(): string
    {
        return "]\n";
    }
}
