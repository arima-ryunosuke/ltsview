<?php

namespace ryunosuke\ltsv\Type;

class Yaml extends AbstractType
{
    public function head($column) { }

    public function meta($file, $n)
    {
        $result = "---";
        if ($this->comment_mode) {
            $result .= " # $file:$n";
        }
        return "$result\n";
    }

    public function body($fields)
    {
        if ($this->compact_mode) {
            return \Symfony\Component\Yaml\Yaml::dump($fields, 0) . "\n";
        }
        return \Symfony\Component\Yaml\Yaml::dump($fields);
    }

    public function foot() { }
}
