<?php

use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\Formats\Keys\OpenSSH;
use phpseclib3\Math\BigInteger;

require __DIR__ . '/../vendor/autoload.php';

class Dummy extends OpenSSH
{
    public static function load($key, $password = '')
    {
        return [
            'isPublicKey'    => false,
            'modulus'        => new BigInteger(),
            'publicExponent' => '',
            'comment'        => '',
        ];
    }
}

RSA::addFileFormat(Dummy::class);
