<?php

namespace ryunosuke\test\Stream;

use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey;
use phpseclib3\System\SSH\Agent;
use ryunosuke\ltsv\Stream\Sftp;
use function ryunosuke\ltsv\array_unset;
use function ryunosuke\ltsv\file_set_contents;
use function ryunosuke\ltsv\uri_parse;

class SftpTest extends AbstractTestCase
{
    protected $urlParts;

    protected $identity_file;

    protected function setUp(): void
    {
        if (!defined('SFTP')) {
            $this->markTestSkipped();
        }

        /** @noinspection PhpUndefinedConstantInspection */
        $this->urlParts = uri_parse(SFTP);
        Sftp::register('sftp');

        $this->identity_file = sys_get_temp_dir() . '/dummy.key';
        file_put_contents($this->identity_file, RSA::createKey()->toString('OpenSsh'));
        $this->identity_file = realpath($this->identity_file);
    }

    function test_parse_path()
    {
        $sftp = new Sftp();

        $parse_path = $this->forcedCallize($sftp, 'parse_path');

        $path = $parse_path(SFTP);
        $this->assertEquals($this->urlParts['path'], $path);

        $options = stream_context_get_options($sftp->context);
        $this->assertEquals($this->urlParts['user'], $options['sftp']['username']);
        $this->assertEquals($this->urlParts['pass'], $options['sftp']['password']);
        $this->assertEquals(null, $options['sftp']['privkey']);
    }

    function test__parse_path()
    {
        $backup = $_SERVER;

        $_SERVER['unittest'] = true;
        $_SERVER['USERNAME'] = 'hoge';
        $_SERVER['HOMEPATH'] = sys_get_temp_dir();
        file_set_contents($_SERVER['HOMEPATH'] . '/.ssh/config', <<<CONFIG
Host hogera
  HostName     host
  User         user
  Port         2222
  IdentityFile $this->identity_file
CONFIG
        );

        $sftp = new Sftp();

        if (DIRECTORY_SEPARATOR === '\\') {
            $parts = $sftp->_parse_path('sftp://dummy');
            $this->assertEquals('hoge', $parts['user']);

            $parts = $sftp->_parse_path('sftp://hogera');
            $this->assertEquals('host', $parts['host']);
            $this->assertEquals('2222', $parts['port']);
            $this->assertEquals('user', $parts['user']);
            $this->assertInstanceOf(PrivateKey::class, $parts['key']);
        }

        $parts = $sftp->_parse_path('sftp://user:@host:22');
        $this->assertInstanceOf(Agent::class, $parts['key']);
        $this->assertStringStartsWith(Agent::class, (string) $parts['key']);

        $parts = $sftp->_parse_path('sftp://user:-@host:22');
        $this->assertIsArray($parts['key']);

        $_SERVER = $backup;
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
                'identityfile' => $this->identity_file,
            ],
            'hogera.*'       => [
                'user'         => 'user2',
                'hostname'     => '%h.domain2',
                'port'         => '222',
                'identityfile' => $this->identity_file,
            ],
        ];

        $config = $sftp->_resolve_host(['host' => 'unknown'], $sshconfig);
        $this->assertEquals([
            'host' => 'unknown',
        ], $config);

        $config = $sftp->_resolve_host(['host' => 'hoge-fuga-piyo'], $sshconfig);
        $this->assertInstanceOf(PrivateKey::class, array_unset($config, 'key'));
        $this->assertEquals([
            'host' => 'hoge-fuga-piyo.domain1',
            'port' => '221',
            'user' => 'user1',
        ], $config);

        $config = $sftp->_resolve_host(['host' => 'hogera.sub'], $sshconfig);
        $this->assertInstanceOf(PrivateKey::class, array_unset($config, 'key'));
        $this->assertEquals([
            'host' => 'hogera.sub.domain2',
            'port' => '222',
            'user' => 'user2',
        ], $config);
    }
}
