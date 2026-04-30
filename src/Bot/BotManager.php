<?php

declare(strict_types=1);

namespace QQBot\Bot;

use QQBot\Core\Config;
use QQBot\Core\EventDispatcher;
use QQBot\Core\Logger;

/**
 * 多机器人管理器
 * 负责管理多个机器人实例的创建、获取和生命周期
 */
class BotManager
{
    /** @var array<string, Bot> */
    private array $bots = [];

    public function __construct(
        private Config $config,
        private Logger $logger,
        private EventDispatcher $dispatcher,
    ) {
        $this->initializeBots();
    }

    /**
     * 根据配置初始化所有机器人实例
     */
    private function initializeBots(): void
    {
        $botsConfig = $this->config->getBotsConfig();

        foreach ($botsConfig as $botId => $botConfig) {
            if (empty($botConfig['app_id']) || empty($botConfig['client_secret'])) {
                $this->logger->warning('Bot config incomplete, skipping', ['bot_id' => $botId]);
                continue;
            }

            $this->bots[$botId] = new Bot($botId, $botConfig, $this->logger, $this->dispatcher);
        }

        $this->logger->info('Bot manager initialized', ['count' => count($this->bots)]);
    }

    /**
     * 获取指定机器人实例
     */
    public function getBot(string $botId): ?Bot
    {
        return $this->bots[$botId] ?? null;
    }

    /**
     * 获取默认机器人实例
     */
    public function getDefaultBot(): ?Bot
    {
        $defaultId = $this->config->getDefaultBotId();
        return $this->getBot($defaultId);
    }

    /**
     * 获取所有机器人实例
     *
     * @return array<string, Bot>
     */
    public function getAllBots(): array
    {
        return $this->bots;
    }

    /**
     * 判断是否存在指定机器人
     */
    public function hasBot(string $botId): bool
    {
        return isset($this->bots[$botId]);
    }
}
