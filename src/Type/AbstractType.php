<?php

namespace ryunosuke\ltsv\Type;

abstract class AbstractType
{
    protected $comment_mode;
    protected $compact_mode;
    protected $color_mode;

    public static function instance($type, $option)
    {
        switch ($type) {
            default:
                throw new \InvalidArgumentException("$type type is not supported.");
            case 'json':
                return new Json($option);
            case 'jsonline':
            case 'jsonl':
                return new JsonLine($option);
            case 'ltsv':
                return new Ltsv($option);
            case 'php':
                return new Php($option);
            case 'yaml':
            case 'yml':
                return new Yaml($option);
            case 'csv':
                return new Csv($option + ['separator' => ","]);
            case 'ssv':
                return new Csv($option + ['separator' => " "]);
            case 'tsv':
                return new Csv($option + ['separator' => "\t"]);
            case 'markdown':
            case 'md':
                return new Markdown($option);
        }
    }

    public function __construct($option)
    {
        $this->comment_mode = $option['comment'];
        $this->compact_mode = $option['compact'];
        $this->color_mode = $option['color'];
    }

    public function parse($string)
    {
        throw new \DomainException(static::class . '::parse is not supported');
    }

    abstract public function head($column);

    abstract public function meta($file, $n);

    abstract public function body($fields);

    abstract public function foot();

    protected function colorComment($string)
    {
        if ($this->color_mode) {
            return "<fg=yellow>{$string}</>";
        }
        return $string;
    }

    protected function colorLabel($string)
    {
        if ($this->color_mode) {
            return "<fg=magenta>{$string}</>";
        }
        return $string;
    }

    protected function colorValue($string)
    {
        if ($this->color_mode) {
            return "<fg=green>{$string}</>";
        }
        return $string;
    }
}
