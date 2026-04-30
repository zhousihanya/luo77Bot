<?php

declare(strict_types=1);

namespace QQBot\Core;

/**
 * 事件分发器
 * 支持监听与触发事件
 */
class EventDispatcher
{
    /** @var array<string, array<callable>> */
    private array $listeners = [];

    /**
     * 注册事件监听器
     *
     * @param string   $eventName 事件类名或标识符
     * @param callable $listener  监听器回调
     * @param int      $priority  优先级，数字越大越先执行
     */
    public function on(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventName][] = ['listener' => $listener, 'priority' => $priority];
        // 按优先级降序排列
        usort($this->listeners[$eventName], fn(array $a, array $b): int => $b['priority'] <=> $a['priority']);
    }

    /**
     * 分发事件
     *
     * @param object $event 事件对象
     */
    public function dispatch(object $event): void
    {
        $eventName = get_class($event);

        if (empty($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $item) {
            $item['listener']($event);

            // 如果事件设置了停止传播，则中断
            if (method_exists($event, 'isPropagationStopped') && $event->isPropagationStopped()) {
                break;
            }
        }
    }
}
