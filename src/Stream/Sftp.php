<?php

namespace ryunosuke\ltsv\Stream;

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
    function _parse_path($path)
    {
        $geteuid = 'posix_geteuid';
        $getpwuid = 'posix_getpwuid';
        $parts = parse_uri($path, [
            'user' => function_exists($geteuid) && function_exists($getpwuid) ? ($getpwuid($geteuid())['name']) : '',
            'pass' => '',
            'port' => 22,
        ]);

        if (isset(static::$instances[$path])) {
            $this->sftp = static::$instances[$path];
        }
        else {
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

            static::$instances[$path] = $this->sftp;
        }

        return $parts['path'];
    }
}
