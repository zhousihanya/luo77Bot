<?php

declare(strict_types=1);

namespace QQBot\Core;

/**
 * 配置管理器
 * 支持从 PHP 数组文件加载配置，支持点号访问
 */
class Config
{
    private array $config = [];

    public function __construct(string $configPath)
    {
        if (is_file($configPath)) {
            $this->config = require $configPath;
        }
    }

    /**
     * 获取配置项，支持点号分隔路径
     *
     * @param string $key 如 'bots.bot1.app_id' 或 'log.level'
     * @param mixed  $default 默认值
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * 获取所有机器人配置
     */
    public function getBotsConfig(): array
    {
        return $this->config['bots'] ?? [];
    }

    /**
     * 获取单个机器人配置
     */
    public function getBotConfig(string $botId): ?array
    {
        return $this->config['bots'][$botId] ?? null;
    }

    /**
     * 获取默认机器人 ID
     */
    public function getDefaultBotId(): string
    {
        return $this->config['default'] ?? array_key_first($this->config['bots'] ?? []);
    }
}
