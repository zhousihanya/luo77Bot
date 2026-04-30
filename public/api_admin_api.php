<?php

declare(strict_types=1);

// 关闭 HTML 错误显示，确保始终返回 JSON
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// 将所有 PHP 错误转为异常，被下方的 try-catch 捕获
set_error_handler(function ($severity, $message, $file, $line) {
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

// 加载类
$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
} else {
    require_once __DIR__ . '/../src/Core/Logger.php';
    require_once __DIR__ . '/../src/Plugin/CustomApi/ApiConfig.php';
    require_once __DIR__ . '/../src/Plugin/CustomApi/ApiExecutor.php';
}

use QQBot\Core\Logger;
use QQBot\Plugin\CustomApi\ApiConfig;
use QQBot\Plugin\CustomApi\ApiExecutor;

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$logger = new Logger(['path' => __DIR__ . '/../data/logs']);

try {
    $config = new ApiConfig(__DIR__ . '/../data');

    switch ($action) {
        case 'list':    handleList($config); break;
        case 'get':     handleGet($config); break;
        case 'save':    handleSave($config); break;
        case 'delete':  handleDelete($config); break;
        case 'toggle':  handleToggle($config); break;
        case 'test':    handleTest($config, $logger); break;
        case 'preview': handlePreview($logger); break;
        case 'parseJson': handleParseJson($logger); break;
        default:        jsonError('Unknown action: ' . $action); break;
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
    ], JSON_THROW_ON_ERROR);
}

// ========== CRUD ==========

function handleList(ApiConfig $config): void
{
    echo json_encode(['success' => true, 'apis' => $config->getAll()], JSON_THROW_ON_ERROR);
}

function handleGet(ApiConfig $config): void
{
    $api = $config->get($_POST['id'] ?? '');
    echo json_encode(['success' => $api !== null, 'api' => $api], JSON_THROW_ON_ERROR);
}

function handleSave(ApiConfig $config): void
{
    $id = trim($_POST['id'] ?? '');
    if (empty($id)) {
        $id = ApiConfig::generateId();
    }

    $headers = parseJsonField($_POST['headers'] ?? '');
    $detailApiHeaders = parseJsonField($_POST['detailApiHeaders'] ?? '');
    $fieldMapping = parseJsonField($_POST['fieldMapping'] ?? '');
    if (!is_array($fieldMapping)) $fieldMapping = [];
    $detailApiFieldMapping = parseJsonField($_POST['detailApiFieldMapping'] ?? '');
    if (!is_array($detailApiFieldMapping)) $detailApiFieldMapping = [];

    $api = [
        'id' => $id,
        'name' => trim($_POST['name'] ?? ''),
        'command' => trim($_POST['command'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'enabled' => ($_POST['enabled'] ?? '1') === '1',
        'url' => trim($_POST['url'] ?? ''),
        'method' => strtoupper($_POST['method'] ?? 'GET'),
        'headers' => $headers,
        'body' => trim($_POST['body'] ?? ''),
        'responseMode' => $_POST['responseMode'] ?? 'json_data',
        'jsonPath' => trim($_POST['jsonPath'] ?? ''),
        'isList' => ($_POST['isList'] ?? '0') === '1',
        'listKey' => trim($_POST['listKey'] ?? ''),
        'fieldMapping' => $fieldMapping,
        'markdownLayout' => $_POST['markdownLayout'] ?? 'card',
        'markdownTemplate' => trim($_POST['markdownTemplate'] ?? ''),
        'mediaUrlPath' => trim($_POST['mediaUrlPath'] ?? ''),
        'detailApiEnabled' => ($_POST['detailApiEnabled'] ?? '0') === '1',
        'detailApiUrl' => trim($_POST['detailApiUrl'] ?? ''),
        'detailApiMethod' => strtoupper($_POST['detailApiMethod'] ?? 'GET'),
        'detailApiHeaders' => $detailApiHeaders,
        'detailApiBody' => trim($_POST['detailApiBody'] ?? ''),
        'detailApiJsonPath' => trim($_POST['detailApiJsonPath'] ?? ''),
        'detailApiFieldMapping' => $detailApiFieldMapping,
        'cacheSeconds' => (int) ($_POST['cacheSeconds'] ?? 0),
        'timeout' => (int) ($_POST['timeout'] ?? 20),
        'updatedAt' => date('Y-m-d H:i:s'),
    ];

    $existing = $config->get($id);
    $api['createdAt'] = $existing['createdAt'] ?? date('Y-m-d H:i:s');

    if ($api['name'] === '') { jsonError('接口名称不能为空'); return; }
    if ($api['command'] === '') { jsonError('触发指令不能为空'); return; }
    if ($api['url'] === '') { jsonError('请求 URL 不能为空'); return; }

    $config->save($api);
    echo json_encode(['success' => true, 'id' => $id], JSON_THROW_ON_ERROR);
}

function handleDelete(ApiConfig $config): void
{
    $config->delete($_POST['id'] ?? '');
    echo json_encode(['success' => true], JSON_THROW_ON_ERROR);
}

function handleToggle(ApiConfig $config): void
{
    $enabled = $config->toggle($_POST['id'] ?? '');
    echo json_encode(['success' => true, 'enabled' => $enabled], JSON_THROW_ON_ERROR);
}

// ========== Test / Preview ==========

function handleTest(ApiConfig $config, Logger $logger): void
{
    $id = $_POST['id'] ?? '';
    $testArgs = explode(' ', trim($_POST['args'] ?? ''));

    $api = $config->get($id);
    if ($api === null) {
        jsonError('接口不存在');
        return;
    }

    $executor = new ApiExecutor($logger);
    $result = $executor->test($api, $testArgs);

    if (!$result['success']) {
        echo json_encode(['success' => false, 'message' => $result['message']], JSON_THROW_ON_ERROR);
        return;
    }

    $suggestions = [];
    if ($result['isJson'] && is_array($result['data'])) {
        $suggestions = generatePathSuggestions($result['data']);
    }

    echo json_encode([
        'success' => true,
        'isJson' => $result['isJson'],
        'data' => $result['data'],
        'raw' => $result['raw'],
        'suggestions' => $suggestions,
    ], JSON_THROW_ON_ERROR);
}

function handlePreview(Logger $logger): void
{
    $apiData = $_POST['api'] ?? '';
    $testArgs = explode(' ', trim($_POST['args'] ?? ''));

    if ($apiData === '') {
        jsonError('缺少 api 参数');
        return;
    }

    $api = json_decode($apiData, true);
    if (!is_array($api)) {
        jsonError('无效的 api 配置数据，JSON 解析失败: ' . json_last_error_msg());
        return;
    }

    $executor = new ApiExecutor($logger);

    try {
        $result = $executor->execute($api, $testArgs);
        echo json_encode([
            'success' => true,
            'type' => $result['type'],
            'content' => $result['content'],
            'mediaUrl' => $result['mediaUrl'] ?? '',
            'raw' => $result['raw'] ?? null,
        ], JSON_THROW_ON_ERROR);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_THROW_ON_ERROR);
    }
}

function handleParseJson(Logger $logger): void
{
    $jsonStr = $_POST['json'] ?? '';
    $path = $_POST['path'] ?? '';

    $data = json_decode($jsonStr, true);
    if (!is_array($data)) {
        jsonError('无效 JSON');
        return;
    }

    $executor = new ApiExecutor($logger);
    $value = $executor->extractJsonPath($data, $path);

    $sample = null;
    if (is_array($value) && isset($value[0])) {
        $sample = $value[0];
    }

    echo json_encode([
        'success' => true,
        'path' => $path,
        'value' => $value,
        'isArray' => is_array($value) && isset($value[0]),
        'sample' => $sample,
        'type' => getTypeLabel($value),
    ], JSON_THROW_ON_ERROR);
}

// ========== Helper ==========

function parseJsonField(string $input): array
{
    if ($input === '') return [];
    $decoded = json_decode($input, true);
    return is_array($decoded) ? $decoded : [];
}

function generatePathSuggestions(array $data, string $prefix = '', int $depth = 0): array
{
    if ($depth > 5) return [];
    $suggestions = [];

    foreach ($data as $key => $value) {
        $path = $prefix === '' ? $key : $prefix . '.' . $key;
        $type = getTypeLabel($value);

        if (is_array($value)) {
            if (isset($value[0]) && is_array($value[0])) {
                $suggestions[] = [
                    'path' => $path,
                    'type' => 'array[' . count($value) . ']',
                    'sample' => json_encode(array_slice($value, 0, 1), JSON_UNESCAPED_UNICODE),
                ];
                $suggestions = array_merge($suggestions, generatePathSuggestions($value[0], $path . '.0', $depth + 1));
            } elseif (isset($value[0])) {
                $suggestions[] = [
                    'path' => $path,
                    'type' => 'array[' . count($value) . ']',
                    'sample' => json_encode(array_slice($value, 0, 3), JSON_UNESCAPED_UNICODE),
                ];
            } else {
                $suggestions[] = [
                    'path' => $path,
                    'type' => 'object',
                    'sample' => json_encode($value, JSON_UNESCAPED_UNICODE),
                ];
                $suggestions = array_merge($suggestions, generatePathSuggestions($value, $path, $depth + 1));
            }
        } else {
            $isUrl = is_string($value) && filter_var($value, FILTER_VALIDATE_URL);
            $suggestions[] = [
                'path' => $path,
                'type' => $type . ($isUrl ? ' (URL)' : ''),
                'sample' => (string) $value,
            ];
        }
    }

    return $suggestions;
}

function getTypeLabel($value): string
{
    if (is_string($value)) return 'string';
    if (is_int($value)) return 'int';
    if (is_float($value)) return 'float';
    if (is_bool($value)) return 'bool';
    if (is_null($value)) return 'null';
    if (is_array($value)) {
        if (isset($value[0])) return 'array[' . count($value) . ']';
        return 'object';
    }
    return 'unknown';
}

function jsonError(string $msg): void
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $msg], JSON_THROW_ON_ERROR);
}
