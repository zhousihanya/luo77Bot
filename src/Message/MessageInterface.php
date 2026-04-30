<?php

declare(strict_types=1);

namespace QQBot\Message;

/**
 * 消息接口
 */
interface MessageInterface
{
    /**
     * 转换为 API 请求数组
     */
    public function toArray(): array;
}
