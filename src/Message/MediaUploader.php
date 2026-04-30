<?php

declare(strict_types=1);

namespace QQBot\Message;

use QQBot\Api\Client;
use QQBot\Core\Logger;

/**
 * 媒体上传助手
 * 封装富媒体文件的上传流程，自动获取 file_info
 */
class MediaUploader
{
    /** 文件类型常量 */
    public const TYPE_IMAGE = 1;
    public const TYPE_VIDEO = 2;
    public const TYPE_AUDIO = 3;
    public const TYPE_FILE  = 4;

    private Client $client;
    private Logger $logger;

    public function __construct(Client $client, Logger $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * 上传单聊图片并返回 file_info
     *
     * @param string $openid 用户 openid
     * @param string $url    图片 URL（png/jpg）
     *
     * @return string file_info
     */
    public function uploadC2CImage(string $openid, string $url): string
    {
        return $this->extractFileInfo(
            $this->client->uploadC2CFile($openid, self::TYPE_IMAGE, $url)
        );
    }

    /**
     * 上传单聊视频并返回 file_info
     *
     * @param string $openid 用户 openid
     * @param string $url    视频 URL（mp4）
     *
     * @return string file_info
     */
    public function uploadC2CVideo(string $openid, string $url): string
    {
        return $this->extractFileInfo(
            $this->client->uploadC2CFile($openid, self::TYPE_VIDEO, $url)
        );
    }

    /**
     * 上传单聊语音并返回 file_info
     *
     * @param string $openid 用户 openid
     * @param string $url    语音 URL（silk/wav/mp3/flac）
     *
     * @return string file_info
     */
    public function uploadC2CAudio(string $openid, string $url): string
    {
        return $this->extractFileInfo(
            $this->client->uploadC2CFile($openid, self::TYPE_AUDIO, $url)
        );
    }

    /**
     * 上传群聊图片并返回 file_info
     *
     * @param string $groupOpenid 群聊 openid
     * @param string $url         图片 URL（png/jpg）
     *
     * @return string file_info
     */
    public function uploadGroupImage(string $groupOpenid, string $url): string
    {
        return $this->extractFileInfo(
            $this->client->uploadGroupFile($groupOpenid, self::TYPE_IMAGE, $url)
        );
    }

    /**
     * 上传群聊视频并返回 file_info
     *
     * @param string $groupOpenid 群聊 openid
     * @param string $url         视频 URL（mp4）
     *
     * @return string file_info
     */
    public function uploadGroupVideo(string $groupOpenid, string $url): string
    {
        return $this->extractFileInfo(
            $this->client->uploadGroupFile($groupOpenid, self::TYPE_VIDEO, $url)
        );
    }

    /**
     * 上传群聊语音并返回 file_info
     *
     * @param string $groupOpenid 群聊 openid
     * @param string $url         语音 URL（silk/wav/mp3/flac）
     *
     * @return string file_info
     */
    public function uploadGroupAudio(string $groupOpenid, string $url): string
    {
        return $this->extractFileInfo(
            $this->client->uploadGroupFile($groupOpenid, self::TYPE_AUDIO, $url)
        );
    }

    /**
     * 上传单聊文件并返回 file_info
     *
     * @param string      $openid   用户 openid
     * @param string      $url      文件 URL（任意格式）
     * @param string|null $fileName 指定文件名（含后缀，如 song.mp3）
     *
     * @return string file_info
     */
    public function uploadC2CFile(string $openid, string $url, ?string $fileName = null): string
    {
        return $this->extractFileInfo(
            $this->client->uploadC2CFile($openid, self::TYPE_FILE, $url, false, $fileName)
        );
    }

    /**
     * 上传群聊文件并返回 file_info
     *
     * @param string      $groupOpenid 群聊 openid
     * @param string      $url         文件 URL（任意格式）
     * @param string|null $fileName    指定文件名（含后缀，如 song.mp3）
     *
     * @return string file_info
     */
    public function uploadGroupFile(string $groupOpenid, string $url, ?string $fileName = null): string
    {
        return $this->extractFileInfo(
            $this->client->uploadGroupFile($groupOpenid, self::TYPE_FILE, $url, false, $fileName)
        );
    }

    /**
     * 从上传响应中提取 file_info
     */
    private function extractFileInfo(array $response): string
    {
        $fileInfo = $response['file_info'] ?? '';

        if ($fileInfo === '') {
            $this->logger->error('Upload media failed: no file_info in response', ['response' => $response]);
            throw new \RuntimeException('Failed to upload media: file_info not found in response');
        }

        $this->logger->debug('Media uploaded', ['file_info' => $fileInfo, 'ttl' => $response['ttl'] ?? 0]);

        return $fileInfo;
    }
}
