<?php

namespace ryunosuke\ltsv\Console\Command;

use ryunosuke\ltsv\Type\AbstractType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LtsviewCommand extends Command
{
    public const NAME    = 'ltsview';
    public const VERSION = '1.0.0';

    private static $STDIN = STDIN;

    /** @var InputInterface */
    private $input;

    /** @var OutputInterface */
    private $output;

    private $cache;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)->setDescription('pretty view ltsv format.');
        $this->setDefinition([
            new InputArgument('from', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, "Specify input file. '-' means STDIN"),
            new InputOption('select', 's', InputOption::VALUE_REQUIRED, "Specify view column. Can use modifier/virtual column by php expression.
                - e.g. select 2 column: --select 'colA, colB'
                - e.g. ignore 1 column: --select '~colC'
                - e.g. column modifier: --select 'colA@strtoupper'
                - e.g. virtual column: --select 'hoge:`strtoupper(\$colA)`'
                - e.g. all and virtual: --select '*, hoge:`strtoupper(\$colA)`'
                "),
            new InputOption('where', 'w', InputOption::VALUE_REQUIRED, "Specify filter statement. Can use all php functions and use virtual column (like having).
                - e.g. filter greater than: --where '\$colA > 100'
                - e.g. filter match string: --where '\$colA == \"word\"'
                - e.g. filter php function: --where 'ctype_digit(\$colA)'"),
            new InputOption('order-by', 't', InputOption::VALUE_REQUIRED, "Specify order column (+/- prefix means ASC/DESC). Can use all php functions and use virtual column.
                - e.g. order DESC column: --order-by '-colA'
                - e.g. order colti column: --order-by '-colA, colB'
                - e.g. order php expression: --order-by '`\$colA + \$colB`'"),
            new InputOption('offset', 'o', InputOption::VALUE_REQUIRED, "Specify skip count."),
            new InputOption('limit', 'l', InputOption::VALUE_REQUIRED, "Specify take count."),
            new InputOption('require', 'r', InputOption::VALUE_REQUIRED, "Specify require file.php."),
            new InputOption('format', 'f', InputOption::VALUE_REQUIRED, "Specify output format[yaml|json|ltsv|tsv|md|php].", 'yaml'),
            new InputOption('below', 'b', InputOption::VALUE_REQUIRED, "Specify count below the matched where (keeping original order)."),
            new InputOption('below-where', 'W', InputOption::VALUE_REQUIRED, "Specify below filter statement."),
            new InputOption('compact', null, InputOption::VALUE_NONE, "Switch compact output."),
            new InputOption('nocomment', 'C', InputOption::VALUE_NONE, "Switch comment output."),
            new InputOption('noerror', 'E', InputOption::VALUE_NONE, "Switch error output."),
        ]);
        $this->setHelp(<<<EOT
<info># simple use STDIN</info>
cat /path/to/ltsv.log | ltsview --select col1,col2

<info># specify files</info>
ltsview /path/to/ltsv.log --select col1,col2

<info># ignore column</info>
ltsview /path/to/ltsv.log --select ~col3

<info># virtual column</info>
ltsview /path/to/ltsv.log --select 'col1, hoge:`strtotime(\$reqtime)`'

<info># filtering statement</info>
ltsview /path/to/ltsv.log --where '1 <= \$col1 && \$col1 <= 99'

<info># virtual and filtering</info>
ltsview /path/to/ltsv.log --select 'col1, hoge:`strtotime(\$reqtime)`' --where '\$hoge <= 1234567890'
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

        ini_set('error_reporting', $this->input->getOption('noerror') ? 0 : E_ALL);
        $result = $this->main();
        ini_restore('error_reporting');
        return $result;
    }

    private function main()
    {
        $this->cache = [];

        $format = $this->input->getOption('format');
        $below = (int) $this->input->getOption('below');
        $comment = !$this->input->getOption('nocomment');
        $compact = !!$this->input->getOption('compact');

        $type = AbstractType::instance($format, $comment, $compact);


        $this->output->write($type->head($this->column()));

        // orderBy requires buffering
        if ($this->input->getOption('order-by')) {
            $buffer = [];
            $lastindex = -1;
            foreach ($this->from() as $it) {
                list($seq, $fname, $n, $fields) = $it;

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

                if ($matched) {
                    $buffer[$seq] = [$seq, $fname, $n, $columns, $allcols, []];
                }
                else {
                    $buffer[$lastindex][5][] = [$seq, $fname, $n, $columns, $allcols, null];
                }
            }

            $this->orderBy($buffer);

            $index = $count = 0;
            foreach ($buffer as $it) {
                list(, $fname, $n, $columns, , $children) = $it;

                if (!$this->offset(++$index)) {
                    continue;
                }

                $this->output->write($type->meta($fname, $n + 1));
                $this->output->write($type->body($columns));

                foreach ($children as $child) {
                    if (!$this->limit(++$count)) {
                        break 2;
                    }
                    list(, $fname, $n, $columns) = $child;
                    $this->output->write($type->meta($fname, $n + 1));
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
            foreach ($this->from() as $it) {
                list($seq, $fname, $n, $fields) = $it;

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

                $this->output->write($type->meta($fname, $n + 1));
                $this->output->write($type->body($columns));

                if (!$this->limit(++$count)) {
                    break;
                }
            }
        }

        $this->output->write($type->foot());
    }

    private function from()
    {
        $froms = (array) ($this->input->getArgument('from') ?: '-');
        $seq = 0;
        foreach ($froms as $from) {
            $handle = $from === '-' ? self::$STDIN : fopen($from, 'r');
            $n = 0;
            while (($line = fgets($handle)) !== false) {
                $n++;
                if (strlen(trim($line))) {
                    yield [$seq++, $from, $n, str_array(explode("\t", $line), ':', true)];
                }
            }
        }
    }

    private function column()
    {
        $this->cache['select'] = $this->cache['select'] ??
            chain($this->input->getOption('select'))
                ->quoteexplode1(',', '`')
                ->map('trim')
                ->filter('strlen')
            ();

        $this->cache['column'] = $this->cache['column'] ??
            chain($this->cache['select'])
                ->filterE('[0] !== "~"')
                ->map(function ($key) { return trim(explode(':', $key, 2)[0], '`'); })
            ();

        return $this->cache['column'];
    }

    private function select($fields)
    {
        $result = $this->column() ? [] : $fields;

        // pattern1: column:`expression`
        // pattern2: column:constant
        // pattern3: column@modifier
        // pattern4: `expression`
        // pattern5: *
        // pattern6: ~ignorecolumn
        // pattern7: simplecolumn
        $this->cache['mapper'] = $this->cache['mapper'] ?? (function () {
                $mapper = [];
                foreach ($this->cache['select'] as $key) {
                    $colon = strpos($key, ':') !== false;
                    $atmark = strpos($key, '@') !== false;
                    $backq = strpos($key, '`') !== false;
                    if ($colon && $backq) {
                        list($key, $expr) = array_map('trim', explode(':', $key, 2));
                        $expr = trim($expr, '`');
                        $mapper[$key] = function ($key, $fields, &$result) use ($expr) {
                            $result[$key] = $this->evaluate($expr, $fields);
                        };
                    }
                    elseif ($colon && !$backq) {
                        list($key, $expr) = array_map('trim', explode(':', $key, 2));
                        $mapper[$key] = function ($key, $fields, &$result) use ($expr) {
                            $result[$key] = $expr;
                        };
                    }
                    elseif ($atmark && !$backq) {
                        list($key, $expr) = array_map('trim', explode('@', $key, 2));
                        $expr = "$expr(\$$key)";
                        $mapper[$key] = function ($key, $fields, &$result) use ($expr) {
                            $result[$key] = $this->evaluate($expr, $fields);
                        };
                    }
                    elseif ($backq) {
                        $mapper[trim($key, '`')] = function ($key, $fields, &$result) {
                            $result[$key] = $this->evaluate($key, $fields);
                        };
                    }
                    elseif ($key === '*') {
                        $mapper[$key] = function ($key, $fields, &$result) {
                            $result = array_replace($result, $fields);
                        };
                    }
                    elseif ($key[0] === '~') {
                        $mapper[ltrim($key, '~')] = function ($key, $fields, &$result) {
                            unset($result[$key]);
                        };
                    }
                    else {
                        $mapper[$key] = function ($key, $fields, &$result) {
                            $result[$key] = $fields[$key];
                        };
                    }
                }
                return $mapper;
            })();

        foreach ($this->cache['mapper'] as $key => $mapper) {
            $mapper($key, $fields, $result);
        }

        return $result;
    }

    private function where($fields)
    {
        $this->cache['where'] = $this->cache['where'] ?? $this->input->getOption('where');

        if ($this->cache['where'] === null) {
            return true;
        }

        return $this->evaluate($this->cache['where'], $fields);
    }

    private function whereBelow($fields)
    {
        $this->cache['below-where'] = $this->cache['below-where'] ?? $this->input->getOption('below-where');

        if ($this->cache['below-where'] === null) {
            return true;
        }

        return $this->evaluate($this->cache['below-where'], $fields);
    }

    private function orderBy(&$buffer)
    {
        $this->cache['order-by'] = $this->cache['order-by'] ?? (function () {
                $orderBy = [];
                foreach (quoteexplode(',', $this->input->getOption('order-by'), '`') as $col) {
                    $prefix = $col[0];
                    $col = ltrim($col, '+-');
                    $ord = ['+' => true, '-' => false][$prefix] ?? true;
                    if ($col[0] === '`') {
                        $orderBy[] = [
                            trim($col, '`'),
                            $ord,
                            function ($col, $fields) {
                                return $this->evaluate($col, $fields);
                            }
                        ];
                    }
                    else {
                        $orderBy[] = [$col, $ord, null];
                    }
                }
                return $orderBy;
            })();

        usort($buffer, function ($a, $b) {
            foreach ($this->cache['order-by'] as $orderBy) {
                list($key, $ord, $expr) = $orderBy;
                if ($expr) {
                    $delta = $expr($key, $a[3]) <=> $expr($key, $b[3]);
                }
                else {
                    $delta = $a[3][$key] <=> $b[3][$key];
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

    private function offset($index)
    {
        $this->cache['offset'] = $this->cache['offset'] ?? (int) $this->input->getOption('offset');

        if ($this->cache['offset'] === 0) {
            return true;
        }

        return $index > $this->cache['offset'];
    }

    private function limit($count)
    {
        $this->cache['limit'] = $this->cache['limit'] ?? (int) $this->input->getOption('limit');

        if ($this->cache['limit'] === 0) {
            return true;
        }

        return $count < $this->cache['limit'];
    }

    private function evaluate($expression, $fields)
    {
        $callee = eval_func("(function () {
            extract(func_get_arg(0));
            return $expression;
        })(\$vars)", 'vars');
        return $callee($fields);
    }
}
