<?php

declare(strict_types=1);

namespace QQBot\Message;

/**
 * 文件消息（富媒体 file_type=4）
 * 用于发送任意格式文件（mp3, zip, pdf 等）
 * 需要先通过 MediaUploader 上传获取 file_info
 */
class FileMessage implements MessageInterface
{
    public function __construct(
        private string $fileInfo,
        private ?string $msgId = null,
        private ?string $eventId = null,
        private int $msgSeq = 1,
    ) {
    }

    public function toArray(): array
    {
        $data = [
            'msg_type' => 7,
            'media'    => ['file_info' => $this->fileInfo],
            'msg_seq'  => $this->msgSeq,
        ];

        if ($this->msgId !== null) {
            $data['msg_id'] = $this->msgId;
        }

        if ($this->eventId !== null) {
            $data['event_id'] = $this->eventId;
        }

        return $data;
    }
}
