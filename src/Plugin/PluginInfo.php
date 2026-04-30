<?php

declare(strict_types=1);

namespace QQBot\Plugin;

/**
 * 插件信息值对象
 * 存储插件元数据（名称、版本、作者、描述、开关状态等）
 */
readonly class PluginInfo
{
    public function __construct(
        public string  $name,
        public string  $displayName,
        public string  $version,
        public string  $description,
        public string  $author,
        public ?string $icon = null,
        public array   $tags = [],
        public bool    $enabled = true,
        public string  $className = '',
    ) {
    }

    /**
     * 转为数组
     */
    public function toArray(): array
    {
        return [
            'name'        => $this->name,
            'displayName' => $this->displayName,
            'version'     => $this->version,
            'description' => $this->description,
            'author'      => $this->author,
            'icon'        => $this->icon,
            'tags'        => $this->tags,
            'enabled'     => $this->enabled,
            'className'   => $this->className,
        ];
    }

    /**
     * 从数组创建
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name:        $data['name']        ?? 'unknown',
            displayName: $data['displayName'] ?? ($data['name'] ?? 'unknown'),
            version:     $data['version']     ?? '0.0.0',
            description: $data['description'] ?? '',
            author:      $data['author']      ?? '',
            icon:        $data['icon']        ?? null,
            tags:        $data['tags']        ?? [],
            enabled:     $data['enabled']     ?? true,
            className:   $data['className']   ?? '',
        );
    }
}
