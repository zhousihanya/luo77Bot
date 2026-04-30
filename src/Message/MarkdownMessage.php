<?php

declare(strict_types=1);

namespace QQBot\Message;

/**
 * Markdown 消息
 */
class MarkdownMessage implements MessageInterface
{
    public function __construct(
        private string $markdownContent,
        private ?string $msgId = null,
        private ?string $eventId = null,
        private int $msgSeq = 1,
    ) {
    }

    public function toArray(): array
    {
        $data = [
            'msg_type' => 2,
            'markdown' => ['content' => $this->markdownContent],
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
