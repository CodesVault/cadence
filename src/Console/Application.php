<?php

declare(strict_types=1);

namespace DaemonManager\Console;

use DaemonManager\Config\Config;
use DaemonManager\Config\EnvLoader;

class Application
{
    public const VERSION = '1.0.0';
    public const NAME = 'Daemon Manager';

    private ?Config $config = null;

    public function __construct(
        private ArgumentParser $parser = new ArgumentParser(),
        private EnvLoader $envLoader = new EnvLoader()
    ) {
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

        if ($this->parser->getScript() === null) {
            $this->printError('Error: `script` path is required. Run \'dm --help\' for usage.');
            $this->printUsage();
            return 1;
        }

        // Build config
        $this->config = $this->buildConfig();

        // Validate config
        $errors = $this->config->validate();
        if (!empty($errors)) {
            $this->printErrors($errors);
            return 1;
        }

        // Show config in verbose mode
        if ($this->parser->isVerbose()) {
            $this->printConfig();
        }

        // Ready to start runner
        $this->printInfo("Starting daemon for: {$this->config->getScript()}");
        $this->printInfo("Interval: {$this->config->getInterval()}s");

        // TODO: Start the Runner (will be implemented in next phase)
        $this->startRunner();

        return 0;
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

    private function startRunner(): void
    {
        // Placeholder for Runner - will be implemented in next phase
        $this->printInfo('Runner would start here (not yet implemented)');
        $this->printInfo('Config: ' . json_encode($this->config->toArray(), JSON_PRETTY_PRINT));
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
        echo "  dm <script> [options]\n\n";

        // Arguments
        echo "Arguments:\n";
        foreach ($commandList->arguments() as $arg) {
            echo sprintf("  %-26s %s\n", "<{$arg['name']}>", $arg['desc']);
        }
        echo "\n";

        // Options
        echo "Options:\n";
        foreach ($commandList->options() as $opt) {
            $short = $opt['short'] ? "-{$opt['short']}, " : '    ';
            $long = "--{$opt['long']}";

            if ($opt['type'] !== 'bool') {
                $long .= '=' . strtoupper($opt['type'] === 'int' ? 'N' : $opt['long']);
            }

            echo sprintf("  %s%-22s %s\n", $short, $long, $opt['desc']);
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
        echo "Usage: dm <script> [options]\n";
        echo "Run 'dm --help' for more information.\n";
    }

    private function printConfig(): void
    {
        echo "Configuration:\n";
        foreach ($this->config->toArray() as $key => $value) {
            $display = $value ?? 'null';
            echo "  {$key}: {$display}\n";
        }
        echo "\n";
    }

    private function printError(string $message): void
    {
        fwrite(STDERR, $message . "\n");
    }

    private function printErrors(array $errors): void
    {
        foreach ($errors as $error) {
            $this->printError("Error: {$error}");
        }
    }

    private function printInfo(string $message): void
    {
        if (!$this->parser->isQuiet()) {
            echo $message . "\n";
        }
    }
}
