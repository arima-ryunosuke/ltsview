<?php

namespace ryunosuke\test\Type;

class PhpTest extends AbstractTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->decoder = function ($string) {
            return eval("return $string;");
        };
    }
}
