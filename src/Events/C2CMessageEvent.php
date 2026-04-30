<?php

declare(strict_types=1);

namespace QQBot\Events;

use QQBot\Api\Client;
use QQBot\Core\Logger;
use QQBot\Message\AudioMessage;
use QQBot\Message\FileMessage;
use QQBot\Message\ImageMessage;
use QQBot\Message\MarkdownMessage;
use QQBot\Message\MediaUploader;
use QQBot\Message\MessageInterface;
use QQBot\Message\TextMessage;
use QQBot\Message\VideoMessage;

/**
 * C2C 单聊消息事件
 */
class C2CMessageEvent implements EventInterface
{
    private bool $propagationStopped = false;
    private int $nextSeq = 1;
    private ?MediaUploader $uploader = null;

    public function __construct(
        private array $payload,
        private Client $client,
        private Logger $logger,
    ) {
    }

    public function setNextSeq(int $seq): void
    {
        $this->nextSeq = $seq;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getEventType(): string
    {
        return 'C2C_MESSAGE_CREATE';
    }

    public function getEventId(): string
    {
        return $this->payload['id'] ?? '';
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * 获取发送者 openid
     */
    public function getUserOpenid(): string
    {
        return $this->payload['author']['user_openid'] ?? '';
    }

    /**
     * 获取消息内容（纯文本）
     */
    public function getContent(): string
    {
        return $this->payload['content'] ?? '';
    }

    /**
     * 获取消息时间戳
     */
    public function getTimestamp(): string
    {
        return $this->payload['timestamp'] ?? '';
    }

    /**
     * 获取附件列表
     */
    public function getAttachments(): array
    {
        return $this->payload['attachments'] ?? [];
    }

    /**
     * 快速回复消息（通用）
     */
    public function reply(MessageInterface $message, ?int $msgSeq = null): array
    {
        $data = $message->toArray();

        // 自动填充被动消息所需的 msg_id
        if (!isset($data['msg_id']) && !isset($data['event_id'])) {
            $data['msg_id'] = $this->getEventId();
        }

        // msg_seq 处理：传入参数优先，其次 nextSeq，最后 message 默认值
        if ($msgSeq !== null) {
            $data['msg_seq'] = $msgSeq;
        } elseif (!isset($data['msg_seq'])) {
            $data['msg_seq'] = $this->nextSeq;
        }

        return $this->client->sendC2CMessage($this->getUserOpenid(), $data);
    }

    /* ================================================================ */
    /* 以下为快捷回复方法                                               */
    /* ================================================================ */

    /**
     * 回复文本消息
     */
    public function replyText(string $content, ?int $msgSeq = null): array
    {
        return $this->reply(new TextMessage($content), $msgSeq);
    }

    /**
     * 回复 Markdown 消息
     */
    public function replyMarkdown(string $markdown, ?int $msgSeq = null): array
    {
        return $this->reply(new MarkdownMessage($markdown), $msgSeq);
    }

    /**
     * 回复图片消息（需先上传获取 file_info）
     */
    public function replyImage(string $fileInfo, ?int $msgSeq = null): array
    {
        return $this->reply(new ImageMessage($fileInfo), $msgSeq);
    }

    /**
     * 回复视频消息（需先上传获取 file_info）
     */
    public function replyVideo(string $fileInfo, ?int $msgSeq = null): array
    {
        return $this->reply(new VideoMessage($fileInfo), $msgSeq);
    }

    /**
     * 回复语音消息（需先上传获取 file_info）
     */
    public function replyAudio(string $fileInfo, ?int $msgSeq = null): array
    {
        return $this->reply(new AudioMessage($fileInfo), $msgSeq);
    }

    /**
     * 回复文件消息（需先上传获取 file_info）
     */
    public function replyFile(string $fileInfo, ?int $msgSeq = null): array
    {
        return $this->reply(new FileMessage($fileInfo), $msgSeq);
    }

    /* ================================================================ */
    /* 以下为媒体上传快捷方法                                           */
    /* ================================================================ */

    /**
     * 上传图片到单聊并返回 file_info
     */
    public function uploadImage(string $url): string
    {
        return $this->getUploader()->uploadC2CImage($this->getUserOpenid(), $url);
    }

    /**
     * 上传视频到单聊并返回 file_info
     */
    public function uploadVideo(string $url): string
    {
        return $this->getUploader()->uploadC2CVideo($this->getUserOpenid(), $url);
    }

    /**
     * 上传语音到单聊并返回 file_info
     */
    public function uploadAudio(string $url): string
    {
        return $this->getUploader()->uploadC2CAudio($this->getUserOpenid(), $url);
    }

    /**
     * 上传文件到单聊并返回 file_info
     *
     * @param string      $url      文件 URL
     * @param string|null $fileName 指定文件名（含后缀，如 song.mp3）
     */
    public function uploadFile(string $url, ?string $fileName = null): string
    {
        return $this->getUploader()->uploadC2CFile($this->getUserOpenid(), $url, $fileName);
    }

    /**
     * 一键发送图片：自动上传 + 发送（被动回复）
     */
    public function sendImage(string $url, ?int $msgSeq = null): array
    {
        $fileInfo = $this->uploadImage($url);
        return $this->replyImage($fileInfo, $msgSeq);
    }

    /**
     * 一键发送视频：自动上传 + 发送（被动回复）
     */
    public function sendVideo(string $url, ?int $msgSeq = null): array
    {
        $fileInfo = $this->uploadVideo($url);
        return $this->replyVideo($fileInfo, $msgSeq);
    }

    /**
     * 一键发送语音：自动上传 + 发送（被动回复）
     */
    public function sendAudio(string $url, ?int $msgSeq = null): array
    {
        $fileInfo = $this->uploadAudio($url);
        return $this->replyAudio($fileInfo, $msgSeq);
    }

    /**
     * 一键发送文件：自动上传 + 发送（被动回复）
     *
     * @param string      $url      文件 URL
     * @param string|null $fileName 指定文件名（含后缀，如 song.mp3）
     */
    public function sendFile(string $url, ?string $fileName = null, ?int $msgSeq = null): array
    {
        $fileInfo = $this->uploadFile($url, $fileName);
        return $this->replyFile($fileInfo, $msgSeq);
    }

    /* ================================================================ */

    private function getUploader(): MediaUploader
    {
        if ($this->uploader === null) {
            $this->uploader = new MediaUploader($this->client, $this->logger);
        }
        return $this->uploader;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
