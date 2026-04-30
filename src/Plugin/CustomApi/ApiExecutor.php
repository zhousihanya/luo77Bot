<?php

declare(strict_types=1);

namespace QQBot\Plugin\CustomApi;

use QQBot\Core\Logger;

/**
 * 接口执行器 v3.0
 * 
 * 新特性：
 *   - responseMode 驱动：direct_text/direct_image/direct_audio/direct_video/direct_markdown/json_data
 *   - test() 方法：管理页面预览用，返回原始 JSON 树
 *   - fieldMapping + markdownLayout：可视化 Markdown 排版
 *   - 从 JSON 中提取媒体 URL
 */
class ApiExecutor
{
    private Logger $logger;
    private string $cacheDir;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->cacheDir = __DIR__ . '/../../../public/temp';
        if (!is_dir($this->cacheDir)) @mkdir($this->cacheDir, 0755, true);
    }

    /**
     * 执行接口请求
     * @return array [type, content, mediaUrl, raw]
     */
    public function execute(array $api, array $args = []): array
    {
        $url = $this->replaceParams($api['url'] ?? '', $args);
        $body = $this->replaceParams($api['body'] ?? '', $args);
        $method = strtoupper($api['method'] ?? 'GET');
        $headers = is_array($api['headers'] ?? null) ? ($api['headers'] ?? []) : [];
        $timeout = (int) ($api['timeout'] ?? 20);
        $cacheSeconds = (int) ($api['cacheSeconds'] ?? 0);
        $responseMode = $api['responseMode'] ?? 'json_data';

        // 缓存 key
        $cacheKey = md5($url . $body . $method . json_encode($headers));
        if ($cacheSeconds > 0) {
            $cached = $this->getCache($cacheKey, $cacheSeconds);
            if ($cached !== null) return $cached;
        }

        $this->logger->info('API execute', ['url' => substr($url, 0, 100), 'method' => $method, 'mode' => $responseMode]);

        // 发送请求
        $raw = $this->httpRequest($url, $method, $headers, $body, $timeout);

        $result = $this->parseResponse($raw, $responseMode, $api);

        // 保存缓存
        if ($cacheSeconds > 0) {
            $this->setCache($cacheKey, $result);
        }

        return $result;
    }

    /**
     * 测试接口（仅返回原始响应，用于管理页面预览）
     */
    public function test(array $api, array $args = []): array
    {
        $url = $this->replaceParams($api['url'] ?? '', $args);
        $body = $this->replaceParams($api['body'] ?? '', $args);
        $method = strtoupper($api['method'] ?? 'GET');
        $headers = is_array($api['headers'] ?? null) ? ($api['headers'] ?? []) : [];
        $timeout = (int) ($api['timeout'] ?? 20);

        $this->logger->info('API test', ['url' => substr($url, 0, 100), 'method' => $method]);

        try {
            $raw = $this->httpRequest($url, $method, $headers, $body, $timeout);

            // 尝试解析 JSON
            $json = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                return [
                    'success' => true,
                    'isJson' => true,
                    'data' => $json,
                    'raw' => $raw,
                ];
            }

            return [
                'success' => true,
                'isJson' => false,
                'data' => $raw,
                'raw' => $raw,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 解析响应
     */
    private function parseResponse(string $raw, string $responseMode, array $api): array
    {
        return match ($responseMode) {
            'direct_text' => [
                'type' => 'text',
                'content' => $raw,
                'mediaUrl' => '',
                'raw' => $raw,
            ],
            'direct_markdown' => [
                'type' => 'markdown',
                'content' => $raw,
                'mediaUrl' => '',
                'raw' => $raw,
            ],
            'direct_image', 'direct_audio', 'direct_video', 'direct_file' => $this->parseDirectMedia($raw, $responseMode, $api),
            'json_data' => $this->parseJsonData($raw, $api),
            default => [
                'type' => 'text',
                'content' => $raw,
                'mediaUrl' => '',
                'raw' => $raw,
            ],
        };
    }

    /**
     * 解析直接媒体类型（从文本或 JSON 中提取 URL）
     */
    private function parseDirectMedia(string $raw, string $responseMode, array $api): array
    {
        $mediaType = str_replace('direct_', '', $responseMode);
        $mediaUrlPath = $api['mediaUrlPath'] ?? '';

        $mediaUrl = $raw;

        // 如果配置了媒体 URL 路径，从 JSON 中提取
        if ($mediaUrlPath !== '') {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $extracted = $this->extractJsonPath($json, $mediaUrlPath);
                if (is_string($extracted) && filter_var($extracted, FILTER_VALIDATE_URL)) {
                    $mediaUrl = $extracted;
                }
            }
        } elseif (!filter_var($raw, FILTER_VALIDATE_URL)) {
            // 尝试从 JSON 中提取第一个 URL
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $mediaUrl = $this->findFirstUrl($json) ?: $raw;
            }
        }

        return [
            'type' => $mediaType,
            'content' => $mediaUrl,
            'mediaUrl' => $mediaUrl,
            'raw' => json_decode($raw, true) ?? $raw,
        ];
    }

    /**
     * 解析 JSON 数据（fieldMapping + Markdown 排版）
     */
    private function parseJsonData(string $raw, array $api): array
    {
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new \RuntimeException('JSON parse failed: ' . json_last_error_msg());
        }

        $jsonPath = $api['jsonPath'] ?? '';
        $isList = $api['isList'] ?? false;
        $listKey = $api['listKey'] ?? '';
        $fieldMapping = $api['fieldMapping'] ?? [];
        $markdownLayout = $api['markdownLayout'] ?? 'card';
        $markdownTemplate = $api['markdownTemplate'] ?? '';
        $mediaUrlPath = $api['mediaUrlPath'] ?? '';

        // 提取数据
        if ($isList && $listKey !== '') {
            $items = $this->extractJsonPath($data, $listKey);
            if (!is_array($items) || !isset($items[0])) {
                // 回退：尝试用 jsonPath
                $fallback = $jsonPath !== '' ? $this->extractJsonPath($data, $jsonPath) : $data;
                if (is_array($fallback)) {
                    $extractedData = $fallback;
                    $isList = true;
                } else {
                    throw new \RuntimeException('列表数据提取失败，路径：' . $listKey);
                }
            } else {
                $extractedData = $items;
            }
        } else {
            $extractedData = $jsonPath !== '' ? $this->extractJsonPath($data, $jsonPath) : $data;
        }

        // 提取媒体 URL（如果配置了）
        $mediaUrl = '';
        if ($mediaUrlPath !== '') {
            $mediaUrl = (string) $this->extractJsonPath($data, $mediaUrlPath);
            // 如果路径是相对于列表项的，尝试从第一项提取
            if ($mediaUrl === '' && $isList && is_array($extractedData) && isset($extractedData[0])) {
                $mediaUrl = (string) $this->extractJsonPath($extractedData[0], $mediaUrlPath);
            }
        }

        // 生成 Markdown
        $markdown = $this->formatMarkdown($extractedData, $fieldMapping, $markdownLayout, $markdownTemplate, $isList);

        return [
            'type' => empty($mediaUrl) ? 'markdown' : $this->detectMediaType($mediaUrl),
            'content' => $markdown,
            'mediaUrl' => $mediaUrl,
            'raw' => $data,
            'extracted' => $extractedData,
        ];
    }

    // ========== Markdown 排版 ==========

    /**
     * 根据 fieldMapping 和布局生成 Markdown
     */
    private function formatMarkdown(mixed $data, array $fieldMapping, string $layout, string $template, bool $isList): string
    {
        // 如果配置了自定义模板，优先使用
        if ($template !== '') {
            $text = $this->applyTemplate($template, $data, $isList);
            if ($text !== $template || !is_array($data)) {
                return $text;
            }
        }

        // 过滤启用的字段
        $enabledFields = array_values(array_filter($fieldMapping, fn(array $f): bool => $f['enabled'] ?? true));
        if (empty($enabledFields)) {
            return $this->toDefaultMarkdown($data);
        }

        return match ($layout) {
            'table' => $this->toTableMarkdown($data, $enabledFields, $isList),
            'list' => $this->toListMarkdown($data, $enabledFields, $isList),
            'custom' => $this->toCustomMarkdown($data, $enabledFields, $isList),
            default => $this->toCardMarkdown($data, $enabledFields, $isList),
        };
    }

    /**
     * Card 布局（每项一个卡片块）
     */
    private function toCardMarkdown(mixed $data, array $fields, bool $isList): string
    {
        if (!$isList || !is_array($data)) {
            return $this->renderCard($data, $fields);
        }

        $cards = [];
        foreach (array_slice($data, 0, 10) as $item) {
            $cards[] = $this->renderCard($item, $fields);
        }
        $md = implode("\n\n---\n\n", $cards);
        if (count($data) > 10) {
            $md .= "\n\n> 共 " . count($data) . " 条，仅显示前 10 条";
        }
        return $md;
    }

    private function renderCard(mixed $item, array $fields): string
    {
        if (!is_array($item)) return (string) $item;

        $lines = [];
        foreach ($fields as $field) {
            $key = $field['key'] ?? '';
            $label = $field['label'] ?? $key;
            $format = $field['format'] ?? 'text';
            $value = $this->getValueByKey($item, $key);

            if ($value === null || $value === '') continue;

            $formatted = $this->formatFieldValue($value, $format);
            $lines[] = "**{$label}**：{$formatted}";
        }

        return implode("\n", $lines);
    }

    /**
     * Table 布局
     */
    private function toTableMarkdown(mixed $data, array $fields, bool $isList): string
    {
        if (!$isList || !is_array($data) || !isset($data[0])) {
            return $this->renderCard($data, $fields);
        }

        $headers = [];
        foreach ($fields as $field) {
            $headers[] = $field['label'] ?? $field['key'] ?? '';
        }

        $md = '| ' . implode(' | ', $headers) . " |\n";
        $md .= '|' . implode('|', array_fill(0, count($headers), ' --- ')) . "|\n";

        foreach (array_slice($data, 0, 20) as $item) {
            if (!is_array($item)) continue;
            $vals = [];
            foreach ($fields as $field) {
                $key = $field['key'] ?? '';
                $format = $field['format'] ?? 'text';
                $value = $this->getValueByKey($item, $key);
                $vals[] = $this->formatFieldValue($value, $format, true);
            }
            $md .= '| ' . implode(' | ', $vals) . " |\n";
        }

        if (count($data) > 20) {
            $md .= "\n> 共 " . count($data) . " 条，仅显示前 20 条";
        }

        return $md;
    }

    /**
     * List 布局（序号列表）
     */
    private function toListMarkdown(mixed $data, array $fields, bool $isList): string
    {
        if (!$isList || !is_array($data)) {
            return $this->renderCard($data, $fields);
        }

        $lines = [];
        foreach (array_slice($data, 0, 15) as $index => $item) {
            if (!is_array($item)) continue;
            $parts = [];
            foreach ($fields as $field) {
                $key = $field['key'] ?? '';
                $label = $field['label'] ?? $key;
                $format = $field['format'] ?? 'text';
                $value = $this->getValueByKey($item, $key);
                if ($value === null || $value === '') continue;
                $formatted = $this->formatFieldValue($value, $format);
                $parts[] = "**{$label}**：{$formatted}";
            }
            $lines[] = ($index + 1) . '. ' . implode(' | ', $parts);
        }

        $md = implode("\n", $lines);
        if (count($data) > 15) {
            $md .= "\n\n> 共 " . count($data) . " 条，仅显示前 15 条";
        }
        return $md;
    }

    /**
     * Custom 布局（简单键值对，紧凑格式）
     */
    private function toCustomMarkdown(mixed $data, array $fields, bool $isList): string
    {
        if (!$isList || !is_array($data)) {
            return $this->renderCard($data, $fields);
        }

        $lines = [];
        foreach (array_slice($data, 0, 15) as $item) {
            if (!is_array($item)) continue;
            $parts = [];
            foreach ($fields as $field) {
                $key = $field['key'] ?? '';
                $format = $field['format'] ?? 'text';
                $value = $this->getValueByKey($item, $key);
                if ($value === null || $value === '') continue;
                $formatted = $this->formatFieldValue($value, $format);
                $parts[] = $formatted;
            }
            $lines[] = '- ' . implode(' · ', $parts);
        }

        $md = implode("\n", $lines);
        if (count($data) > 15) {
            $md .= "\n\n> 共 " . count($data) . " 条，仅显示前 15 条";
        }
        return $md;
    }

    // ========== 模板 ==========

    /**
     * 应用自定义模板
     */
    private function applyTemplate(string $template, mixed $data, bool $isList): string
    {
        if (!$isList || !is_array($data) || !isset($data[0])) {
            return $this->applyTemplateSingle($template, $data);
        }

        $results = [];
        foreach (array_slice($data, 0, 10) as $item) {
            $results[] = $this->applyTemplateSingle($template, $item);
        }
        return implode("\n\n---\n\n", $results);
    }

    private function applyTemplateSingle(string $template, mixed $data): string
    {
        if (!is_array($data)) {
            return str_replace('{content}', (string) $data, $template);
        }

        $result = $template;
        // 替换 {key} 占位符
        foreach ($data as $key => $value) {
            $placeholder = '{' . $key . '}';
            if (str_contains($result, $placeholder)) {
                $result = str_replace($placeholder, is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value, $result);
            }
        }
        // 清理未替换的占位符
        $result = preg_replace('/\{[a-zA-Z0-9_.]+\}/', '-', $result);
        return $result;
    }

    // ========== 字段格式化 ==========

    /**
     * 格式化字段值
     */
    private function formatFieldValue(mixed $value, string $format, bool $forTable = false): string
    {
        $str = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
        if ($str === '' || $str === 'null') return '-';

        return match ($format) {
            'link' => $forTable ? "[链接]({$str})" : "[{$str}]({$str})",
            'image' => $forTable ? "![图]({$str})" : "![图片]({$str})",
            'code' => "`{$str}`",
            'bold' => "**{$str}**",
            default => $str,
        };
    }

    /**
     * 根据键路径获取值（支持点号路径和通配符）
     */
    private function getValueByKey(array $data, string $keyPath): mixed
    {
        // 如果 key 直接存在，直接返回
        if (array_key_exists($keyPath, $data)) return $data[$keyPath];
        // 尝试点号路径
        return $this->extractJsonPath($data, $keyPath);
    }

    /**
     * 提取 JSON 路径（公开方法，供管理页面使用）
     */
    public function extractJsonPath(array $data, string $path): mixed
    {
        if ($path === '') return $data;

        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } else {
                return null;
            }
        }

        return $current;
    }

    /**
     * 在数组中递归查找第一个 URL
     */
    private function findFirstUrl(array $data): ?string
    {
        foreach ($data as $value) {
            if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                return $value;
            }
            if (is_array($value)) {
                $found = $this->findFirstUrl($value);
                if ($found !== null) return $found;
            }
        }
        return null;
    }

    // ========== 默认排版 ==========

    /**
     * 默认 Markdown（无 fieldMapping 时的降级处理）
     */
    private function toDefaultMarkdown(mixed $data): string
    {
        if (is_string($data)) return $data;
        if (is_numeric($data)) return (string) $data;
        if (!is_array($data)) return (string) $data;

        // 标量数组
        $first = reset($data);
        if (!is_array($first)) {
            $md = '';
            foreach ($data as $k => $v) {
                $md .= "- **{$k}**：" . (is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v) . "\n";
            }
            return $md;
        }

        // 表格
        return $this->arrayToTable($data);
    }

    private function arrayToTable(array $data): string
    {
        if (empty($data)) return '';
        $first = reset($data);
        if (!is_array($first)) return json_encode($data, JSON_UNESCAPED_UNICODE);

        $headers = array_keys($first);
        $md = '| ' . implode(' | ', $headers) . " |\n";
        $md .= '|' . implode('|', array_fill(0, count($headers), ' --- ')) . "|\n";

        foreach (array_slice($data, 0, 20) as $row) {
            if (!is_array($row)) continue;
            $vals = [];
            foreach ($headers as $h) {
                $v = $row[$h] ?? '';
                $vals[] = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v;
            }
            $md .= '| ' . implode(' | ', $vals) . " |\n";
        }

        return $md;
    }

    // ========== 参数替换 ==========

    /**
     * 多参数替换 {arg1} {arg2} ... {keyword}
     */
    public function replaceParams(string $template, array $args): string
    {
        if (str_contains($template, '{keyword}')) {
            $template = str_replace('{keyword}', urlencode($args[0] ?? ''), $template);
        }

        foreach ($args as $i => $val) {
            $template = str_replace('{arg' . ($i + 1) . '}', urlencode($val), $template);
        }

        $template = preg_replace('/\{arg\d+\}/', '', $template);
        $template = str_replace('{keyword}', '', $template);

        return $template;
    }

    // ========== 媒体类型检测 ==========

    /**
     * 自动判断媒体类型
     */
    public function detectMediaType(string $url): string
    {
        $lower = strtolower($url);
        if (str_contains($lower, '.mp3') || str_contains($lower, '.m4a') || str_contains($lower, '.flac') || str_contains($lower, '.wav') || str_contains($lower, '.aac')) {
            return 'audio';
        }
        if (str_contains($lower, '.mp4') || str_contains($lower, '.m3u8') || str_contains($lower, '.avi') || str_contains($lower, '.mkv') || str_contains($lower, '.mov')) {
            return 'video';
        }
        if (str_contains($lower, '.jpg') || str_contains($lower, '.jpeg') || str_contains($lower, '.png') || str_contains($lower, '.gif') || str_contains($lower, '.webp') || str_contains($lower, '.bmp')) {
            return 'image';
        }
        return 'file';
    }

    // ========== 缓存 ==========

    private function getCache(string $key, int $ttl): ?array
    {
        $path = $this->cacheDir . '/api_cache_' . $key . '.json';
        if (!is_file($path)) return null;
        if ((time() - filemtime($path)) > $ttl) {
            @unlink($path);
            return null;
        }
        $data = json_decode(file_get_contents($path), true);
        return is_array($data) ? $data : null;
    }

    private function setCache(string $key, array $data): void
    {
        $path = $this->cacheDir . '/api_cache_' . $key . '.json';
        file_put_contents($path, json_encode($data), LOCK_EX);
    }

    // ========== HTTP ==========

    private function httpRequest(string $url, string $method, array $headers, string $body, int $timeout): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => $method,
        ]);

        $curlHeaders = [];
        foreach ($headers as $k => $v) {
            $curlHeaders[] = "{$k}: {$v}";
        }
        if (!empty($curlHeaders)) curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

        if ($method === 'POST' && $body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resp === false || $code < 200 || $code >= 300) {
            throw new \RuntimeException("HTTP {$code}");
        }

        return (string) $resp;
    }
}
