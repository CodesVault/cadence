<?php

declare(strict_types=1);

namespace Cadence\Console;

class CommandList
{
    public const SUBCOMMANDS = ['stop', 'status', 'list'];

    public function arguments(): array
    {
        return [
            [
                'name' => 'script|command',
                'type' => 'string',
                'desc' => 'Path to PHP script (.php) OR CLI command (quoted string)',
            ],
        ];
    }

    public function subcommands(): array
    {
        return [
            [
                'name' => 'stop <name>',
                'desc' => 'Stop a running daemon by name',
            ],
            [
                'name' => 'status <name>',
                'desc' => 'Show status of a daemon by name',
            ],
            [
                'name' => 'list',
                'desc' => 'List all registered daemons',
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
                'short' => 'v',
                'long'  => 'version',
                'type'  => 'bool',
                'desc'  => 'Display the version information',
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
                'short' => '',
                'long'  => 'name',
                'type'  => 'string',
                'desc'  => 'Name for the daemon process [default: auto-derived]',
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
            'cadence /var/www/html/wp-cron.php',
            'cadence /var/www/html/wp-cron.php --interval 10 --max-memory 256M',
            'cadence /var/www/html/wp-cron.php --name my-cron',
            "cadence '/var/www/html/artisan schedule:run' --env /var/www/html/.env",
            "cadence 'curl -s https://example.com/webhook' -i 60",
            "cadence 'echo hello' --max-cycles 5",
        ];
    }

    public function envVariables(): array
    {
        return [
            'CAD_INTERVAL',
            'CAD_MAX_MEMORY',
            'CAD_MAX_RUNTIME',
            'CAD_MAX_CYCLES',
            'CAD_LOG_FILE',
            'CAD_LOG_LEVEL',
            'CAD_LOG_TIMEZONE',
            'CAD_DEBUG_LOG_FILE',
        ];
    }
}
