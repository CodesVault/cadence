<?php

declare(strict_types=1);

namespace DaemonManager\Console;

use DaemonManager\Config\Config;
use DaemonManager\Config\EnvLoader;

class Application
{
    public const VERSION = '1.0.0';
    public const NAME = 'Daemon Manager';

    private ArgumentParser $parser;
    private EnvLoader $envLoader;
    private ?Config $config = null;

    public function __construct()
    {
        $this->parser = new ArgumentParser();
        $this->envLoader = new EnvLoader();
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

        if ($this->parser->getScript() === null) {
            $this->printError('Error: Script path is required');
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
        $this->printVersion();
        echo "\n";
        echo "Usage:\n";
        echo "  dm <script> [options]\n";
        echo "\n";
        echo "Arguments:\n";
        echo "  script                    Path to PHP script to execute\n";
        echo "\n";
        echo "Options:\n";
        echo "  -i, --interval=SECONDS    Sleep interval between runs [default: 60]\n";
        echo "  -m, --max-memory=SIZE     Max memory before restart [default: 128M]\n";
        echo "  -t, --max-runtime=SECONDS Max runtime before restart [default: 3600]\n";
        echo "  -n, --max-iterations=N    Max iterations before restart [default: unlimited]\n";
        echo "  -l, --lock-file=PATH      Lock file path [default: auto]\n";
        echo "      --log-file=PATH       Log file path [default: stdout]\n";
        echo "      --log-level=LEVEL     Log level: debug, info, warning, error, quiet [default: info]\n";
        echo "  -e, --env=PATH            Path to .env file [default: auto-detect]\n";
        echo "  -v, --verbose             Verbose output\n";
        echo "  -q, --quiet               Suppress output\n";
        echo "  -h, --help                Display this help\n";
        echo "  -V, --version             Display version\n";
        echo "\n";
        echo "Examples:\n";
        echo "  dm /var/www/html/wp-cron.php\n";
        echo "  dm /var/www/html/wp-cron.php --interval=10 --max-memory=256M\n";
        echo "  dm /var/www/html/artisan schedule:run --env=/var/www/.env\n";
        echo "\n";
        echo "Environment Variables (.env):\n";
        echo "  DM_INTERVAL, DM_MAX_MEMORY, DM_MAX_RUNTIME, DM_MAX_ITERATIONS\n";
        echo "  DM_LOCK_FILE, DM_LOG_FILE, DM_LOG_LEVEL\n";
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
