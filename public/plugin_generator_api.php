<?php
/**
 * 插件生成器 API — 完全独立，不依赖框架任何文件
 * 支持 PHP 8.0+
 */

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'test':
            handleTest();
            break;
        case 'generate':
            handleGenerate();
            break;
        default:
            jsonError('Unknown action: ' . $action);
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
}

// ========== 测试接口 ==========

function handleTest(): void
{
    $cfg = json_decode($_POST['config'] ?? '', true);
    if (!is_array($cfg)) {
        jsonError('无效配置');
        return;
    }

    $url = replaceParams($cfg['url'] ?? '', $cfg['args'] ?? []);
    $body = replaceParams($cfg['body'] ?? '', $cfg['args'] ?? []);
    $method = strtoupper($cfg['method'] ?? 'GET');
    $headers = is_array($cfg['headers'] ?? null) ? $cfg['headers'] : [];
    $timeout = (int) ($cfg['timeout'] ?? 20);

    $raw = httpRequest($url, $method, $headers, $body, $timeout);

    $json = json_decode($raw, true);
    $isJson = json_last_error() === JSON_ERROR_NONE && is_array($json);

    $suggestions = [];
    if ($isJson) {
        $suggestions = generatePathSuggestions($json);
    }

    echo json_encode([
        'success' => true,
        'isJson' => $isJson,
        'data' => $isJson ? $json : $raw,
        'raw' => $raw,
        'suggestions' => $suggestions,
    ]);
}

// ========== 生成代码 ==========

function handleGenerate(): void
{
    $cfg = json_decode($_POST['config'] ?? '', true);
    if (!is_array($cfg)) {
        jsonError('无效配置');
        return;
    }

    $className = preg_replace('/[^a-zA-Z0-9_]/', '', $cfg['className'] ?? '');
    if (empty($className) || strlen($className) < 2) {
        $className = 'CustomApi';
    }
    if (!str_ends_with($className, 'Plugin')) {
        $className .= 'Plugin';
    }

    $code = generatePluginCode($className, $cfg);

    echo json_encode([
        'success' => true,
        'filename' => $className . '.php',
        'code' => $code,
    ]);
}

// ========== 代码生成器 ==========

function generatePluginCode(string $className, array $c): string
{
    $name = addslashes($c['name'] ?? '自定义接口');
    $command = addslashes($c['command'] ?? 'api');
    $description = addslashes($c['description'] ?? '');
    $url = addslashes($c['url'] ?? '');
    $method = strtoupper($c['method'] ?? 'GET');
    $headers = var_export($c['headers'] ?? [], true);
    $body = addslashes($c['body'] ?? '');
    $responseMode = $c['responseMode'] ?? 'json_data';
    $jsonPath = addslashes($c['jsonPath'] ?? '');
    $isList = ($c['isList'] ?? false) ? 'true' : 'false';
    $listKey = addslashes($c['listKey'] ?? '');
    $fieldMapping = var_export($c['fieldMapping'] ?? [], true);
    $markdownLayout = $c['markdownLayout'] ?? 'card';
    $markdownTemplate = addslashes($c['markdownTemplate'] ?? '');
    $mediaUrlPath = addslashes($c['mediaUrlPath'] ?? '');
    $cacheSeconds = (int) ($c['cacheSeconds'] ?? 0);
    $timeout = (int) ($c['timeout'] ?? 20);

    return <<<PHP
<?php

declare(strict_types=1);

namespace QQBot\Plugin;

use QQBot\Core\EventDispatcher;
use QQBot\Core\Logger;
use QQBot\Events\C2CMessageEvent;
use QQBot\Events\GroupAtMessageEvent;

/**
 * {$name} — 自动生成的自定义接口插件
 *
 * 触发指令：{$command}
 * 响应模式：{$responseMode}
 */
class {$className} implements PluginInterface
{
    private Logger \$logger;
    private string \$cacheDir;
    private int \$cacheSeconds = {$cacheSeconds};
    private int \$timeout = {$timeout};

    private string \$url = '{$url}';
    private string \$method = '{$method}';
    private array \$headers = {$headers};
    private string \$bodyTemplate = '{$body}';
    private string \$responseMode = '{$responseMode}';

    private string \$jsonPath = '{$jsonPath}';
    private bool \$isList = {$isList};
    private string \$listKey = '{$listKey}';
    private array \$fieldMapping = {$fieldMapping};
    private string \$markdownLayout = '{$markdownLayout}';
    private string \$markdownTemplate = '{$markdownTemplate}';
    private string \$mediaUrlPath = '{$mediaUrlPath}';

    public function getName(): string { return '{$command}'; }
    public function getDisplayName(): string { return '{$name}'; }
    public function getDescription(): string { return '{$description}'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getAuthor(): string { return 'Plugin Generator'; }
    public function getIcon(): ?string { return null; }
    public function getTags(): array { return ['自定义接口', '自动生成']; }

    public function register(EventDispatcher \$dispatcher, Logger \$logger): void
    {
        \$this->logger = \$logger;
        \$this->cacheDir = __DIR__ . '/../public/temp';
        if (!is_dir(\$this->cacheDir)) @mkdir(\$this->cacheDir, 0755, true);

        \$dispatcher->on(C2CMessageEvent::class, function (C2CMessageEvent \$event): void {
            \$this->handle(\$event, \$event->getContent());
        });
        \$dispatcher->on(GroupAtMessageEvent::class, function (GroupAtMessageEvent \$event): void {
            \$this->handle(\$event, \$event->getContent());
        });

        \$this->logger->info('{$className} loaded');
    }

    private function handle(object \$event, string \$content): void
    {
        \$content = trim(\$content);
        if (\$content === '{$command}帮助') {
            \$event->replyMarkdown("**{$name}**\\n\\n触发指令：`{$command}`\\n\\n使用方法：发送 `{$command} 参数1 参数2`");
            return;
        }

        \$parts = explode(' ', \$content, 2);
        \$cmd = \$parts[0];
        \$argsStr = \$parts[1] ?? '';
        if (\$cmd !== '{$command}') return;

        \$args = \$argsStr !== '' ? explode(' ', \$argsStr) : [];
        try {
            \$this->executeAndSend(\$event, \$args);
        } catch (\\Throwable \$e) {
            \$this->logger->error('API failed', ['err' => \$e->getMessage()]);
            \$event->replyText('接口调用失败：' . \$e->getMessage());
        }
    }

    private function executeAndSend(object \$event, array \$args): void
    {
        \$result = \$this->execute(\$args);
        \$type = \$result['type'];
        \$content = \$result['content'];
        \$mediaUrl = \$result['mediaUrl'] ?? '';

        match (\$type) {
            'text' => \$event->replyText(\$content),
            'markdown' => \$event->replyMarkdown(\$content),
            'image' => \$this->sendMedia(\$event, \$mediaUrl ?: \$content, 'image'),
            'audio' => \$this->sendMedia(\$event, \$mediaUrl ?: \$content, 'audio'),
            'video' => \$this->sendMedia(\$event, \$mediaUrl ?: \$content, 'video'),
            'file' => \$this->sendMedia(\$event, \$mediaUrl ?: \$content, 'file'),
            default => \$event->replyText(\$content),
        };
    }

    private function execute(array \$args): array
    {
        \$url = \$this->replaceParams(\$this->url, \$args);
        \$body = \$this->replaceParams(\$this->bodyTemplate, \$args);
        \$cacheKey = md5(\$url . \$body . \$this->method);

        if (\$this->cacheSeconds > 0) {
            \$cached = \$this->getCache(\$cacheKey);
            if (\$cached !== null) return \$cached;
        }

        \$this->logger->info('API execute', ['url' => substr(\$url, 0, 100)]);
        \$raw = \$this->httpRequest(\$url, \$this->method, \$this->headers, \$body);
        \$result = \$this->parseResponse(\$raw);

        if (\$this->cacheSeconds > 0) {
            \$this->setCache(\$cacheKey, \$result);
        }
        return \$result;
    }

    private function parseResponse(string \$raw): array
    {
        return match (\$this->responseMode) {
            'direct_text' => ['type' => 'text', 'content' => \$raw, 'mediaUrl' => ''],
            'direct_markdown' => ['type' => 'markdown', 'content' => \$raw, 'mediaUrl' => ''],
            'direct_image' => \$this->parseDirectMedia(\$raw, 'image'),
            'direct_audio' => \$this->parseDirectMedia(\$raw, 'audio'),
            'direct_video' => \$this->parseDirectMedia(\$raw, 'video'),
            'direct_file' => \$this->parseDirectMedia(\$raw, 'file'),
            'json_data' => \$this->parseJsonData(\$raw),
            default => ['type' => 'text', 'content' => \$raw, 'mediaUrl' => ''],
        };
    }

    private function parseDirectMedia(string \$raw, string \$mediaType): array
    {
        \$mediaUrl = \$raw;
        if (\$this->mediaUrlPath !== '') {
            \$json = json_decode(\$raw, true);
            if (is_array(\$json)) {
                \$extracted = \$this->extractJsonPath(\$json, \$this->mediaUrlPath);
                if (is_string(\$extracted) && filter_var(\$extracted, FILTER_VALIDATE_URL)) {
                    \$mediaUrl = \$extracted;
                }
            }
        }
        return ['type' => \$mediaType, 'content' => \$mediaUrl, 'mediaUrl' => \$mediaUrl];
    }

    private function parseJsonData(string \$raw): array
    {
        \$data = json_decode(\$raw, true);
        if (!is_array(\$data)) {
            throw new \\RuntimeException('JSON parse failed: ' . json_last_error_msg());
        }

        \$extractedData = \$this->jsonPath !== '' ? \$this->extractJsonPath(\$data, \$this->jsonPath) : \$data;

        if (\$this->isList && \$this->listKey !== '') {
            \$items = \$this->extractJsonPath(\$data, \$this->listKey);
            if (is_array(\$items) && isset(\$items[0])) {
                \$extractedData = \$items;
            }
        }

        \$mediaUrl = '';
        if (\$this->mediaUrlPath !== '') {
            \$mediaUrl = (string) \$this->extractJsonPath(\$data, \$this->mediaUrlPath);
            if (\$mediaUrl === '' && is_array(\$extractedData) && isset(\$extractedData[0])) {
                \$mediaUrl = (string) \$this->extractJsonPath(\$extractedData[0], \$this->mediaUrlPath);
            }
        }

        \$markdown = \$this->formatMarkdown(\$extractedData);

        return [
            'type' => empty(\$mediaUrl) ? 'markdown' : \$this->detectMediaType(\$mediaUrl),
            'content' => \$markdown,
            'mediaUrl' => \$mediaUrl,
        ];
    }

    private function formatMarkdown(mixed \$data): string
    {
        if (\$this->markdownTemplate !== '') {
            \$text = \$this->applyTemplate(\$this->markdownTemplate, \$data);
            if (\$text !== \$this->markdownTemplate) return \$text;
        }

        \$enabledFields = array_values(array_filter(\$this->fieldMapping, fn(array \$f): bool => \$f['enabled'] ?? true));
        if (empty(\$enabledFields)) {
            return \$this->toDefaultMarkdown(\$data);
        }

        return match (\$this->markdownLayout) {
            'table' => \$this->toTableMarkdown(\$data, \$enabledFields),
            'list' => \$this->toListMarkdown(\$data, \$enabledFields),
            'custom' => \$this->toCustomMarkdown(\$data, \$enabledFields),
            default => \$this->toCardMarkdown(\$data, \$enabledFields),
        };
    }

    private function toCardMarkdown(mixed \$data, array \$fields): string
    {
        if (!\$this->isList || !is_array(\$data)) {
            return \$this->renderCard(\$data, \$fields);
        }
        \$cards = [];
        foreach (array_slice(\$data, 0, 10) as \$item) {
            \$cards[] = \$this->renderCard(\$item, \$fields);
        }
        \$md = implode("\\n\\n---\\n\\n", \$cards);
        if (count(\$data) > 10) \$md .= "\\n\\n> 共 " . count(\$data) . " 条，仅显示前 10 条";
        return \$md;
    }

    private function renderCard(mixed \$item, array \$fields): string
    {
        if (!is_array(\$item)) return (string) \$item;
        \$lines = [];
        foreach (\$fields as \$field) {
            \$key = \$field['key'] ?? '';
            \$label = \$field['label'] ?? \$key;
            \$format = \$field['format'] ?? 'text';
            \$value = \$this->getValueByKey(\$item, \$key);
            if (\$value === null || \$value === '') continue;
            \$lines[] = "**{\$label}**：" . \$this->formatFieldValue(\$value, \$format);
        }
        return implode("\\n", \$lines);
    }

    private function toTableMarkdown(mixed \$data, array \$fields): string
    {
        if (!\$this->isList || !is_array(\$data) || !isset(\$data[0])) {
            return \$this->renderCard(\$data, \$fields);
        }
        \$headers = array_map(fn(\$f) => \$f['label'] ?? \$f['key'] ?? '', \$fields);
        \$md = '| ' . implode(' | ', \$headers) . " |\\n";
        \$md .= '|' . implode('|', array_fill(0, count(\$headers), ' --- ')) . "|\\n";
        foreach (array_slice(\$data, 0, 20) as \$item) {
            if (!is_array(\$item)) continue;
            \$vals = [];
            foreach (\$fields as \$field) {
                \$v = \$this->getValueByKey(\$item, \$field['key'] ?? '');
                \$vals[] = \$this->formatFieldValue(\$v, \$field['format'] ?? 'text', true);
            }
            \$md .= '| ' . implode(' | ', \$vals) . " |\\n";
        }
        if (count(\$data) > 20) \$md .= "\\n> 共 " . count(\$data) . " 条，仅显示前 20 条";
        return \$md;
    }

    private function toListMarkdown(mixed \$data, array \$fields): string
    {
        if (!\$this->isList || !is_array(\$data)) {
            return \$this->renderCard(\$data, \$fields);
        }
        \$lines = [];
        foreach (array_slice(\$data, 0, 15) as \$index => \$item) {
            if (!is_array(\$item)) continue;
            \$parts = [];
            foreach (\$fields as \$field) {
                \$v = \$this->getValueByKey(\$item, \$field['key'] ?? '');
                if (\$v === null || \$v === '') continue;
                \$parts[] = "**" . (\$field['label'] ?? \$field['key']) . "**：" . \$this->formatFieldValue(\$v, \$field['format'] ?? 'text');
            }
            \$lines[] = (\$index + 1) . '. ' . implode(' | ', \$parts);
        }
        \$md = implode("\\n", \$lines);
        if (count(\$data) > 15) \$md .= "\\n\\n> 共 " . count(\$data) . " 条，仅显示前 15 条";
        return \$md;
    }

    private function toCustomMarkdown(mixed \$data, array \$fields): string
    {
        if (!\$this->isList || !is_array(\$data)) {
            return \$this->renderCard(\$data, \$fields);
        }
        \$lines = [];
        foreach (array_slice(\$data, 0, 15) as \$item) {
            if (!is_array(\$item)) continue;
            \$parts = [];
            foreach (\$fields as \$field) {
                \$v = \$this->getValueByKey(\$item, \$field['key'] ?? '');
                if (\$v === null || \$v === '') continue;
                \$parts[] = \$this->formatFieldValue(\$v, \$field['format'] ?? 'text');
            }
            \$lines[] = '- ' . implode(' · ', \$parts);
        }
        \$md = implode("\\n", \$lines);
        if (count(\$data) > 15) \$md .= "\\n\\n> 共 " . count(\$data) . " 条，仅显示前 15 条";
        return \$md;
    }

    private function applyTemplate(string \$template, mixed \$data): string
    {
        if (!\$this->isList || !is_array(\$data) || !isset(\$data[0])) {
            return \$this->applyTemplateSingle(\$template, \$data);
        }
        \$results = [];
        foreach (array_slice(\$data, 0, 10) as \$item) {
            \$results[] = \$this->applyTemplateSingle(\$template, \$item);
        }
        return implode("\\n\\n---\\n\\n", \$results);
    }

    private function applyTemplateSingle(string \$template, mixed \$data): string
    {
        if (!is_array(\$data)) {
            return str_replace('{content}', (string) \$data, \$template);
        }
        \$result = \$template;
        foreach (\$data as \$key => \$value) {
            \$ph = '{' . \$key . '}';
            if (str_contains(\$result, \$ph)) {
                \$result = str_replace(\$ph, is_array(\$value) ? json_encode(\$value, JSON_UNESCAPED_UNICODE) : (string) \$value, \$result);
            }
        }
        \$result = preg_replace('/\\{[a-zA-Z0-9_.]+\\}/', '-', \$result);
        return \$result;
    }

    private function formatFieldValue(mixed \$value, string \$format, bool \$forTable = false): string
    {
        \$str = is_array(\$value) ? json_encode(\$value, JSON_UNESCAPED_UNICODE) : (string) \$value;
        if (\$str === '' || \$str === 'null') return '-';
        return match (\$format) {
            'link' => \$forTable ? "[链接]({\$str})" : "[{\$str}]({\$str})",
            'image' => \$forTable ? "![图]({\$str})" : "![图片]({\$str})",
            'code' => "`{\$str}`",
            'bold' => "**{\$str}**",
            default => \$str,
        };
    }

    private function getValueByKey(array \$data, string \$keyPath): mixed
    {
        if (array_key_exists(\$keyPath, \$data)) return \$data[\$keyPath];
        return \$this->extractJsonPath(\$data, \$keyPath);
    }

    private function extractJsonPath(array \$data, string \$path): mixed
    {
        if (\$path === '') return \$data;
        \$keys = explode('.', \$path);
        \$current = \$data;
        foreach (\$keys as \$key) {
            if (is_array(\$current) && array_key_exists(\$key, \$current)) {
                \$current = \$current[\$key];
            } else {
                return null;
            }
        }
        return \$current;
    }

    private function toDefaultMarkdown(mixed \$data): string
    {
        if (is_string(\$data)) return \$data;
        if (!is_array(\$data)) return (string) \$data;
        \$first = reset(\$data);
        if (!is_array(\$first)) {
            \$md = '';
            foreach (\$data as \$k => \$v) {
                \$md .= "- **{\$k}：**" . (is_array(\$v) ? json_encode(\$v, JSON_UNESCAPED_UNICODE) : \$v) . "\\n";
            }
            return \$md;
        }
        \$headers = array_keys(\$first);
        \$md = '| ' . implode(' | ', \$headers) . " |\\n";
        \$md .= '|' . implode('|', array_fill(0, count(\$headers), ' --- ')) . "|\\n";
        foreach (array_slice(\$data, 0, 20) as \$row) {
            if (!is_array(\$row)) continue;
            \$vals = [];
            foreach (\$headers as \$h) {
                \$v = \$row[\$h] ?? '';
                \$vals[] = is_array(\$v) ? json_encode(\$v, JSON_UNESCAPED_UNICODE) : (string) \$v;
            }
            \$md .= '| ' . implode(' | ', \$vals) . " |\\n";
        }
        return \$md;
    }

    private function detectMediaType(string \$url): string
    {
        \$lower = strtolower(\$url);
        if (str_contains(\$lower, '.mp3') || str_contains(\$lower, '.m4a') || str_contains(\$lower, '.flac') || str_contains(\$lower, '.wav') || str_contains(\$lower, '.aac')) return 'audio';
        if (str_contains(\$lower, '.mp4') || str_contains(\$lower, '.m3u8') || str_contains(\$lower, '.avi') || str_contains(\$lower, '.mkv') || str_contains(\$lower, '.mov')) return 'video';
        if (str_contains(\$lower, '.jpg') || str_contains(\$lower, '.jpeg') || str_contains(\$lower, '.png') || str_contains(\$lower, '.gif') || str_contains(\$lower, '.webp') || str_contains(\$lower, '.bmp')) return 'image';
        return 'file';
    }

    private function sendMedia(object \$event, string \$url, string \$type): void
    {
        try {
            if (!filter_var(\$url, FILTER_VALIDATE_URL)) {
                \$event->replyText('无效的媒体链接');
                return;
            }
            match (\$type) {
                'image' => \$event->sendImage(\$url),
                'audio' => \$event->sendFile(\$url, 'audio.mp3'),
                'video' => \$event->sendFile(\$url, 'video.mp4'),
                'file' => \$event->sendFile(\$url, 'file'),
                default => \$event->replyText(\$url),
            };
        } catch (\\Throwable \$e) {
            \$this->logger->warning('Media send failed', ['err' => \$e->getMessage()]);
            \$event->replyText(\$url);
        }
    }

    private function httpRequest(string \$url, string \$method, array \$headers, string \$body): string
    {
        \$ch = curl_init(\$url);
        curl_setopt_array(\$ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => \$this->timeout,
            CURLOPT_CONNECTTIMEOUT => min(10, \$this->timeout),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => \$method,
        ]);
        \$curlHeaders = [];
        foreach (\$headers as \$k => \$v) { \$curlHeaders[] = "{\$k}: {\$v}"; }
        if (!empty(\$curlHeaders)) curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$curlHeaders);
        if (\$method === 'POST' && \$body !== '') {
            curl_setopt(\$ch, CURLOPT_POSTFIELDS, \$body);
        }
        \$resp = curl_exec(\$ch);
        \$code = curl_getinfo(\$ch, CURLINFO_HTTP_CODE);
        // curl_close() is deprecated since PHP 8.0, CurlHandle auto-releases
        if (\$resp === false || \$code < 200 || \$code >= 300) {
            throw new \\RuntimeException("HTTP {\$code}");
        }
        return (string) \$resp;
    }

    private function replaceParams(string \$template, array \$args): string
    {
        if (str_contains(\$template, '{keyword}')) {
            \$template = str_replace('{keyword}', urlencode(\$args[0] ?? ''), \$template);
        }
        foreach (\$args as \$i => \$val) {
            \$template = str_replace('{arg' . (\$i + 1) . '}', urlencode(\$val), \$template);
        }
        \$template = preg_replace('/\\{arg\\d+\\}/', '', \$template);
        \$template = str_replace('{keyword}', '', \$template);
        return \$template;
    }

    private function getCache(string \$key): ?array
    {
        if (\$this->cacheSeconds <= 0) return null;
        \$path = \$this->cacheDir . '/api_cache_' . \$key . '.json';
        if (!is_file(\$path)) return null;
        if ((time() - filemtime(\$path)) > \$this->cacheSeconds) { @unlink(\$path); return null; }
        \$data = json_decode(file_get_contents(\$path), true);
        return is_array(\$data) ? \$data : null;
    }

    private function setCache(string \$key, array \$data): void
    {
        if (\$this->cacheSeconds <= 0) return;
        \$path = \$this->cacheDir . '/api_cache_' . \$key . '.json';
        file_put_contents(\$path, json_encode(\$data), LOCK_EX);
    }

    public function enable(): void { \$this->logger->info('{$className} enabled'); }
    public function disable(): void { \$this->logger->info('{$className} disabled'); }
}
PHP;
}

// ========== 共享工具函数 ==========

function replaceParams(string $template, array $args): string
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

function httpRequest(string $url, string $method, array $headers, string $body, int $timeout): string
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
    foreach ($headers as $k => $v) { $curlHeaders[] = "{$k}: {$v}"; }
    if (!empty($curlHeaders)) curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
    if ($method === 'POST' && $body !== '') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // curl_close() deprecated since PHP 8.0, CurlHandle auto-releases
    if ($resp === false || $code < 200 || $code >= 300) {
        throw new \RuntimeException("HTTP {$code}");
    }
    return (string) $resp;
}

function generatePathSuggestions(array $data, string $prefix = '', int $depth = 0): array
{
    if ($depth > 5) return [];
    $suggestions = [];
    foreach ($data as $key => $value) {
        $path = $prefix === '' ? $key : $prefix . '.' . $key;
        $type = getTypeLabel($value);
        if (is_array($value)) {
            if (isset($value[0]) && is_array($value[0])) {
                $suggestions[] = ['path' => $path, 'type' => 'array[' . count($value) . ']', 'sample' => json_encode(array_slice($value, 0, 1), JSON_UNESCAPED_UNICODE)];
                $suggestions = array_merge($suggestions, generatePathSuggestions($value[0], $path . '.0', $depth + 1));
            } elseif (isset($value[0])) {
                $suggestions[] = ['path' => $path, 'type' => 'array[' . count($value) . ']', 'sample' => json_encode(array_slice($value, 0, 3), JSON_UNESCAPED_UNICODE)];
            } else {
                $suggestions[] = ['path' => $path, 'type' => 'object', 'sample' => json_encode($value, JSON_UNESCAPED_UNICODE)];
                $suggestions = array_merge($suggestions, generatePathSuggestions($value, $path, $depth + 1));
            }
        } else {
            $isUrl = is_string($value) && filter_var($value, FILTER_VALIDATE_URL);
            $suggestions[] = ['path' => $path, 'type' => $type . ($isUrl ? ' (URL)' : ''), 'sample' => (string) $value];
        }
    }
    return $suggestions;
}

function getTypeLabel($value): string
{
    if (is_string($value)) return 'string';
    if (is_int($value)) return 'int';
    if (is_float($value)) return 'float';
    if (is_bool($value)) return 'bool';
    if (is_null($value)) return 'null';
    if (is_array($value)) {
        if (isset($value[0])) return 'array[' . count($value) . ']';
        return 'object';
    }
    return 'unknown';
}

function jsonError(string $msg): void
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $msg]);
}
