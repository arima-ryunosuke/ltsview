<?php

namespace ryunosuke\ltsv\Traits;

trait User
{
    public function getUser()
    {
        if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
            return posix_getpwuid(posix_geteuid()); // @codeCoverageIgnore
        }
        else {
            return [
                'name' => $_SERVER['USERNAME'] ?? get_current_user(),
                'uid'  => getmyuid(),
                'dir'  => $_SERVER['HOMEPATH'] ?? null,
            ];
        }
    }

    public function resolveHome($path)
    {
        // shoddy
        return str_replace('~', $this->getUser()['dir'], $path);
    }
}
