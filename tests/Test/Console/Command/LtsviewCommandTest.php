<?php

namespace ryunosuke\test\Console\Command;

use ryunosuke\ltsv\Console\Command\LtsviewCommand;

class LtsviewCommandTest extends AbstractTestCase
{
    /** @var LtsviewCommand */
    private $command;

    protected $commandName = 'ltsview';

    protected $defaultArgs = [
        '--format'    => 'php',
        '--nocomment' => true,
        '--compact'   => true,
        '--nocolor'   => true,
    ];

    private $stdin;

    protected function setup(): void
    {
        parent::setUp();

        $this->command = new LtsviewCommand('test');

        $ref = new \ReflectionClass($this->command);
        $stdin = $ref->getProperty('STDIN');
        $stdin->setAccessible(true);
        $stdin->setValue($this->stdin = fopen('php://memory', 'rw'));

        $this->app->add($this->command);
    }

    protected function tearDown(): void
    {
        $ref = new \ReflectionClass($this->command);
        $stdin = $ref->getProperty('STDIN');
        $stdin->setAccessible(true);
        $stdin->setValue(STDIN);
    }

    function test_all()
    {
        $result = $this->runApp([
            'from'        => [__DIR__ . '/_files/log1.ltsv', __DIR__ . '/_files/log2.ltsv'],
            '--select'    => 'colA, colC',
            '--where'     => '$colA < 600',
            '--offset'    => 1,
            '--limit'     => 6,
            '--format'    => 'ltsv',
            '--nocomment' => false,
        ]);
        $this->assertEquals('colA:456	colC:ho ge ra2
colA:123	colC:fu ga yo1
colA:456	colC:fu ga yo2
colA:234	colC:fu ga yo4
colA:567	colC:fu ga yo5
colA:345	colC:fu ga yo7
', $result);
    }

    function test_from()
    {
        ftruncate($this->stdin, 0);
        fwrite($this->stdin, "a:A1\tb:B1\tc:C1\na:A2\tb:B2\tc:C2");
        rewind($this->stdin);

        $result = $this->runApp([
            'from' => '-',
        ]);
        $this->assertEquals([
            ['a' => 'A1', 'b' => 'B1', 'c' => 'C1',],
            ['a' => 'A2', 'b' => 'B2', 'c' => 'C2',],
        ], eval("return $result;"), "Actual:\n$result");
    }

    function test_from_gz()
    {
        $result = $this->runApp([
            'from' => __DIR__ . '/_files/log.ltsv.gz',
        ]);
        $this->assertEquals([
            ['colB' => 'aaa', 'colA' => '123', 'colC' => 'ho ge ra1',],
            ['colB' => 'bbb', 'colA' => '456', 'colC' => 'ho ge ra2',],
            ['colB' => 'ccc', 'colA' => '789', 'colC' => 'ho ge ra3',],
        ], eval("return $result;"), "Actual:\n$result");
    }

    function test_from_glob()
    {
        $expected = $this->runApp([
            'from' => glob(__DIR__ . '/_files/log*.ltsv'),
        ]);
        $result = $this->runApp([
            'from' => __DIR__ . '/_files/log*.ltsv',
        ]);
        $this->assertEquals($expected, $result);
    }

    function test_from_regex()
    {
        $result = $this->runApp([
            'from'    => __DIR__ . '/_files/apache.log',
            '--regex' => '/^(?<host>.*?) (.*?) (.*?) \[(?<time>.*?)\] "(?<request>.*?)" (?<status>.*?) (?<size>.*?) "(?<referer>.*?)" "(?<uagent>.*?)"$/',
        ]);
        $this->assertEquals([
            ['host' => '127.0.0.1', 'time' => '21/Apr/2019:12:34:56 +0900', 'request' => 'GET /path/to/file1 HTTP/1.1', 'status' => '200', 'size' => '12345', 'referer' => '-', 'uagent' => 'Mozilla/5.0 Custom Browser',],
            ['host' => '127.0.0.2', 'time' => '21/Apr/2019:13:12:33 +0900', 'request' => 'GET /path/to/file2 HTTP/1.1', 'status' => '200', 'size' => '54321', 'referer' => '-', 'uagent' => 'Mozilla/5.0 Custom Browser',],
            ['host' => '127.0.0.1', 'time' => '21/Apr/2019:14:47:28 +0900', 'request' => 'GET /path/to/file1 HTTP/1.1', 'status' => '200', 'size' => '23456', 'referer' => '-', 'uagent' => 'Mozilla/5.0 Custom Browser',],
            ['host' => '127.0.0.1', 'time' => '21/Apr/2019:15:51:39 +0900', 'request' => 'GET /path/to/file2 HTTP/1.1', 'status' => '200', 'size' => '67890', 'referer' => '-', 'uagent' => 'Mozilla/5.0 Custom Browser',],
            ['host' => '127.0.0.2', 'time' => '21/Apr/2019:16:12:46 +0900', 'request' => 'GET /path/to/file1 HTTP/1.1', 'status' => '200', 'size' => '34567', 'referer' => '-', 'uagent' => 'Mozilla/5.0 Custom Browser',],
        ], eval("return $result;"), "Actual:\n$result");

        $result = $this->runApp([
            'from'     => __DIR__ . '/_files/apache.log',
            '--regex'  => __DIR__ . '/_files/preset-combined.txt',
            '--select' => '*, time:`date("Y/m/d H:i:s", strtotime($time))`',
            '--where'  => '$host == "127.0.0.2" && $size > 50000',
        ]);
        $this->assertEquals([
            [
                'host'     => '127.0.0.2',
                'time'     => '2019/04/21 13:12:33',
                'method'   => 'GET',
                'path'     => '/path/to/file2',
                'protocol' => 'HTTP/1.1',
                'status'   => '200',
                'size'     => '54321',
                'referer'  => '-',
                'uagent'   => 'Mozilla/5.0 Custom Browser',
            ],
        ], eval("return $result;"), "Actual:\n$result");
    }

    function test_select()
    {
        $result = $this->runApp([
            'from'     => [__DIR__ . '/_files/log1.ltsv'],
            '--select' => 'colB, *',
        ]);
        $this->assertEquals([
            ['colB' => 'aaa', 'colA' => '123', 'colC' => 'ho ge ra1',],
            ['colB' => 'bbb', 'colA' => '456', 'colC' => 'ho ge ra2',],
            ['colB' => 'ccc', 'colA' => '789', 'colC' => 'ho ge ra3',],
        ], eval("return $result;"), "Actual:\n$result");
    }

    function test_select_except()
    {
        $result = $this->runApp([
            'from'     => [__DIR__ . '/_files/log1.ltsv'],
            '--select' => '~colA',
        ]);
        $this->assertEquals([
            ['colB' => 'aaa', 'colC' => 'ho ge ra1',],
            ['colB' => 'bbb', 'colC' => 'ho ge ra2',],
            ['colB' => 'ccc', 'colC' => 'ho ge ra3',],
        ], eval("return $result;"), "Actual:\n$result");
    }

    function test_select_virtual()
    {
        $result = $this->runApp([
            'from'     => [__DIR__ . '/_files/log1.ltsv'],
            '--select' => 'colA, ,trim:`trim($colB, "a")`, `strtoupper($colC)`, const:aaaa',
        ]);
        $this->assertEquals([
            ['colA' => '123', 'trim' => '', 'strtoupper($colC)' => 'HO GE RA1', 'const' => 'aaaa',],
            ['colA' => '456', 'trim' => 'bbb', 'strtoupper($colC)' => 'HO GE RA2', 'const' => 'aaaa',],
            ['colA' => '789', 'trim' => 'ccc', 'strtoupper($colC)' => 'HO GE RA3', 'const' => 'aaaa',],
        ], eval("return $result;"), "Actual:\n$result");
    }

    function test_select_modifier()
    {
        $result = $this->runApp([
            'from'     => [__DIR__ . '/_files/log1.ltsv'],
            '--select' => 'colA,colC@strtoupper',
        ]);
        $this->assertEquals([
            ['colA' => '123', 'colC' => 'HO GE RA1',],
            ['colA' => '456', 'colC' => 'HO GE RA2',],
            ['colA' => '789', 'colC' => 'HO GE RA3',],
        ], eval("return $result;"), "Actual:\n$result");
    }

    function test_select_distinct()
    {
        $result = $this->runApp([
            'from'       => [__DIR__ . '/_files/log4.ltsv'],
            '--distinct' => '',
        ]);
        $this->assertEquals([
            ['colA' => 'a', 'colB' => 'b', 'colC' => 'c'],
            ['colA' => 'A', 'colB' => 'b', 'colC' => 'c'],
            ['colA' => 'a', 'colB' => 'B', 'colC' => 'c'],
            ['colA' => 'a', 'colB' => 'b', 'colC' => 'C'],
        ], eval("return $result;"), "Actual:\n$result");

        $result = $this->runApp([
            'from'       => [__DIR__ . '/_files/log4.ltsv'],
            '--distinct' => 'colA,colC',
        ]);
        $this->assertEquals([
            ['colA' => 'a', 'colB' => 'b', 'colC' => 'c'],
            ['colA' => 'A', 'colB' => 'b', 'colC' => 'c'],
            ['colA' => 'a', 'colB' => 'b', 'colC' => 'C'],
        ], eval("return $result;"), "Actual:\n$result");

        $result = $this->runApp([
            'from'       => [__DIR__ . '/_files/log4.ltsv'],
            '--distinct' => 'colB',
        ]);
        $this->assertEquals([
            ['colA' => 'a', 'colB' => 'b', 'colC' => 'c'],
            ['colA' => 'a', 'colB' => 'B', 'colC' => 'c'],
        ], eval("return $result;"), "Actual:\n$result");
    }

    function test_where()
    {
        $result = $this->runApp([
            'from'    => [__DIR__ . '/_files/log1.ltsv'],
            '--where' => '$colA > 500 OR strtolower($colB) == "aaa"',
        ]);
        $this->assertEquals([
            ['colA' => '123', 'colB' => 'aaa', 'colC' => 'ho ge ra1',],
            ['colA' => '789', 'colB' => 'ccc', 'colC' => 'ho ge ra3',],
        ], eval("return $result;"), "Actual:\n$result");
    }

    function test_where_vcolumn()
    {
        $result = $this->runApp([
            'from'     => [__DIR__ . '/_files/log1.ltsv'],
            '--select' => 'upper:`strtoupper($colB)`',
            '--where'  => '$upper === "AAA"',
        ]);
        $this->assertEquals([
            ['upper' => 'AAA'],
        ], eval("return $result;"), "Actual:\n$result");
    }

    function test_where_below()
    {
        $result = $this->runApp([
            'from'    => [__DIR__ . '/_files/log2.ltsv'],
            '--where' => 'in_array($colA, [789, 678])',
            '--below' => 3,
        ]);
        $this->assertEquals([
            ['colA' => '789', 'colB' => 'CCC', 'colC' => 'fu ga yo3',],
            ['colA' => '234', 'colB' => 'DDD', 'colC' => 'fu ga yo4',],
            ['colA' => '567', 'colB' => 'EEE', 'colC' => 'fu ga yo5',],
            ['colA' => '890', 'colB' => 'FFF', 'colC' => 'fu ga yo6',],
            ['colA' => '678', 'colB' => 'HHH', 'colC' => 'fu ga yo8',],
            ['colA' => '901', 'colB' => 'III', 'colC' => 'fu ga yo9',],
            ['colA' => '999', 'colB' => 'JJJ', 'colC' => 'fu ga yo10',],
        ], eval("return $result;"), "Actual:\n$result");
    }

    function test_below_where()
    {
        $result = $this->runApp([
            'from'          => [__DIR__ . '/_files/log2.ltsv'],
            '--where'       => 'in_array($colA, [789, 678])',
            '--below'       => 3,
            '--below-where' => 'in_array($colB, ["DDD", "JJJ"])',
        ]);
        $this->assertEquals([
            ['colA' => '789', 'colB' => 'CCC', 'colC' => 'fu ga yo3',],
            ['colA' => '234', 'colB' => 'DDD', 'colC' => 'fu ga yo4',],
            ['colA' => '678', 'colB' => 'HHH', 'colC' => 'fu ga yo8',],
            ['colA' => '999', 'colB' => 'JJJ', 'colC' => 'fu ga yo10',],
        ], eval("return $result;"), "Actual:\n$result");
    }

    function test_order_by()
    {
        $result = $this->runApp([
            'from'       => [__DIR__ . '/_files/log3.ltsv'],
            '--order-by' => 'colB,-colA',
        ]);
        $this->assertEquals([
            ['colA' => '11', 'colB' => 'a', 'colC' => '7',],
            ['colA' => '1', 'colB' => 'a', 'colC' => '3',],
            ['colA' => '4', 'colB' => 'b', 'colC' => '9',],
            ['colA' => '7', 'colB' => 'c', 'colC' => '1',],
            ['colA' => '8', 'colB' => 'd', 'colC' => '2',],
            ['colA' => '10', 'colB' => 'e', 'colC' => '1',],
            ['colA' => '7', 'colB' => 'e', 'colC' => '6',],
            ['colA' => '11', 'colB' => 'f', 'colC' => '5',],
            ['colA' => '5', 'colB' => 'f', 'colC' => '9',],
            ['colA' => '1', 'colB' => 'g', 'colC' => '2',],
            ['colA' => '5', 'colB' => 'h', 'colC' => '3',],
            ['colA' => '2', 'colB' => 'h', 'colC' => '2',],
            ['colA' => '10', 'colB' => 'i', 'colC' => '7',],
            ['colA' => '9', 'colB' => 'i', 'colC' => '7',],
            ['colA' => '6', 'colB' => 'j', 'colC' => '2',],
            ['colA' => '4', 'colB' => 'k', 'colC' => '5',],
            ['colA' => '3', 'colB' => 'k', 'colC' => '8',],
        ], eval("return $result;"), "Actual:\n$result");

        $result = $this->runApp([
            'from'       => [__DIR__ . '/_files/log3.ltsv'],
            '--select'   => 'aliasA:`$colA`',
            '--order-by' => 'colA',
        ]);
        $this->assertEquals([
            ['aliasA' => '1'],
            ['aliasA' => '1'],
            ['aliasA' => '2'],
            ['aliasA' => '3'],
            ['aliasA' => '4'],
            ['aliasA' => '4'],
            ['aliasA' => '5'],
            ['aliasA' => '5'],
            ['aliasA' => '6'],
            ['aliasA' => '7'],
            ['aliasA' => '7'],
            ['aliasA' => '8'],
            ['aliasA' => '9'],
            ['aliasA' => '10'],
            ['aliasA' => '10'],
            ['aliasA' => '11'],
            ['aliasA' => '11'],
        ], eval("return $result;"), "Actual:\n$result");
    }

    function test_order_by_vcolumn()
    {
        $result = $this->runApp([
            'from'       => [__DIR__ . '/_files/log3.ltsv'],
            '--select'   => 'colA,colC, colAC:`$colA + $colC`',
            '--order-by' => 'colAC',
        ]);
        $expected = [
            'php7' => [
                ['colA' => '1', 'colC' => '2', 'colAC' => 3,],
                ['colA' => '2', 'colC' => '2', 'colAC' => 4,],
                ['colA' => '1', 'colC' => '3', 'colAC' => 4,],
                ['colA' => '7', 'colC' => '1', 'colAC' => 8,],
                ['colA' => '6', 'colC' => '2', 'colAC' => 8,],
                ['colA' => '5', 'colC' => '3', 'colAC' => 8,],
                ['colA' => '4', 'colC' => '5', 'colAC' => 9,],
                ['colA' => '8', 'colC' => '2', 'colAC' => 10,],
                ['colA' => '3', 'colC' => '8', 'colAC' => 11,],
                ['colA' => '10', 'colC' => '1', 'colAC' => 11,],
                ['colA' => '7', 'colC' => '6', 'colAC' => 13,],
                ['colA' => '4', 'colC' => '9', 'colAC' => 13,],
                ['colA' => '5', 'colC' => '9', 'colAC' => 14,],
                ['colA' => '11', 'colC' => '5', 'colAC' => 16,],
                ['colA' => '9', 'colC' => '7', 'colAC' => 16,],
                ['colA' => '10', 'colC' => '7', 'colAC' => 17,],
                ['colA' => '11', 'colC' => '7', 'colAC' => 18,],
            ],
            'php8' => [
                ['colA' => '1', 'colC' => '2', 'colAC' => 3],
                ['colA' => '1', 'colC' => '3', 'colAC' => 4],
                ['colA' => '2', 'colC' => '2', 'colAC' => 4],
                ['colA' => '7', 'colC' => '1', 'colAC' => 8],
                ['colA' => '5', 'colC' => '3', 'colAC' => 8],
                ['colA' => '6', 'colC' => '2', 'colAC' => 8],
                ['colA' => '4', 'colC' => '5', 'colAC' => 9],
                ['colA' => '8', 'colC' => '2', 'colAC' => 10],
                ['colA' => '10', 'colC' => '1', 'colAC' => 11],
                ['colA' => '3', 'colC' => '8', 'colAC' => 11],
                ['colA' => '4', 'colC' => '9', 'colAC' => 13],
                ['colA' => '7', 'colC' => '6', 'colAC' => 13],
                ['colA' => '5', 'colC' => '9', 'colAC' => 14],
                ['colA' => '11', 'colC' => '5', 'colAC' => 16],
                ['colA' => '9', 'colC' => '7', 'colAC' => 16],
                ['colA' => '10', 'colC' => '7', 'colAC' => 17],
                ['colA' => '11', 'colC' => '7', 'colAC' => 18],
            ],
        ];
        $this->assertEquals($expected['php' . explode('.', PHP_VERSION)[0]], eval("return $result;"), "Actual:\n$result");
    }

    function test_order_by_expr()
    {
        $result = $this->runApp([
            'from'       => [__DIR__ . '/_files/log3.ltsv'],
            '--select'   => 'colA,colC',
            '--order-by' => '`$colA + $colC`',
        ]);
        $expected = [
            'php7' => [
                ['colA' => '1', 'colC' => '2',],
                ['colA' => '2', 'colC' => '2',],
                ['colA' => '1', 'colC' => '3',],
                ['colA' => '7', 'colC' => '1',],
                ['colA' => '6', 'colC' => '2',],
                ['colA' => '5', 'colC' => '3',],
                ['colA' => '4', 'colC' => '5',],
                ['colA' => '8', 'colC' => '2',],
                ['colA' => '3', 'colC' => '8',],
                ['colA' => '10', 'colC' => '1',],
                ['colA' => '7', 'colC' => '6',],
                ['colA' => '4', 'colC' => '9',],
                ['colA' => '5', 'colC' => '9',],
                ['colA' => '11', 'colC' => '5',],
                ['colA' => '9', 'colC' => '7',],
                ['colA' => '10', 'colC' => '7',],
                ['colA' => '11', 'colC' => '7',],
            ],
            'php8' => [
                ['colA' => '1', 'colC' => '2'],
                ['colA' => '1', 'colC' => '3'],
                ['colA' => '2', 'colC' => '2'],
                ['colA' => '7', 'colC' => '1'],
                ['colA' => '5', 'colC' => '3'],
                ['colA' => '6', 'colC' => '2'],
                ['colA' => '4', 'colC' => '5'],
                ['colA' => '8', 'colC' => '2'],
                ['colA' => '10', 'colC' => '1'],
                ['colA' => '3', 'colC' => '8'],
                ['colA' => '4', 'colC' => '9'],
                ['colA' => '7', 'colC' => '6'],
                ['colA' => '5', 'colC' => '9'],
                ['colA' => '11', 'colC' => '5'],
                ['colA' => '9', 'colC' => '7'],
                ['colA' => '10', 'colC' => '7'],
                ['colA' => '11', 'colC' => '7'],
            ],
        ];
        $this->assertEquals($expected['php' . explode('.', PHP_VERSION)[0]], eval("return $result;"), "Actual:\n$result");
    }

    function test_order_by_offsetAndLimit()
    {
        $result = $this->runApp([
            'from'       => [__DIR__ . '/_files/log3.ltsv'],
            '--order-by' => 'colB,+colA',
            '--offset'   => 3,
            '--limit'    => 3,
        ]);
        $this->assertEquals([
            ['colA' => '7', 'colB' => 'c', 'colC' => '1',],
            ['colA' => '8', 'colB' => 'd', 'colC' => '2',],
            ['colA' => '7', 'colB' => 'e', 'colC' => '6',],
        ], eval("return $result;"), "Actual:\n$result");
    }

    function test_order_by_below()
    {
        $result = $this->runApp([
            'from'       => [__DIR__ . '/_files/log3.ltsv'],
            '--select'   => 'colC',
            '--where'    => '$colC == 2',
            '--below'    => 3,
            '--order-by' => 'colC',
        ]);
        $this->assertEquals([
            ['colC' => '2',],
            ['colC' => '3',],
            ['colC' => '7',],
            ['colC' => '5',],
            ['colC' => '2',],
            ['colC' => '9',],
            ['colC' => '5',],
            ['colC' => '1',],
            ['colC' => '2',],
            ['colC' => '7',],
            ['colC' => '9',],
            ['colC' => '3',],
            ['colC' => '2',],
            ['colC' => '8',],
            ['colC' => '6',],
        ], eval("return $result;"), "Actual:\n$result");

        $result = $this->runApp([
            'from'       => [__DIR__ . '/_files/log3.ltsv'],
            '--select'   => 'colC',
            '--where'    => '$colC == 2',
            '--below'    => 3,
            '--limit'    => 14,
            '--order-by' => 'colC',
        ]);
        $this->assertEquals([
            ['colC' => '2',],
            ['colC' => '3',],
            ['colC' => '7',],
            ['colC' => '5',],
            ['colC' => '2',],
            ['colC' => '9',],
            ['colC' => '5',],
            ['colC' => '1',],
            ['colC' => '2',],
            ['colC' => '7',],
            ['colC' => '9',],
            ['colC' => '3',],
            ['colC' => '2',],
            ['colC' => '8',],
        ], eval("return $result;"), "Actual:\n$result");
    }

    function test_order_by_below_where()
    {
        $result = $this->runApp([
            'from'          => [__DIR__ . '/_files/log3.ltsv'],
            '--select'      => 'colC',
            '--where'       => '$colC == 2',
            '--below'       => 3,
            '--below-where' => '$colB == "k"',
            '--order-by'    => 'colC',
        ]);
        $this->assertEquals([
            ['colC' => '2',],
            ['colC' => '5',],
            ['colC' => '2',],
            ['colC' => '2',],
            ['colC' => '2',],
            ['colC' => '8',],
        ], eval("return $result;"), "Actual:\n$result");
    }

    function test_group_by()
    {
        $result = $this->runApp([
            'from'       => [__DIR__ . '/_files/log*.ltsv'],
            '--select'   => 'colX:`$colB[0]`',
            '--group-by' => 'colX, countA:`count($colA)`, minA:`min($colA)`',
        ]);
        $this->assertEquals([
            ['colX' => 'a', 'countA' => 3, 'minA' => '1'],
            ['colX' => 'b', 'countA' => 6, 'minA' => '4'],
            ['colX' => 'c', 'countA' => 2, 'minA' => '7'],
            ['colX' => 'A', 'countA' => 1, 'minA' => '123'],
            ['colX' => 'B', 'countA' => 2, 'minA' => '456'],
            ['colX' => 'C', 'countA' => 1, 'minA' => '789'],
            ['colX' => 'D', 'countA' => 1, 'minA' => '234'],
            ['colX' => 'E', 'countA' => 1, 'minA' => '567'],
            ['colX' => 'F', 'countA' => 1, 'minA' => '890'],
            ['colX' => 'G', 'countA' => 1, 'minA' => '345'],
            ['colX' => 'H', 'countA' => 1, 'minA' => '678'],
            ['colX' => 'I', 'countA' => 1, 'minA' => '901'],
            ['colX' => 'J', 'countA' => 1, 'minA' => '999'],
            ['colX' => 'd', 'countA' => 1, 'minA' => '8'],
            ['colX' => 'k', 'countA' => 2, 'minA' => '3'],
            ['colX' => 'g', 'countA' => 1, 'minA' => '1'],
            ['colX' => 'f', 'countA' => 2, 'minA' => '5'],
            ['colX' => 'e', 'countA' => 2, 'minA' => '7'],
            ['colX' => 'h', 'countA' => 2, 'minA' => '2'],
            ['colX' => 'i', 'countA' => 2, 'minA' => '9'],
            ['colX' => 'j', 'countA' => 1, 'minA' => '6'],
        ], eval("return $result;"), "Actual:\n$result");
    }

    function test_offsetAndLimit()
    {
        $result = $this->runApp([
            'from'     => [__DIR__ . '/_files/log1.ltsv'],
            '--offset' => 1,
            '--limit'  => 1,
        ]);
        $this->assertEquals([
            ['colA' => '456', 'colB' => 'bbb', 'colC' => 'ho ge ra2',],
        ], eval("return $result;"), "Actual:\n$result");
    }

    function test_require()
    {
        $result = $this->runApp([
            'from'      => [__DIR__ . '/_files/log1.ltsv'],
            '--require' => __DIR__ . '/_files/function.php',
            '--select'  => 'cws:`concat_ws(",", $colA, $colB)`, nscws:`\\ns\\concat_ws(",", $colA, $colB)`',
        ]);
        $this->assertEquals([
            ['cws' => '123,aaa', 'nscws' => 'aaa,123',],
            ['cws' => '456,bbb', 'nscws' => 'bbb,456',],
            ['cws' => '789,ccc', 'nscws' => 'ccc,789',],
        ], eval("return $result;"), "Actual:\n$result");
    }

    function test_nocomment()
    {
        $result = $this->runApp([
            'from'        => [__DIR__ . '/_files/log1.ltsv'],
            '--nocomment' => false,
            '--format'    => 'tsv',
        ]);
        $this->assertEquals("colA	colB	colC
123	aaa	ho ge ra1
456	bbb	ho ge ra2
789	ccc	ho ge ra3
", $result);

        $result = $this->runApp([
            'from'        => [__DIR__ . '/_files/log1.ltsv'],
            '--select'    => '~colB',
            '--nocomment' => false,
            '--format'    => 'tsv',
        ]);
        $this->assertEquals("colA	colC
123	ho ge ra1
456	ho ge ra2
789	ho ge ra3
", $result);
    }

    function test_noerror()
    {
        $result = $this->runApp([
            'from'      => [__DIR__ . '/_files/log1.ltsv'],
            '--noerror' => true,
            '--select'  => 'colA, dummy, colC',
        ]);
        $this->assertEquals([
            ['colA' => '123', 'dummy' => null, 'colC' => 'ho ge ra1',],
            ['colA' => '456', 'dummy' => null, 'colC' => 'ho ge ra2',],
            ['colA' => '789', 'dummy' => null, 'colC' => 'ho ge ra3',],
        ], eval("return $result;"), "Actual:\n$result");
    }

    function test_linenumber()
    {
        $fname = realpath(__DIR__ . '/_files/log3.ltsv');

        $result = $this->runApp([
            'from'        => [$fname],
            '--nocomment' => false,
            '--format'    => 'yaml',
        ]);
        $this->assertStringContainsString("# $fname:1", $result);
        $this->assertStringContainsString("# $fname:2", $result);
        $this->assertStringContainsString("# $fname:3", $result);

        $result = $this->runApp([
            'from'        => [$fname],
            '--nocomment' => false,
            '--format'    => 'yaml',
            '--offset'    => 1,
            '--limit'     => 2,
        ]);
        $this->assertStringContainsString("# $fname:2", $result);
        $this->assertStringContainsString("# $fname:3", $result);

        $result = $this->runApp([
            'from'          => [$fname],
            '--nocomment'   => false,
            '--format'      => 'yaml',
            '--select'      => 'colC',
            '--where'       => '$colC == 2',
            '--below'       => 3,
            '--below-where' => '$colB == "k"',
            '--order-by'    => 'colC',
        ]);
        $this->assertStringContainsString("# $fname:1", $result);
        $this->assertStringContainsString("# $fname:4", $result);
        $this->assertStringContainsString("# $fname:6", $result);
        $this->assertStringContainsString("# $fname:10", $result);
        $this->assertStringContainsString("# $fname:15", $result);
        $this->assertStringContainsString("# $fname:16", $result);
    }
}
