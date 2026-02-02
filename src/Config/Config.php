<?php

declare(strict_types=1);

namespace DaemonManager\Config;

class Config
{
    private string $script;
    private int $interval;
    private string $maxMemory;
    private ?int $maxRuntime;
    private ?int $maxCycles;
    private ?string $lockFile;
    private ?string $logFile;
    private string $logLevel;
    private ?string $logTimezone;

    private const DEFAULTS = [
        'interval'    => 60,
        'maxMemory'   => '128M',
        'maxRuntime'  => 3600,
        'maxCycles'   => null,
        'lockFile'    => null,
        'logFile'     => null,
        'logLevel'    => 'info',
        'logTimezone' => null,
    ];

    public function __construct(array $options = [])
    {
        $this->script = $options['script'] ?? '';
        $this->interval = (int) ($options['interval'] ?? self::DEFAULTS['interval']);
        $this->maxMemory = (string) ($options['maxMemory'] ?? self::DEFAULTS['maxMemory']);
        $this->maxRuntime = isset($options['maxRuntime']) ? (int) $options['maxRuntime'] : self::DEFAULTS['maxRuntime'];
        $this->maxCycles = isset($options['maxCycles']) ? (int) $options['maxCycles'] : self::DEFAULTS['maxCycles'];
        $this->lockFile = $options['lockFile'] ?? self::DEFAULTS['lockFile'];
        $this->logFile = $options['logFile'] ?? self::DEFAULTS['logFile'];
        $this->logLevel = (string) ($options['logLevel'] ?? self::DEFAULTS['logLevel']);
        $this->logTimezone = $options['logTimezone'] ?? self::DEFAULTS['logTimezone'];
    }

    public static function fromMerged(array $defaults, array $env, array $cli): self
    {
        $merged = array_merge(
            self::DEFAULTS,
            array_filter($defaults, fn ($v) => $v !== null),
            array_filter($env, fn ($v) => $v !== null),
            array_filter($cli, fn ($v) => $v !== null)
        );

        return new self($merged);
    }

    public function getScript(): string
    {
        return $this->script;
    }

    public function getInterval(): int
    {
        return $this->interval;
    }

    public function getMaxMemory(): string
    {
        return $this->maxMemory;
    }

    public function getMaxMemoryBytes(): int
    {
        return $this->parseMemoryString($this->maxMemory);
    }

    public function getMaxRuntime(): ?int
    {
        return $this->maxRuntime;
    }

    public function getMaxCycles(): ?int
    {
        return $this->maxCycles;
    }

    public function getLockFile(): ?string
    {
        if ($this->lockFile === null && $this->script !== '') {
            return sys_get_temp_dir() . '/dm-' . md5($this->script) . '.lock';
        }

        return $this->lockFile;
    }

    public function getLogFile(): ?string
    {
        return $this->logFile;
    }

    public function getLogLevel(): string
    {
        return $this->logLevel;
    }

    public function getLogTimezone(): ?string
    {
        return $this->logTimezone;
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->script)) {
            $errors[] = 'Script path is required';
        } elseif (!file_exists($this->script)) {
            $errors[] = "Script not found: {$this->script}";
        }

        if ($this->interval < 1) {
            $errors[] = 'Interval must be at least 1 second';
        }

        if ($this->maxRuntime !== null && $this->maxRuntime < 1) {
            $errors[] = 'Max runtime must be at least 1 second';
        }

        if ($this->maxCycles !== null && $this->maxCycles < 1) {
            $errors[] = 'Max cycles must be at least 1';
        }

        $validLogLevels = ['debug', 'info', 'warning', 'error', 'quiet'];
        if (!in_array($this->logLevel, $validLogLevels, true)) {
            $errors[] = 'Log level must be one of: ' . implode(', ', $validLogLevels);
        }

        return $errors;
    }

    public function isValid(): bool
    {
        return empty($this->validate());
    }

    private function parseMemoryString(string $memory): int
    {
        $memory = strtoupper(trim($memory));
        $value = (int) $memory;

        if (str_ends_with($memory, 'G')) {
            return $value * 1024 * 1024 * 1024;
        }

        if (str_ends_with($memory, 'M')) {
            return $value * 1024 * 1024;
        }

        if (str_ends_with($memory, 'K')) {
            return $value * 1024;
        }

        return $value;
    }

    public function toArray(): array
    {
        return [
            'script'      => $this->script,
            'interval'    => $this->interval,
            'maxMemory'   => $this->maxMemory,
            'maxRuntime'  => $this->maxRuntime,
            'maxCycles'   => $this->maxCycles,
            'lockFile'    => $this->getLockFile(),
            'logFile'     => $this->logFile,
            'logLevel'    => $this->logLevel,
            'logTimezone' => $this->logTimezone,
        ];
    }
}
