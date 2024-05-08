<?php

namespace ryunosuke\ltsv\Type;

class Csv extends AbstractType
{
    private string $separator;
    private string $enclosure;

    public function __construct(array $option)
    {
        parent::__construct($option);

        $this->separator = $option['separator'];
        $this->enclosure = $option['enclosure'];
    }

    public function parse(string $string): ?array
    {
        $fields = str_getcsv($string, $this->separator);
        return array_combine(array_map(fn($k) => "c$k", array_keys($fields)), $fields);
    }

    public function head(array $columns): string
    {
        if ($this->comment_mode) {
            return $this->colorComment($this->_line($columns, $this->separator, $this->enclosure));
        }
        return '';
    }

    public function meta(string $file, int $n): string
    {
        return '';
    }

    public function body(array $fields): string
    {
        return $this->colorValue($this->_line($fields, $this->separator, $this->enclosure));
    }

    public function foot(): string
    {
        return '';
    }

    private function _line(array $fields, string $delimiter = ',', string $enclosure = '"', string $escape = "\\"): string
    {
        if (!strlen($enclosure)) {
            return implode($delimiter, $fields) . "\n";
        }

        static $fp = null;
        $fp ??= fopen('php://memory', 'rw+');
        rewind($fp);
        ftruncate($fp, 0);
        fputcsv($fp, $fields, $delimiter, $enclosure, $escape);
        rewind($fp);
        return stream_get_contents($fp);
    }
}
