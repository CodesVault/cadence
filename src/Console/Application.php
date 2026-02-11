<?php

declare(strict_types=1);

namespace Cadence\Console;

use Cadence\App\Logger;
use Cadence\App\Registry;
use Cadence\App\Ticker;
use Cadence\Config\Config;
use Cadence\Config\EnvLoader;

class Application
{
    public const VERSION = '1.0.2';
    public const NAME = 'Cadence';

    private ?Config $config = null;

    /** @var resource */
    private $stderr;

    public function __construct(
        private ArgumentParser $parser = new ArgumentParser(),
        private EnvLoader $envLoader = new EnvLoader(),
        mixed $stderr = null,
        private ?Registry $registry = null
    ) {
        $this->stderr = $stderr ?? STDERR;
        $this->registry = $registry ?? new Registry();
    }

    public function run(array $argv): int
    {
        $this->parser->parse($argv);

        if ($this->parser->hasErrors()) {
            $this->printErrors($this->parser->getErrors());
            return 1;
        }

        if ($this->parser->wantsHelp()) {
            $this->printHelp();
            return 0;
        }

        if ($this->parser->wantsVersion()) {
            $this->printVersion();
            return 0;
        }

        if ($this->parser->wantsConfigs()) {
            $this->config = $this->buildConfig();
            $this->printConfig();
            return 0;
        }

        // Handle subcommands: stop, status, list
        if ($this->parser->hasSubcommand()) {
            return $this->handleSubcommand();
        }

        if ($this->parser->getScript() === null) {
            $this->printError('Error: script path or command is required. Run \'cadence --help\' for usage.');
            $this->printUsage();
            return 1;
        }

        // Build config
        $this->config = $this->buildConfig();

        // Validate config
        $errors = $this->config->validate();
        if (!empty($errors) && is_array($errors)) {
            $this->printErrors($errors);
            return 1;
        }

        // Start the process
        return $this->startTicker();
    }

    private function buildConfig(): Config
    {
        $cliConfig = $this->parser->toConfigArray();
        $envConfig = $this->envLoader->load(
            $this->parser->getEnvPath(),
            $this->parser->getScript()
        );

        return Config::fromMerged([], $envConfig, $cliConfig);
    }

    private function startTicker(): int
    {
        $logger = new Logger(
            $this->config->getLogLevel(),
            $this->config->getLogFile(),
            null,
            $this->config->getLogTimezone(),
            $this->config->getDebugLogFile()
        );

        // Determine process name
        $name = $this->parser->getName()
            ?? $this->registry->deriveName($this->config->getScript());

        // Register process
        $pid = getmypid();
        $result = $this->registry->register($name, $pid, $this->config->getScript());

        if ($result !== true) {
            $this->printError($result);
            return 1;
        }

        $ticker = new Ticker($this->config, $logger);

        try {
            return $ticker->run();
        } finally {
            "Stopping... process";
            $this->registry->unregister($name);
        }
    }

    private function handleSubcommand(): int
    {
        $subcommand = $this->parser->getSubcommand();

        return match ($subcommand) {
            'stop'   => $this->handleStop(),
            'status' => $this->handleStatus(),
            'list'   => $this->handleList(),
            default  => 1,
        };
    }

    private function handleStop(): int
    {
        $name = $this->parser->getSubcommandTarget();

        if ($name === null) {
            $this->printError('Error: process name is required. Usage: cadence stop <name>');
            return 1;
        }

        $entry = $this->registry->get($name);

        if ($entry === null) {
            $this->printError("Error: no process found with name '{$name}'");
            return 1;
        }

        if (! $this->registry->isPidAlive($entry['pid'])) {
            echo "Process '{$name}' is not running (stale entry). Cleaning up.\n";
            $this->registry->unregister($name);
            return 0;
        }

        // Send SIGTERM (15)
        if (\function_exists('posix_kill')) {
            \posix_kill($entry['pid'], 15);
        } else {
            \exec("kill {$entry['pid']}");
        }

        echo "'{$name}' has been stopped (PID: {$entry['pid']})\n";

        return 0;
    }

    private function handleStatus(): int
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

    private function handleList(): int
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

    public function getConfig(): ?Config
    {
        return $this->config;
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

    private function printVersion(): void
    {
        echo self::NAME . ' v' . self::VERSION . "\n";
    }

    private function printUsage(): void
    {
        echo "Usage:\n  cadence <script.php> [options]\n";
        echo "  cadence '<command>'  [options]\n\n";
        echo "Run 'cadence --help' for more information.\n";
    }

    private function printConfig(): void
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
}
