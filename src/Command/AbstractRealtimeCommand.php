<?php

declare(strict_types=1);

namespace Tourze\TmdTopBundle\Command;

use ChrisUllyott\FileSize;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * 实时监控命令的抽象基类
 *
 * 提供通用的实时更新功能、参数处理、输出分段管理等
 * 子类只需实现具体的数据显示逻辑
 */
abstract class AbstractRealtimeCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption(
                'interval',
                'i',
                InputOption::VALUE_OPTIONAL,
                '刷新间隔时间（秒），设置后将启用实时更新模式',
                null
            )
            ->addOption(
                'count',
                'c',
                InputOption::VALUE_OPTIONAL,
                '更新次数，设置后将在指定次数后退出，默认无限',
                null
            )
        ;

        // 允许子类继续配置
        $this->configureCommand();
    }

    /**
     * 子类可以重写此方法来添加额外的配置
     */
    protected function configureCommand(): void
    {
        // 默认空实现，子类可以重写
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $intervalValue = $input->getOption('interval');
        $countValue = $input->getOption('count');

        // 确保类型安全：将 mixed 转换为 string|null
        $interval = is_string($intervalValue) ? $intervalValue : null;
        $count = is_string($countValue) ? $countValue : null;

        $isRealtime = null !== $interval;

        if (!$this->validateOutput($output)) {
            return Command::FAILURE;
        }

        /** @var ConsoleOutputInterface $output */
        $sections = $this->createOutputSections($output);
        $signalResult = $this->setupSignalHandling($isRealtime);
        $isRealtime = $signalResult['isRealtime'];

        return $this->runRealtimeLoop($input, $sections, $interval, $count, $isRealtime);
    }

    /**
     * 验证输出接口
     */
    private function validateOutput(OutputInterface $output): bool
    {
        if (!$output instanceof ConsoleOutputInterface) {
            $io = new SymfonyStyle(new ArrayInput([]), $output);
            $io->error('This command requires an interactive terminal.');

            return false;
        }

        return true;
    }

    /**
     * 创建输出分段
     *
     * @return array{header: ConsoleSectionOutput, table: ConsoleSectionOutput}
     */
    private function createOutputSections(ConsoleOutputInterface $output): array
    {
        return [
            'header' => $output->section(),
            'table' => $output->section(),
        ];
    }

    /**
     * 设置信号处理（可由子类重写以自定义信号处理）
     *
     * @return array{isRealtime: bool}
     */
    protected function setupSignalHandling(bool $isRealtime): array
    {
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, function () use (&$isRealtime): void {
                $isRealtime = false;
            });
        }

        return ['isRealtime' => $isRealtime];
    }

    /**
     * 运行实时更新循环
     *
     * @param array{header: ConsoleSectionOutput, table: ConsoleSectionOutput} $sections
     */
    private function runRealtimeLoop(
        InputInterface $input,
        array $sections,
        ?string $interval,
        ?string $count,
        bool $isRealtime,
    ): int {
        $updateCount = 0;

        do {
            $this->displayTimestamp($input, $sections['header']);
            $this->displayData($input, $sections);

            if (!$isRealtime) {
                break;
            }

            ++$updateCount;
            if ($this->shouldExit($count, $updateCount)) {
                break;
            }

            $this->handleSignals();
            sleep((int) $interval);
        } while ($isRealtime);

        return Command::SUCCESS;
    }

    /**
     * 显示时间戳标题
     *
     * @param ConsoleSectionOutput $headerSection
     */
    private function displayTimestamp(InputInterface $input, $headerSection): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $headerSection->clear();
        $io = new SymfonyStyle($input, $headerSection);
        $io->title($this->getDisplayTitle() . ' - ' . $timestamp);
    }

    /**
     * 检查是否应该退出循环
     */
    private function shouldExit(?string $count, int $updateCount): bool
    {
        return null !== $count && $updateCount >= (int) $count;
    }

    /**
     * 处理信号
     */
    private function handleSignals(): void
    {
        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }
    }

    /**
     * 格式化字节数为人类可读的形式
     */
    protected function formatBytes(int $bytes): string
    {
        try {
            return (new FileSize($bytes))->asAuto();
        } catch (\Throwable $e) {
            // 如果 FileSize 库出现问题，提供基本的后备格式化
            if ($bytes < 1024) {
                return "{$bytes} B";
            }
            if ($bytes < 1048576) {
                return round($bytes / 1024, 2) . ' KB';
            }
            if ($bytes < 1073741824) {
                return round($bytes / 1048576, 2) . ' MB';
            }

            return round($bytes / 1073741824, 2) . ' GB';
        }
    }

    /**
     * 格式化百分比
     */
    protected function formatPercentage(float $value): string
    {
        return number_format($value, 1) . '%';
    }

    /**
     * 获取显示标题（子类必须实现）
     */
    abstract protected function getDisplayTitle(): string;

    /**
     * 显示具体数据（子类必须实现）
     *
     * @param InputInterface $input    输入接口
     * @param array{header: ConsoleSectionOutput, table: ConsoleSectionOutput} $sections 输出分段数组，包含 'header' 和 'table' 两个分段
     */
    abstract protected function displayData(InputInterface $input, array $sections): void;
}
