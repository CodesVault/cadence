# Cadence

Cadence is a PHP library designed to facilitate the management of background processes (daemons) in PHP applications. It provides a simple and efficient way to start, stop, and monitor daemons, making it easier to handle long-running tasks in your applications.

<br>

## Features

- Use with Supervisor for robust process management in production environments
- Run on foreground for development and debugging purposes
- Run Cron jobs as daemons without modifying existing code
- Start and stop daemons with ease
- Monitor process status
- Handle process logs
- Easy integration with existing PHP applications
- Framework agnostic, works with any PHP framework or plain PHP scripts

<br>

## Prerequisites

- PHP 8.1 or higher
- Composer 2.x for installation

<br>

## Installation

You can install Cadence via Composer. It's recommanded to install Cadence as a global dependency. Run the following command in your terminal:

```bash
composer global require codesvault/cadence
```

Make sure Composer's global bin directory is in your system's PATH:

`export PATH="$PATH:$HOME/.composer/vendor/bin"`

Then run the following command to verify the installation:

```bash
cadence --help
```

<br>

It's recommended to use Cadence as a global dependency for easier access to the `cadence` command from any location in your terminal. 
Alternatively, you can install Cadence as a project dependency:

```bash
composer require codesvault/cadence
```

<br>

## Usages for Developers

Developers can run Cadence on the foreground for development and debugging purposes. This allows to run background-process and see real-time output and logs directly in your terminal or in log file.

Cadence also can make php debugging real-time by running background-process in foreground mode.

### Basic Usage

```bash

cadence </absolute/path/to/index.php> [options]

# Or

cadence <CLI Command> [options]
```

### Examples

```bash

cadence /var/www/html/wp-cron.php

cadence /var/www/html/wp-cron.php --interval 10 --max-memory 256M

cadence '/var/www/html/artisan schedule:run' --env /var/www/.env

# with cli command
cadence 'curl -s https://example.com/webhook' -i 60
cadence 'echo hello' -n 5
```

<br>

### Options

| Short | Long | Type | Description |
|-------|------|------|-------------|
| `-i` | `--interval` | INT | Sleep interval between runs [default: 60] |
| `-m` | `--max-memory` | STRING | Maximum memory usage before restart (e.g., 128M, 1G) [default: 128M] |
| `-t` | `--max-runtime` | INT | Maximum runtime in seconds before restart [default: 3600] |
| `-n` | `--max-cycles` | INT | Maximum number of cycles before restart [default: unlimited] |
| `-lf` | `--log-file` | STRING | Path to log file [default: none] |
| `-ll` | `--log-level` | STRING | Logging level (debug, info, warning, error) [default: info] |
| `-e` | `--env` | STRING | Path to .env file for configuration [default: auto-detect] |
| `-V` | `--version` | - | Display the version information |
| `-v` | `--verbose` | - | Enable verbose output |
| `-q` | `--quiet` | - | Suppress all output except errors |
| `-c` | `--config` | - | Show current configurations |
| `-h` | `--help` | - | Display this help message |

<br>

### Environment Configuration

Cadence can automatically detect and load environment variables from a `.env` file located in the same directory as your script. For example, if your script is located at `/var/www/html/wp-cron.php`, Cadence will look for a `.env` file at `/var/www/html/.env`.

You can also specify a custom path to the `.env` file using the `--env` option.
The following environment variables can be used to configure Cadence:

| Variable | Description | Default |
|----------|-------------|---------|
| `CAD_INTERVAL` | Interval between Cycles in seconds | 60 |
| `CAD_MAX_MEMORY` | Maximum memory usage before restart (e.g., 128M, 1G) | 128M |
| `CAD_MAX_RUNTIME` | Maximum runtime in seconds before restart | 3600 |
| `CAD_MAX_CYCLES` | Maximum number of cycles before restart | unlimited |
| `CAD_LOG_FILE` | Path to log file | none |
| `CAD_LOG_LEVEL` | Logging level (debug, info, warning, error) | info |
| `CAD_DEBUG_LOG_FILE` | Path to debug log file | none |

<br>

## Production Usage with Supervisor

In production environments, it's recommended to use Cadence in conjunction with [Supervisor](https://supervisord.org/).

### Supervisor Installation & Configuration

Supervisor is a process control system that allows you to monitor and control long-running background processes. Install Supervisor on your Linux server:

```bash
sudo apt-get install supervisor
```

Create a Supervisor configuration file for Cadence process. For example, create a file named `cadence.conf` in `/etc/supervisor/conf.d/` with the following configuration:

```ini
[program:cadence]
command=cadence /var/www/html/wp-cron.php
directory=/var/www/html
autostart=true
autorestart=true
stderr_logfile=/var/log/cadence_wp_cron.err.log
stdout_logfile=/var/log/cadence_wp_cron.out.log
user=www-data
```

<br>

Now make a `.env` file in `/var/www/html/` directory for environment variables if needed. Cadence will automatically load the environment variables from this file.

```env
CAD_LOG_FILE=/var/www/html/cad.log
CAD_LOG_LEVEL=debug
CAD_DEBUG_LOG_FILE=/var/www/html/cad_debug.log
CAD_INTERVAL=5
CAD_MAX_CYCLES=6
CAD_MAX_MEMORY=128M
```

### Managing Cadence with Supervisor

You can manage the Cadence process using Supervisor commands:

```bash
# Start the Cadence process
sudo supervisorctl start cadence
# Stop the Cadence process
sudo supervisorctl stop cadence
# Restart the Cadence process
sudo supervisorctl restart cadence
# Check the status of the Cadence process
sudo supervisorctl status cadence
```
