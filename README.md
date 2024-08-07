logrep
====

## Description

This package is command-line tool for gather log

## Install

```
wget https://github.com/arima-ryunosuke/ltsview/raw/master/logrep.phar
chmod +x logrep.phar
# mv logrep.phar /usr/bin/logrep
```

## Usage

```
Description:
  pretty view formatted log.

Usage:
  logrep [options] [--] [<from>...]

Arguments:
  from                           Specify input file. '-' means STDIN. and support stream wrapper.
                                 - e.g. local file:     /path/to/log
                                 - e.g. specify stdin:  -
                                 - e.g. sftp protocol1: sftp://user:pass@host/path/to/log (embedded password. very dangerous)
                                 - e.g. sftp protocol2: sftp://user:-@host/path/to/log (using stdin input)
                                 - e.g. sftp protocol3: sftp://user@host/path/to/log (using ssh agent)
                                 - e.g. sftp protocol4: sftp://sshconfig-host/path/to/log (using ssh config)
                                 

Options:
  -c, --config=CONFIG            Specify config file. config file can set default values for all arguments and options by php.
  -i, --input=INPUT              Specify input format[auto|jsonl|ltsv|csv|ssv|tsv]. [default: "auto"]
  -f, --output=OUTPUT            Specify output format[auto|yaml|json|jsonl|ltsv|csv|ssv|tsv|sql|md|php]. [default: "auto"]
  -e, --regex=REGEX              Specify regex for not lstv file (only named subpattern).
                                 - e.g. combined log: --regex '/^(?<host>.*?) (.*?) (.*?) \[(?<time>.*?)\] "(?<request>.*?)" (?<status>.*?) (?<size>.*?) "(?<referer>.*?)" "(?<uagent>.*?)"$/'
                                 - e.g. preset file:  --regex ./combined.txt
                                 
  -d, --distinct[=DISTINCT]      Specify distinct column. This option forces buffering mode.
                                 - e.g. distinct all:    --distinct
                                 - e.g. distinct column: --distinct 'colC'
                                 
  -s, --select=SELECT            Specify view column. Can use modifier/virtual column by php expression.
                                 - e.g. select 2 column: --select 'colA, colB'
                                 - e.g. ignore 1 column: --select '~colC'
                                 - e.g. column modifier: --select 'colA@strtoupper'
                                 - e.g. virtual column:  --select 'hoge:`strtoupper($colA)`'
                                 - e.g. all and virtual: --select '*, hoge:`strtoupper($colA)`'
                                 
  -w, --where=WHERE              Specify filter statement. Can use all php functions and use virtual column (like having).
                                 - e.g. filter greater than: --where '$colA > 100'
                                 - e.g. filter match string: --where '$colA == "word"'
                                 - e.g. filter php function: --where 'ctype_digit($colA)'
                                 
  -t, --order-by=ORDER-BY        Specify order column (+/- prefix means ASC/DESC). Can use all php functions and use virtual column. This option forces buffering mode.
                                 - e.g. order DESC column:    --order-by '-colA'
                                 - e.g. order multi column:   --order-by '-colA, colB'
                                 - e.g. order php expression: --order-by '`$colA + $colB`'
                                 
  -g, --group-by=GROUP-BY        Specify group column. Can use all php functions and use virtual column. Grouping will be executed after all finished. This option forces buffering mode.
                                 - e.g. group colA:           --group-by 'colA'
                                 - e.g. group php expression: --group-by '`substr($colA, 0, 10)`'
                                 - e.g. group virtual:        --select 'subcolA:`substr($colA, 0, 10)`' --group-by 'subcolA'
                                 
  -o, --offset=OFFSET            Specify skip count.
  -l, --limit=LIMIT              Specify take count.
  -r, --require=REQUIRE          Specify require file.php.
  -b, --below=BELOW              Specify count below the matched where (keeping original order).
  -W, --below-where=BELOW-WHERE  Specify below filter statement.
      --table=TABLE              Specify tablename when SQL output.
      --compact                  Switch compact output.
  -C, --nocomment                Switch comment output.
  -H, --nocolor                  Switch color output.
  -E, --noerror                  Switch error output.
  -h, --help                     Display help for the given command. When no command is given display help for the logrep command
  -q, --quiet                    Do not output any message
  -V, --version                  Display this application version
      --ansi|--no-ansi           Force (or disable --no-ansi) ANSI output
  -n, --no-interaction           Do not ask any interactive question
  -v|vv|vvv, --verbose           Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  # simple use STDIN
  cat /path/to/log.jsonl | logrep --select col1,col2
  
  # specify files
  logrep /path/to/log.jsonl --select col1,col2
  
  # ignore column
  logrep /path/to/log.jsonl --select ~col3
  
  # virtual column
  logrep /path/to/log.jsonl --select 'col1, hoge:`strtotime($reqtime)`'
  
  # filtering statement
  logrep /path/to/log.jsonl --where '1 <= $col1 && $col1 <= 99'
  
  # virtual and filtering
  logrep /path/to/log.jsonl --select 'col1, hoge:`strtotime($reqtime)`' --where '$hoge <= 1234567890'
```

## License

MIT
