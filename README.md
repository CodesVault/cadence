# Cadence

Cadence creates and manages PHP daemons with ease for running all heavy-lifting tasks in the background.

Running cron jobs and background tasks in PHP typically requires system-level configuration, custom process management, or framework-specific solutions. Cadence eliminates this complexity. It turns any existing code into a managed daemon without modifications. It handles the repetitive execution cycle, memory management, graceful restarts, and structured logging so you can focus on your application logic. Enables real-time debugging by streaming output directly to your terminal or log files. Works standalone for development or pairs with Supervisor for production deployments.

<br>

[![Total Downloads](https://img.shields.io/packagist/dt/codesvault/cadence.svg)](https://packagist.org/packages/codesvault/cadence)
[![Latest Version](https://img.shields.io/packagist/v/codesvault/cadence.svg)](https://packagist.org/packages/codesvault/cadence)

[![PHP Version](https://img.shields.io/packagist/php-v/codesvault/cadence.svg)](https://packagist.org/packages/codesvault/cadence)
[![Composer](https://img.shields.io/badge/composer-2.0%2B-blue.svg)](https://getcomposer.org/)
[![License](https://img.shields.io/packagist/l/codesvault/cadence.svg)](LICENSE)

<br>

## Why Cadence?

| Challenge | Without Cadence | With Cadence |
|-----------|----------------|--------------|
| **Running background tasks** | Write custom loop scripts, manage sleep cycles, handle exits manually | `cadence /path/to/script.php` — done |
| **Memory leaks** | Processes grow until they crash or get killed | Auto-restarts when memory limit is reached |
| **Process crashes** | Cron runs once and fails silently, no retry | Continuous execution with structured error logging |
| **Debugging** | Tail log files, add var_dump, redeploy | Real-time output in terminal or dedicated debug log file |
| **Configuration** | Edit crontab, modify system configs, restart services | `.env` file or CLI flags, no system changes needed |
| **Framework dependency** | Laravel Scheduler, Symfony Messenger — locked to one framework | Framework agnostic, works with any PHP script or CLI command |

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

*Click to play Demo Video* ⬇️

[![Cadence](https://pub-5fc605b04a4c467ca4a3fbed361deaf9.r2.dev/cadence-demo/cadence-demo-Cover.jpg)](https://pub-5fc605b04a4c467ca4a3fbed361deaf9.r2.dev/cadence-demo/cadence-demo.mp4)

<br>

## Installation

Install Cadence via Composer. It's recommanded to install Cadence as a global dependency. Run the following command in terminal:

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

## Usages for Development and Debugging

Developers can run Cadence on the foreground for development and real-time debugging purposes. This allows to run background-process and see real-time output and logs directly in your terminal or in log file.

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

### Commands

| Name | Description |
|------|-------------|
| `stop <name>` | Stop a running daemon by name |
| `status <name>` | Show status of a daemon by name |
| `list` | List all registered daemons |

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
| `-v` | `--version` | - | Display the version information |
| `-q` | `--quiet` | - | Suppress all output except errors |
| `-c` | `--config` | - | Show current configurations |
|  | `--name` | STRING | Name for the daemon process [default: auto-derived] |
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

<br>

## Contribution Guidelines

We welcome contributions to Cadence! Whether it's a bug fix, new feature, or documentation improvement, your help is appreciated. Please follow our [contributing guidelines](CONTRIBUTING.md) to get started.
