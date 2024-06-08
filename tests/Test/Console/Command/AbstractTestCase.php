<?php

namespace ryunosuke\test\Console\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

abstract class AbstractTestCase extends \ryunosuke\test\AbstractTestCase
{
    /**
     * @var Application
     */
    protected $app;

    protected $commandName;

    protected $defaultArgs = [];

    protected function setup(): void
    {
        parent::setUp();

        $this->app = new Application('Test');
        $this->app->setCatchExceptions(false);
        $this->app->setAutoExit(false);
    }

    /**
     * @closurable
     * @param array $inputArray
     * @return string
     */
    protected function runApp($inputArray, $useDefault = true)
    {
        $inputArray = ['command' => $this->commandName] + $inputArray;
        if ($useDefault) {
            $inputArray += $this->defaultArgs;
        }

        $input = new ArrayInput($inputArray);
        $output = new BufferedOutput();

        $this->app->run($input, $output);

        return $output->fetch();
    }
}
