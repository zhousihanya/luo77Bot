<?php

declare(strict_types=1);

namespace QQBot\Core;

use QQBot\Bot\BotManager;
use QQBot\Plugin\PluginLoader;
use QQBot\Plugin\PluginManager;
use QQBot\Plugin\PluginRegistry;

/**
 * 应用核心
 * 负责初始化配置、日志、多机器人管理、插件系统
 */
class Application
{
    private Config $config;
    private Logger $logger;
    private BotManager $botManager;
    private PluginManager $pluginManager;
    private PluginLoader $pluginLoader;
    private PluginRegistry $pluginRegistry;

    public function __construct(string $configPath)
    {
        $this->config = new Config($configPath);
        $this->logger = new Logger($this->config->get('log', []));

        $sharedDispatcher = new EventDispatcher();
        $this->pluginRegistry = new PluginRegistry($this->config->get('plugin.data_path', __DIR__ . '/../../data'));
        $this->botManager    = new BotManager($this->config, $this->logger, $sharedDispatcher);
        $this->pluginManager = new PluginManager($this->logger, $sharedDispatcher, $this->pluginRegistry);
        $this->pluginLoader  = new PluginLoader($this->logger, $this->config->get('plugin.path', __DIR__ . '/../../plugins'));
    }

    /**
     * 启动应用：加载插件并初始化
     */
    public function boot(): void
    {
        $this->logger->info('Application booting...');

        try {
            // 1. 加载配置文件指定的插件
            $autoloadClasses = $this->config->get('plugin.autoload', []);
            foreach ($autoloadClasses as $className) {
                try {
                    $plugin = $this->pluginLoader->create($className);
                    if ($plugin !== null) {
                        $this->pluginManager->register($plugin, $className);
                    }
                } catch (\Throwable $e) {
                    $this->logger->error('Autoload plugin failed', [
                        'class' => $className,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 2. 扫描插件目录自动加载
            try {
                $scannedClasses = $this->pluginLoader->scan();
                $this->logger->info('Plugin scan complete', ['found' => count($scannedClasses)]);

                foreach ($scannedClasses as $className) {
                    try {
                        $plugin = $this->pluginLoader->create($className);
                        if ($plugin === null) {
                            continue;
                        }

                        // 避免重复加载同名插件
                        if ($this->pluginManager->getPlugin($plugin->getName()) !== null) {
                            $this->logger->debug('Plugin already loaded, skip', ['name' => $plugin->getName()]);
                            continue;
                        }

                        $this->pluginManager->register($plugin, $className);
                    } catch (\Throwable $e) {
                        $this->logger->error('Plugin register failed', [
                            'class' => $className,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->error('Plugin scan failed', ['error' => $e->getMessage()]);
            }

            // 3. 所有 Bot 和插件共享同一个 EventDispatcher
            foreach ($this->botManager->getAllBots() as $bot) {
                $this->logger->info('Bot ready', ['bot_id' => $bot->getBotId()]);
            }

            $this->logger->info('Application booted successfully');
        } catch (\Throwable $e) {
            $this->logger->error('Application boot failed', ['error' => $e->getMessage()]);
            // 即使启动失败，也确保基本的 Webhook 处理可用
        }
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public function getBotManager(): BotManager
    {
        return $this->botManager;
    }

    public function getPluginManager(): PluginManager
    {
        return $this->pluginManager;
    }

    public function getPluginLoader(): PluginLoader
    {
        return $this->pluginLoader;
    }

    public function getPluginRegistry(): PluginRegistry
    {
        return $this->pluginRegistry;
    }
}
