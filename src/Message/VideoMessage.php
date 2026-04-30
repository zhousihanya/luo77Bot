<?php

declare(strict_types=1);

namespace QQBot\Message;

/**
 * 视频消息（富媒体）
 * 需要使用 file_info 发送，file_info 可通过 MediaUploader 或 Client::uploadXxxFile 获取
 */
class VideoMessage implements MessageInterface
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
