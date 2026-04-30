<?php

declare(strict_types=1);

namespace QQBot\Plugin;

use QQBot\Core\EventDispatcher;
use QQBot\Core\Logger;
use QQBot\Events\C2CMessageEvent;
use QQBot\Events\GroupAtMessageEvent;

/**
 * 示例插件：多媒体消息自动回复
 * 演示文本 / Markdown / 图片 / 视频 / 音频 的发送
 */
class ExamplePlugin implements PluginInterface
{
    private Logger $logger;

    public function getName(): string
    {
        return 'example';
    }

    public function getDisplayName(): string
    {
        return '示例插件';
    }

    public function getDescription(): string
    {
        return '支持文本/Markdown/图片/视频/音频的自动回复演示';
    }

    public function getVersion(): string
    {
        return '1.1.0';
    }

    public function getAuthor(): string
    {
        return 'QQBot Framework';
    }

    public function getIcon(): ?string
    {
        return '💬';
    }

    public function getTags(): array
    {
        return ['demo', '多媒体', '自动回复'];
    }

    public function register(EventDispatcher $dispatcher, Logger $logger): void
    {
        $this->logger = $logger;

        /* ========== 单聊消息处理 ========== */
        $dispatcher->on(C2CMessageEvent::class, function (C2CMessageEvent $event): void {
            $content = trim($event->getContent());
            $this->logger->info('ExamplePlugin handling C2C', ['content' => $content]);

            try {
                match (true) {
                    str_contains($content, '你好') => $event->replyText('你好呀！我是机器人助手~'),

                    str_contains($content, 'md') || str_contains($content, 'Markdown')
                        => $event->replyMarkdown("# 欢迎使用\n> 这是 **Markdown** 消息\n- 支持列表\n- 支持排版"),

                    str_contains($content, '图片')
                        => $event->sendImage('https://example.com/image.jpg'),

                    str_contains($content, '视频')
                        => $event->sendVideo('https://v26-cold.douyinvod.com/5c825684e16cd5f04e50c3a586cbb4b3/69f314ab/video/tos/cn/tos-cn-ve-15c001-alinc2/o4gEDivCaMaIc52IZzoQQkPgkAEwAIxUt7AiB/?a=1128&ch=0&cr=0&dr=0&cd=0|0|0|0&cv=1&br=2470&bt=2470&cs=0&ds=3&ft=BaXAWVVywIiRZm8Zmo~pK7pswApDWOV_vrKOyER3to0g3cI&mime_type=video_mp4&qs=0&rc=NDU6aWdnOjk5N2c0MzQ7Z0BpamttZGo5cnQ6MzMzNGkzM0AvYjUtMy9fX2MxYi5jY2E0YSNtYTBeMmRrajBhLS1kLS9zcw==&btag=c0010e00090000&cquery=100y&dy_q=1777534604&feature_id=59cb2766d89ae6284516c6a254e9fb61&l=202604301536445287CAB07365E8C164BA'),

                    str_contains($content, '语音') || str_contains($content, '音频')
                        => $event->sendAudio('https://example.com/audio.mp3'),

                    str_contains($content, '帮助') => $event->replyText(
                        "支持关键词：\n" .
                        "- 你好：文本回复\n" .
                        "- md / Markdown：Markdown 消息\n" .
                        "- 图片：发送图片\n" .
                        "- 视频：发送视频\n" .
                        "- 语音 / 音频：发送语音\n" .
                        "- 帮助：查看此消息"
                    ),

                    default => null,
                };
            } catch (\Throwable $e) {
                $this->logger->error('Failed to handle C2C message', ['error' => $e->getMessage()]);
            }
        });

        /* ========== 群聊 @ 消息处理 ========== */
        $dispatcher->on(GroupAtMessageEvent::class, function (GroupAtMessageEvent $event): void {
            $content = trim($event->getContent());
            $this->logger->info('ExamplePlugin handling Group AT', ['content' => $content]);

            try {
                match (true) {
                    str_contains($content, '你好') => $event->replyText('大家好！我是本群机器人助手~'),

                    str_contains($content, 'md') || str_contains($content, 'Markdown')
                        => $event->replyMarkdown("# 群公告\n> 本机器人支持 **多媒体消息**\n1. 图片\n2. 视频\n3. 语音"),

                    str_contains($content, '图片')
                        => $event->sendImage('https://example.com/image.jpg'),

                    str_contains($content, '视频')
                        => $event->sendVideo('https://example.com/video.mp4'),

                    str_contains($content, '语音') || str_contains($content, '音频')
                        => $event->sendAudio('https://example.com/audio.mp3'),

                    str_contains($content, '帮助') => $event->replyText(
                        "群里 @我 发送以下关键词：\n" .
                        "- 你好 / 帮助\n" .
                        "- md / Markdown\n" .
                        "- 图片 / 视频 / 语音"
                    ),

                    default => $event->replyText('收到你的消息啦！发送「帮助」查看支持的指令~'),
                };
            } catch (\Throwable $e) {
                $this->logger->error('Failed to handle Group AT message', ['error' => $e->getMessage()]);
            }
        });
    }

    public function enable(): void
    {
        $this->logger->info('ExamplePlugin enabled (v1.1.0)');
    }

    public function disable(): void
    {
        $this->logger->info('ExamplePlugin disabled');
    }
}
