<?php

declare(strict_types=1);

namespace QQBot\Events;

use QQBot\Api\Client;

/**
 * 事件接口
 */
interface EventInterface
{
    /**
     * 获取事件原始数据
     */
    public function getPayload(): array;

    /**
     * 获取事件类型
     */
    public function getEventType(): string;

    /**
     * 获取事件 ID
     */
    public function getEventId(): string;

    /**
     * 获取机器人 API 客户端（用于回复消息）
     */
    public function getClient(): Client;

    /**
     * 设置消息已处理，停止事件传播
     */
    public function stopPropagation(): void;

    /**
     * 是否已停止传播
     */
    public function isPropagationStopped(): bool;
}
