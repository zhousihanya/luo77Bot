<?php

declare(strict_types=1);

namespace QQBot\Webhook;

/**
 * HTTP 辅助函数
 * 提供跨服务器环境的兼容支持
 */
class HttpHelper
{
    /**
     * 获取所有 HTTP 请求头（兼容 Nginx / Apache / CLI）
     *
     * @return array<string, string>
     */
    public static function getAllHeaders(): array
    {
        // Apache 环境
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        // Nginx / PHP-FPM / 其他环境
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($name, 5));
                $headers[$headerName] = $value;
            } elseif (in_array($name, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headers[str_replace('_', '-', $name)] = $value;
            }
        }

        return $headers;
    }
}
