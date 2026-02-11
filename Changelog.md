# Changelog

All notable changes to Cadence will be documented in this file.

## v1.0.2 - 2026-02-11

- **Named Process Registry** — PID-based process registry with JSON file storage at `~/.cadence/registry/`
- `cadence stop <name>` — Stop a running daemon by name (sends SIGTERM)
- `cadence status <name>` — Show process info (PID, script, started time, running status)
- `cadence list` — List all registered daemons with status
- `--name` option to assign a custom name to a daemon process
- Stale PID cleanup — automatically cleans up entries for dead processes
- Process auto-registration on start and auto-unregistration on stop

<br>

## v1.0.1 - 2026-02-04

- Invalid log file path handling fixed
- Verbose option removed

<br>

## v1.0.0 - 2026-02-04

- Core daemon management process loop
- Subprocess execution with real-time output streaming
- Configurable sleep interval between cycles (`--interval`, `-i`)
- Memory limit with auto-restart (`--max-memory`, `-m`)
- Maximum runtime with auto-restart (`--max-runtime`, `-t`)
- Maximum cycle count (`--max-cycles`, `-n`)
- Structured logging with configurable levels (`--log-level`, `-ll`)
- Log file output (`--log-file`, `-lf`)
- Debug log file support via `CAD_DEBUG_LOG_FILE` environment variable
- Local timezone support for log timestamps (`CAD_LOG_TIMEZONE`)
- `.env` file auto-detection and loading
- Custom `.env` path via `--env` option
- CLI command support (quoted strings) as alternative to PHP script paths
- `--config` flag to display current configuration
- `--quiet` mode to suppress output except errors
- `--help` and `--version` flags
- Supervisor integration support for production deployments
