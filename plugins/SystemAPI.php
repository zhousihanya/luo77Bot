<?php

declare(strict_types=1);

namespace QQBot\Plugin;

use QQBot\Core\EventDispatcher;
use QQBot\Core\Logger;
use QQBot\Events\C2CMessageEvent;
use QQBot\Events\GroupAtMessageEvent;

/**
 * 系统信息插件 - 获取框架信息
 * 指令：框架信息
 */
class SystemAPI implements PluginInterface
{
    private Logger $logger;
    
    // API地址
    private const API_URL = 'https://plugin.18years.ink/api.php';

    public function getName(): string { return 'system_api'; }
    public function getDisplayName(): string { return '系统信息'; }
    public function getDescription(): string { return '获取框架信息，发送「框架信息」即可'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getAuthor(): string { return 'QQBot Framework'; }
    public function getIcon(): ?string { return 'ℹ️'; }
    public function getTags(): array { return ['系统', '信息', '框架']; }

    public function register(EventDispatcher $dispatcher, Logger $logger): void
    {
        $this->logger = $logger;

        $dispatcher->on(C2CMessageEvent::class, function (C2CMessageEvent $event): void {
            $this->process($event, $event->getContent());
        });
        
        $dispatcher->on(GroupAtMessageEvent::class, function (GroupAtMessageEvent $event): void {
            $this->process($event, $event->getContent());
        });
    }

    private function process(object $event, string $content): void
    {
        $content = trim($content);
        
        if ($content === '框架信息') {
            $this->fetchAndOutput($event);
        }
    }
    
    private function fetchAndOutput(object $event): void
    {
        try {
            $this->logger->info('Fetching framework info');
            
            $response = $this->httpGet(self::API_URL);
            $data = json_decode($response, true);
            
            if (empty($data)) {
                throw new \RuntimeException('获取框架信息失败');
            }
            
            $output = $this->formatOutput($data);
            $event->replyMarkdown($output);
            $this->logger->info('Framework info sent');
            
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch framework info', ['error' => $e->getMessage()]);
            $event->replyText("获取框架信息失败：{$e->getMessage()}");
        }
    }
    
    private function formatOutput(array $data): string
    {
        $name = $data['name'] ?? '未知';
        $introduction = $data['introduction'] ?? '';
        $version = $data['version'] ?? '未知';
        $author = $data['author'] ?? '未知';
        $releaseDate = $data['release_date'] ?? '未知';
        $changelog = $data['changelog'] ?? [];
        $lastUpdate = $data['last_update'] ?? [];
        
        $output = "## {$name}\n\n";
        $output .= "> {$introduction}\n\n";
        
        $output .= "### 📋 基本信息\n\n";
        $output .= "| 项目 | 信息 |\n";
        $output .= "|------|------|\n";
        $output .= "| 版本 | `{$version}` |\n";
        $output .= "| 作者 | {$author} |\n";
        $output .= "| 发布日期 | {$releaseDate} |\n";
        
        if (!empty($lastUpdate)) {
            $updateDate = $lastUpdate['date'] ?? '';
            $updateDesc = $lastUpdate['description'] ?? '';
            $output .= "| 最后更新 | {$updateDate} |\n";
        }
        
        $output .= "\n### 📝 更新日志\n\n";
        
        foreach ($changelog as $log) {
            $date = $log['date'] ?? '';
            $content = $log['content'] ?? '';
            $output .= "** {$date}**\n";
            $output .= "- {$content}\n\n";
        }
        
        $output .= "---\n";
        $output .= "💡 数据来源：api.18years.ink\n";
        $output .= "🔧 发送「框架信息」可再次查看";
        
        return $output;
    }
    
    private function httpGet(string $url): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'QQBot-Plugin/1.0'
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($error) {
            throw new \RuntimeException("网络请求失败: {$error}");
        }
        if ($httpCode !== 200) {
            throw new \RuntimeException("HTTP请求失败，状态码: {$httpCode}");
        }
        
        return $response;
    }

    public function enable(): void
    {
        $this->logger->info('SystemAPI plugin enabled');
    }

    public function disable(): void
    {
        $this->logger->info('SystemAPI plugin disabled');
    }
}