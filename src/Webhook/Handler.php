<?php

declare(strict_types=1);

namespace QQBot\Webhook;

use QQBot\Api\Client;
use QQBot\Core\EventDispatcher;
use QQBot\Core\Logger;
use QQBot\Events\C2CMessageEvent;
use QQBot\Events\GroupAtMessageEvent;

/**
 * Webhook 事件处理器
 * 负责解析 Payload、分发事件、处理回调验证
 */
class Handler
{
    private Logger $logger;
    private EventDispatcher $dispatcher;
    private Validator $validator;
    private Client $client;
    private string $botSecret;
    private bool $verifySign;

    /** @var array<string, int> 记录每个 msg_id 的 msg_seq，用于去重 */
    private array $msgSeqMap = [];

    public function __construct(
        Logger $logger,
        EventDispatcher $dispatcher,
        Validator $validator,
        Client $client,
        string $botSecret,
        bool $verifySign = true,
    ) {
        $this->logger      = $logger;
        $this->dispatcher  = $dispatcher;
        $this->validator   = $validator;
        $this->client      = $client;
        $this->botSecret   = $botSecret;
        $this->verifySign  = $verifySign;
    }

    public function getDispatcher(): EventDispatcher
    {
        return $this->dispatcher;
    }

    /**
     * 处理 Webhook HTTP 请求
     *
     * @param array  $headers HTTP 请求头（小写键名）
     * @param string $body    HTTP 请求体
     *
     * @return array 响应数组，将被 json_encode 后返回给平台
     */
    public function handle(array $headers, string $body): array
    {
        // 1. 先解析 Payload 获取 op 码
        $payload = json_decode($body, true);
        if (!is_array($payload)) {
            $this->logger->error('Invalid JSON payload');
            http_response_code(400);
            return ['code' => 400, 'message' => 'Invalid JSON'];
        }

        $op = (int) ($payload['op'] ?? 0);
        $t  = $payload['t'] ?? '';
        $d  = $payload['d'] ?? [];

        $this->logger->debug('Webhook received', ['op' => $op, 'type' => $t]);

        // 2. OpCode 13（回调地址验证）没有 Ed25519 签名头，直接处理
        if ($op === 13) {
            return $this->handleValidation($d);
        }

        // 3. 其他情况（如事件推送 OpCode 0）验证 Ed25519 签名
        if ($this->verifySign) {
            $signature = $headers['x-signature-ed25519'] ?? '';
            $timestamp = $headers['x-signature-timestamp'] ?? '';

            if (!$this->validator->validate($this->botSecret, $signature, $timestamp, $body)) {
                $this->logger->error('Webhook signature validation failed');
                http_response_code(401);
                return ['code' => 401, 'message' => 'Invalid signature'];
            }
        }

        // 4. 根据 OpCode 处理
        return match ($op) {
            0  => $this->handleDispatch($t, $d),      // 事件推送
            default => $this->ack(),                   // 其他情况返回 ACK
        };
    }

    /**
     * 获取下一个 msg_seq，用于被动消息去重
     */
    private function getNextMsgSeq(string $msgId): int
    {
        if ($msgId === '') {
            return 1;
        }
        if (!isset($this->msgSeqMap[$msgId])) {
            $this->msgSeqMap[$msgId] = 0;
        }
        $this->msgSeqMap[$msgId]++;
        return $this->msgSeqMap[$msgId];
    }

    /**
     * 处理事件推送 (OpCode 0 Dispatch)
     */
    private function handleDispatch(string $eventType, array $data): array
    {
        $msgId = $data['id'] ?? '';
        $nextSeq = $this->getNextMsgSeq($msgId);

        switch ($eventType) {
            case 'C2C_MESSAGE_CREATE':
                $event = new C2CMessageEvent($data, $this->client, $this->logger);
                $this->logger->info('C2C message received', [
                    'user'    => $event->getUserOpenid(),
                    'content' => mb_substr($event->getContent(), 0, 100),
                ]);
                break;

            case 'GROUP_AT_MESSAGE_CREATE':
                $event = new GroupAtMessageEvent($data, $this->client, $this->logger);
                $this->logger->info('Group AT message received', [
                    'group'   => $event->getGroupOpenid(),
                    'member'  => $event->getMemberOpenid(),
                    'content' => mb_substr($event->getContent(), 0, 100),
                ]);
                break;

            default:
                // 不处理其他类型事件（如频道相关）
                $this->logger->debug('Unhandled event type', ['type' => $eventType]);
                return $this->ack();
        }

        // 将 msg_seq 注入事件对象，供 reply 使用
        $event->setNextSeq($nextSeq);

        // 分发事件给插件
        $this->dispatcher->dispatch($event);

        return $this->ack();
    }

    /**
     * 处理回调地址验证 (OpCode 13)
     * 平台会发送 plain_token 和 event_ts，需要返回签名
     */
    private function handleValidation(array $data): array
    {
        $plainToken = $data['plain_token'] ?? '';
        $eventTs    = $data['event_ts'] ?? '';

        $this->logger->info('Handling webhook validation', ['event_ts' => $eventTs]);

        $signature = $this->validator->signValidation($this->botSecret, $eventTs, $plainToken);

        return [
            'plain_token' => $plainToken,
            'signature'   => $signature,
        ];
    }

    /**
     * 返回 HTTP Callback ACK (OpCode 12)
     */
    private function ack(): array
    {
        return [];
    }
}
