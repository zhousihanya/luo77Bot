<?php

declare(strict_types=1);

namespace QQBot\Plugin;

use QQBot\Core\EventDispatcher;
use QQBot\Core\Logger;
use QQBot\Events\C2CMessageEvent;
use QQBot\Events\GroupAtMessageEvent;
use QQBot\Plugin\CustomApi\ApiConfig;
use QQBot\Plugin\CustomApi\ApiExecutor;

/**
 * 自定义 API 接口插件 v3.0
 * 
 * 全新可视化配置：
 *   - 响应模式：直接文本 / 直接图片 / 直接音频 / 直接视频 / 直接 Markdown / JSON 数据
 *   - JSON 模式：fieldMapping + markdownLayout 可视化排版
 *   - 列表支持：isList + listKey 处理数组数据
 *   - 详情接口：列表+详情双接口模式
 *   - 零代码：全部通过管理页面 UI 配置
 */
class CustomApiPlugin implements PluginInterface
{
    private Logger $logger;
    private ApiConfig $config;
    private ApiExecutor $executor;

    public function getName(): string { return 'custom_api'; }
    public function getDisplayName(): string { return '自定义接口'; }
    public function getDescription(): string { return '可视化自定义 HTTP 接口，支持图片/音频/视频/JSON 解析/Markdown 排版'; }
    public function getVersion(): string { return '3.0.0'; }
    public function getAuthor(): string { return 'QQBot Framework'; }
    public function getIcon(): ?string { return '**[API]**'; }
    public function getTags(): array { return ['自定义', '接口', '可视化', '零代码']; }

    public function register(EventDispatcher $dispatcher, Logger $logger): void
    {
        $this->logger = $logger;
        $this->config = new ApiConfig(__DIR__ . '/../data');
        $this->executor = new ApiExecutor($logger);

        $dispatcher->on(C2CMessageEvent::class, function (C2CMessageEvent $event): void {
            $this->handle($event, $event->getContent());
        });
        $dispatcher->on(GroupAtMessageEvent::class, function (GroupAtMessageEvent $event): void {
            $this->handle($event, $event->getContent());
        });

        $this->logger->info('CustomApiPlugin v3.0 loaded', ['apis' => count($this->config->getAll())]);
    }

    private function handle(object $event, string $content): void
    {
        $content = trim($content);

        // 帮助
        if ($content === 'api帮助' || $content === '接口帮助') {
            $this->sendHelp($event);
            return;
        }

        // 测试接口（管理员功能）
        if (str_starts_with($content, 'api测试 ')) {
            $this->testApi($event, trim(substr($content, 7)));
            return;
        }

        // 匹配接口指令：「指令 参数1 参数2 ...」
        $parts = explode(' ', $content, 2);
        $cmd = $parts[0];
        $argsStr = $parts[1] ?? '';

        $api = $this->config->getByCommand($cmd);
        if ($api === null) return;

        // 解析多参数（空格分隔）
        $args = $argsStr !== '' ? explode(' ', $argsStr) : [];

        try {
            $this->executeAndSend($event, $api, $args);
        } catch (\Throwable $e) {
            $this->logger->error('API execute failed', ['cmd' => $cmd, 'err' => $e->getMessage()]);
            $event->replyText('接口调用失败：' . $e->getMessage());
        }
    }

    /**
     * 执行接口并发送结果
     */
    private function executeAndSend(object $event, array $api, array $args): void
    {
        $result = $this->executor->execute($api, $args);
        $type = $result['type'];
        $content = $result['content'];
        $mediaUrl = $result['mediaUrl'] ?? '';

        $this->logger->info('API result', ['type' => $type, 'hasMedia' => $mediaUrl !== '']);

        match ($type) {
            'text' => $event->replyText($content),
            'markdown' => $event->replyMarkdown($content),
            'image' => $this->sendMedia($event, $mediaUrl !== '' ? $mediaUrl : $content, 'image', $api),
            'audio' => $this->sendMedia($event, $mediaUrl !== '' ? $mediaUrl : $content, 'audio', $api),
            'video' => $this->sendMedia($event, $mediaUrl !== '' ? $mediaUrl : $content, 'video', $api),
            'file' => $this->sendMedia($event, $mediaUrl !== '' ? $mediaUrl : $content, 'file', $api),
            default => $event->replyText($content),
        };
    }

    /**
     * 发送媒体消息
     */
    private function sendMedia(object $event, string $url, string $type, array $api): void
    {
        try {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $event->replyText('无效的媒体链接：' . substr($url, 0, 100));
                return;
            }

            match ($type) {
                'image' => $event->sendImage($url),
                'audio' => $event->sendFile($url, ($api['name'] ?? 'audio') . '.mp3'),
                'video' => $event->sendFile($url, ($api['name'] ?? 'video') . '.mp4'),
                'file' => $event->sendFile($url, $api['name'] ?? 'file'),
                default => $event->replyText($url),
            };
        } catch (\Throwable $e) {
            $this->logger->warning('Media send failed', ['type' => $type, 'err' => $e->getMessage()]);
            $event->replyText($url);
        }
    }

    /**
     * 测试接口（管理员功能）
     */
    private function testApi(object $event, string $input): void
    {
        $parts = explode(' ', $input, 2);
        $cmd = $parts[0];
        $argsStr = $parts[1] ?? '';
        $args = $argsStr !== '' ? explode(' ', $argsStr) : [];

        $api = $this->config->getByCommand($cmd);
        if ($api === null) {
            $event->replyText('接口「' . $cmd . '」不存在');
            return;
        }

        $event->replyText('正在测试「' . $api['name'] . '」（' . ($api['responseMode'] ?? 'json_data') . '）...');

        try {
            $result = $this->executor->execute($api, $args);
            $type = $result['type'];
            $content = $result['content'];

            if ($type === 'markdown') {
                $event->replyMarkdown("**测试结果**\n\n" . $content);
            } elseif ($type === 'text') {
                $preview = substr($content, 0, 800);
                if (strlen($content) > 800) $preview .= "\n... (truncated)";
                $event->replyMarkdown("**测试结果**\n\n```\n{$preview}\n```");
            } elseif (in_array($type, ['image', 'audio', 'video', 'file'])) {
                $mediaUrl = $result['mediaUrl'] ?? $content;
                $event->replyMarkdown("**测试结果**\n类型：`{$type}`\n链接：{$mediaUrl}");
                // 同时发送媒体
                $this->sendMedia($event, $mediaUrl, $type, $api);
            } else {
                $event->replyText('类型：' . $type . '\n' . substr($content, 0, 500));
            }
        } catch (\Throwable $e) {
            $event->replyText('测试失败：' . $e->getMessage());
        }
    }

    /**
     * 发送帮助
     */
    private function sendHelp(object $event): void
    {
        $apis = $this->config->getEnabled();
        if (empty($apis)) {
            $event->replyText("暂无配置接口\n管理页面：api_admin.php");
            return;
        }

        $md = "**自定义接口列表**\n\n";
        foreach ($apis as $api) {
            $modeLabel = match ($api['responseMode'] ?? 'json_data') {
                'direct_text' => '📝',
                'direct_markdown' => '📝',
                'direct_image' => '🖼️',
                'direct_audio' => '🎵',
                'direct_video' => '🎬',
                'direct_file' => '📎',
                'json_data' => '📊',
                default => '📊',
            };
            $md .= "{$modeLabel} `{$api['command']}`：{$api['name']}\n";
            if (!empty($api['description'])) $md .= "  > {$api['description']}\n";
            $example = $this->buildExample($api);
            $md .= "  > 例：`{$example}`\n";
        }
        $md .= "\n> 发送「指令 参数1 参数2」调用\n> api_admin.php 管理接口";
        $event->replyMarkdown($md);
    }

    /**
     * 构建使用示例
     */
    private function buildExample(array $api): string
    {
        $url = $api['url'] ?? '';
        preg_match_all('/\{(arg\d+|keyword)\}/', $url, $matches);
        $placeholders = $matches[1] ?? [];

        $example = $api['command'];
        foreach ($placeholders as $ph) {
            $example .= ' ' . ($ph === 'keyword' ? '关键词' : '参数' . preg_replace('/[^0-9]/', '', $ph));
        }

        return $example;
    }

    public function enable(): void { $this->logger->info('CustomApiPlugin v3.0 enabled'); }
    public function disable(): void { $this->logger->info('CustomApiPlugin disabled'); }
}
