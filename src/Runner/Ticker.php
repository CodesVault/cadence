<?php

declare(strict_types=1);

namespace DaemonManager\Runner;

use DaemonManager\Config\Config;

class Ticker
{
    private int $iterations = 0;
    private int $startTime;
    private bool $shouldStop = false;

    /** @var callable|null */
    private $logger;

    public function __construct(
        private Config $config,
        ?callable $logger = null
    ) {
        $this->startTime = time();
        $this->logger = $logger;
    }

    public function run(): int
    {
        $this->registerSignalHandlers();
        $this->log('info', "Starting ticker for: {$this->config->getScript()}");
        $this->log('info', "Interval: {$this->config->getInterval()}s");

        while (!$this->shouldStop) {
            $this->tick();

            if ($this->shouldStop()) {
                break;
            }

            $this->sleep();
        }

        $this->log('info', "Ticker stopped after {$this->iterations} iterations");

        return 0;
    }

    private function tick(): void
    {
        $this->iterations++;
        $this->log('debug', "Tick #{$this->iterations}");

        $exitCode = $this->executeScript();

        if ($exitCode !== 0) {
            $this->log('warning', "Script exited with code: {$exitCode}");
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
            $this->log('debug', "Output: {$outputStr}");
        }

        return $exitCode;
    }

    private function shouldStop(): bool
    {
        if ($this->shouldStop) {
            $this->log('info', 'Received stop signal');
            return true;
        }

        if ($this->isMemoryExceeded()) {
            $this->log('info', 'Memory limit exceeded, stopping');
            return true;
        }

        if ($this->isRuntimeExceeded()) {
            $this->log('info', 'Runtime limit exceeded, stopping');
            return true;
        }

        if ($this->isIterationsExceeded()) {
            $this->log('info', 'Iteration limit exceeded, stopping');
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
            $this->log('info', 'Received SIGTERM');
            $this->shouldStop = true;
        });

        pcntl_signal(SIGINT, function () {
            $this->log('info', 'Received SIGINT');
            $this->shouldStop = true;
        });
    }

    private function log(string $level, string $message): void
    {
        $configLevel = $this->config->getLogLevel();

        if ($configLevel === 'quiet') {
            return;
        }

        $levels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
        $currentLevelValue = $levels[$configLevel] ?? 1;
        $messageLevelValue = $levels[$level] ?? 1;

        if ($messageLevelValue < $currentLevelValue) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $formatted = "[{$timestamp}] [{$level}] {$message}";

        if ($this->logger !== null) {
            ($this->logger)($level, $formatted);
        } else {
            echo $formatted . "\n";
        }
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
