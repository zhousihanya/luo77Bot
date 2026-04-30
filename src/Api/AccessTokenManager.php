<?php

declare(strict_types=1);

namespace QQBot\Api;

use QQBot\Core\Logger;

/**
 * AccessToken 管理器
 * 负责获取、缓存和自动刷新 QQ 机器人的 AccessToken
 */
class AccessTokenManager
{
    private string $appId;
    private string $clientSecret;
    private bool $sandbox;
    private Logger $logger;

    private ?string $accessToken = null;
    private int $expiresAt = 0;

    private const TOKEN_URL = 'https://bots.qq.com/app/getAppAccessToken';

    public function __construct(string $appId, string $clientSecret, bool $sandbox, Logger $logger)
    {
        $this->appId        = $appId;
        $this->clientSecret = $clientSecret;
        $this->sandbox      = $sandbox;
        $this->logger       = $logger;
    }

    /**
     * 获取有效 AccessToken
     */
    public function getToken(): string
    {
        // 如果 token 即将过期（提前 120 秒刷新），则重新获取
        if ($this->accessToken === null || time() >= $this->expiresAt - 120) {
            $this->refreshToken();
        }

        return $this->accessToken ?? '';
    }

    /**
     * 强制刷新 AccessToken
     */
    public function refreshToken(): void
    {
        $this->logger->debug('Refreshing access token', ['app_id' => $this->appId]);

        $payload = json_encode([
            'appId'        => $this->appId,
            'clientSecret'   => $this->clientSecret,
        ]);

        $ch = curl_init(self::TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            $this->logger->error('Failed to get access token', [
                'app_id'    => $this->appId,
                'http_code' => $httpCode,
                'error'     => $curlError,
            ]);
            throw new \RuntimeException('Failed to get access token for app: ' . $this->appId);
        }

        $data = json_decode($response, true);
        if (empty($data['access_token'])) {
            $this->logger->error('Invalid access token response', ['response' => $response]);
            throw new \RuntimeException('Invalid access token response');
        }

        $this->accessToken = $data['access_token'];
        $expiresIn       = (int) ($data['expires_in'] ?? 7200);
        $this->expiresAt  = time() + $expiresIn;

        $this->logger->info('Access token refreshed', [
            'app_id'     => $this->appId,
            'expires_in' => $expiresIn,
        ]);
    }
}
