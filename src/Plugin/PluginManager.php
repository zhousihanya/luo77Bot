<?php

declare(strict_types=1);

namespace QQBot\Plugin;

use QQBot\Core\EventDispatcher;
use QQBot\Core\Logger;

/**
 * 插件管理器
 * 负责插件的注册、启用/禁用、信息查询
 */
class PluginManager
{
    /** @var PluginInterface[] 已加载的插件实例 */
    private array $plugins = [];

    /** @var PluginInfo[] 插件信息索引 */
    private array $pluginInfos = [];

    private Logger $logger;
    private EventDispatcher $dispatcher;
    private PluginRegistry $registry;

    public function __construct(Logger $logger, EventDispatcher $dispatcher, PluginRegistry $registry)
    {
        $this->logger     = $logger;
        $this->dispatcher = $dispatcher;
        $this->registry   = $registry;
    }

    /**
     * 注册插件（根据注册表的开关状态决定是否启用）
     */
    public function register(PluginInterface $plugin, string $className = ''): void
    {
        $name = $plugin->getName();

        // 创建插件信息
        $info = new PluginInfo(
            name:        $name,
            displayName: $plugin->getDisplayName(),
            version:     $plugin->getVersion(),
            description: $plugin->getDescription(),
            author:      $plugin->getAuthor(),
            icon:        $plugin->getIcon(),
            tags:        $plugin->getTags(),
            enabled:     true,
            className:   $className,
        );

        $this->pluginInfos[$name] = $info;

        if (isset($this->plugins[$name])) {
            $this->logger->warning('Plugin already registered', ['name' => $name]);
            return;
        }

        // 注册到注册表（如果是新插件）
        if (!$this->registry->isRegistered($name)) {
            $this->registry->register($info);
        }

        // 保存插件实例
        $this->plugins[$name] = $plugin;

        // 根据注册表的开关状态决定是否注册事件
        if ($this->registry->isEnabled($name)) {
            $plugin->register($this->dispatcher, $this->logger);
            $plugin->enable();
            $this->logger->info('Plugin enabled', [
                'name'    => $name,
                'version' => $plugin->getVersion(),
                'author'  => $plugin->getAuthor(),
            ]);
        } else {
            $this->logger->info('Plugin loaded but disabled', ['name' => $name]);
        }
    }

    /**
     * 启用插件（动态开关）
     */
    public function enable(string $name): bool
    {
        if (!isset($this->plugins[$name])) {
            return false;
        }

        $this->registry->setEnabled($name, true);

        // 注意：动态启用需要重新注册事件监听器
        // 简单实现：重新加载页面后生效
        // 如需即时生效，需要 EventDispatcher 支持移除监听器
        $this->plugins[$name]->enable();

        $this->logger->info('Plugin enabled', ['name' => $name]);
        return true;
    }

    /**
     * 禁用插件（动态开关）
     */
    public function disable(string $name): bool
    {
        if (!isset($this->plugins[$name])) {
            return false;
        }

        $this->registry->setEnabled($name, false);
        $this->plugins[$name]->disable();

        $this->logger->info('Plugin disabled', ['name' => $name]);
        return true;
    }

    /**
     * 获取插件信息
     */
    public function getInfo(string $name): ?PluginInfo
    {
        return $this->pluginInfos[$name] ?? null;
    }

    /**
     * 获取所有插件信息
     *
     * @return PluginInfo[]
     */
    public function getAllInfos(): array
    {
        return $this->pluginInfos;
    }

    /**
     * 获取已注册的插件实例
     */
    public function getPlugin(string $name): ?PluginInterface
    {
        return $this->plugins[$name] ?? null;
    }

    /**
     * 获取所有已注册插件实例
     *
     * @return PluginInterface[]
     */
    public function getAllPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * 获取注册表
     */
    public function getRegistry(): PluginRegistry
    {
        return $this->registry;
    }
}
