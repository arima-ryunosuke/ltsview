<?php

namespace ryunosuke\ltsv\Type;

class Php extends AbstractType
{
    public function head($column)
    {
        return "[\n";
    }

    public function meta($file, $n)
    {
        if ($this->comment_mode) {
            return "    // $file:$n\n";
        }
    }

    public function body($fields)
    {
        if ($this->compact_mode) {
            $result = "    [";
            foreach ($fields as $label => $value) {
                $result .= var_export($label, true) . "=>" . var_export($value, true) . ",";
            }
            return $result . "],\n";
        }
        else {
            $result = "    [\n";
            foreach ($fields as $label => $value) {
                $result .= "        " . var_export($label, true) . " => " . var_export($value, true) . ",\n";
            }
            return $result . "    ],\n";
        }
    }

    public function foot()
    {
        return "]\n";
    }
}
