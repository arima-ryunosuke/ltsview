<?php

namespace ryunosuke\ltsv\Type;

abstract class AbstractType
{
    protected bool $comment_mode;
    protected bool $compact_mode;
    protected bool $color_mode;

    public static function instance(string $type, array $option): static
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
                return new Csv($option + ['separator' => ",", 'enclosure' => '"']);
            case 'ssv':
                return new Csv($option + ['separator' => " ", 'enclosure' => '']);
            case 'tsv':
                return new Csv($option + ['separator' => "\t", 'enclosure' => '']);
            case 'markdown':
            case 'md':
                return new Markdown($option);
        }
    }

    public function __construct(array $option)
    {
        $this->comment_mode = $option['comment'];
        $this->compact_mode = $option['compact'];
        $this->color_mode = $option['color'];
    }

    public function parse(string $string): ?array
    {
        throw new \DomainException(static::class . '::parse is not supported');
    }

    abstract public function head(array $columns): string;

    abstract public function meta(string $file, int $n): string;

    abstract public function body(array $fields): string;

    abstract public function foot(): string;

    protected function colorComment(string $string): string
    {
        if ($this->color_mode) {
            return "<fg=yellow>{$string}</>";
        }
        return $string;
    }

    protected function colorLabel(string $string): string
    {
        if ($this->color_mode) {
            return "<fg=magenta>{$string}</>";
        }
        return $string;
    }

    protected function colorValue(string $string): string
    {
        if ($this->color_mode) {
            return "<fg=green>{$string}</>";
        }
        return $string;
    }
}
