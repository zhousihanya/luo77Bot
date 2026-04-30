<?php

declare(strict_types=1);

namespace QQBot\Message;

use QQBot\Core\Logger;

/**
 * 图片缩放器
 * 下载远程图片，缩小尺寸后保存到本地，返回本地 URL
 */
class ImageResizer
{
    private string $tempDir;
    private string $publicUrl;
    private Logger $logger;

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
     * 下载远程图片并缩放到指定尺寸
     *
     * @param string $remoteUrl 远程图片 URL
     * @param int    $width     目标宽度
     * @param int    $height    目标高度
     *
     * @return string 本地可访问的 URL
     */
    public function resize(string $remoteUrl, int $width, int $height): string
    {
        $hash = md5($remoteUrl . "_{$width}x{$height}");
        $safeName = $hash . '.jpg';
        $localPath = $this->tempDir . '/' . $safeName;

        // 缓存命中（5分钟内）
        if (is_file($localPath) && (time() - filemtime($localPath)) < 300) {
            return $this->publicUrl . '/' . $safeName;
        }

        $this->logger->debug('ImageResizer downloading', ['url' => substr($remoteUrl, 0, 60)]);

        // 下载远程图片
        $content = $this->download($remoteUrl);
        if ($content === null) {
            throw new \RuntimeException('Failed to download image');
        }

        // 缩放
        $this->resizeAndSave($content, $localPath, $width, $height);

        $this->logger->debug('ImageResizer saved', ['path' => $localPath]);

        // 随机清理旧文件
        if (random_int(1, 10) === 1) {
            $this->cleanup();
        }

        return $this->publicUrl . '/' . $safeName;
    }

    /**
     * 下载远程文件
     */
    private function download(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
        ]);

        $content = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($content === false || $code !== 200) {
            return null;
        }

        return $content;
    }

    /**
     * 缩放并保存
     */
    private function resizeAndSave(string $content, string $savePath, int $w, int $h): void
    {
        $src = @imagecreatefromstring($content);
        if ($src === false) {
            throw new \RuntimeException('Cannot decode image');
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);

        $dst = imagecreatetruecolor($w, $h);

        // 白色背景（JPEG 不支持透明）
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);

        // 等比缩放并居中
        $ratio = min($w / $srcW, $h / $srcH);
        $newW = (int) ($srcW * $ratio);
        $newH = (int) ($srcH * $ratio);
        $offsetX = (int) (($w - $newW) / 2);
        $offsetY = (int) (($h - $newH) / 2);

        imagecopyresampled($dst, $src, $offsetX, $offsetY, 0, 0, $newW, $newH, $srcW, $srcH);

        imagejpeg($dst, $savePath, 90);

        imagedestroy($src);
        imagedestroy($dst);
    }

    /**
     * 清理过期临时文件
     */
    private function cleanup(): void
    {
        $expireTime = time() - 600;
        foreach (glob($this->tempDir . '/*') as $file) {
            if (is_file($file) && filemtime($file) < $expireTime) {
                @unlink($file);
            }
        }
    }
}
