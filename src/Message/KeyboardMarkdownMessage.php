<?php
declare(strict_types=1);

namespace QQBot\Message;

/**
 * 带自定义键盘的 Markdown 消息对象
 *
 * 实现 MessageInterface，可直接传入 $event->reply()，
 * 复用框架原有的 msg_id / msg_seq 自动处理逻辑。
 */
class KeyboardMarkdownMessage implements MessageInterface
{
    private string $markdown;
    private array $keyboard;

    public function __construct(string $markdown, array $keyboard)
    {
        $this->markdown = $markdown;
        $this->keyboard = $keyboard;
    }

    public function toArray(): array
    {
        return [
            'msg_type' => 2,               // markdown
            'markdown' => ['content' => $this->markdown],
            'keyboard' => $this->keyboard,
        ];
    }
}