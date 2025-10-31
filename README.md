# TmdTopBundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/tmd-top-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/tmd-top-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/tmd-top-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/tmd-top-bundle)
[![License](https://img.shields.io/packagist/l/tourze/tmd-top-bundle.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/tmd-top-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/tmd-top-bundle)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?branch=master&style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo/master.svg?style=flat-square)](https://codecov.io/gh/tourze/php-monorepo)

A Symfony Bundle for monitoring system status, including network interface information, listening services, connection details, and running programs.

## Table of Contents

- [Features](#features)
- [Cross-Platform Support](#cross-platform-support)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Network Cards](#network-cards)
  - [Listening Services](#listening-services)
  - [Connection Details](#connection-details)
  - [Running Programs](#running-programs)
- [Advanced Usage](#advanced-usage)
  - [Continuous Monitoring](#continuous-monitoring)
  - [Output Filtering](#output-filtering)
- [Troubleshooting](#troubleshooting)
  - [Permission Denied Errors](#permission-denied-errors)
  - [Missing Command Tools](#missing-command-tools)
  - [GeoIP Database Issues](#geoip-database-issues)
- [Reference](#reference)
- [Contributing](#contributing)
  - [Running Tests](#running-tests)
- [Changelog](#changelog)
- [License](#license)
- [Credits](#credits)

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

## Requirements

- PHP >= 8.1
- Symfony >= 6.4
- System command-line tools (varies by OS):
  - Linux: netstat, ss, ps
  - Windows: netstat, tasklist, wmic
  - macOS: netstat, lsof, ps

## Installation

Install with Composer:

```bash
composer require tourze/tmd-top-bundle
```

## Configuration

In Symfony applications, no additional configuration is required. The Bundle will automatically register commands.

For optimal performance, ensure the following:

1. **GeoIP Database**: The bundle uses GeoLite2 database for IP geolocation. The database is automatically downloaded via `leo108/geolite2-db` package.

2. **System Permissions**: Some commands may require elevated privileges to display complete information (especially for viewing connections from other users).

3. **Command Availability**: Make sure your system has the appropriate command-line tools installed:
    - Linux: `apt-get install net-tools` or `yum install net-tools`
    - Windows: Built-in tools, no additional installation needed
    - macOS: Built-in tools, may need to install `lsof`

## Usage

### Network Cards

Display network interface information with real-time upload/download rates:

```bash
bin/console tmd-top:netcard
```

Example output:
```text
Interface: eth0
IP: 192.168.1.100
Upload Rate: 1.2 MB/s
Download Rate: 3.5 MB/s
```

### Listening Services

Display services currently listening on system ports:

```bash
bin/console tmd-top:services
```

Example output:
```text
PID: 1234 | Service: nginx | IP: 0.0.0.0 | Port: 80
PID: 5678 | Service: mysql | IP: 127.0.0.1 | Port: 3306
```

### Connection Details

Display active network connections with geographic information:

```bash
bin/console tmd-top:connections
```

Example output:
```text
Client: 123.45.67.89:12345 | Location: Beijing, China
Upload: 100 KB | Download: 500 KB
```

### Running Programs

Display programs with network activity:

```bash
bin/console tmd-top:processes
```

Example output:
```text
PID: 1234 | Program: chrome | Connections: 15 | IPs: 5
PID: 5678 | Program: firefox | Connections: 8 | IPs: 3
```

## Advanced Usage

### Continuous Monitoring

You can use these commands with `watch` for continuous monitoring:

```bash
# Update every 2 seconds
watch -n 2 'php bin/console tmd-top:connections'
```

### Output Filtering

Commands support standard Unix pipes for filtering:

```bash
# Show only connections from specific country
bin/console tmd-top:connections | grep "China"

# Monitor specific service
bin/console tmd-top:services | grep "nginx"
```

## Troubleshooting

### Permission Denied Errors

Some commands require elevated privileges:

```bash
sudo php bin/console tmd-top:connections
```

### Missing Command Tools

If you encounter "command not found" errors:

- **Linux**: Install `net-tools` package
- **macOS**: Install `lsof` via Homebrew: `brew install lsof`
- **Windows**: Run as Administrator

### GeoIP Database Issues

If geographic information is not showing:

1. Clear cache: `php bin/console cache:clear`
2. Update GeoIP database: `composer update leo108/geolite2-db`

## Reference

TmdTopBundle is inspired by the [tmd-top](https://github.com/CDWEN0526/tmd-top) Python tool.

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Write tests for new features
4. Ensure all tests pass
5. Submit a pull request

### Running Tests

```bash
# Run PHPUnit tests
vendor/bin/phpunit

# Run PHPStan analysis
vendor/bin/phpstan analyse src --level=5
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for details on changes between versions.

## License

This package is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Credits

- Inspired by [tmd-top](https://github.com/CDWEN0526/tmd-top) Python tool
- GeoIP data provided by MaxMind's GeoLite2 database
