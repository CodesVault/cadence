<?php

namespace Cadence\Console;

use Cadence\Config\Config;

trait Publisher
{
    public $version = '1.0.2';
    public $name = 'Cadence';

    public function printVersion(): void
    {
        echo $this->name . ' v' . $this->version . "\n";
    }

    public function printUsage(): void
    {
        echo "Usage:\n  cadence <script.php> [options]\n";
        echo "  cadence '<command>'  [options]\n\n";
        echo "Run 'cadence --help' for more information.\n";
    }

    public function getConfig(): ?Config
    {
        return $this->config;
    }

    public function printConfig(): void
    {
        echo "Default Configuration:\n\n";
        foreach ($this->config->toArray() as $key => $value) {
            if ($key === 'script') {
                continue;
            }
            $display = $value ?? 'null';
            echo "  {$key}: {$display}\n";
        }
        echo "\n";
    }

    private function printError(string $message): void
    {
        fwrite($this->stderr, $message . "\n");
    }

    private function printErrors(array $errors): void
    {
        echo "\n";
        foreach ($errors as $error) {
            $this->printError("Error: {$error}");
        }
        echo "\n";
    }

    private function printHelp(): void
    {
        $commandList = new CommandList();

        $this->printVersion();
        echo "\n";

        // Usage
        echo "Usage:\n";
        echo "  cadence <script.php> [options]\n";
        echo "  cadence '<command>'  [options]\n";
        echo "  cadence <subcommand> [name]\n\n";

        // Subcommands
        echo "Commands:\n";
        foreach ($commandList->subcommands() as $sub) {
            echo sprintf("  %-26s %s\n", $sub['name'], $sub['desc']);
        }
        echo "\n";

        // Arguments
        echo "Arguments:\n";
        foreach ($commandList->arguments() as $arg) {
            echo sprintf("  %-26s %s\n", "<{$arg['name']}>", $arg['desc']);
        }
        echo "\n";

        // Options
        echo "Options:\n";

        // Build option strings first to calculate max length
        $optionLines = [];
        foreach ($commandList->options() as $opt) {
            $short = $opt['short'] ? "-{$opt['short']}" : '  ';
            $long = "--{$opt['long']}";

            if ($opt['type'] !== 'bool') {
                $long .= ' <' . strtoupper($opt['type']) . '>';
            }

            $optionLines[] = [
                'short' => $short,
                'long'  => $long,
                'desc'  => $opt['desc'],
            ];
        }

        // Find max length for alignment
        $maxShortLen = max(array_map(fn ($o) => strlen($o['short']), $optionLines));
        $maxLongLen = max(array_map(fn ($o) => strlen($o['long']), $optionLines));

        foreach ($optionLines as $line) {
            $shortPadded = str_pad($line['short'], $maxShortLen);
            $longPadded = str_pad($line['long'], $maxLongLen);
            echo "  {$shortPadded}  {$longPadded}   {$line['desc']}\n";
        }
        echo "\n";

        // Examples
        echo "Examples:\n";
        foreach ($commandList->examples() as $example) {
            echo "  {$example}\n";
        }
        echo "\n";

        // Environment Variables
        echo "Environment Variables (.env):\n";
        echo '  ' . implode(', ', $commandList->envVariables()) . "\n";
    }

    private function printStatus(): int
    {
        $name = $this->parser->getSubcommandTarget();

        if ($name === null) {
            $this->printError('Error: process name is required. Usage: cadence status <name>');
            return 1;
        }

        $entry = $this->registry->get($name);

        if ($entry === null) {
            echo "No process found with name '{$name}'\n";
            return 1;
        }

        $alive = $this->registry->isPidAlive($entry['pid']);
        $status = $alive ? 'running' : 'stopped';

        echo "Name:       {$entry['name']}\n";
        echo "PID:        {$entry['pid']}\n";
        echo "Script:     {$entry['script']}\n";
        echo "Started:    {$entry['started_at']}\n";
        echo "Status:     {$status}\n";

        return 0;
    }

    private function printList(): int
    {
        $entries = $this->registry->all();

        if (empty($entries)) {
            echo "No registered daemons.\n";
            return 0;
        }

        echo sprintf("%-20s %-8s %-40s %s\n", 'NAME', 'PID', 'SCRIPT', 'STATUS');
        echo str_repeat('-', 80) . "\n";

        foreach ($entries as $entry) {
            $status = $entry['alive'] ? 'running' : 'stopped';
            echo sprintf(
                "%-20s %-8s %-40s %s\n",
                $entry['name'],
                $entry['pid'],
                $entry['script'],
                $status
            );
        }

        return 0;
    }
}
