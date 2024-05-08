<?php

namespace ryunosuke\ltsv\Stream;

use ryunosuke\ltsv\Traits\User;
use function ryunosuke\ltsv\uri_build;
use function ryunosuke\ltsv\uri_parse;

class Sftp extends \phpseclib3\Net\SFTP\Stream
{
    use User;

    private static $contexts = [];

    protected function parse_path($path)
    {
        $parts = $this->_parse_path($path);
        $origin = sprintf('%s://%s:%s@%s:%d', $parts['scheme'], $parts['user'], $parts['pass'], $parts['host'], $parts['port']);
        $this->context = self::$contexts[$origin] ??= stream_context_create([
            $parts['scheme'] => [
                'username' => $parts['user'] ?? null,
                'password' => $parts['key'] ?? $parts['pass'] ?? null,
                'privkey'  => $parts['key'] ?? null,
            ],
        ]);
        return parent::parse_path(uri_build($parts));
    }

    function _parse_path($path)
    {
        // original parsed
        $parts = uri_parse($path, [
            'user' => '',
            'pass' => '',
        ]);

        // combine ssh/config
        $parts = $this->_resolve_host($parts, $this->_parse_config());

        // user is empty -> Current user
        if ($parts['user'] === '') {
            $parts['user'] = $this->getUser()['name'];
        }

        if (!isset($parts['key'])) {
            // pass is empty -> Agent
            if ($parts['pass'] === '') {
                $parts['key'] = new class() extends \phpseclib3\System\SSH\Agent {
                    public function __construct($address = null)
                    {
                        if (!($_SERVER['unittest'] ?? false)) {
                            parent::__construct($address); // @codeCoverageIgnore
                        }
                    }

                    // @see phpseclib/Net/SFTP/Stream.php#216 stringify for sftp caches
                    public function __toString()
                    {
                        return \phpseclib3\System\SSH\Agent::class . '#' . spl_object_id($this);
                    }
                };
            }
            // pass is hyphen -> Keyboard-Interactive
            elseif ($parts['pass'] === '-') {
                // @see https://phpseclib.com/docs/auth helpme not work _keyboard_interactive_login
                $parts['key'] = [
                    ['Password' => $parts['pass']],
                ];
            }
        }

        return $parts;
    }

    function _parse_config($filename = null)
    {
        $filename = $filename ?? $this->getUser()['dir'] . '/.ssh/config';
        if (!file_exists($filename)) {
            return [];
        }

        $config = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $hosts = [];
        $current = '';
        foreach ($config as $entry) {
            $entry = trim($entry);
            if (($entry[0] ?? '') === '#') {
                continue;
            }
            [$key, $value] = explode(' ', $entry, 2) + [1 => null];
            $key = strtolower(trim($key));
            $value = trim($value);
            if ($key === 'host') {
                $hosts[$value] = [];
                $current = $value;
                continue;
            }
            $hosts[$current][$key] = $value;
        }
        return $hosts;
    }

    function _resolve_host($original, $sshconfig)
    {
        foreach ($sshconfig as $key => $config) {
            if (fnmatch($key, $original['host'])) {
                $overridden = [];
                if (isset($config['hostname'])) {
                    // shoddy
                    $overridden['host'] = strtr($config['hostname'], [
                        '%h' => $original['host'],
                        '%%' => '%',
                    ]);
                }
                if (isset($config['port'])) {
                    $overridden['port'] = $config['port'];
                }
                if (isset($config['user'])) {
                    $overridden['user'] = $config['user'];
                }
                if (isset($config['identityfile'])) {
                    $privatekey = file_get_contents($this->resolveHome($config['identityfile']));
                    $passphrase = strlen($original['pass'] ?? '') ? $original['pass'] : false;
                    $overridden['key'] = \phpseclib3\Crypt\PublicKeyLoader::load($privatekey, $passphrase);
                }
                return $overridden + $original;
            }
        }

        return $original;
    }
}
