# TmdTopBundle

一个用于监控系统状态的Symfony Bundle，包括网卡信息、监听服务、连接详情和运行程序的数据展示。

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

## 安装

使用Composer安装:

```bash
composer require tourze/tmd-top-bundle
```

## 配置

在Symfony应用中，无需额外配置，Bundle会自动注册命令。

确保你的系统上安装了相应的命令行工具：
- Linux: netstat, ss, ps
- Windows: netstat, tasklist, wmic
- macOS: netstat, lsof, ps

## 使用

### 网卡信息

```bash
bin/console tmd:top:netcard
```

### 监听服务

```bash
bin/console tmd:top:services
```

### 连接详情

```bash
bin/console tmd:top:connections
```

### 运行程序

```bash
bin/console tmd:top:processes
```

## 参考

TmdTopBundle的功能灵感来源于 [tmd-top](https://github.com/CDWEN0526/tmd-top) Python工具。

## 许可证

本软件包遵循MIT许可证。详情请查看LICENSE文件。
