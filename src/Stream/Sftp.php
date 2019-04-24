<?php

namespace ryunosuke\ltsv\Stream;

use ryunosuke\ltsv\Traits\User;

/**
 * simplize \phpseclib\Net\SFTP\Stream
 *
 * - delete query and fragment
 * - delete context
 * - delete global host
 * - delete notification
 */
class Sftp extends \phpseclib\Net\SFTP\Stream
{
    use User;

    function _parse_path($path)
    {
        $parts = parse_uri($path, [
            'user' => $this->getUser()['name'],
            'pass' => '',
            'port' => 22,
        ]);

        $cachekey = $parts['user'] . '@' . $parts['host'] . ':' . $parts['port'];

        if (isset(static::$instances[$cachekey])) {
            $this->sftp = static::$instances[$cachekey];
        }
        else {
            $parts = $this->_resolve_host($parts, $this->_parse_config());

            $this->sftp = new \phpseclib\Net\SFTP($parts['host'], $parts['port']);
            $this->sftp->disableStatCache();

            // for stdin
            if ($parts['pass'] === '') {
                $ret = $this->sftp->login($parts['user'], new \phpseclib\System\SSH\Agent());
            }
            // for interactive
            elseif ($parts['pass'] === '-') {
                // @helpme not work _keyboard_interactive_login
                $ret = $this->sftp->login($parts['user'], ['Password' => null]);
            }
            // for password auth
            else {
                $ret = $this->sftp->login($parts['user'], $parts['pass']);
            }
            if (!$ret) {
                return false;
            }

            static::$instances[$cachekey] = $this->sftp;
        }

        return $parts['path'];
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
            list($key, $value) = explode(' ', $entry, 2) + [1 => null];
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
                    $key = new \phpseclib\Crypt\RSA();
                    $key->loadKey(file_get_contents($this->resolveHome($config['identityfile'])));
                    $overridden['pass'] = $key;
                }
                return $overridden + $original;
            }
        }

        return $original;
    }
}
