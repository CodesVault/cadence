<?php

declare(strict_types=1);

namespace DaemonManager\App;

use DaemonManager\App\Logger;
use DaemonManager\Config\Config;

class Ticker
{
    private int $iterations = 0;
    private int $startTime;
    private bool $shouldStop = false;
    private Logger $logger;

    public function __construct(
        private Config $config,
        ?Logger $logger = null
    ) {
        $this->startTime = time();
        $this->logger = $logger ?? new Logger($config->getLogLevel());
    }

    public function run(): int
    {
        $this->beforeProcessStart();

        while (! $this->shouldStop) {
            $this->tick();

            if ($this->shouldStop()) {
                break;
            }

            $this->sleep();
        }

        $this->afterProcessEnd();

        return 0;
    }

    private function beforeProcessStart(): void
    {
        $this->registerSignalHandlers();
        $this->log(Logger::LEVEL_INFO, "Starting Daemon Manager for: {$this->config->getScript()}");
        $this->log(Logger::LEVEL_INFO, "Interval: {$this->config->getInterval()}s");
    }

    private function afterProcessEnd(): void
    {
        $this->log(Logger::LEVEL_INFO, "Daemon Manager stopped after {$this->iterations} iterations");
    }

    private function tick(): void
    {
        $this->iterations++;
        $this->log(Logger::LEVEL_INFO, "Iteration: #{$this->iterations}");

        $exitCode = $this->executeScript();

        if ($exitCode !== 0) {
            $this->log(Logger::LEVEL_WARNING, "Script exited with code: {$exitCode}");
        }
    }

    private function executeScript(): int
    {
        $script = $this->config->getScript();
        $command = 'php ' . escapeshellarg($script) . ' 2>&1';

        $output = [];
        $exitCode = 0;

        exec($command, $output, $exitCode);

        if (!empty($output)) {
            $outputStr = implode("\n", $output);
            $this->log(Logger::LEVEL_DEBUG, "Output: {$outputStr}");
        }

        return $exitCode;
    }

    private function shouldStop(): bool
    {
        if ($this->shouldStop) {
            $this->log(Logger::LEVEL_INFO, 'Received stop signal');
            return true;
        }

        if ($this->isMemoryExceeded()) {
            $this->log(Logger::LEVEL_INFO, 'Memory limit exceeded, stopping');
            return true;
        }

        if ($this->isRuntimeExceeded()) {
            $this->log(Logger::LEVEL_INFO, 'Runtime limit exceeded, stopping');
            return true;
        }

        if ($this->isIterationsExceeded()) {
            $this->log(Logger::LEVEL_INFO, 'Iteration limit exceeded, stopping');
            return true;
        }

        return false;
    }

    private function isMemoryExceeded(): bool
    {
        $currentMemory = memory_get_usage(true);
        $maxMemory = $this->config->getMaxMemoryBytes();

        return $currentMemory >= $maxMemory;
    }

    private function isRuntimeExceeded(): bool
    {
        $maxRuntime = $this->config->getMaxRuntime();

        if ($maxRuntime === null) {
            return false;
        }

        $elapsed = time() - $this->startTime;

        return $elapsed >= $maxRuntime;
    }

    private function isIterationsExceeded(): bool
    {
        $maxIterations = $this->config->getMaxIterations();

        if ($maxIterations === null) {
            return false;
        }

        return $this->iterations >= $maxIterations;
    }

    private function sleep(): void
    {
        $interval = $this->config->getInterval();

        for ($i = 0; $i < $interval; $i++) {
            if ($this->shouldStop) {
                break;
            }
            sleep(1);
        }
    }

    private function registerSignalHandlers(): void
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, function () {
            $this->log(Logger::LEVEL_INFO, 'Received SIGTERM');
            $this->shouldStop = true;
        });

        pcntl_signal(SIGINT, function () {
            $this->log(Logger::LEVEL_INFO, 'Received SIGINT');
            $this->shouldStop = true;
        });
    }

    private function log(string $level, string $message): void
    {
        $this->logger->log($level, $message);
    }

    public function stop(): void
    {
        $this->shouldStop = true;
    }

    public function getIterations(): int
    {
        return $this->iterations;
    }

    public function getElapsedTime(): int
    {
        return time() - $this->startTime;
    }
}
