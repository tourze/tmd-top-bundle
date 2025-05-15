# TmdTopBundle

A Symfony Bundle for monitoring system status, including network interface information, listening services, connection details, and running programs.

## Features

This Bundle provides the following commands to help you monitor system status:

1. **Network Cards** - Display network interface information, including upload and download rates
2. **Listening Services** - Display services currently running on the system, including PID, service name, IP, port, etc.
3. **Connection Details** - Display client connection information, including client IP, port, upload/download rates, and geographic location
4. **Running Programs** - Display currently running programs, including PID, name, IP count, connection count, etc.

## Cross-Platform Support

This Bundle supports the following operating systems:

- **Linux** - Uses netstat, ss, ps commands
- **Windows** - Uses netstat, tasklist, wmic commands
- **macOS** - Uses netstat, lsof, ps commands

## Installation

Install with Composer:

```bash
composer require tourze/tmd-top-bundle
```

## Configuration

In Symfony applications, no additional configuration is required. The Bundle will automatically register commands.

Make sure your system has the appropriate command-line tools installed:
- Linux: netstat, ss, ps
- Windows: netstat, tasklist, wmic
- macOS: netstat, lsof, ps

## Usage

### Network Cards

```bash
bin/console tmd:top:netcard
```

### Listening Services

```bash
bin/console tmd:top:services
```

### Connection Details

```bash
bin/console tmd:top:connections
```

### Running Programs

```bash
bin/console tmd:top:processes
```

## Reference

TmdTopBundle is inspired by the [tmd-top](https://github.com/CDWEN0526/tmd-top) Python tool.

## License

This package is licensed under the MIT License. See the LICENSE file for details.
