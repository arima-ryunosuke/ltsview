<?php

namespace ryunosuke\ltsv\Console\Command;

use ryunosuke\ltsv\Stream\Sftp;
use ryunosuke\ltsv\Type\AbstractType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function ryunosuke\ltsv\array_any;
use function ryunosuke\ltsv\evaluate;
use function ryunosuke\ltsv\ini_sets;
use function ryunosuke\ltsv\path_parse;
use function ryunosuke\ltsv\quoteexplode;
use function ryunosuke\ltsv\split_noempty;
use function ryunosuke\ltsv\var_export2;

class LogrepCommand extends Command
{
    public const NAME    = 'logrep';
    public const VERSION = '2.1.1';

    private static $STDIN = STDIN;

    private InputInterface  $input;
    private OutputInterface $output;

    private $cache;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $bufferingMode = "<comment>This option forces buffering mode.</comment>";
        $this->setName(self::NAME)->setDescription('pretty view formatted log.');
        $this->setDefinition([
            new InputArgument('from', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, "Specify input file. '-' means STDIN. and support stream wrapper.
                - e.g. local file:     /path/to/log
                - e.g. specify stdin:  -
                - e.g. sftp protocol1: sftp://user:pass@host/path/to/log (embedded password. very dangerous)
                - e.g. sftp protocol2: sftp://user:-@host/path/to/log (using stdin input)
                - e.g. sftp protocol3: sftp://user@host/path/to/log (using ssh agent)
                - e.g. sftp protocol4: sftp://sshconfig-host/path/to/log (using ssh config)
            "),
            new InputOption('input', 'i', InputOption::VALUE_REQUIRED, "Specify input format[auto|jsonl|ltsv|csv|ssv|tsv].", 'auto'),
            new InputOption('output', 'f', InputOption::VALUE_REQUIRED, "Specify output format[auto|yaml|json|jsonl|ltsv|csv|ssv|tsv|md|php].", 'auto'),
            new InputOption('regex', 'e', InputOption::VALUE_REQUIRED, "Specify regex for not lstv file (only named subpattern).
                - e.g. combined log: --regex '/^(?<host>.*?) (.*?) (.*?) \[(?<time>.*?)\] \"(?<request>.*?)\" (?<status>.*?) (?<size>.*?) \"(?<referer>.*?)\" \"(?<uagent>.*?)\"$/'
                - e.g. preset file:  --regex ./combined.txt
            "),
            new InputOption('distinct', 'd', InputOption::VALUE_OPTIONAL, "Specify distinct column. $bufferingMode
                - e.g. distinct all:    --distinct
                - e.g. distinct column: --distinct 'colC'
            "),
            new InputOption('select', 's', InputOption::VALUE_REQUIRED, "Specify view column. Can use modifier/virtual column by php expression.
                - e.g. select 2 column: --select 'colA, colB'
                - e.g. ignore 1 column: --select '~colC'
                - e.g. column modifier: --select 'colA@strtoupper'
                - e.g. virtual column:  --select 'hoge:`strtoupper(\$colA)`'
                - e.g. all and virtual: --select '*, hoge:`strtoupper(\$colA)`'
            "),
            new InputOption('where', 'w', InputOption::VALUE_REQUIRED, "Specify filter statement. Can use all php functions and use virtual column (like having).
                - e.g. filter greater than: --where '\$colA > 100'
                - e.g. filter match string: --where '\$colA == \"word\"'
                - e.g. filter php function: --where 'ctype_digit(\$colA)'
            "),
            new InputOption('order-by', 't', InputOption::VALUE_REQUIRED, "Specify order column (+/- prefix means ASC/DESC). Can use all php functions and use virtual column. $bufferingMode
                - e.g. order DESC column:    --order-by '-colA'
                - e.g. order multi column:   --order-by '-colA, colB'
                - e.g. order php expression: --order-by '`\$colA + \$colB`'
            "),
            new InputOption('group-by', 'g', InputOption::VALUE_REQUIRED, "Specify group column. Can use all php functions and use virtual column. Grouping will be executed after all finished. $bufferingMode
                - e.g. group colA:           --group-by 'colA'
                - e.g. group php expression: --group-by '`substr(\$colA, 0, 10)`'
                - e.g. group virtual:        --select 'subcolA:`substr(\$colA, 0, 10)`' --group-by 'subcolA'
            "),
            new InputOption('offset', 'o', InputOption::VALUE_REQUIRED, "Specify skip count."),
            new InputOption('limit', 'l', InputOption::VALUE_REQUIRED, "Specify take count."),
            new InputOption('require', 'r', InputOption::VALUE_REQUIRED, "Specify require file.php."),
            new InputOption('below', 'b', InputOption::VALUE_REQUIRED, "Specify count below the matched where (keeping original order)."),
            new InputOption('below-where', 'W', InputOption::VALUE_REQUIRED, "Specify below filter statement."),
            new InputOption('compact', null, InputOption::VALUE_NONE, "Switch compact output."),
            new InputOption('nocomment', 'C', InputOption::VALUE_NONE, "Switch comment output."),
            new InputOption('nocolor', 'H', InputOption::VALUE_NONE, "Switch color output."),
            new InputOption('noerror', 'E', InputOption::VALUE_NONE, "Switch error output."),
        ]);
        $this->setHelp(<<<EOT
<info># simple use STDIN</info>
cat /path/to/log.jsonl | logrep --select col1,col2

<info># specify files</info>
logrep /path/to/log.jsonl --select col1,col2

<info># ignore column</info>
logrep /path/to/log.jsonl --select ~col3

<info># virtual column</info>
logrep /path/to/log.jsonl --select 'col1, hoge:`strtotime(\$reqtime)`'

<info># filtering statement</info>
logrep /path/to/log.jsonl --where '1 <= \$col1 && \$col1 <= 99'

<info># virtual and filtering</info>
logrep /path/to/log.jsonl --select 'col1, hoge:`strtotime(\$reqtime)`' --where '\$hoge <= 1234567890'
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->output->writeln(var_export2($this->input->getArguments(), true), OutputInterface::VERBOSITY_DEBUG);
        $this->output->writeln(var_export2($this->input->getOptions(), true), OutputInterface::VERBOSITY_DEBUG);

        if ($require = $this->input->getOption('require')) {
            require_once $require;
        }

        Sftp::register();
        $restore = ini_sets([
            'error_reporting' => $this->input->getOption('noerror') ? 0 : E_ALL,
            'display_errors'  => 'stderr',
            'log_errors'      => 0,
        ]);
        $result = $this->main();
        $restore();
        return $result;
    }

    private function main()
    {
        $this->cache = [];

        $output = $this->input->getOption('output');
        $below = (int) $this->input->getOption('below');

        $from = $this->from();
        $header = $from->current();
        $from->next();

        if ($output === 'auto') {
            $type = reset($this->cache['from'])['type'] ?? null;
            if ($type === null && !$header) {
                return 255;
            }
        }
        $type ??= AbstractType::instance($output, [
            'comment' => !$this->input->getOption('nocomment'),
            'compact' => !!$this->input->getOption('compact'),
            'color'   => !$this->input->getOption('nocolor'),
        ]);

        $this->output->write($type->head(array_keys($this->column($header))));

        // force group-by
        if (!strlen($this->input->getOption('group-by')) && array_any($this->cache['group-column'], fn($c) => $c)) {
            $this->input->setOption('group-by', '0');
        }

        // distinct/orderBy/groupBy requires buffering
        if ($this->input->hasParameterOption('--distinct') || strlen($this->input->getOption('order-by')) || strlen($this->input->getOption('group-by'))) {
            $buffer = [];
            $lastindex = -1;
            while ($from->valid()) {
                [$seq, $fname, $n, $fields] = $from->current();
                $from->next();

                $columns = $this->select($fields);
                $allcols = $columns + $fields;

                $matched = $this->where($allcols);
                $belowed = !($below === 0 || $lastindex === -1 || ($lastindex + $below < $seq));
                if ($matched) {
                    $lastindex = $seq;
                }
                elseif (!$belowed) {
                    continue;
                }
                elseif ($belowed && !$this->whereBelow($allcols)) {
                    continue;
                }

                if (!$this->distinct($allcols)) {
                    continue;
                }

                if ($matched) {
                    $buffer[$seq] = [$seq, $fname, $n, $columns, $allcols, []];
                }
                else {
                    $buffer[$lastindex][5][] = [$seq, $fname, $n, $columns, $allcols, null];
                }
            }

            $this->orderBy($buffer, 4);
            $this->groupBy($buffer, 4, 3);

            $index = $count = 0;
            foreach ($buffer as $it) {
                [, $fname, $n, $columns, , $children] = $it;

                if (!$this->offset(++$index)) {
                    continue;
                }

                $this->output->write($type->meta($fname, $n));
                $this->output->write($type->body($columns));

                foreach ($children as $child) {
                    if (!$this->limit(++$count)) {
                        break 2;
                    }
                    [, $fname, $n, $columns] = $child;
                    $this->output->write($type->meta($fname, $n));
                    $this->output->write($type->body($columns));
                }

                if (!$this->limit(++$count)) {
                    break;
                }
            }
        }
        else {
            $lastindex = -1;
            $index = $count = 0;
            while ($from->valid()) {
                [$seq, $fname, $n, $fields] = $from->current();
                $from->next();

                $columns = $this->select($fields);
                $allcols = $columns + $fields;

                $matched = $this->where($allcols);
                $belowed = !($below === 0 || $lastindex === -1 || ($lastindex + $below < $seq));
                if ($matched) {
                    $lastindex = $seq;
                }
                elseif (!$belowed) {
                    continue;
                }
                elseif ($belowed && !$this->whereBelow($allcols)) {
                    continue;
                }

                if (!$this->offset(++$index)) {
                    continue;
                }

                $this->output->write($type->meta($fname, $n));
                $this->output->write($type->body($columns));

                if (!$this->limit(++$count)) {
                    break;
                }
            }
        }

        $this->output->write($type->foot());

        return 0;
    }

    private function from(): \Generator
    {
        // shoddy emulation glob (https://www.php.net/manual/function.glob.php)
        $this->cache['from'] ??= (function () {
            $filter = function (&$exts) {
                $inflaters = [
                    'gz'  => ['zlib.inflate', STREAM_FILTER_READ, ['window' => 15 + 16]],
                    'bz2' => ['bzip2.decompress', STREAM_FILTER_READ, []],
                ];
                $filters = [];
                foreach ($exts as $n => $ext) {
                    if (isset($inflaters[$ext])) {
                        $filters[] = $inflaters[$ext];
                        unset($exts[$n]);
                    }
                }
                $exts = array_values($exts);
                return array_reverse($filters);
            };
            $froms = [];
            foreach ((array) ($this->input->getArgument('from') ?: '-') as $from) {
                $pathinfo = path_parse($from);

                if ($from === '-' || file_exists($from)) {
                    $froms[] = [
                        'path'   => $from,
                        'filter' => $filter($pathinfo['extensions']),
                        'ext'    => $pathinfo['extensions'][0] ?? null,
                    ];
                }
                else {
                    foreach (scandir($pathinfo['dirname'], SCANDIR_SORT_NONE) as $entry) {
                        if ($entry === '.' || $entry === '..') {
                            continue;
                        }
                        if (fnmatch($pathinfo['basename'], $entry)) {
                            $pathinfo2 = path_parse($entry);
                            $froms[] = [
                                'path'   => $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $entry,
                                'filter' => $filter($pathinfo2['extensions']),
                                'ext'    => $pathinfo2['extensions'][0] ?? null,
                            ];
                        }
                    }
                }
            }
            return $froms;
        })();

        $itype = $this->input->getOption('input');
        $regex = $this->input->getOption('regex');
        if (file_exists($regex)) {
            $regex = trim(file_get_contents($regex));
        }
        $seq = 0;
        foreach ($this->cache['from'] as &$from) {
            $handle = $from['path'] === '-' ? self::$STDIN : fopen($from['path'], 'r');
            foreach ($from['filter'] as $filter) {
                stream_filter_append($handle, ...$filter);
            }
            $n = 0;
            while (($line = fgets($handle)) !== false) {
                $n++;
                $line = trim($line);
                if (strlen($line)) {
                    if ($regex) {
                        if (!preg_match($regex, $line, $m)) {
                            continue;
                        }
                        $item = [];
                        foreach ($m as $name => $value) {
                            if (is_string($name)) {
                                $item[trim($name)] = trim($value);
                            }
                        }
                    }
                    else {
                        $from['type'] ??= (function ($itype, $line, $ext) {
                            if ($itype === 'auto') {
                                $aliases = [
                                    ''     => 'ssv',
                                    'log'  => 'ssv',
                                    'json' => 'jsonl',
                                ];
                                $itype = $aliases[$ext] ?? $ext;
                            }
                            $option = [
                                'comment' => !$this->input->getOption('nocomment'),
                                'compact' => !!$this->input->getOption('compact'),
                                'color'   => !$this->input->getOption('nocolor'),
                            ];
                            try {
                                return AbstractType::instance($itype, $option);
                            }
                            catch (\Throwable $t) {
                                foreach ([
                                    'jsonl',
                                    'ltsv',
                                ] as $ext) {
                                    $instance = AbstractType::instance($ext, $option);
                                    if ($instance->parse($line) !== null) {
                                        return $instance;
                                    }
                                }
                                throw $t; // @codeCoverageIgnore
                            }
                        })($itype, $line, $from['ext']);

                        $item = $from['type']->parse($line);
                        if ($item === null) {
                            $this->output->writeln(sprintf('<error>%s:%d is not %s line.</error>', $from['path'], $n, $from['ext']));
                            continue;
                        }
                    }
                    // for detect header
                    if ($seq === 0) {
                        yield $item;
                    }
                    yield [$seq++, $from['path'], $n, $item];
                }
            }
        }
    }

    private function column(array $header): array
    {
        // pattern1: column:constant
        // pattern2: column:`expression`
        // pattern3: column@modifier
        // pattern4: `expression`
        // pattern5: *
        // pattern6: ~ignorecolumn
        // pattern7: simplecolumn
        $this->cache['group-column'] ??= [];
        $this->cache['column'] ??= (function ($header) {
            $column = [];
            $ignore = [];
            foreach (quoteexplode(',', $this->input->getOption('select'), null, '`') as $select) {
                $select = $this->expression($select);
                if ($select === null) {
                    continue;
                }
                if (is_array($select)) {
                    foreach ($select as $label => $expr) {
                        if ($expr instanceof \Closure) {
                            try {
                                $expr($header);
                            }
                            catch (\TypeError) {
                                $this->cache['group-column'][$label] = true;
                                break;
                            }
                        }
                    }
                    $column = array_replace($column, $select);
                }
                elseif ($select === '*') {
                    $column = array_fill_keys(array_keys($header), null);
                }
                elseif ($select[0] === '~') {
                    $ignore[ltrim($select, '~')] = true;
                }
                else {
                    $column[$select] = null;
                }
            }
            return array_diff_key($column ?: array_fill_keys(array_keys($header), null), $ignore);
        })($header);

        return $this->cache['column'];
    }

    private function select(array $fields): array
    {
        $result = [];

        foreach ($this->cache['column'] as $column => $mapper) {
            if ($mapper === null) {
                $result[$column] = $fields[$column] ?? null;
            }
            elseif ($mapper instanceof \Closure) {
                if ($this->cache['group-column'][$column] ?? false) {
                    $result[$column] = $mapper; // for group by
                }
                else {
                    $result[$column] = $mapper($fields);
                }
            }
            else {
                $result[$column] = $mapper;
            }
        }

        return $result;
    }

    private function distinct(array $fields): bool
    {
        if (!$this->input->hasParameterOption('--distinct')) {
            return true;
        }

        $this->cache['distinct'] ??= array_fill_keys(
            split_noempty(',', $this->input->getOption('distinct')) ?: array_keys($this->cache['column']), null
        );
        $this->cache['history'] ??= [];

        $key = serialize(array_intersect_key($fields, $this->cache['distinct']));
        if (isset($this->cache['history'][$key])) {
            return false;
        }

        $this->cache['history'][$key] = true;
        return true;
    }

    private function where(array $fields): bool
    {
        $this->cache['where'] ??= (function () {
            if ($this->input->getOption('where') !== null) {
                return $this->evaluate($this->input->getOption('where'));
            }
            return false;
        })();

        if ($this->cache['where'] === false) {
            return true;
        }

        return $this->cache['where']($fields);
    }

    private function whereBelow(array $fields): bool
    {
        $this->cache['below-where'] ??= (function () {
            if ($this->input->getOption('below-where') !== null) {
                return $this->evaluate($this->input->getOption('below-where'));
            }
            return false;
        })();

        if ($this->cache['below-where'] === false) {
            return true;
        }

        return $this->cache['below-where']($fields);
    }

    private function orderBy(array &$buffer, int $allindex)
    {
        if (!strlen($this->input->getOption('order-by'))) {
            return;
        }

        $this->cache['order-by'] ??= (function () {
            $orderBy = [];
            foreach (quoteexplode(',', $this->input->getOption('order-by'), null, '`') as $col) {
                $prefix = $col[0];
                $col = ltrim($col, '+-');
                $ord = ['+' => true, '-' => false][$prefix] ?? true;
                if ($col[0] === '`') {
                    $orderBy[] = [
                        trim($col, '`'),
                        $ord,
                        function ($col, $fields) {
                            return $this->evaluate($col)($fields);
                        },
                    ];
                }
                else {
                    $orderBy[] = [$col, $ord, null];
                }
            }
            return $orderBy;
        })();

        usort($buffer, function ($a, $b) use ($allindex) {
            foreach ($this->cache['order-by'] as $orderBy) {
                [$key, $ord, $expr] = $orderBy;
                if ($expr) {
                    $delta = $expr($key, $a[$allindex]) <=> $expr($key, $b[$allindex]);
                }
                else {
                    $delta = $a[$allindex][$key] <=> $b[$allindex][$key];
                }
                if ($delta !== 0) {
                    if (!$ord) {
                        $delta = -$delta;
                    }
                    return $delta;
                }
            }
            return 0;
        });
    }

    private function groupBy(array &$buffer, int $allindex, int $colindex)
    {
        if (!strlen($this->input->getOption('group-by'))) {
            return;
        }

        $this->cache['group-by'] ??= function ($line) {
            $keys = [];
            foreach (quoteexplode(',', $this->input->getOption('group-by'), null, '`') as $by) {
                $col = $this->expression($by);
                if (is_array($col)) {
                    foreach ($col as $c => $val) {
                        if ($val instanceof \Closure) {
                            $keys[$c] = $val($line);
                        }
                        else {
                            throw new \InvalidArgumentException("group by doesn't allow alias($by)");
                        }
                    }
                }
                elseif (is_string($col)) {
                    $keys[$col] = $line[$col] ?? null;
                }
            }
            return $keys;
        };

        $groups = [];
        foreach ($buffer as $line) {
            $key = serialize($this->cache['group-by']($line[$allindex]));
            $groups[$key][] = $line;
        }

        $buffer = [];
        foreach ($groups as $lines) {
            $line = reset($lines);

            $values = array_column($lines, $allindex);
            $fields = [];
            foreach ($line[$allindex] as $c => $v) {
                $fields[$c] = array_column($values, $c);
            }

            foreach ($line[$colindex] as $c => $v) {
                if ($v instanceof \Closure) {
                    $line[$colindex][$c] = $v($fields);
                }
            }
            $buffer[] = $line;
        }
    }

    private function offset(int $index): bool
    {
        $this->cache['offset'] ??= (int) $this->input->getOption('offset');

        if ($this->cache['offset'] === 0) {
            return true;
        }

        return $index > $this->cache['offset'];
    }

    private function limit(int $count): bool
    {
        $this->cache['limit'] ??= (int) $this->input->getOption('limit');

        if ($this->cache['limit'] === 0) {
            return true;
        }

        return $count < $this->cache['limit'];
    }

    private function expression(string $expression)
    {
        $expression = trim($expression);
        if (!strlen($expression)) {
            return null;
        }
        $colon = strpos($expression, ':') !== false;
        $atmark = strpos($expression, '@') !== false;
        $backq = strpos($expression, '`') !== false;
        if ($colon && !$backq) {
            [$expression, $expr] = array_map('trim', explode(':', $expression, 2));
            return [$expression => $expr];
        }
        elseif ($colon && $backq) {
            [$expression, $expr] = array_map('trim', explode(':', $expression, 2));
            return [$expression => $this->evaluate(trim($expr, '`'))];
        }
        elseif ($atmark && !$backq) {
            [$expression, $expr] = array_map('trim', explode('@', $expression, 2));
            return [$expression => $this->evaluate("$expr(\$$expression)")];
        }
        elseif ($backq) {
            $expression = trim($expression, '`');
            return [$expression => $this->evaluate($expression)];
        }
        else {
            return $expression;
        }
    }

    private function evaluate(string $expression)
    {
        return evaluate("return static function() {
            extract(func_get_arg(0));
            return $expression;
        };");
    }
}
