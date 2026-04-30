<?php

declare(strict_types=1);

/**
 * 管理后台 API
 * 提供插件管理和机器人配置的 REST 接口
 */

require_once __DIR__ . '/../vendor/autoload.php';

use QQBot\Core\Application;

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// 简单的 token 校验（生产环境应使用更强的鉴权）
$apiToken = $_SERVER['HTTP_X_API_TOKEN'] ?? $_POST['api_token'] ?? '';
$configToken = $_ENV['QQBOT_ADMIN_TOKEN'] ?? 'changeme';

if ($apiToken !== $configToken && $action !== 'status') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid API token']);
    exit;
}

try {
    $app = new Application(__DIR__ . '/../config/bots.php');
    $app->boot();

    match ($action) {
        // 获取所有插件列表
        'plugins' => handlePlugins($app),

        // 切换插件开关
        'toggle_plugin' => handleTogglePlugin($app),

        // 获取所有机器人配置
        'bots' => handleBots($app),

        // 保存机器人配置
        'save_bot' => handleSaveBot($app),

        // 删除机器人
        'delete_bot' => handleDeleteBot($app),

        // 获取系统状态
        'status' => handleStatus($app),

        default => (function () {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
        })(),
    };
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/* ================================================================ */

function handlePlugins(Application $app): void
{
    $manager = $app->getPluginManager();
    $registry = $app->getPluginRegistry();

    $plugins = [];
    foreach ($manager->getAllInfos() as $name => $info) {
        $state = $registry->getAllStates()[$name] ?? [];
        $plugins[] = [
            'name'        => $info->name,
            'displayName' => $info->displayName,
            'version'     => $info->version,
            'description' => $info->description,
            'author'      => $info->author,
            'icon'        => $info->icon,
            'tags'        => $info->tags,
            'className'   => $info->className,
            'enabled'     => $registry->isEnabled($name),
            'installed'   => $state['installed'] ?? true,
            'installedAt' => $state['installedAt'] ?? '',
        ];
    }

    echo json_encode(['success' => true, 'plugins' => $plugins]);
}

function handleTogglePlugin(Application $app): void
{
    $name = $_POST['name'] ?? '';
    $enabled = filter_var($_POST['enabled'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Plugin name required']);
        return;
    }

    if ($enabled) {
        $app->getPluginManager()->enable($name);
    } else {
        $app->getPluginManager()->disable($name);
    }

    echo json_encode(['success' => true, 'enabled' => $enabled]);
}

function handleBots(Application $app): void
{
    $config = $app->getConfig();
    $bots = [];

    foreach ($config->getBotsConfig() as $botId => $botConfig) {
        $bots[] = [
            'id'            => $botId,
            'app_id'        => $botConfig['app_id'] ?? '',
            'client_secret' => maskSecret($botConfig['client_secret'] ?? ''),
            'nickname'      => $botConfig['nickname'] ?? $botId,
            'sandbox'       => $botConfig['sandbox'] ?? false,
            'intents'       => $botConfig['intents'] ?? (1 << 25),
        ];
    }

    echo json_encode([
        'success'   => true,
        'bots'      => $bots,
        'default'   => $config->getDefaultBotId(),
        'webhookUrl'=> 'https://' . ($_SERVER['HTTP_HOST'] ?? 'your-domain.com') . '/webhook.php?bot=',
    ]);
}

function handleSaveBot(Application $app): void
{
    $botId = $_POST['id'] ?? '';
    $appId = $_POST['app_id'] ?? '';
    $secret = $_POST['client_secret'] ?? '';
    $nickname = $_POST['nickname'] ?? '';
    $sandbox = filter_var($_POST['sandbox'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
    $isDefault = filter_var($_POST['is_default'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

    if (empty($botId) || empty($appId)) {
        echo json_encode(['success' => false, 'message' => 'Bot ID and App ID are required']);
        return;
    }

    // 读取现有配置
    $configPath = __DIR__ . '/../config/bots.php';
    $config = require $configPath;

    // 更新或创建机器人配置
    $config['bots'][$botId] = [
        'app_id'        => $appId,
        'client_secret' => $secret,
        'nickname'      => $nickname,
        'sandbox'       => $sandbox,
        'intents'       => 1 << 25,
    ];

    if ($isDefault || empty($config['default'])) {
        $config['default'] = $botId;
    }

    // 写回配置文件
    $phpCode = "<?php\n\nreturn " . arrayToPhp($config) . ";\n";
    file_put_contents($configPath, $phpCode, LOCK_EX);

    echo json_encode(['success' => true]);
}

function handleDeleteBot(Application $app): void
{
    $botId = $_POST['id'] ?? '';

    if (empty($botId)) {
        echo json_encode(['success' => false, 'message' => 'Bot ID required']);
        return;
    }

    $configPath = __DIR__ . '/../config/bots.php';
    $config = require $configPath;

    unset($config['bots'][$botId]);

    // 如果删除的是默认机器人，重新设置默认
    if (($config['default'] ?? '') === $botId) {
        $config['default'] = array_key_first($config['bots'] ?? []);
    }

    $phpCode = "<?php\n\nreturn " . arrayToPhp($config) . ";\n";
    file_put_contents($configPath, $phpCode, LOCK_EX);

    echo json_encode(['success' => true]);
}

function handleStatus(Application $app): void
{
    $botCount = count($app->getBotManager()->getAllBots());
    $pluginCount = count($app->getPluginManager()->getAllPlugins());
    $enabledPlugins = 0;
    foreach ($app->getPluginManager()->getAllInfos() as $info) {
        if ($info->enabled) $enabledPlugins++;
    }

    echo json_encode([
        'success' => true,
        'bots' => $botCount,
        'plugins' => ['total' => $pluginCount, 'enabled' => $enabledPlugins],
        'php_version' => PHP_VERSION,
        'sodium' => extension_loaded('sodium'),
        'time' => date('Y-m-d H:i:s'),
    ]);
}

/* ================================================================ */

function maskSecret(string $secret): string
{
    if (strlen($secret) <= 8) return '****';
    return substr($secret, 0, 4) . str_repeat('*', strlen($secret) - 8) . substr($secret, -4);
}

/**
 * 将数组转为 PHP 代码字符串
 */
function arrayToPhp(array $array, int $indent = 0): string
{
    $spaces = str_repeat('    ', $indent);
    $inner = str_repeat('    ', $indent + 1);

    if (empty($array)) {
        return '[]';
    }

    $parts = [];
    foreach ($array as $key => $value) {
        $keyStr = is_string($key) ? "'" . addslashes($key) . "'" : $key;

        if (is_array($value)) {
            $parts[] = "{$inner}{$keyStr} => " . arrayToPhp($value, $indent + 1);
        } elseif (is_bool($value)) {
            $parts[] = "{$inner}{$keyStr} => " . ($value ? 'true' : 'false');
        } elseif (is_int($value) || is_float($value)) {
            $parts[] = "{$inner}{$keyStr} => {$value}";
        } elseif ($value === null) {
            $parts[] = "{$inner}{$keyStr} => null";
        } else {
            $parts[] = "{$inner}{$keyStr} => '" . addslashes((string)$value) . "'";
        }
    }

    return "[\n" . implode(",\n", $parts) . ",\n{$spaces}]";
}
