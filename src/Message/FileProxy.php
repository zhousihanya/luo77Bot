<?php

declare(strict_types=1);

namespace QQBot\Message;

use QQBot\Core\Logger;

/**
 * 文件代理
 * 将远程文件下载到本地临时目录，生成带正确文件名的本地 URL 供上传使用
 */
class FileProxy
{
    private string $tempDir;
    private string $publicUrl;
    private Logger $logger;

    /**
     * @param string $tempDir   本地临时目录（Web 可访问的绝对路径）
     * @param string $publicUrl 临时目录对应的公网 URL 前缀
     * @param Logger $logger
     */
    public function __construct(string $tempDir, string $publicUrl, Logger $logger)
    {
        $this->tempDir   = rtrim($tempDir, '/');
        $this->publicUrl = rtrim($publicUrl, '/');
        $this->logger    = $logger;

        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    /**
     * 下载远程文件到本地，返回带文件名的本地 URL
     *
     * @param string $remoteUrl 远程文件 URL
     * @param string $fileName  期望的文件名（含后缀，如 "song.mp3"）
     *
     * @return string 本地可访问的 URL
     */
    public function prepare(string $remoteUrl, string $fileName): string
    {
        // 安全的文件名
        $safeName = $this->sanitizeFileName($fileName);

        // 本地路径
        $localPath = $this->tempDir . '/' . $safeName;

        // 如果文件已存在且不超过 5 分钟，直接复用
        if (is_file($localPath) && (time() - filemtime($localPath)) < 300) {
            $this->logger->debug('FileProxy cache hit', ['file' => $safeName]);
            return $this->publicUrl . '/' . $safeName;
        }

        // 下载远程文件
        $this->logger->info('FileProxy downloading', ['remote' => substr($remoteUrl, 0, 60), 'file' => $safeName]);

        $ch = curl_init($remoteUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
        ]);

        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($content === false || $httpCode !== 200) {
            throw new \RuntimeException("Download failed: HTTP {$httpCode}");
        }

        // 写入本地文件
        file_put_contents($localPath, $content, LOCK_EX);
        $this->logger->info('FileProxy saved', ['file' => $safeName, 'size' => strlen($content)]);

        // 自动清理过期文件（随机概率触发，避免每次请求都扫目录）
        if (random_int(1, 10) === 1) {
            $this->cleanup();
        }

        return $this->publicUrl . '/' . $safeName;
    }

    /**
     * 清理过期临时文件（超过 10 分钟）
     */
    public function cleanup(): void
    {
        $expireTime = time() - 600;
        $files = glob($this->tempDir . '/*');

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $expireTime) {
                @unlink($file);
            }
        }

        $this->logger->debug('FileProxy cleanup done');
    }

    /**
     * 安全化文件名
     */
    private function sanitizeFileName(string $name): string
    {
        // 保留中文、英文、数字、空格、横线、下划线、点号
        $name = preg_replace('/[^\x{4e00}-\x{9fa5}a-zA-Z0-9\s\._-]/u', '_', $name);
        return trim($name, '._ ');
    }
}
