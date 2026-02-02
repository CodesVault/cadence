<?php

declare(strict_types=1);

namespace DaemonManager\Console;

class CommandList
{
    public function arguments(): array
    {
        return [
            [
                'name' => 'script',
                'type' => 'string',
                'desc' => 'Path to PHP script to execute',
            ],
        ];
    }

    public function options(): array
    {
        return [
            [
                'short' => 'i',
                'long'  => 'interval',
                'type'  => 'int',
                'desc'  => 'Sleep interval between runs [default: 60]',
            ],
            [
                'short' => 'm',
                'long'  => 'max-memory',
                'type'  => 'string',
                'desc'  => 'Maximum memory usage before restart (e.g., 128M, 1G) [default: 128M]',
            ],
            [
                'short' => 't',
                'long'  => 'max-runtime',
                'type'  => 'int',
                'desc'  => 'Maximum runtime in seconds before restart [default: 3600]',
            ],
            [
                'short' => 'n',
                'long'  => 'max-cycles',
                'type'  => 'int',
                'desc'  => 'Maximum number of cycles before restart [default: unlimited]',
            ],
            [
                'short' => 'l',
                'long'  => 'lock-file',
                'type'  => 'string',
                'desc'  => 'Path to lock file to prevent multiple instances [default: none]',
            ],
            [
                'short' => 'lf',
                'long'  => 'log-file',
                'type'  => 'string',
                'desc'  => 'Path to log file [default: none]',
            ],
            [
                'short' => 'll',
                'long'  => 'log-level',
                'type'  => 'string',
                'desc'  => 'Logging level (debug, info, warning, error) [default: info]',
            ],
            [
                'short' => 'e',
                'long'  => 'env',
                'type'  => 'string',
                'desc'  => 'Path to .env file for configuration [default: auto-detect]',
            ],
            [
                'short' => 'V',
                'long'  => 'version',
                'type'  => 'bool',
                'desc'  => 'Display the version information',
            ],
            [
                'short' => 'v',
                'long'  => 'verbose',
                'type'  => 'bool',
                'desc'  => 'Enable verbose output',
            ],
            [
                'short' => 'q',
                'long'  => 'quiet',
                'type'  => 'bool',
                'desc'  => 'Suppress all output except errors',
            ],
            [
                'short' => 'c',
                'long'  => 'config',
                'type'  => 'bool',
                'desc'  => 'Show current configurations',
            ],
            [
                'short' => 'h',
                'long'  => 'help',
                'type'  => 'bool',
                'desc'  => 'Display this help message',
            ],
        ];
    }

    public function examples(): array
    {
        return [
            'dm /var/www/html/wp-cron.php',
            'dm /var/www/html/wp-cron.php --interval 10 --max-memory 256M',
            'dm /var/www/html/artisan schedule:run --env /var/www/.env',
        ];
    }

    public function envVariables(): array
    {
        return [
            'DM_INTERVAL',
            'DM_MAX_MEMORY',
            'DM_MAX_RUNTIME',
            'DM_MAX_CYCLES',
            'DM_LOCK_FILE',
            'DM_LOG_FILE',
            'DM_LOG_LEVEL',
            'DM_LOG_TIMEZONE',
        ];
    }
}
