<?php

declare(strict_types=1);

namespace QQBot\Webhook;

use QQBot\Core\Logger;

/**
 * Ed25519 Webhook 签名验证器
 * 实现 QQ 官方机器人要求的 Ed25519 签名校验逻辑
 */
class Validator
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * 验证 Webhook 请求的 Ed25519 签名
     *
     * @param string $botSecret  机器人的 ClientSecret
     * @param string $signature  HTTP Header X-Signature-Ed25519（hex 编码的 64 字节签名）
     * @param string $timestamp  HTTP Header X-Signature-Timestamp
     * @param string $body       HTTP 请求体（原始 JSON 字符串）
     *
     * @return bool 验证是否通过
     */
    public function validate(string $botSecret, string $signature, string $timestamp, string $body): bool
    {
        if (empty($signature) || empty($timestamp)) {
            $this->logger->warning('Missing signature or timestamp headers');
            return false;
        }

        // 1. 根据 botSecret 生成 32 字节 seed（不足则重复自身）
        $seed = $this->generateSeed($botSecret);

        // 2. 通过 seed 生成 Ed25519 密钥对
        $keypair = sodium_crypto_sign_seed_keypair($seed);
        $publicKey = sodium_crypto_sign_publickey($keypair);

        // 3. 将 hex 编码的 signature 解码为二进制
        $sig = sodium_hex2bin($signature);
        if ($sig === false || \strlen($sig) !== 64) {
            $this->logger->warning('Invalid signature format');
            return false;
        }

        // 4. 组合签名消息体：timestamp + body
        $message = $timestamp . $body;

        // 5. 使用 Ed25519 验证签名
        $valid = sodium_crypto_sign_verify_detached($sig, $message, $publicKey);

        if (!$valid) {
            $this->logger->warning('Ed25519 signature verification failed');
        }

        return $valid;
    }

    /**
     * 生成回调地址验证所需的签名响应
     * 用于 OpCode 13（回调地址验证）
     *
     * @param string $botSecret   机器人的 ClientSecret
     * @param string $eventTs     事件时间戳
     * @param string $plainToken  平台下发的 plain_token
     *
     * @return string hex 编码的 64 字节签名
     */
    public function signValidation(string $botSecret, string $eventTs, string $plainToken): string
    {
        // 1. 生成 seed
        $seed = $this->generateSeed($botSecret);

        // 2. 生成密钥对并获取 64 字节私钥
        $keypair = sodium_crypto_sign_seed_keypair($seed);
        $secretKey = sodium_crypto_sign_secretkey($keypair);

        // 3. 签名消息：event_ts + plain_token
        $message = $eventTs . $plainToken;
        $signature = sodium_crypto_sign_detached($message, $secretKey);

        return sodium_bin2hex($signature);
    }

    /**
     * 根据 botSecret 生成 Ed25519 所需的 32 字节 seed
     * 逻辑与官方 Go 示例一致：不足 32 字节则重复自身
     */
    private function generateSeed(string $botSecret): string
    {
        $seed = $botSecret;
        while (\strlen($seed) < 32) {
            $seed .= $botSecret;
        }
        return \substr($seed, 0, 32);
    }
}
