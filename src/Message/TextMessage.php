<?php

declare(strict_types=1);

namespace QQBot\Message;

/**
 * 文本消息
 */
class TextMessage implements MessageInterface
{
    public function __construct(
        private string $content,
        private ?string $msgId = null,
        private ?string $eventId = null,
        private int $msgSeq = 1,
    ) {
    }

    public function toArray(): array
    {
        $data = [
            'content'  => $this->content,
            'msg_type' => 0,
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
