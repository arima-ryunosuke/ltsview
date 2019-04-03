<?php

namespace ryunosuke\ltsv\Type;

abstract class AbstractType
{
    protected $comment_mode;
    protected $compact_mode;

    public static function instance($type, ...$args)
    {
        switch ($type) {
            default:
                throw new \InvalidArgumentException("$type type is not supported.");
            case 'json':
                return new Json(...$args);
            case 'ltsv':
                return new Ltsv(...$args);
            case 'php':
                return new Php(...$args);
            case 'yaml':
            case 'yml':
                return new Yaml(...$args);
            case 'tsv':
                return new Tsv(...$args);
            case 'markdown':
            case 'md':
                return new Markdown(...$args);
        }
    }

    public function __construct($comment_mode, $compact_mode)
    {
        $this->comment_mode = $comment_mode;
        $this->compact_mode = $compact_mode;
    }

    abstract public function head($column);

    abstract public function meta($file, $n);

    abstract public function body($fields);

    abstract public function foot();
}
