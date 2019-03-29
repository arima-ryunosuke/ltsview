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
            new InputOption('offset', 'o', InputOption::VALUE_REQUIRED, "Specify skip count."),
            new InputOption('limit', 'l', InputOption::VALUE_REQUIRED, "Specify take count."),
            new InputOption('require', 'r', InputOption::VALUE_REQUIRED, "Specify require file.php."),
            new InputOption('format', 'f', InputOption::VALUE_REQUIRED, "Specify output format[yaml|json|ltsv|tsv|md|php].", 'yaml'),
            new InputOption('below', 'b', InputOption::VALUE_REQUIRED, "Specify count below the matched where."),
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

        $type = AbstractType::instance($format, $comment);

        $index = $count = 0;
        $lastindex = -1;

        $this->output->write($type->head($this->column()));
        foreach ($this->from() as $fname => $from) {
            foreach ($from as $n => $fields) {
                $columns = $this->select($fields);

                if ($this->where($columns + $fields)) {
                    $lastindex = $n;
                }
                elseif ($below === 0 || $lastindex === -1 || ($lastindex + $below < $n)) {
                    continue;
                }

                if (!$this->offset(++$index)) {
                    continue;
                }

                $this->output->write($type->meta($fname, $n + 1));
                $this->output->write($type->body($columns));

                if (!$this->limit(++$count)) {
                    break 2;
                }
            }
        }
        $this->output->write($type->foot());
    }

    private function from()
    {
        $froms = (array) ($this->input->getArgument('from') ?: '-');
        foreach ($froms as $from) {
            yield $from => (function ($from) {
                $handle = $from === '-' ? self::$STDIN : fopen($from, 'r');
                while (($line = fgets($handle)) !== false) {
                    if (strlen(trim($line))) {
                        yield str_array(explode("\t", $line), ':', true);
                    }
                }
            })($from);
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