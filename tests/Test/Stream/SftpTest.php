<?php

namespace ryunosuke\test\Stream;

use phpseclib\Crypt\RSA;
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

    function test__parse_config()
    {
        $sftp = new Sftp();

        file_put_contents(sys_get_temp_dir() . '/ssh.config', <<<CONFIG
# actual match
Host hoge-fuga-piyo
  User         user1
  HostName     %h.domain1
  Port         221
  IdentityFile ~/.ssh/id_rsa1

# pattern match
Host hogera.*
  User         user2
  HostName     %h.domain2
  Port         222
  IdentityFile ~/.ssh/id_rsa2
CONFIG
        );

        $this->assertEmpty($sftp->_parse_config('notfound'));

        $this->assertEquals([
            'hoge-fuga-piyo' => [
                'user'         => 'user1',
                'hostname'     => '%h.domain1',
                'port'         => '221',
                'identityfile' => '~/.ssh/id_rsa1',
            ],
            'hogera.*'       => [
                'user'         => 'user2',
                'hostname'     => '%h.domain2',
                'port'         => '222',
                'identityfile' => '~/.ssh/id_rsa2',
            ],
        ], $sftp->_parse_config(sys_get_temp_dir() . '/ssh.config'));
    }

    function test__resolve_host()
    {
        $sftp = new Sftp();

        $sshconfig = [
            'hoge-fuga-piyo' => [
                'user'         => 'user1',
                'hostname'     => '%h.domain1',
                'port'         => '221',
                'identityfile' => __FILE__,
            ],
            'hogera.*'       => [
                'user'         => 'user2',
                'hostname'     => '%h.domain2',
                'port'         => '222',
                'identityfile' => __FILE__,
            ],
        ];

        $config = $sftp->_resolve_host(['host' => 'unknown'], $sshconfig);
        $this->assertEquals([
            'host' => 'unknown',
        ], $config);

        $config = $sftp->_resolve_host(['host' => 'hoge-fuga-piyo'], $sshconfig);
        $this->assertInstanceOf(RSA::class, array_unset($config, 'pass'));
        $this->assertEquals([
            'host' => 'hoge-fuga-piyo.domain1',
            'port' => '221',
            'user' => 'user1',
        ], $config);

        $config = $sftp->_resolve_host(['host' => 'hogera.sub'], $sshconfig);
        $this->assertInstanceOf(RSA::class, array_unset($config, 'pass'));
        $this->assertEquals([
            'host' => 'hogera.sub.domain2',
            'port' => '222',
            'user' => 'user2',
        ], $config);
    }
}
