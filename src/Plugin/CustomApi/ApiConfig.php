<?php

declare(strict_types=1);

namespace QQBot\Plugin\CustomApi;

/**
 * 自定义接口配置管理 v3.0
 * 
 * 新特性：
 *   - responseMode: 可视化响应类型选择
 *   - fieldMapping: 字段映射（用于 Markdown 排版）
 *   - listKey: 列表数据键（数组结果）
 *   - markdownLayout: Markdown 排版布局
 *   - detailApi: 详情接口配置
 *   - 自动 v2.0 → v3.0 配置迁移
 */
class ApiConfig
{
    private string $filePath;
    private array $data = ['apis' => [], 'version' => '3.0'];

    public function __construct(string $dataDir)
    {
        $this->filePath = rtrim($dataDir, '/') . '/custom_apis.json';
        $this->load();
    }

    public function getAll(): array
    {
        return array_map(fn(array $a): array => $this->ensureV3($a), $this->data['apis'] ?? []);
    }

    public function getEnabled(): array
    {
        return array_filter($this->getAll(), fn(array $a): bool => $a['enabled'] ?? true);
    }

    public function get(string $id): ?array
    {
        foreach ($this->data['apis'] as $a) {
            if ($a['id'] === $id) return $this->ensureV3($a);
        }
        return null;
    }

    public function getByCommand(string $cmd): ?array
    {
        foreach ($this->data['apis'] as $a) {
            if (($a['command'] ?? '') === $cmd && ($a['enabled'] ?? true)) {
                return $this->ensureV3($a);
            }
        }
        return null;
    }

    public function save(array $api): void
    {
        // 确保 v3.0 结构
        $api = $this->ensureV3($api);

        $found = false;
        foreach ($this->data['apis'] as $i => $e) {
            if ($e['id'] === $api['id']) {
                $this->data['apis'][$i] = $api;
                $found = true;
                break;
            }
        }
        if (!$found) $this->data['apis'][] = $api;
        $this->data['version'] = '3.0';
        $this->persist();
    }

    public function delete(string $id): bool
    {
        foreach ($this->data['apis'] as $i => $a) {
            if ($a['id'] === $id) {
                array_splice($this->data['apis'], $i, 1);
                $this->persist();
                return true;
            }
        }
        return false;
    }

    public function toggle(string $id): bool
    {
        foreach ($this->data['apis'] as $i => $a) {
            if ($a['id'] === $id) {
                $this->data['apis'][$i]['enabled'] = !($a['enabled'] ?? true);
                $this->persist();
                return $this->data['apis'][$i]['enabled'];
            }
        }
        return false;
    }

    public static function generateId(): string
    {
        return 'api_' . date('Ymd') . '_' . substr(md5(uniqid()), 0, 8);
    }

    /**
     * 确保配置是 v3.0 格式（自动迁移）
     */
    private function ensureV3(array $api): array
    {
        // 如果已有 responseMode，说明是 v3.0
        if (isset($api['responseMode'])) {
            return $this->fillV3Defaults($api);
        }

        // ===== v2.0 → v3.0 迁移 =====
        $outputType = $api['outputType'] ?? 'text';
        $contentKey = $api['contentKey'] ?? '';
        $contentUrlKey = $api['contentUrlKey'] ?? '';
        $isJson = $api['isJson'] ?? true;

        // 映射 outputType 到 responseMode
        $responseMode = match ($outputType) {
            'image' => 'direct_image',
            'audio' => 'direct_audio',
            'video' => 'direct_video',
            'file' => 'direct_file',
            'markdown' => 'direct_markdown',
            'code' => 'direct_text',
            'text' => $isJson ? 'json_data' : 'direct_text',
            'auto' => 'json_data',
            default => $isJson ? 'json_data' : 'direct_text',
        };

        // 构建 fieldMapping
        $fieldMapping = [];
        if ($responseMode === 'json_data' && $contentKey !== '') {
            // 尝试推断字段
            $fieldMapping = [
                [
                    'key' => $contentKey,
                    'label' => '内容',
                    'enabled' => true,
                    'format' => 'text',
                ]
            ];
            if ($contentUrlKey !== '') {
                $fieldMapping[] = [
                    'key' => $contentUrlKey,
                    'label' => '链接',
                    'enabled' => true,
                    'format' => 'link',
                ];
            }
        }

        // 媒体 URL 路径（从 JSON 中提取媒体 URL）
        $mediaUrlPath = '';
        if ($contentUrlKey !== '') {
            $mediaUrlPath = $contentUrlKey;
        } elseif (in_array($outputType, ['image', 'audio', 'video', 'file'])) {
            $mediaUrlPath = $contentKey;
        }

        $v3 = [
            'id' => $api['id'] ?? self::generateId(),
            'name' => $api['name'] ?? '',
            'command' => $api['command'] ?? '',
            'description' => $api['description'] ?? '',
            'enabled' => $api['enabled'] ?? true,

            // 请求配置（扁平结构，兼容 v2.0）
            'url' => $api['url'] ?? '',
            'method' => $api['method'] ?? 'GET',
            'headers' => is_array($api['headers'] ?? null) ? $api['headers'] : [],
            'body' => $api['body'] ?? '',

            // 响应模式（v3.0 核心）
            'responseMode' => $responseMode,

            // JSON 路径和字段映射
            'jsonPath' => $contentKey,
            'isList' => false,
            'listKey' => '',
            'fieldMapping' => $fieldMapping,
            'markdownLayout' => 'card',
            'markdownTemplate' => $api['responseTemplate'] ?? '',

            // 媒体 URL 路径（从 JSON 中提取）
            'mediaUrlPath' => $mediaUrlPath,

            // 详情接口
            'detailApiEnabled' => false,
            'detailApiUrl' => '',
            'detailApiMethod' => 'GET',
            'detailApiHeaders' => [],
            'detailApiBody' => '',
            'detailApiJsonPath' => '',
            'detailApiFieldMapping' => [],

            // 高级
            'cacheSeconds' => (int) ($api['cacheSeconds'] ?? 0),
            'timeout' => (int) ($api['timeout'] ?? 20),

            // 元信息
            'createdAt' => $api['createdAt'] ?? ($api['updatedAt'] ?? date('Y-m-d H:i:s')),
            'updatedAt' => $api['updatedAt'] ?? date('Y-m-d H:i:s'),
        ];

        return $this->fillV3Defaults($v3);
    }

    /**
     * 填充 v3.0 默认值
     */
    private function fillV3Defaults(array $api): array
    {
        $defaults = [
            'responseMode' => 'json_data',
            'jsonPath' => '',
            'isList' => false,
            'listKey' => '',
            'fieldMapping' => [],
            'markdownLayout' => 'card',
            'markdownTemplate' => '',
            'mediaUrlPath' => '',
            'detailApiEnabled' => false,
            'detailApiUrl' => '',
            'detailApiMethod' => 'GET',
            'detailApiHeaders' => [],
            'detailApiBody' => '',
            'detailApiJsonPath' => '',
            'detailApiFieldMapping' => [],
            'headers' => [],
            'body' => '',
            'description' => '',
            'cacheSeconds' => 0,
            'timeout' => 20,
            'enabled' => true,
        ];

        return array_merge($defaults, $api);
    }

    private function load(): void
    {
        if (is_file($this->filePath)) {
            $decoded = json_decode(file_get_contents($this->filePath), true);
            if (is_array($decoded)) {
                $this->data = $decoded;
                // 版本迁移标记
                if (($this->data['version'] ?? '') !== '3.0') {
                    $this->data['version'] = '3.0';
                    // 保存时自动迁移
                }
            }
        }
    }

    private function persist(): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $this->data['version'] = '3.0';
        file_put_contents($this->filePath, json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
}
