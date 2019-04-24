<?php

namespace ryunosuke\test\Stream;

use ryunosuke\ltsv\Traits\User;

class UserTest extends AbstractTestCase
{
    use User;

    function test_getUser()
    {
        $user = $this->getUser();
        if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
            $this->assertEquals(posix_getpwuid(posix_geteuid()), $user);
        }
        else {
            $this->assertEquals([
                'name' => $_SERVER['USERNAME'] ?? get_current_user(),
                'uid'  => getmyuid(),
                'dir'  => $_SERVER['HOMEPATH'] ?? null,
            ], $user);
        }
    }

    function test_resolveHome()
    {
        $this->assertEquals($this->getUser()['dir'] . '/.ssh/config', $this->resolveHome('~/.ssh/config'));
    }
}
