# Cadence

Creates and manages PHP daemons with ease for running all heavy-lifting tasks in the background.

Running scripts as daemons is a common requirement for PHP applications. Traditional cron jobs work for simple tasks, but when you need reliable background processing with memory management, graceful restarts, and real-time monitoring — you need a daemon manager.

Cadence transforms any PHP script or CLI command into a managed, long-running background process.

---

## Why Cadence?

| Challenge | Without Cadence | With Cadence |
|-----------|----------------|--------------|
| **Running background tasks** | Write custom loop scripts, manage sleep cycles, handle exits manually | `cadence /path/to/script.php` — done |
| **Memory leaks** | Processes grow until they crash or get killed | Auto-restarts when memory limit is reached |
| **Process crashes** | Cron runs once and fails silently, no retry | Continuous execution with structured error logging |
| **Debugging** | Tail log files, add var_dump, redeploy | Real-time output in terminal or dedicated debug log file |
| **Configuration** | Edit crontab, modify system configs, restart services | `.env` file or CLI flags, no system changes needed |
| **Framework dependency** | Laravel Scheduler, Symfony Messenger — locked to one framework | Framework agnostic, works with any PHP script or CLI command |

---

## Quick Start

```bash
# Install globally
composer global require codesvault/cadence

# Run your first daemon
cadence /var/www/html/wp-cron.php

# With options
cadence /var/www/html/wp-cron.php --interval 30 --max-memory 256M
```

That's it! Your script is now running as a managed daemon.

---

## Installation

### Global Installation (Recommended)

```bash
composer global require codesvault/cadence
```

Make sure Composer's global bin directory is in your PATH:

```bash
export PATH="$PATH:$HOME/.composer/vendor/bin"
```

Verify installation:

```bash
# Command:
cadence --help

# result:

Cadence v1.0.2

Usage:
  cadence <script.php> [options]
  cadence '<command>'  [options]
  cadence <subcommand> [name]

Commands:
  stop <name>                Stop a running daemon by name
  status <name>              Show status of a daemon by name
  list                       List all registered daemons

Arguments:
  <script|command>           Path to PHP script (.php) OR CLI command (quoted string)

Options:
  -i   --interval <INT>        Sleep interval between runs [default: 60]
  -m   --max-memory <STRING>   Maximum memory usage before restart (e.g., 128M, 1G) [default: 128M]
  -t   --max-runtime <INT>     Maximum runtime in seconds before restart [default: 3600]
  -n   --max-cycles <INT>      Maximum number of cycles before restart [default: unlimited]
  -lf  --log-file <STRING>     Path to log file [default: none]
  -ll  --log-level <STRING>    Logging level (debug, info, warning, error) [default: info]
  -e   --env <STRING>          Path to .env file for configuration [default: auto-detect]
  -v   --version               Display the version information
  -q   --quiet                 Suppress all output except errors
  -c   --config                Show current configurations
       --name <STRING>         Name for the daemon process [default: auto-derived]
  -h   --help                  Display this help message

Examples:
  cadence /var/www/html/wp-cron.php
  cadence /var/www/html/wp-cron.php --interval 10 --max-memory 256M
  cadence /var/www/html/wp-cron.php --name my-cron
  cadence '/var/www/html/artisan schedule:run' --env /var/www/html/.env
  cadence 'curl -s https://example.com/webhook' -i 60
  cadence 'echo hello' --max-cycles 5

Environment Variables (.env):
  CAD_INTERVAL, CAD_MAX_MEMORY, CAD_MAX_RUNTIME, CAD_MAX_CYCLES, CAD_LOG_FILE, CAD_LOG_LEVEL, CAD_LOG_TIMEZONE, CAD_DEBUG_LOG_FILE
```

### Project-Level Installation

```bash
composer require codesvault/cadence
```

Run via vendor bin:

```bash
./vendor/bin/cadence -v
```

### Requirements

- PHP 8.1 or higher
- Composer 2.x

---

## Basic Usage

### Running a PHP Script

```bash
cadence /absolute/path/to/script.php
```

### Running a CLI Command

```bash
cadence 'php /var/www/html/artisan schedule:run'
cadence 'curl -s https://example.com/webhook'
```

> **Note:** CLI commands must be wrapped in single quotes.

### Viewing Configuration

```bash
cadence /path/to/script.php --config
```

---

## CLI Options

| Short | Long | Default | Description |
|-------|------|---------|-------------|
| `-i` | `--interval` | `60` | Sleep interval between runs (seconds) |
| `-m` | `--max-memory` | `128M` | Memory limit before restart |
| `-t` | `--max-runtime` | `3600` | Maximum runtime in seconds |
| `-n` | `--max-cycles` | unlimited | Maximum execution cycles |
| `-lf` | `--log-file` | stdout | Path to log file |
| `-ll` | `--log-level` | `info` | Log level (debug, info, warning, error, quiet) |
| `-e` | `--env` | auto-detect | Path to .env file |
| `-v` | `--version` | - | Display version |
| `-q` | `--quiet` | - | Suppress all output except errors |
| `-c` | `--config` | - | Display current configuration |
|  | `--name` | auto-derived | Name for the daemon process |
| `-h` | `--help` | - | Display help |

### Memory Units

Memory values support units:
- `K` or `k` — Kilobytes
- `M` or `m` — Megabytes
- `G` or `g` — Gigabytes

Examples: `128M`, `1G`, `512K`

---

## Environment Variables

Cadence auto-detects `.env` files in your script's root directory. You can also specify a custom path with `--env`.

| Variable | Default | Description |
|----------|---------|-------------|
| `CAD_INTERVAL` | `60` | Interval between cycles (seconds) |
| `CAD_MAX_MEMORY` | `128M` | Memory limit before restart |
| `CAD_MAX_RUNTIME` | `3600` | Maximum runtime (seconds) |
| `CAD_MAX_CYCLES` | unlimited | Maximum execution cycles |
| `CAD_LOG_FILE` | stdout | Path to log file |
| `CAD_LOG_LEVEL` | `info` | Logging level |
| `CAD_LOG_TIMEZONE` | system | Timezone for timestamps |
| `CAD_DEBUG_LOG_FILE` | none | Separate debug log file |

### Example .env File

```env
CAD_INTERVAL=30
CAD_MAX_MEMORY=256M
CAD_MAX_RUNTIME=7200
CAD_LOG_FILE=/var/log/cadence.log
CAD_LOG_LEVEL=info
CAD_DEBUG_LOG_FILE=/var/log/cadence_debug.log
```

---

## Process Registry

Cadence includes a built-in **process registry** that tracks all running daemons. Each daemon is registered with a name, PID, and script path in `~/.cadence/registry/`.

### Naming Daemons

Assign a custom name to a daemon using `--name`:

```bash
cadence /var/www/html/wp-cron.php --name my-proc
```

When `--name` is not provided, Cadence auto-derives the name from the script filename. For example, `/var/www/html/wp-cron.php` becomes `wp-cron`.

### Listing Daemons

View all registered daemons and their status:

```bash
cadence list
```

Output:
```
NAME                 PID      SCRIPT                                   STATUS
--------------------------------------------------------------------------------
wp-cron              3545    /var/www/html/web/wp-cron.php             running
worker               1238    /var/www/html/another-web/worker.php      stopped
proc                 5267    /var/www/html/jobs/job.php                running
```

### Checking Status

View detailed information about a specific daemon:

```bash
cadence status wp-cron
```

Output:
```
Name:       wp-cron
PID:        12345
Script:     /var/www/html/wp-cron.php
Started:    2026-02-11 12:30:45
Status:     running
```

### Stopping Daemons

Stop a running daemon by name:

```bash
cadence stop wp-cron
```

---

## Logging

### Log Levels

| Level | Priority | Description |
|-------|----------|-------------|
| `debug` | 0 | Detailed diagnostic information |
| `info` | 1 | General informational messages |
| `warning` | 2 | Potential issues |
| `error` | 3 | Error messages only |
| `quiet` | 4 | Suppress all output |

### Log Format

```
[2026-02-05 12:30:45] [info] Cadence started
[2026-02-05 12:30:45] [info] Executing: /var/www/html/wp-cron.php
[2026-02-05 12:30:46] [info] Memory: 12.5 MB | Load: 0.45
```

### File Logging

```bash
cadence /path/to/script.php --log-file /var/log/cadence.log
```

---

## Debug Logging

Route debug output to a separate file for real-time debugging without cluttering your main logs.

```env
CAD_DEBUG_LOG_FILE=/var/log/cadence_debug.log
```

Debug log format:
```
[2026-02-05 12:30:45]
Array
(
    [task] => process_queue
    [items] => 42
)
```

> **Note:** If the debug log directory doesn't exist, Cadence will log a warning and continue without debug file logging.

---

## Process Management

### Execution Cycle

1. Execute script/command
2. Log resource usage (memory, load average)
3. Sleep for interval duration
4. Check stop conditions
5. Repeat

### Stop Conditions

Cadence gracefully stops when:

- **Signal received** — SIGTERM or SIGINT (Ctrl+C)
- **Memory limit exceeded** — Configurable via `--max-memory`
- **Runtime limit reached** — Configurable via `--max-runtime`
- **Cycle limit reached** — Configurable via `--max-cycles`

---

## Production Deployment with Supervisor

For production environments, use Supervisor to manage Cadence processes.

### Install Supervisor

```bash
# Debian/Ubuntu
sudo apt-get install supervisor

# CentOS/RHEL
sudo yum install supervisor
```

### Create Configuration

Create `/etc/supervisor/conf.d/cadence.conf`:

```ini
[program:cadence-wp-cron]
command=cadence /var/www/html/wp-cron.php
directory=/var/www/html
autostart=true
autorestart=true
stderr_logfile=/var/log/cadence.err.log
stdout_logfile=/var/log/cadence.out.log
user=www-data
```

### Manage Process

```bash
# Reload configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start process
sudo supervisorctl start cadence-wp-cron

# Stop process
sudo supervisorctl stop cadence-wp-cron

# Check status
sudo supervisorctl status cadence-wp-cron

# Restart process
sudo supervisorctl restart cadence-wp-cron
```

---

## Configuration Priority

Cadence applies configuration in this order (highest to lowest priority):

1. **CLI Arguments** — `--interval 30`
2. **Environment Variables** — `CAD_INTERVAL=30`
3. **Default Values**

This means CLI arguments always override environment variables.

---

## Examples

### WordPress Cron

```bash
cadence /var/www/html/wp-cron.php --interval 60
```

### Laravel Scheduler

```bash
cadence '/var/www/html/artisan schedule:run' --interval 60 --env /var/www/html/.env
```

### Webhook Ping

```bash
cadence 'curl -s https://api.example.com/ping' --interval 30
```

### Queue Worker

```bash
cadence /var/www/html/worker.php --max-memory 512M --max-runtime 3600
```

### Development with Debug

```bash
cadence /path/to/script.php --interval 5 --log-level debug
```

### Named Daemons

```bash
# Start with a custom name
cadence /var/www/html/wp-cron.php --name wp-cron

# Check its status
cadence status wp-cron

# List all daemons
cadence list

# Stop it
cadence stop wp-cron
```

### Limited Cycles for Testing

```bash
cadence /path/to/script.php --max-cycles 5 --interval 2
```

---

## License

MIT License - [codesvault/cadence](https://github.com/codesvault/cadence)
