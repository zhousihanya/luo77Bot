<?php

declare(strict_types=1);

namespace QQBot\Core;

/**
 * 日志系统
 * 支持按日期分割、多级别日志、控制台输出
 */
class Logger
{
    private string $logPath;
    private string $level;
    private bool $daily;
    private bool $console;

    private const LEVEL_MAP = [
        'debug'   => 0,
        'info'    => 1,
        'warning' => 2,
        'error'   => 3,
    ];

    public function __construct(array $config)
    {
        $this->logPath = rtrim($config['path'] ?? __DIR__ . '/../../logs', '/');
        $this->level   = strtolower($config['level'] ?? 'info');
        $this->daily   = $config['daily'] ?? true;
        $this->console = $config['console'] ?? true;

        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    private function log(string $level, string $message, array $context): void
    {
        if ((self::LEVEL_MAP[$level] ?? 0) < (self::LEVEL_MAP[$this->level] ?? 0)) {
            return;
        }

        $time     = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $line     = "[{$time}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        // 写入文件
        $filename = $this->daily
            ? "{$this->logPath}/qqbot-" . date('Y-m-d') . '.log'
            : "{$this->logPath}/qqbot.log";

        error_log($line, 3, $filename);

        // 控制台输出（仅在 CLI 模式且非 HTTP 请求时）
        if ($this->console && PHP_SAPI === 'cli') {
            echo $line;
        }
    }
}
