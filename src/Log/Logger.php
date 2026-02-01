<?php

declare(strict_types=1);

namespace DaemonManager\Log;

class Logger
{
    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_QUIET = 'quiet';

    private const LEVEL_PRIORITIES = [
        self::LEVEL_DEBUG   => 0,
        self::LEVEL_INFO    => 1,
        self::LEVEL_WARNING => 2,
        self::LEVEL_ERROR   => 3,
        self::LEVEL_QUIET   => 4,
    ];

    private string $level;
    private ?string $logFile;

    /** @var resource|null */
    private $stdout;

    public function __construct(
        string $level = self::LEVEL_INFO,
        ?string $logFile = null,
        mixed $stdout = null
    ) {
        $this->level = $level;
        $this->logFile = $logFile;
        $this->stdout = $stdout ?? STDOUT;
    }

    public function debug(string $message): void
    {
        $this->log(self::LEVEL_DEBUG, $message);
    }

    public function info(string $message): void
    {
        $this->log(self::LEVEL_INFO, $message);
    }

    public function warning(string $message): void
    {
        $this->log(self::LEVEL_WARNING, $message);
    }

    public function error(string $message): void
    {
        $this->log(self::LEVEL_ERROR, $message);
    }

    public function log(string $level, string $message): void
    {
        if (! $this->shouldLog($level)) {
            return;
        }

        $formatted = $this->format($level, $message);

        $this->write($formatted);
    }

    private function shouldLog(string $level): bool
    {
        if ($this->level === self::LEVEL_QUIET) {
            return false;
        }

        $currentPriority = self::LEVEL_PRIORITIES[$this->level] ?? self::LEVEL_PRIORITIES[self::LEVEL_INFO];
        $messagePriority = self::LEVEL_PRIORITIES[$level] ?? self::LEVEL_PRIORITIES[self::LEVEL_INFO];

        return $messagePriority >= $currentPriority;
    }

    private function format(string $level, string $message): string
    {
        $timestamp = date('Y-m-d H:i:s');

        return "[{$timestamp}] [{$level}] {$message}";
    }

    private function write(string $message): void
    {
        if ($this->logFile !== null) {
            file_put_contents($this->logFile, $message . "\n", FILE_APPEND);
        } else {
            fwrite($this->stdout, $message . "\n");
        }
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getLogFile(): ?string
    {
        return $this->logFile;
    }

    public static function getValidLevels(): array
    {
        return [
            self::LEVEL_DEBUG,
            self::LEVEL_INFO,
            self::LEVEL_WARNING,
            self::LEVEL_ERROR,
            self::LEVEL_QUIET,
        ];
    }
}
