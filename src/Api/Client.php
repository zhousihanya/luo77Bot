<?php

declare(strict_types=1);

namespace QQBot\Api;

use QQBot\Core\Logger;

/**
 * QQ 开放平台 API 客户端
 * 封装 HTTP 请求，统一处理鉴权、重试、错误日志
 */
class Client
{
    private AccessTokenManager $tokenManager;
    private Logger $logger;
    private bool $sandbox;

    private const BASE_URL         = 'https://api.sgroup.qq.com';
    private const SANDBOX_BASE_URL = 'https://sandbox.api.sgroup.qq.com';

    public function __construct(AccessTokenManager $tokenManager, Logger $logger, bool $sandbox = false)
    {
        $this->tokenManager = $tokenManager;
        $this->logger       = $logger;
        $this->sandbox      = $sandbox;
    }

    /**
     * 发送 HTTP 请求
     *
     * @param string $method HTTP 方法
     * @param string $path   API 路径，如 /v2/users/{openid}/messages
     * @param array  $data   请求体数据
     *
     * @return array 响应数据
     */
    public function request(string $method, string $path, array $data = []): array
    {
        $token = $this->tokenManager->getToken();
        $url   = $this->getBaseUrl() . $path;

        $headers = [
            'Content-Type: application/json',
            'Authorization: QQBot ' . $token,
        ];

        $this->logger->debug('API request', ['method' => $method, 'url' => $url]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $this->logger->error('API request failed', ['url' => $url, 'error' => $error]);
            throw new \RuntimeException('API request failed: ' . $error);
        }

        $result = json_decode($response, true) ?? [];

        if ($httpCode >= 400) {
            $this->logger->error('API error response', [
                'url'        => $url,
                'http_code'  => $httpCode,
                'response'   => $result,
            ]);
            throw new \RuntimeException('API error: ' . ($result['message'] ?? 'Unknown error') . ' (code: ' . ($result['code'] ?? $httpCode) . ')');
        }

        $this->logger->debug('API response', ['url' => $url, 'http_code' => $httpCode]);

        return $result;
    }

    /**
     * 发送单聊消息
     *
     * @param string $openid  用户 openid
     * @param array  $message 消息内容数组
     *
     * @return array 包含 id 和 timestamp
     */
    public function sendC2CMessage(string $openid, array $message): array
    {
        return $this->request('POST', "/v2/users/{$openid}/messages", $message);
    }

    /**
     * 发送群聊消息
     *
     * @param string $groupOpenid 群聊 openid
     * @param array  $message     消息内容数组
     *
     * @return array 包含 id 和 timestamp
     */
    public function sendGroupMessage(string $groupOpenid, array $message): array
    {
        return $this->request('POST', "/v2/groups/{$groupOpenid}/messages", $message);
    }

    /**
     * 上传单聊富媒体文件
     *
     * @param string      $openid   用户 openid
     * @param int         $fileType 1图片 2视频 3语音 4文件
     * @param string      $url      媒体资源 URL
     * @param bool        $sendMsg  是否直接发送（true 占用主动频次）
     * @param string|null $fileName 指定文件名（含后缀，如 song.mp3）
     *
     * @return array 包含 file_uuid, file_info, ttl 等
     */
    public function uploadC2CFile(string $openid, int $fileType, string $url, bool $sendMsg = false, ?string $fileName = null): array
    {
        $data = [
            'file_type'    => $fileType,
            'url'          => $url,
            'srv_send_msg' => $sendMsg,
        ];
        if ($fileName !== null) {
            $data['file_name'] = $fileName;
        }
        return $this->request('POST', "/v2/users/{$openid}/files", $data);
    }

    /**
     * 上传群聊富媒体文件
     *
     * @param string      $groupOpenid 群聊 openid
     * @param int         $fileType    1图片 2视频 3语音 4文件
     * @param string      $url         媒体资源 URL
     * @param bool        $sendMsg     是否直接发送
     * @param string|null $fileName    指定文件名（含后缀，如 song.mp3）
     *
     * @return array 包含 file_uuid, file_info, ttl 等
     */
    public function uploadGroupFile(string $groupOpenid, int $fileType, string $url, bool $sendMsg = false, ?string $fileName = null): array
    {
        $data = [
            'file_type'    => $fileType,
            'url'          => $url,
            'srv_send_msg' => $sendMsg,
        ];
        if ($fileName !== null) {
            $data['file_name'] = $fileName;
        }
        return $this->request('POST', "/v2/groups/{$groupOpenid}/files", $data);
    }

    private function getBaseUrl(): string
    {
        return $this->sandbox ? self::SANDBOX_BASE_URL : self::BASE_URL;
    }
}
