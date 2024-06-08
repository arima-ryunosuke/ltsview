<?php

namespace ryunosuke\ltsv\Console;

use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputInterface;

class DefaultableInput extends Input
{
    public function __construct(private InputInterface $input, private array $defaults)
    {
        parent::__construct();

        $this->definition = &$input->definition;
        $this->stream = &$input->stream;
        $this->options = &$input->options;
        $this->arguments = &$input->arguments;
        $this->interactive = &$input->interactive;
    }

    public function getArgument(string $name): mixed
    {
        return $this->defaults[$name] ?? parent::getArgument($name);
    }

    public function getArguments(): array
    {
        $result = parent::getArguments();
        return array_merge(array_intersect_key($this->defaults, $result), $result);
    }

    public function getOption(string $name): mixed
    {
        return $this->defaults[$name] ?? parent::getOption($name);
    }

    public function getOptions(): array
    {
        $result = parent::getOptions();
        return array_merge(array_intersect_key($this->defaults, $result), $result);
    }

    public function isEmptyOption(string $name): bool
    {
        $option = $this->getOption($name);
        if (is_object($option)) {
            return false;
        }
        if (is_array($option)) {
            return !count($option);
        }
        return !strlen($option);
    }

    // <editor-fold desc="delegation">
    // @codeCoverageIgnoreStart

    protected function parse()
    {
        return $this->input->parse(...func_get_args());
    }

    public function getFirstArgument(): ?string
    {
        return $this->input->getFirstArgument(...func_get_args());
    }

    public function hasParameterOption(array|string $values, bool $onlyParams = false): bool
    {
        return $this->input->hasParameterOption(...func_get_args());
    }

    public function getParameterOption(array|string $values, float|array|bool|int|string|null $default = false, bool $onlyParams = false)
    {
        return $this->input->getParameterOption(...func_get_args());
    }

    // @codeCoverageIgnoreEnd
    // </editor-fold>
}
