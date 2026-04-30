<?php

declare(strict_types=1);

/**
 * QQ 官方机器人 Webhook 入口文件
 * 部署到支持 HTTPS 的 Web 服务器，配置回调地址指向此文件
 * 示例回调地址: https://your-domain.com/webhook.php?bot=bot1
 */

require_once __DIR__ . '/../vendor/autoload.php';

use QQBot\Core\Application;
use QQBot\Webhook\HttpHelper;

// 初始化应用
$app = new Application(__DIR__ . '/../config/bots.php');
$app->boot();

$logger = $app->getLogger();

// 1. 获取请求参数中的 bot
$botId = $_GET['bot'] ?? '';

if (empty($botId)) {
    $logger->error('Missing bot parameter');
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['code' => 400, 'message' => 'Missing bot parameter']);
    exit;
}

// 2. 获取对应机器人
$bot = $app->getBotManager()->getBot($botId);

if ($bot === null) {
    $logger->error('Bot not found', ['bot' => $botId]);
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['code' => 404, 'message' => 'Bot not found']);
    exit;
}

// 3. 读取请求头和请求体
$headers = array_change_key_case(HttpHelper::getAllHeaders(), CASE_LOWER);
$body = file_get_contents('php://input');

$logger->debug('Webhook request received', [
    'bot'     => $botId,
    'method'  => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'headers' => array_keys($headers),
]);

// 4. 交给对应机器人的 Handler 处理
$response = $bot->getHandler()->handle($headers, $body);

// 5. 返回响应
http_response_code(200);
header('Content-Type: application/json');
echo json_encode($response);
