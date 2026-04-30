<?php

declare(strict_types=1);

namespace QQBot\Plugin;

use QQBot\Core\EventDispatcher;
use QQBot\Core\Logger;

/**
 * 插件接口
 * 所有插件必须实现此接口
 */
interface PluginInterface
{
    /**
     * 插件名称（英文标识符，唯一）
     */
    public function getName(): string;

    /**
     * 插件显示名称
     */
    public function getDisplayName(): string;

    /**
     * 插件描述
     */
    public function getDescription(): string;

    /**
     * 插件版本（语义化版本，如 1.0.0）
     */
    public function getVersion(): string;

    /**
     * 插件作者
     */
    public function getAuthor(): string;

    /**
     * 插件图标（emoji 或 URL）
     */
    public function getIcon(): ?string;

    /**
     * 插件标签
     *
     * @return string[]
     */
    public function getTags(): array;

    /**
     * 注册事件监听器
     * 框架会在插件加载时调用此方法
     */
    public function register(EventDispatcher $dispatcher, Logger $logger): void;

    /**
     * 插件启用时调用
     */
    public function enable(): void;

    /**
     * 插件禁用时调用
     */
    public function disable(): void;
}
