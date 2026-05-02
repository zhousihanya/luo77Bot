<?php
declare(strict_types=1);

/**
 * 网页客服 API
 *
 * 接口：
 *   GET  ?action=list&token=密钥              → 获取对话列表
 *   GET  ?action=messages&openid=xxx&token=密钥  → 获取某个对话的消息
 *   POST ?action=reply&token=密钥               → 客服回复
 *        Body: openid=xxx&content=消息内容&msg_type=0
 */

require_once __DIR__ . '/../vendor/autoload.php';

use QQBot\Api\AccessTokenManager;
use QQBot\Api\Client;
use QQBot\Core\Logger;
use QQBot\Service\ChatBridge;

// ---------- 配置 ----------
$configFile = __DIR__ . '/../config/bots.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'Config not found']);
    exit;
}
$config = require $configFile;

$token = $_GET['token'] ?? ($_SERVER['HTTP_X_CHAT_TOKEN'] ?? '');
$chatSecret = $config['chat_secret'] ?? ($config['push_secret'] ?? 'change_me');
if (!hash_equals($chatSecret, $token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$botId = $_GET['bot_id'] ?? ($config['default'] ?? 'default');
$bot = $config['bots'][$botId] ?? null;
if (!$bot) {
    http_response_code(400);
    echo json_encode(['error' => 'Bot not found']);
    exit;
}

$logger = new Logger($config['log'] ?? ['level' => 'info', 'path' => __DIR__ . '/../logs', 'daily' => true, 'console' => false]);
$tokenManager = new AccessTokenManager($bot['app_id'], $bot['client_secret'], $bot['sandbox'] ?? false, $logger);
$client = new Client($tokenManager, $logger, $bot['sandbox'] ?? false);
$bridge = new ChatBridge();

// ---------- 路由 ----------
$action = $_GET['action'] ?? '';
header('Content-Type: application/json');

function countUnread(array $messages): int
{
    if (empty($messages)) return 0;
    $last = end($messages);
    return $last['type'] === 'incoming' ? 1 : 0;
}

switch ($action) {
    case 'list':
        $convs = $bridge->getConversations();
        $result = [];
        foreach ($convs as $conv) {
            $lastMsg = end($conv['messages']) ?: null;
            $result[] = [
                'openid'       => $conv['openid'],
                'scene'        => $conv['scene'] ?? 'c2c',
                'last_active'  => date('Y-m-d H:i:s', $conv['last_active'] ?? 0),
                'unread'       => countUnread($conv['messages']),
                'last_message' => $lastMsg ? [
                    'type'    => $lastMsg['type'],
                    'content' => mb_substr($lastMsg['content'], 0, 50),
                    'time'    => $lastMsg['time'],
                ] : null,
            ];
        }
        echo json_encode(['conversations' => $result], JSON_UNESCAPED_UNICODE);
        break;

    case 'messages':
        $openid = $_GET['openid'] ?? '';
        if (empty($openid)) {
            echo json_encode(['error' => 'openid required']);
            exit;
        }
        $messages = $bridge->getMessages($openid);
        echo json_encode(['openid' => $openid, 'messages' => $messages], JSON_UNESCAPED_UNICODE);
        break;

    case 'reply':
        $openid = $_POST['openid'] ?? ($_GET['openid'] ?? '');
        $content = $_POST['content'] ?? ($_GET['content'] ?? '');
        $msgType = (int) ($_POST['msg_type'] ?? ($_GET['msg_type'] ?? 0));

        if (empty($openid) || empty($content)) {
            echo json_encode(['error' => 'openid and content required']);
            exit;
        }

        // 查找有效 msg_id（被动回复）
        $msgId = $bridge->getValidMsgId($openid);
        if (empty($msgId)) {
            echo json_encode([
                'error' => '无法回复：该对话已超过被动回复时间窗口（单聊60分钟/群聊5分钟），请引导用户重新在QQ上发送消息激活对话。',
                'code'  => 'MSG_ID_EXPIRED'
            ]);
            exit;
        }

        // 构建消息
        $payload = [];
        if ($msgType === 2) {
            $payload['msg_type'] = 2;
            $payload['markdown'] = ['content' => $content];
        } else {
            $payload['msg_type'] = 0;
            $payload['content'] = $content;
        }
        $payload['msg_id'] = $msgId;

        try {
            $meta = $bridge->getConversationMeta($openid);
            $scene = $meta['scene'] ?? 'c2c';
            $groupOpenid = $meta['group_openid'] ?? null;

            if ($scene === 'group' && $groupOpenid) {
                $client->sendGroupMessage($groupOpenid, $payload);
            } else {
                $client->sendC2CMessage($openid, $payload);
            }

            $bridge->saveOutgoing($openid, $content, $scene, $groupOpenid);
            echo json_encode(['success' => true, 'msg_id' => $msgId], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            $logger->error('Reply failed', ['openid' => $openid, 'error' => $e->getMessage()]);
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action'], JSON_UNESCAPED_UNICODE);
}