ltsview
====

## Description

This package is command-line tool for pretty print LTSV

## Install

```
wget https://github.com/arima-ryunosuke/ltsview/raw/master/ltsview.phar
chmod +x ltsview.phar
# mv ltsview.phar /usr/bin/ltsview
```

## Usage

```
Usage:
  ltsview [options] [--] [<from>]...

Arguments:
  from                           Specify input file. '-' means STDIN. and support stream wrapper.
                                 - e.g. local file:     /path/to/ltsv
                                 - e.g. specify stdin:  -
                                 - e.g. sftp protocol1: sftp://user:pass@host/path/to/ltsv (embedded password. very dangerous)
                                 - e.g. sftp protocol2: sftp://user:-@host/path/to/ltsv (using stdin input)
                                 - e.g. sftp protocol3: sftp://user@host/path/to/ltsv (using ssh agent)
                                 - e.g. sftp protocol4: sftp://sshconfig-host/path/to/ltsv (using ssh config)
                                 

Options:
  -e, --regex=REGEX              Specify regex for not lstv file (only named subpattern).
                                 - e.g. combined log: --regex '/^(?<host>.*?) (.*?) (.*?) \[(?<time>.*?)\] "(?<request>.*?)" (?<status>.*?) (?<size>.*?) "(?<referer>.*?)" "(?<uagent>.*?)"$/'
                                 - e.g. preset file:  --regex ./combined.txt
                                 
  -d, --distinct[=DISTINCT]      Specify distinct column.
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
                                 
  -t, --order-by=ORDER-BY        Specify order column (+/- prefix means ASC/DESC). Can use all php functions and use virtual column.
                                 - e.g. order DESC column:    --order-by '-colA'
                                 - e.g. order colti column:   --order-by '-colA, colB'
                                 - e.g. order php expression: --order-by '`$colA + $colB`'
                                 
  -o, --offset=OFFSET            Specify skip count.
  -l, --limit=LIMIT              Specify take count.
  -r, --require=REQUIRE          Specify require file.php.
  -f, --format=FORMAT            Specify output format[yaml|json|jsonl|ltsv|tsv|md|php]. [default: "yaml"]
  -b, --below=BELOW              Specify count below the matched where (keeping original order).
  -W, --below-where=BELOW-WHERE  Specify below filter statement.
      --compact                  Switch compact output.
  -C, --nocomment                Switch comment output.
  -H, --nocolor                  Switch color output.
  -E, --noerror                  Switch error output.
  -h, --help                     Display this help message
  -q, --quiet                    Do not output any message
  -V, --version                  Display this application version
      --ansi                     Force ANSI output
      --no-ansi                  Disable ANSI output
  -n, --no-interaction           Do not ask any interactive question
  -v|vv|vvv, --verbose           Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  # simple use STDIN
  cat /path/to/ltsv.log | ltsview --select col1,col2
  
  # specify files
  ltsview /path/to/ltsv.log --select col1,col2
  
  # ignore column
  ltsview /path/to/ltsv.log --select ~col3
  
  # virtual column
  ltsview /path/to/ltsv.log --select 'col1, hoge:`strtotime($reqtime)`'
  
  # filtering statement
  ltsview /path/to/ltsv.log --where '1 <= $col1 && $col1 <= 99'
  
  # virtual and filtering
  ltsview /path/to/ltsv.log --select 'col1, hoge:`strtotime($reqtime)`' --where '$hoge <= 1234567890'
```

## License

MIT
