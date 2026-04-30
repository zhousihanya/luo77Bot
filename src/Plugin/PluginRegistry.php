<?php

declare(strict_types=1);

namespace QQBot\Plugin;

/**
 * 插件注册表
 * 负责插件启用/禁用状态的持久化存储
 */
class PluginRegistry
{
    private string $dataPath;

    /** @var array<string, array> 插件状态缓存 [name => [enabled => bool, ...]] */
    private array $states = [];

    public function __construct(string $dataDir)
    {
        $this->dataPath = rtrim($dataDir, '/') . '/plugins.json';

        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        $this->load();
    }

    /**
     * 注册插件（保存到注册表）
     */
    public function register(PluginInfo $info): void
    {
        $this->states[$info->name] = [
            'enabled'   => $info->enabled,
            'className' => $info->className,
            'version'   => $info->version,
            'author'    => $info->author,
            'installed' => true,
            'installedAt' => $this->states[$info->name]['installedAt'] ?? date('Y-m-d H:i:s'),
        ];
        $this->save();
    }

    /**
     * 设置插件启用状态
     */
    public function setEnabled(string $name, bool $enabled): void
    {
        if (isset($this->states[$name])) {
            $this->states[$name]['enabled'] = $enabled;
            $this->save();
        }
    }

    /**
     * 获取插件启用状态
     */
    public function isEnabled(string $name): bool
    {
        return $this->states[$name]['enabled'] ?? true;
    }

    /**
     * 检查插件是否已注册
     */
    public function isRegistered(string $name): bool
    {
        return isset($this->states[$name]);
    }

    /**
     * 卸载插件
     */
    public function unregister(string $name): void
    {
        unset($this->states[$name]);
        $this->save();
    }

    /**
     * 获取所有已注册插件的状态
     *
     * @return array<string, array>
     */
    public function getAllStates(): array
    {
        return $this->states;
    }

    /**
     * 从持久化文件加载
     */
    private function load(): void
    {
        if (is_file($this->dataPath)) {
            $content = file_get_contents($this->dataPath);
            $data = json_decode($content, true);
            if (is_array($data)) {
                $this->states = $data;
            }
        }
    }

    /**
     * 保存到持久化文件
     */
    private function save(): void
    {
        file_put_contents(
            $this->dataPath,
            json_encode($this->states, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }
}
