<?php

declare(strict_types=1);

namespace Cadence\Console;

class ArgumentParser
{
    private ?string $script = null;
    private bool $isCliCommand = false;
    private ?string $subcommand = null;
    private ?string $subcommandTarget = null;
    private array $options = [];
    private array $errors = [];
    private array $optionDefinitions = [];

    public function __construct(
        private CommandList $commandList = new CommandList()
    ) {
        $this->buildOptionDefinitions();
    }

    private function buildOptionDefinitions(): void
    {
        foreach ($this->commandList->options() as $opt) {
            $this->optionDefinitions[$opt['long']] = [
                'short' => $opt['short'],
                'type'  => $opt['type'],
            ];
        }
    }

    public function parse(array $argv): self
    {
        array_shift($argv);

        while (count($argv) > 0) {
            $arg = array_shift($argv);

            // check if it's a option
            if (str_starts_with($arg, '--')) {
                $this->parseLongOption($arg, $argv);
                continue;
            } elseif (str_starts_with($arg, '-') && strlen($arg) > 1) {
                $this->parseShortOption($arg, $argv);
                continue;
            }

            // check if it's a subcommand
            if (in_array($arg, CommandList::SUBCOMMANDS, true)) {
                $this->subcommand = $arg;

                // stop/status require a target name
                if ($arg !== 'list') {
                    $this->subcommandTarget = array_shift($argv);
                }
            } else {
                $this->script = $arg;
                $this->isCliCommand = ! $this->looksLikePhpScript($arg);
            }
        }

        return $this;
    }

    private function parseLongOption(string $arg, array &$argv): void
    {
        $name = substr($arg, 2);
        $value = null;

        if (!isset($this->optionDefinitions[$name])) {
            $this->errors[] = "Unknown option: --{$name}";
            return;
        }

        $option = $this->optionDefinitions[$name];

        if ($option['type'] === 'bool') {
            $this->options[$this->toCamelCase($name)] = true;
            return;
        }

        if ($value === null && count($argv) > 0 && !str_starts_with($argv[0], '-')) {
            $value = array_shift($argv);
        }

        if ($value === null) {
            $this->errors[] = "Option --{$name} requires a value";
            return;
        }

        $this->options[$this->toCamelCase($name)] = $this->castValue($option['type'], $value);
    }

    private function parseShortOption(string $arg, array &$argv): void
    {
        $short = substr($arg, 1);

        // Find matching long option
        $longName = null;
        foreach ($this->optionDefinitions as $name => $option) {
            if ($option['short'] === $short) {
                $longName = $name;
                break;
            }
        }

        if ($longName === null) {
            $this->errors[] = "Unknown option: -{$short}";
            return;
        }

        $option = $this->optionDefinitions[$longName];

        if ($option['type'] === 'bool') {
            $this->options[$this->toCamelCase($longName)] = true;
        } else {
            if (count($argv) === 0 || str_starts_with($argv[0], '-')) {
                $this->errors[] = "Option -{$short} requires a value";
                return;
            }

            $value = array_shift($argv);
            $this->options[$this->toCamelCase($longName)] = $this->castValue($option['type'], $value);
        }
    }

    private function castValue(string $type, string $value): mixed
    {
        return match ($type) {
            'int'   => (int) $value,
            'bool'  => true,
            default => $value,
        };
    }

    private function toCamelCase(string $name): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $name))));
    }

    private function looksLikePhpScript(string $arg): bool
    {
        return str_ends_with($arg, '.php') || file_exists($arg);
    }

    public function getScript(): ?string
    {
        return $this->script;
    }

    public function isCliCommand(): bool
    {
        return $this->isCliCommand;
    }

    public function wantsHelp(): bool
    {
        return $this->options['help'] ?? false;
    }

    public function wantsVersion(): bool
    {
        return $this->options['version'] ?? false;
    }

    public function wantsConfigs(): bool
    {
        return $this->options['config'] ?? false;
    }

    public function isQuiet(): bool
    {
        return $this->options['quiet'] ?? false;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function toConfigArray(): array
    {
        $config = [];

        if ($this->script !== null) {
            $config['script'] = $this->script;
            $config['isCliCommand'] = $this->isCliCommand;
        }

        $mapping = [
            'interval'   => 'interval',
            'maxMemory'  => 'maxMemory',
            'maxRuntime' => 'maxRuntime',
            'maxCycles'  => 'maxCycles',
            'logFile'    => 'logFile',
            'logLevel'   => 'logLevel',
        ];

        foreach ($mapping as $option => $configKey) {
            if (isset($this->options[$option])) {
                $config[$configKey] = $this->options[$option];
            }
        }

        // Handle quiet flag
        if ($this->isQuiet()) {
            $config['logLevel'] = 'quiet';
        }

        return $config;
    }

    public function getEnvPath(): ?string
    {
        return $this->options['env'] ?? null;
    }

    public function hasSubcommand(): bool
    {
        return $this->subcommand !== null;
    }

    public function getSubcommand(): ?string
    {
        return $this->subcommand;
    }

    public function getSubcommandTarget(): ?string
    {
        return $this->subcommandTarget;
    }

    public function getName(): ?string
    {
        return $this->options['name'] ?? null;
    }
}
