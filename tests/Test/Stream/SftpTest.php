<?php

namespace ryunosuke\test\Stream;

use ryunosuke\ltsv\Stream\Sftp;

class SftpTest extends AbstractTestCase
{
    protected $urlParts;

    protected function setUp()
    {
        if (!defined('SFTP')) {
            $this->markTestSkipped();
        }

        /** @noinspection PhpUndefinedConstantInspection */
        $this->urlParts = parse_uri(SFTP);
        Sftp::register('sftp');
    }

    function test__parse_path()
    {
        $parts = $this->urlParts;
        $parts['pass'] = '';
        $this->assertFalse(@fopen(build_uri($parts), 'r')); // for coverage

        $parts = $this->urlParts;
        $parts['pass'] = '-';
        $this->assertFalse(@fopen(build_uri($parts), 'r')); // for coverage

        $parts = $this->urlParts;
        $this->assertIsResource(fopen(build_uri($parts), 'r'));
        $this->assertIsResource(fopen(build_uri($parts), 'r')); //reuse
    }
}
