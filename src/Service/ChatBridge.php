<?php
declare(strict_types=1);

namespace QQBot\Service;

/**
 * 消息桥接存储（网页客服对话）
 *
 * 零侵入框架：纯新增文件
 * 存储位置：data/chat_bridge/
 */
class ChatBridge
{
    private string $dataDir;
    private int $c2cTtl;
    private int $groupTtl;

    public function __construct(?string $baseDir = null, int $c2cTtl = 3600, int $groupTtl = 300)
    {
        $this->dataDir = ($baseDir ?? __DIR__ . '/../../data') . '/chat_bridge';
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
        $this->c2cTtl = $c2cTtl;
        $this->groupTtl = $groupTtl;
    }

    /**
     * 存储收到的用户消息
     */
    public function saveIncoming(string $openid, string $msgId, string $content, string $scene = 'c2c', ?string $groupOpenid = null, ?string $senderName = null): void
    {
        $conv = $this->getConversation($openid);
        $conv['messages'][] = [
            'type'         => 'incoming',
            'msg_id'       => $msgId,
            'content'      => $content,
            'scene'        => $scene,
            'group_openid' => $groupOpenid,
            'sender_name'  => $senderName,
            'time'         => date('Y-m-d H:i:s'),
            'timestamp'    => time(),
        ];
        $conv['last_active'] = time();
        $conv['scene'] = $scene;
        $conv['group_openid'] = $groupOpenid;
        $this->saveConversation($openid, $conv);
    }

    /**
     * 存储客服发出的回复
     */
    public function saveOutgoing(string $openid, string $content, string $scene = 'c2c', ?string $groupOpenid = null): void
    {
        $conv = $this->getConversation($openid);
        $conv['messages'][] = [
            'type'         => 'outgoing',
            'content'      => $content,
            'scene'        => $scene,
            'group_openid' => $groupOpenid,
            'time'         => date('Y-m-d H:i:s'),
            'timestamp'    => time(),
        ];
        $conv['last_active'] = time();
        $this->saveConversation($openid, $conv);
    }

    /**
     * 获取可用于被动回复的最新有效 msg_id
     */
    public function getValidMsgId(string $openid): ?string
    {
        $conv = $this->getConversation($openid);
        $ttl = ($conv['scene'] ?? 'c2c') === 'group' ? $this->groupTtl : $this->c2cTtl;

        $messages = array_reverse($conv['messages'] ?? []);
        foreach ($messages as $msg) {
            if ($msg['type'] === 'incoming' && !empty($msg['msg_id'])) {
                if (time() - ($msg['timestamp'] ?? 0) < $ttl) {
                    return $msg['msg_id'];
                }
            }
        }
        return null;
    }

    /**
     * 获取对话元信息
     */
    public function getConversationMeta(string $openid): array
    {
        $conv = $this->getConversation($openid);
        return [
            'scene'        => $conv['scene'] ?? 'c2c',
            'group_openid' => $conv['group_openid'] ?? null,
            'last_active'  => $conv['last_active'] ?? 0,
        ];
    }

    /**
     * 获取所有活跃对话
     */
    public function getConversations(): array
    {
        $files = glob($this->dataDir . '/*.json');
        $convs = [];
        foreach ($files as $file) {
            $openid = basename($file, '.json');
            $conv = $this->getConversation($openid);
            $conv['openid'] = $openid;
            $convs[] = $conv;
        }
        usort($convs, fn($a, $b) => ($b['last_active'] ?? 0) <=> ($a['last_active'] ?? 0));
        return $convs;
    }

    /**
     * 获取某个对话的消息记录
     */
    public function getMessages(string $openid): array
    {
        return $this->getConversation($openid)['messages'] ?? [];
    }

    /**
     * 清理过期对话（超过24小时无消息）
     */
    public function cleanup(int $maxAge = 86400): int
    {
        $files = glob($this->dataDir . '/*.json');
        $removed = 0;
        foreach ($files as $file) {
            $conv = json_decode(file_get_contents($file), true) ?? [];
            if ((time() - ($conv['last_active'] ?? 0)) > $maxAge) {
                unlink($file);
                $removed++;
            }
        }
        return $removed;
    }

    private function getConversation(string $openid): array
    {
        $file = $this->dataDir . '/' . $this->sanitize($openid) . '.json';
        if (!file_exists($file)) {
            return [
                'messages'    => [],
                'last_active' => 0,
                'scene'       => 'c2c',
            ];
        }
        return json_decode(file_get_contents($file), true) ?? [];
    }

    private function saveConversation(string $openid, array $conv): void
    {
        $file = $this->dataDir . '/' . $this->sanitize($openid) . '.json';
        file_put_contents($file, json_encode($conv, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    private function sanitize(string $openid): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $openid);
    }
}