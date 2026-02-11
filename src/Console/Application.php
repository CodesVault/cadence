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
    use Publisher;

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
        $name = $this->parser->getName() ?? $this->registry->deriveName($this->config->getScript());

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
            $this->registry->unregister($name);
        }
    }

    private function handleSubcommand(): int
    {
        $subcommand = $this->parser->getSubcommand();

        return match ($subcommand) {
            'stop'   => $this->handleStop(),
            'status' => $this->printStatus(),
            'list'   => $this->printList(),
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
}
