<?php

declare(strict_types=1);

namespace DaemonManager\App;

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
    private ?\DateTimeZone $logTimezone;

    /** @var resource|null */
    private $stdout;

    public function __construct(
        string $level = self::LEVEL_INFO,
        ?string $logFile = null,
        mixed $stdout = null,
        ?string $logTimezone = null
    ) {
        $this->level = $level;
        $this->logFile = $logFile;
        $this->stdout = $stdout ?? STDOUT;
        $this->logTimezone = $this->parseLogTimezone($logTimezone);
    }

    private function parseLogTimezone(?string $timezone): ?\DateTimeZone
    {
        if ($timezone === null) {
            return null;
        }

        try {
            return new \DateTimeZone($timezone);
        } catch (\Exception) {
            return null;
        }
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
        $dateTime = new \DateTime('now', $this->logTimezone);
        $timestamp = $dateTime->format('Y-m-d H:i:s');

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

    public function logOutput(string $stdout, string $stderr): void
    {
        // Parse stderr for errors and warnings (PHP prefixed)
        if ($stderr !== '') {
            $this->logStdErr($stderr);
        }

        // stdout may contain normal output mixed with display_errors output
        // Extract only non-error lines for debug
        if ($stdout !== '') {
            $debugOutput = $this->extractDebugOutput($stdout);
            if ($debugOutput !== '') {
                $this->debug('Output: ' . $debugOutput);
            }
        }
    }

    private function logStdErr(string $stderr): void
    {
        $lines = explode("\n", $stderr);
        $errorLines = [];
        $warningLines = [];
        $currentBlock = null;

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            if (preg_match('/^PHP (Fatal error|Parse error):/', $line)) {
                $currentBlock = 'error';
                $errorLines[] = trim($line);
            } elseif (preg_match('/^PHP (Warning|Notice|Deprecated):/', $line)) {
                $currentBlock = 'warning';
                $warningLines[] = trim($line);
            } elseif ($this->isStackTrace($line) && $currentBlock === 'error') {
                $errorLines[] = trim($line);
            }
        }

        if (! empty($errorLines)) {
            $this->error('Output: ' . implode(' | ', $errorLines));
        }

        if (! empty($warningLines)) {
            $this->warning('Output: ' . implode(' | ', $warningLines));
        }
    }

    private function extractDebugOutput(string $stdout): string
    {
        $lines = explode("\n", $stdout);
        $debugLines = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            // Skip error/warning lines
            if (preg_match('/^(Fatal error|Parse error|Warning|Notice|Deprecated):/', $line)) {
                continue;
            }

            // Skip stack trace lines
            if ($this->isStackTrace($line)) {
                continue;
            }

            $debugLines[] = trim($line);
        }

        return implode(' ', $debugLines);
    }

    private function isStackTrace(string $line): bool
    {
        return (bool) preg_match('/^\s*(Stack trace:|#\d+|thrown in)/', $line);
    }
}
