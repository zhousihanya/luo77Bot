<?php
declare(strict_types=1);

namespace QQBot\Plugin;

use QQBot\Core\EventDispatcher;
use QQBot\Core\Logger;
use QQBot\Events\C2CMessageEvent;
use QQBot\Events\GroupAtMessageEvent;
use QQBot\Message\MarkdownTemplate;

class OwnerSystemPlugin implements PluginInterface
{
    private Logger $logger;
    private string $dataPath;
    
    /**
     * 主人QQ OpenID列表（支持多主人）
     * 注意：QQ官方Bot API获取的是OpenID，不是原始QQ号
     * 请通过 getUserOpenid() 获取后填入
     */
    private array $masterOpenIds = [
        'CA6D7035230FFB74D3616CCCBE6A7F5A',
    ];
    
    /**
     * 主人系统数据文件
     */
    private string $ownerDataFile;

    // ===== 插件元信息 =====
    public function getName(): string { return 'owner_system'; }
    public function getDisplayName(): string { return '主人系统'; }
    public function getDescription(): string { return '主人权限管理系统，支持多主人、动态添加/移除主人、主人专属指令'; }
    public function getVersion(): string { return '1.0.1'; }
    public function getAuthor(): string { return 'AI Assistant'; }
    public function getIcon(): ?string { return '👑'; }
    public function getTags(): array { return ['权限', '管理', '系统']; }

    // ===== 注册事件监听 =====
    public function register(EventDispatcher $dispatcher, Logger $logger): void
    {
        $this->logger = $logger;
        $this->dataPath = __DIR__ . '/../data';
        $this->ownerDataFile = $this->dataPath . '/owner_system.json';
        
        // 初始化数据文件
        $this->initDataFile();

        // 监听单聊消息
        $dispatcher->on(C2CMessageEvent::class, function (C2CMessageEvent $event): void {
            $this->handleMessage($event);
        });

        // 监听群聊 @ 消息
        $dispatcher->on(GroupAtMessageEvent::class, function (GroupAtMessageEvent $event): void {
            $this->handleMessage($event);
        });
    }

    public function enable(): void
    {
        $this->logger->info('OwnerSystemPlugin enabled');
    }

    public function disable(): void
    {
        $this->logger->info('OwnerSystemPlugin disabled');
    }

    // ==================== 核心逻辑 ====================

    /**
     * 处理消息事件（兼容 C2C 和 GroupAt）
     */
    private function handleMessage(C2CMessageEvent|GroupAtMessageEvent $event): void
    {
        $content = trim($event->getContent());
        $userId = strtoupper(trim($event->getUserOpenid()));
        
        // 加载主人列表（统一大写）
        $masters = $this->getMasters();
        
        // 判断是否是主人
        $isMaster = in_array($userId, $masters, true);
        
        // 记录日志（调试用）
        $this->logger->info("收到消息: {$content} | 用户: {$userId} | 主人: " . ($isMaster ? '是' : '否'));
        $this->logger->info("当前主人列表: " . json_encode($masters));

        // ========== 公共指令（任何人可用） ==========
        
        // 获取自己的OpenID（方便配置主人）
        if ($content === '我的ID') {
            $event->replyText("你的 OpenID 是：\n`{$userId}`\n\n将此ID发给机器人主人，可添加为临时管理员。");
            return;
        }

        // ========== 主人专属指令 ==========
        if (!$isMaster) {
            // 非主人尝试执行主人指令时的提示
            if (str_starts_with($content, '主人') || str_starts_with($content, '系统')) {
                $event->replyText('❌ 你没有权限执行此操作，该指令仅主人可用。');
                $event->stopPropagation();
            }
            return;
        }

        // 主人指令分发
        match (true) {
            $content === '主人帮助' => $this->showOwnerHelp($event),
            $content === '主人列表' => $this->showMasterList($event, $masters),
            $content === '系统状态' => $this->showSystemStatus($event),
            
            str_starts_with($content, '主人添加 ') => $this->addMaster($event, $content, $masters),
            str_starts_with($content, '主人移除 ') => $this->removeMaster($event, $content, $masters),
            
            str_starts_with($content, '主人广播 ') => $this->broadcastMessage($event, $content),
            str_starts_with($content, '主人禁言 ') => $this->muteUser($event, $content),
            str_starts_with($content, '主人解禁 ') => $this->unmuteUser($event, $content),
            
            str_starts_with($content, '主人昵称 ') => $this->setBotNickname($event, $content),
            str_starts_with($content, '主人执行 ') => $this->executeCommand($event, $content),
            
            $content === '主人重启' => $this->restartBot($event),
            $content === '主人清理日志' => $this->cleanLogs($event),
            
            default => null,
        };

        // 如果是主人指令，阻止后续插件处理
        if (str_starts_with($content, '主人') || str_starts_with($content, '系统')) {
            $event->stopPropagation();
        }
    }

    // ==================== 主人管理 ====================

    /**
     * 显示主人帮助菜单
     */
    private function showOwnerHelp(C2CMessageEvent|GroupAtMessageEvent $event): void
    {
        $help = <<<MD
**👑 主人系统帮助菜单**

**主人管理**
- `主人列表` — 查看所有主人
- `主人添加 <OpenID>` — 添加新主人
- `主人移除 <OpenID>` — 移除主人（不能移除自己）

**系统信息**
- `系统状态` — 查看框架运行状态
- `主人重启` — 重启框架（谨慎使用）
- `主人清理日志` — 清理过期日志文件

**消息管理**
- `主人广播 <内容>` — 向所有群发送广播（群聊中可用）

**机器人设置**
- `主人昵称 <新昵称>` — 修改机器人昵称

**调试工具**
- `主人执行 <命令>` — 执行系统命令（危险！）
- `我的ID` — 获取自己的OpenID（任何人可用）

⚠️ 注意：OpenID 是 QQ 官方 Bot API 的用户标识，不是原始QQ号。
MD;
        $event->replyMarkdown($help);
    }

    /**
     * 显示主人列表
     */
    private function showMasterList(C2CMessageEvent|GroupAtMessageEvent $event, array $masters): void
    {
        if (empty($masters)) {
            $event->replyText('当前没有配置任何主人。');
            return;
        }

        $list = "**👑 主人列表**\n\n";
        foreach ($masters as $index => $masterId) {
            $shortId = substr($masterId, 0, 8) . '...' . substr($masterId, -4);
            $list .= ($index + 1) . ". `{$shortId}`\n";
        }
        $list .= "\n共 " . count($masters) . " 位主人";
        
        $event->replyMarkdown($list);
    }

    /**
     * 添加新主人
     */
    private function addMaster(C2CMessageEvent|GroupAtMessageEvent $event, string $content, array $masters): void
    {
        $newMasterId = strtoupper(trim(substr($content, 6))); // 去掉 "主人添加 " 并转大写
        
        if (empty($newMasterId) || strlen($newMasterId) < 10) {
            $event->replyText('❌ 无效的 OpenID，请提供完整的用户 OpenID。');
            return;
        }

        if (in_array($newMasterId, $masters, true)) {
            $event->replyText('⚠️ 该用户已经是主人了。');
            return;
        }

        $masters[] = $newMasterId;
        $this->saveMasters($masters);
        
        $shortId = substr($newMasterId, 0, 8) . '...' . substr($newMasterId, -4);
        $event->replyText("✅ 已成功添加主人：`{$shortId}`");
        $this->logger->info("主人添加成功: {$newMasterId}");
    }

    /**
     * 移除主人
     */
    private function removeMaster(C2CMessageEvent|GroupAtMessageEvent $event, string $content, array $masters): void
    {
        $removeId = strtoupper(trim(substr($content, 6))); // 去掉 "主人移除 " 并转大写
        $currentUserId = strtoupper(trim($event->getUserOpenid()));
        
        if (empty($removeId)) {
            $event->replyText('❌ 请提供要移除的 OpenID。');
            return;
        }

        // 防止移除自己
        if ($removeId === $currentUserId) {
            $event->replyText('❌ 不能移除自己！');
            return;
        }

        $index = array_search($removeId, $masters, true);
        if ($index === false) {
            $event->replyText('❌ 该用户不在主人列表中。');
            return;
        }

        array_splice($masters, $index, 1);
        $this->saveMasters($masters);
        
        $shortId = substr($removeId, 0, 8) . '...' . substr($removeId, -4);
        $event->replyText("✅ 已成功移除主人：`{$shortId}`");
        $this->logger->info("主人移除成功: {$removeId}");
    }

    // ==================== 系统管理 ====================

    /**
     * 显示系统状态
     */
    private function showSystemStatus(C2CMessageEvent|GroupAtMessageEvent $event): void
    {
        $phpVersion = PHP_VERSION;
        $memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 2);
        $memoryPeak = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        $uptime = $this->getUptime();
        
        // 统计插件数量
        $pluginPath = __DIR__ . '/../plugins';
        $pluginCount = count(glob($pluginPath . '/*Plugin.php'));
        
        // 统计日志大小
        $logPath = __DIR__ . '/../logs';
        $logSize = $this->getDirSize($logPath);
        
        $status = <<<MD
**📊 系统状态**

| 项目 | 状态 |
|------|------|
| PHP版本 | {$phpVersion} |
| 内存占用 | {$memoryUsage} MB / 峰值 {$memoryPeak} MB |
| 运行时间 | {$uptime} |
| 已加载插件 | {$pluginCount} 个 |
| 日志大小 | {$logSize} |
| 主人数量 | {$this->getMasterCount()} 位 |

系统运行正常 ✅
MD;
        
        $event->replyMarkdown($status);
    }

    /**
     * 广播消息（仅群聊中可用）
     */
    private function broadcastMessage(C2CMessageEvent|GroupAtMessageEvent $event, string $content): void
    {
        if ($event instanceof C2CMessageEvent) {
            $event->replyText('❌ 广播功能只能在群聊中使用。');
            return;
        }

        $message = trim(substr($content, 6)); // 去掉 "主人广播 "
        
        if (empty($message)) {
            $event->replyText('❌ 广播内容不能为空。');
            return;
        }

        // 注意：QQ Bot API 不支持直接获取群列表进行广播
        // 这里仅作为示例，实际实现需要维护群列表
        $event->replyText("📢 广播内容已记录：\n{$message}\n\n（注：QQ官方Bot API不支持主动获取群列表，需自行维护群ID数据库）");
        $this->logger->info("主人广播: {$message}");
    }

    /**
     * 设置机器人昵称（需配合Bot API实现）
     */
    private function setBotNickname(C2CMessageEvent|GroupAtMessageEvent $event, string $content): void
    {
        $nickname = trim(substr($content, 6)); // 去掉 "主人昵称 "
        
        if (empty($nickname) || mb_strlen($nickname) > 20) {
            $event->replyText('❌ 昵称不能为空且不能超过20个字符。');
            return;
        }

        // 注意：QQ官方Bot API目前不支持修改机器人昵称
        // 这里仅记录到本地配置
        $this->saveConfig('bot_nickname', $nickname);
        $event->replyText("✅ 机器人昵称已设置为：{$nickname}\n（注：QQ官方API暂不支持修改昵称，仅本地记录）");
    }

    /**
     * 执行系统命令（危险操作！）
     */
    private function executeCommand(C2CMessageEvent|GroupAtMessageEvent $event, string $content): void
    {
        $command = trim(substr($content, 6)); // 去掉 "主人执行 "
        
        if (empty($command)) {
            $event->replyText('❌ 命令不能为空。');
            return;
        }

        // 安全限制：禁止执行危险命令
        $dangerous = ['rm -rf', 'dd', 'mkfs', 'format', 'del /f', 'rd /s'];
        foreach ($dangerous as $danger) {
            if (str_contains(strtolower($command), $danger)) {
                $event->replyText('🚫 检测到危险命令，已阻止执行！');
                $this->logger->warning("主人尝试执行危险命令: {$command}");
                return;
            }
        }

        // 执行命令
        exec($command . ' 2>&1', $output, $returnCode);
        $result = implode("\n", $output);
        
        if (empty($result)) {
            $result = '（命令执行完成，无输出）';
        }

        // 截断过长输出
        if (mb_strlen($result) > 800) {
            $result = mb_substr($result, 0, 800) . "\n...（输出已截断）";
        }

        $event->replyMarkdown("**执行结果**\n```\n{$result}\n```\n返回码: {$returnCode}");
        $this->logger->info("主人执行命令: {$command}");
    }

    /**
     * 重启框架（通过touch文件触发）
     */
    private function restartBot(C2CMessageEvent|GroupAtMessageEvent $event): void
    {
        $restartFile = $this->dataPath . '/restart.flag';
        file_put_contents($restartFile, date('Y-m-d H:i:s'));
        
        $event->replyText('🔄 重启指令已发送，框架将在下次请求时重新初始化...');
        $this->logger->info('主人触发框架重启');
    }

    /**
     * 清理日志文件
     */
    private function cleanLogs(C2CMessageEvent|GroupAtMessageEvent $event): void
    {
        $logPath = __DIR__ . '/../logs';
        $deleted = 0;
        $saved = 0;
        $today = date('Y-m-d');

        if (!is_dir($logPath)) {
            $event->replyText('日志目录不存在。');
            return;
        }

        $files = glob($logPath . '/*.log');
        foreach ($files as $file) {
            $filename = basename($file);
            // 保留今天和昨天的日志
            if (str_contains($filename, $today) || str_contains($filename, date('Y-m-d', strtotime('-1 day')))) {
                $saved++;
                continue;
            }
            
            unlink($file);
            $deleted++;
        }

        $event->replyText("✅ 日志清理完成\n删除: {$deleted} 个文件\n保留: {$saved} 个文件（今天/昨天）");
        $this->logger->info("主人清理日志: 删除{$deleted}个，保留{$saved}个");
    }

    // ==================== 群管功能（占位） ====================

    /**
     * 禁言用户（需要群管权限和相应API权限）
     */
    private function muteUser(C2CMessageEvent|GroupAtMessageEvent $event, string $content): void
    {
        // QQ官方Bot API v2 暂不支持直接禁言
        $event->replyText('⚠️ QQ官方Bot API v2 暂不支持禁言操作，需申请相应权限。');
    }

    /**
     * 解除禁言
     */
    private function unmuteUser(C2CMessageEvent|GroupAtMessageEvent $event, string $content): void
    {
        $event->replyText('⚠️ QQ官方Bot API v2 暂不支持解禁操作，需申请相应权限。');
    }

    // ==================== 数据持久化 ====================

    /**
     * 初始化数据文件
     */
    private function initDataFile(): void
    {
        if (!is_dir($this->dataPath)) {
            mkdir($this->dataPath, 0755, true);
        }

        if (!file_exists($this->ownerDataFile)) {
            // 初始化时将默认主人统一转大写
            $defaultMasters = array_map('strtoupper', $this->masterOpenIds);
            $data = [
                'masters' => $defaultMasters,
                'config' => [],
                'created_at' => date('Y-m-d H:i:s'),
            ];
            file_put_contents($this->ownerDataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * 获取主人列表（统一大写）
     */
    private function getMasters(): array
    {
        if (!file_exists($this->ownerDataFile)) {
            return array_map('strtoupper', $this->masterOpenIds);
        }

        $data = json_decode(file_get_contents($this->ownerDataFile), true);
        $masters = $data['masters'] ?? $this->masterOpenIds;
        return array_map('strtoupper', $masters);
    }

    /**
     * 保存主人列表
     */
    private function saveMasters(array $masters): void
    {
        $data = json_decode(file_get_contents($this->ownerDataFile), true);
        $data['masters'] = $masters;
        $data['updated_at'] = date('Y-m-d H:i:s');
        file_put_contents($this->ownerDataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * 保存配置项
     */
    private function saveConfig(string $key, mixed $value): void
    {
        $data = json_decode(file_get_contents($this->ownerDataFile), true);
        $data['config'][$key] = $value;
        file_put_contents($this->ownerDataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * 获取主人数量
     */
    private function getMasterCount(): int
    {
        return count($this->getMasters());
    }

    // ==================== 工具方法 ====================

    /**
     * 获取运行时间
     */
    private function getUptime(): string
    {
        if (function_exists('sys_getloadavg')) {
            $uptime = @file_get_contents('/proc/uptime');
            if ($uptime !== false) {
                $seconds = (int) floatval(explode(' ', $uptime)[0]);
                $days = floor($seconds / 86400);
                $hours = floor(($seconds % 86400) / 3600);
                $minutes = floor(($seconds % 3600) / 60);
                return "{$days}天 {$hours}小时 {$minutes}分钟";
            }
        }
        return '未知';
    }

    /**
     * 获取目录大小
     */
    private function getDirSize(string $path): string
    {
        if (!is_dir($path)) {
            return '0 B';
        }

        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
            $size += $file->getSize();
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        while ($size > 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }
}