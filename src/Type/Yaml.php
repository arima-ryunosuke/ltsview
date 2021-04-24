<?php

namespace ryunosuke\ltsv\Type;

class Yaml extends AbstractType
{
    public function head($column) { }

    public function meta($file, $n)
    {
        if ($this->comment_mode) {
            return $this->colorComment("# $file:$n\n");
        }
    }

    public function body($fields)
    {
        if ($this->compact_mode) {
            $result = [];
            foreach ($fields as $label => $value) {
                $hlabel = $this->colorLabel($label);
                $hvalue = $this->colorValue(\Symfony\Component\Yaml\Yaml::dump($value));
                $result[] = "$hlabel: $hvalue";
            }
            return "- {" . implode(",", $result) . "}\n";
        }
        else {
            $result = [];
            foreach ($fields as $label => $value) {
                $hlabel = $this->colorLabel($label);
                $hvalue = $this->colorValue(\Symfony\Component\Yaml\Yaml::dump($value));
                $result[] = "$hlabel: $hvalue";
            }
            return "- " . implode("\n  ", $result) . "\n";
        }
    }

    public function foot() { }
}
