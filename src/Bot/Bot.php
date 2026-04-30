<?php

declare(strict_types=1);

namespace QQBot\Bot;

use QQBot\Api\AccessTokenManager;
use QQBot\Api\Client;
use QQBot\Core\EventDispatcher;
use QQBot\Core\Logger;
use QQBot\Webhook\Handler;
use QQBot\Webhook\Validator;

/**
 * 单个机器人实例
 * 封装了配置、API 客户端、Token 管理、Webhook 处理器
 */
class Bot
{
    private string $botId;
    private array $config;
    private Logger $logger;
    private AccessTokenManager $tokenManager;
    private Client $client;
    private Handler $handler;

    public function __construct(string $botId, array $config, Logger $logger, EventDispatcher $dispatcher)
    {
        $this->botId   = $botId;
        $this->config  = $config;
        $this->logger  = $logger;

        $appId        = $config['app_id'] ?? '';
        $clientSecret = $config['client_secret'] ?? '';
        $sandbox      = $config['sandbox'] ?? false;
        $nickname     = $config['nickname'] ?? $botId;

        $this->tokenManager = new AccessTokenManager($appId, $clientSecret, $sandbox, $logger);
        $this->client       = new Client($this->tokenManager, $logger, $sandbox);

        $validator = new Validator($logger);

        $this->handler = new Handler(
            logger: $logger,
            dispatcher: $dispatcher,
            validator: $validator,
            client: $this->client,
            botSecret: $clientSecret,
            verifySign: true,
        );

        $this->logger->info('Bot instance created', ['bot_id' => $botId, 'nickname' => $nickname]);
    }

    public function getBotId(): string
    {
        return $this->botId;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getHandler(): Handler
    {
        return $this->handler;
    }

    public function getDispatcher(): EventDispatcher
    {
        // Handler 内部创建了 EventDispatcher，这里需要返回同一个实例
        // 通过反射或公共访问获取 —— 简单起见，在 Handler 中暴露一个 getter
        return $this->handler->getDispatcher();
    }
}
