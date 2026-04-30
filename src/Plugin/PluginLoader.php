<?php

declare(strict_types=1);

namespace QQBot\Plugin;

use QQBot\Core\Logger;

/**
 * 插件加载器
 * 从指定目录扫描并加载插件类
 */
class PluginLoader
{
    private Logger $logger;
    private string $pluginPath;

    public function __construct(Logger $logger, string $pluginPath)
    {
        $this->logger     = $logger;
        $this->pluginPath = rtrim($pluginPath, '/');
    }

    /**
     * 扫描插件目录，返回符合条件的插件类名列表
     *
     * @return array<string> 完整类名列表
     */
    public function scan(): array
    {
        $classes = [];

        if (!is_dir($this->pluginPath)) {
            $this->logger->warning('Plugin path does not exist', ['path' => $this->pluginPath]);
            return $classes;
        }

        $iterator = new \DirectoryIterator($this->pluginPath);

        foreach ($iterator as $file) {
            if ($file->isDot() || $file->isDir()) {
                continue;
            }

            $filename = $file->getFilename();

            // 仅加载 .php 文件
            if (!str_ends_with($filename, '.php')) {
                continue;
            }

            $className = 'QQBot\\Plugin\\' . basename($filename, '.php');

            try {
                // 检查类是否存在（触发 autoloader）
                if (!class_exists($className, false)) {
                    // 尝试手动 require 文件
                    $filepath = $file->getPathname();
                    require_once $filepath;
                }

                if (!class_exists($className, false)) {
                    $this->logger->warning('Plugin class not found after require', [
                        'file'  => $filename,
                        'class' => $className,
                    ]);
                    continue;
                }

                if (!is_subclass_of($className, PluginInterface::class)) {
                    $this->logger->debug('Not a plugin class', ['file' => $filename]);
                    continue;
                }

                $classes[] = $className;
                $this->logger->debug('Plugin class found', ['class' => $className]);
            } catch (\Throwable $e) {
                $this->logger->error('Plugin scan error', [
                    'file'  => $filename,
                    'class' => $className,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        return $classes;
    }

    /**
     * 实例化指定类名的插件
     */
    public function create(string $className): ?PluginInterface
    {
        if (!class_exists($className) || !is_subclass_of($className, PluginInterface::class)) {
            return null;
        }

        try {
            return new $className();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to instantiate plugin', [
                'class' => $className,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
