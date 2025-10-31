# TmdTopBundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/tmd-top-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/tmd-top-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/tmd-top-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/tmd-top-bundle)
[![License](https://img.shields.io/packagist/l/tourze/tmd-top-bundle.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/tmd-top-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/tmd-top-bundle)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/ci.yml?branch=master&style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo/master.svg?style=flat-square)](https://codecov.io/gh/tourze/php-monorepo)

一个用于监控系统状态的Symfony Bundle，包括网卡信息、监听服务、连接详情和运行程序的数据展示。

## 目录

- [功能](#功能)
- [跨平台支持](#跨平台支持)
- [系统要求](#系统要求)
- [安装](#安装)
- [配置](#配置)
- [使用](#使用)
  - [网卡信息](#网卡信息)
  - [监听服务](#监听服务)
  - [连接详情](#连接详情)
  - [运行程序](#运行程序)
- [高级用法](#高级用法)
  - [持续监控](#持续监控)
  - [输出过滤](#输出过滤)
- [故障排除](#故障排除)
  - [权限拒绝错误](#权限拒绝错误)
  - [缺少命令工具](#缺少命令工具)
  - [GeoIP数据库问题](#geoip数据库问题)
- [参考](#参考)
- [贡献](#贡献)
  - [运行测试](#运行测试)
- [更新日志](#更新日志)
- [许可证](#许可证)
- [致谢](#致谢)

## 功能

本Bundle提供以下命令来帮助你监控系统状态：

1. **网卡信息** - 显示网络接口信息，包括上传和下载速率
2. **监听服务** - 显示当前系统上运行的服务，包括PID、服务名、IP、端口等信息
3. **连接详情** - 显示客户端连接信息，包括客户端IP、端口、上传下载速率和地理位置
4. **运行程序** - 显示当前运行的程序，包括PID、名称、IP数、连接数等信息

## 跨平台支持

本Bundle支持以下操作系统：

- **Linux** - 使用 netstat, ss, ps 等命令
- **Windows** - 使用 netstat, tasklist, wmic 等命令
- **macOS** - 使用 netstat, lsof, ps 等命令

## 系统要求

- PHP >= 8.1
- Symfony >= 6.4
- 系统命令行工具（根据操作系统而定）：
  - Linux: netstat, ss, ps
  - Windows: netstat, tasklist, wmic
  - macOS: netstat, lsof, ps

## 安装

使用Composer安装:

```bash
composer require tourze/tmd-top-bundle
```

## 配置

在Symfony应用中，无需额外配置，Bundle会自动注册命令。

为了获得最佳性能，请确保以下几点：

1. **GeoIP 数据库**：本 Bundle 使用 GeoLite2 数据库进行 IP 地理定位。数据库通过 `leo108/geolite2-db` 包自动下载。

2. **系统权限**：某些命令可能需要提升的权限才能显示完整信息（特别是查看其他用户的连接时）。

3. **命令可用性**：确保你的系统上安装了相应的命令行工具：
    - Linux: `apt-get install net-tools` 或 `yum install net-tools`
    - Windows: 内置工具，无需额外安装
    - macOS: 内置工具，可能需要安装 `lsof`

## 使用

### 网卡信息

显示网络接口信息及实时上传/下载速率：

```bash
bin/console tmd-top:netcard
```

输出示例：
```text
接口: eth0
IP: 192.168.1.100
上传速率: 1.2 MB/s
下载速率: 3.5 MB/s
```

### 监听服务

显示当前系统上监听端口的服务：

```bash
bin/console tmd-top:services
```

输出示例：
```text
PID: 1234 | 服务: nginx | IP: 0.0.0.0 | 端口: 80
PID: 5678 | 服务: mysql | IP: 127.0.0.1 | 端口: 3306
```

### 连接详情

显示活动的网络连接及地理信息：

```bash
bin/console tmd-top:connections
```

输出示例：
```text
客户端: 123.45.67.89:12345 | 位置: 中国北京
上传: 100 KB | 下载: 500 KB
```

### 运行程序

显示有网络活动的程序：

```bash
bin/console tmd-top:processes
```

输出示例：
```text
PID: 1234 | 程序: chrome | 连接数: 15 | IP数: 5
PID: 5678 | 程序: firefox | 连接数: 8 | IP数: 3
```

## 高级用法

### 持续监控

您可以使用 `watch` 命令进行持续监控：

```bash
# 每 2 秒更新一次
watch -n 2 'php bin/console tmd-top:connections'
```

### 输出过滤

命令支持标准 Unix 管道进行过滤：

```bash
# 仅显示来自特定国家的连接
bin/console tmd-top:connections | grep "中国"

# 监控特定服务
bin/console tmd-top:services | grep "nginx"
```

## 故障排除

### 权限拒绝错误

某些命令需要提升的权限：

```bash
sudo php bin/console tmd-top:connections
```

### 缺少命令工具

如果遇到“命令未找到”错误：

- **Linux**：安装 `net-tools` 包
- **macOS**：通过 Homebrew 安装 `lsof`：`brew install lsof`
- **Windows**：以管理员身份运行

### GeoIP数据库问题

如果地理信息未显示：

1. 清除缓存：`php bin/console cache:clear`
2. 更新 GeoIP 数据库：`composer update leo108/geolite2-db`

## 参考

TmdTopBundle的功能灵感来源于 [tmd-top](https://github.com/CDWEN0526/tmd-top) Python工具。

## 贡献

欢迎贡献！请：

1. Fork 本仓库
2. 创建功能分支
3. 为新功能编写测试
4. 确保所有测试通过
5. 提交 Pull Request

### 运行测试

```bash
# 运行 PHPUnit 测试
vendor/bin/phpunit

# 运行 PHPStan 分析
vendor/bin/phpstan analyse src --level=5
```

## 更新日志

请查看 [CHANGELOG.md](CHANGELOG.md) 了解版本间的变更详情。

## 许可证

本软件包遵循 MIT 许可证。详情请查看 [LICENSE](LICENSE) 文件。

## 致谢

- 功能灵感来源于 [tmd-top](https://github.com/CDWEN0526/tmd-top) Python 工具
- GeoIP 数据由 MaxMind 的 GeoLite2 数据库提供
