<?php

namespace ryunosuke\ltsv\Type;

use function ryunosuke\ltsv\sql_quote;

class Sql extends AbstractType
{
    private string $table;
    private bool   $first = true;
    private string $meta  = '';

    public function __construct(array $option)
    {
        parent::__construct($option);

        $this->table = $option['table'];
    }

    public function head(array $columns): string
    {
        if ($this->compact_mode) {
            return "INSERT INTO $this->table (" . implode(',', $columns) . ") VALUES\n";
        }
        return '';
    }

    public function meta(string $file, int $n): string
    {
        if ($this->comment_mode) {
            $this->meta = $this->colorComment("-- $file:$n");
        }
        return '';
    }

    public function body(array $fields): string
    {
        $first = $this->first;
        $this->first = false;

        $meta = strlen($this->meta) ? "{$this->meta}\n" : "";

        if ($this->compact_mode) {
            $values = implode(',', array_map(fn($v) => sql_quote($v), $fields));
            return $first ? "{$meta}($values)" : ",\n{$meta}($values)";
        }
        else {
            $columns = implode(', ', array_keys($fields));
            $values = implode(', ', array_map(fn($v) => sql_quote($v), $fields));
            return "{$meta}INSERT INTO $this->table ($columns) VALUES ($values);\n";
        }
    }

    public function foot(): string
    {
        if ($this->compact_mode) {
            return ";\n";
        }
        return '';
    }
}
