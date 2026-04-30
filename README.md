# 落七七的唧唧人框架

QQ官方机器人框架 献给api提供用户的一份礼物 官方保证永不收费 持续开源更新

插件生成地址: https://plugin.18years.ink/(测试阶段 很多BUG未修复)
插件库: https://plugin.18years.ink/download.php

插件开发: 看不懂文件就把几个示例插件发给唧唧人 让唧唧人写

框架bug及建议 请联系QQ: 2036513862 群聊: 750957985

因为是免费框架 所有system插件可以保留尽量保留 感谢!

---

## 框架信息

| 项目 | 说明 |
|------|------|
| 名称 | 落七七的唧唧人框架 |
| PHP 版本 | >= 8.1（实测8.5） |
| 依赖扩展 | sodium, json, curl |
| 协议 | QQ 官方 Bot API v2（Webhook） |
| 支持消息 | 私聊（C2C）、群聊 @ |
| 支持类型 | 文本、Markdown、图片、视频、音频、文件 |
| 插件机制 | 自动扫描 + 可视化生成 |

---

## 目录结构

```
qq-bot-framework/
|-- composer.json              Composer 配置
|-- config/
|   `-- bots.php               机器人配置文件（多机器人、日志、插件）
|-- data/                      运行时数据（插件状态、自定义接口配置）
|-- logs/                      日志目录（按日期自动分割）
|-- plugins/                   插件目录（所有 *Plugin.php 自动加载）
|   |-- ExamplePlugin.php      示例插件（文本/Markdown/多媒体）
|   |-- MarkdownDemoPlugin.php Markdown 模板演示插件
|   |-- CustomApiPlugin.php    自定义接口插件
|   `-- ...                    你生成的插件放这里
|-- public/                    Web 入口
|   |-- webhook.php            Webhook 回调入口（QQ 服务器推送地址）
|   |-- admin.php              插件管理后台（启用/禁用/配置插件）
|   |-- api.php                管理后台后端 API
|   |-- plugin_generator.php   插件生成器（可视化生成插件代码）
|   |-- plugin_generator_api.php 生成器后端 API
|   `-- temp/                  临时文件目录（媒体缓存）
|-- src/                       框架源码
|   |-- Api/
|   |   |-- AccessTokenManager.php   AccessToken 获取与缓存
|   |   `-- Client.php               QQ API 调用封装（发消息/上传媒体）
|   |-- Bot/
|   |   |-- Bot.php                  单个机器人实例
|   |   `-- BotManager.php           多机器人管理器
|   |-- Core/
|   |   |-- Application.php          应用核心（启动、初始化）
|   |   |-- Config.php               配置读取
|   |   |-- EventDispatcher.php      事件分发器
|   |   `-- Logger.php               日志记录器
|   |-- Events/
|   |   |-- EventInterface.php       事件接口
|   |   |-- C2CMessageEvent.php      单聊消息事件
|   |   `-- GroupAtMessageEvent.php  群聊 @ 消息事件
|   |-- Message/
|   |   |-- MessageInterface.php     消息接口
|   |   |-- TextMessage.php          文本消息
|   |   |-- MarkdownMessage.php      Markdown 消息
|   |   |-- ImageMessage.php         图片消息
|   |   |-- VideoMessage.php         视频消息
|   |   |-- AudioMessage.php         语音消息
|   |   |-- FileMessage.php          文件消息
|   |   |-- MarkdownTemplate.php     Markdown 模板构建器
|   |   |-- MediaUploader.php        媒体上传工具
|   |   |-- ImageResizer.php         图片缩放工具
|   |   `-- FileProxy.php            文件代理下载
|   |-- Plugin/
|   |   |-- PluginInterface.php      插件接口
|   |   |-- PluginInfo.php           插件信息值对象
|   |   |-- PluginLoader.php         插件加载器（扫描+实例化）
|   |   |-- PluginManager.php        插件管理器（注册/启用/禁用）
|   |   |-- PluginRegistry.php       插件注册表（状态持久化）
|   |   `-- CustomApi/               自定义接口插件核心
|   |       |-- ApiConfig.php        接口配置管理
|   |       `-- ApiExecutor.php      接口执行与响应解析
|   `-- Webhook/
|       |-- Handler.php              Webhook 请求处理器
|       |-- Validator.php            Ed25519 签名验证
|       `-- HttpHelper.php           HTTP 响应辅助
|-- test_boot.php              启动测试脚本
`-- test_validation.php        签名验证测试
```

---

## 每个文件的作用

### 入口文件

| 文件 | 作用 |
|------|------|
| `public/webhook.php` | QQ 服务器推送事件的接收入口，处理消息推送、回调验证 |
| `public/admin.php` | 插件管理后台页面，可视化开关插件、查看插件信息 |
| `public/api.php` | 管理后台的后端 API |
| `public/plugin_generator.php` | **插件生成器**页面，通过可视化配置生成完整 PHP 插件代码 |
| `public/plugin_generator_api.php` | 生成器的后端 API（测试接口 + 生成代码） |

### 核心类

| 类 | 说明 |
|----|------|
| `Application` | 框架启动核心，初始化配置、日志、机器人管理、插件系统 |
| `Config` | 读取 `config/bots.php`，支持多机器人配置 |
| `EventDispatcher` | 事件分发器，插件通过 `on()` 注册监听器 |
| `Logger` | 日志记录，支持按日期分割、多级别（debug/info/warning/error） |
| `BotManager` | 多机器人管理，从配置读取多个 Bot 实例 |
| `Bot` | 单个机器人实例，包含 API 客户端和 Webhook 处理器 |
| `AccessTokenManager` | 自动获取和缓存 QQ 平台的 AccessToken |
| `Client` | QQ API 调用封装：发送消息、上传媒体文件 |
| `Handler` | Webhook 请求处理器，解析 OpCode 分发事件 |
| `Validator` | Ed25519 签名验证，确保请求来自 QQ 官方 |

### 事件类

| 类 | 触发场景 | 常用方法 |
|----|----------|----------|
| `C2CMessageEvent` | 用户私聊消息 | `getContent()`, `replyText()`, `replyMarkdown()`, `sendImage()`, `sendFile()` |
| `GroupAtMessageEvent` | 群聊中 @ 机器人 | `getContent()`, `replyText()`, `replyMarkdown()`, `sendImage()`, `sendFile()`, `getGroupId()` |

### 消息类

| 类 | 用途 |
|----|------|
| `TextMessage` | 纯文本消息 |
| `MarkdownMessage` | Markdown 格式消息（支持标题、粗体、引用、代码块等） |
| `ImageMessage` | 图片消息（需先上传获取 file_info） |
| `VideoMessage` | 视频消息（需先上传获取 file_info） |
| `AudioMessage` | 语音消息（需先上传获取 file_info） |
| `FileMessage` | 文件消息（需先上传获取 file_info） |

### 插件系统

| 类 | 说明 |
|----|------|
| `PluginInterface` | 插件必须实现的接口，定义元信息和生命周期方法 |
| `PluginInfo` | 插件信息值对象（名称、版本、作者、开关状态等） |
| `PluginLoader` | 扫描 `plugins/` 目录，自动加载 `*Plugin.php` 文件 |
| `PluginManager` | 注册插件、管理插件的启用/禁用状态 |
| `PluginRegistry` | 插件状态持久化（保存到 `data/` 目录） |

---

## 使用教程

### 1. 环境要求

- PHP >= 8.1
- 扩展：`ext-sodium`, `ext-json`, `ext-curl`
- Web 服务器：Nginx / Apache（需要 HTTPS，QQ Webhook 要求）

### 2. 安装依赖

```bash
composer install
```

### 3. 配置机器人

编辑 `config/bots.php`：

```php
return [
    'default' => 'bot1',
    'bots' => [
        'bot1' => [
            'app_id'        => '你的 APPID',
            'client_secret' => '你的 SECRET',
            'intents'       => 1 << 25,
            'sandbox'       => false,
            'nickname'      => '我的机器人',
        ],
    ],
    'webhook' => [
        'path_prefix'   => '/webhook',
        'verify_sign'   => true,      // 生产环境必须开启
        'msg_seq_start' => 1,
    ],
    'log' => [
        'level'   => 'debug',
        'path'    => __DIR__ . '/../logs',
        'daily'   => true,
        'console' => true,
    ],
    'file_proxy' => [
        'temp_dir'   => __DIR__ . '/../public/temp',
        'public_url' => 'https://你的域名.com/temp',
    ],
    'plugin' => [
        'path'      => __DIR__ . '/../plugins',
        'data_path' => __DIR__ . '/../data',
        'autoload'  => [],
    ],
];
```

### 4. 配置 QQ 平台 Webhook

1. 登录 [QQ 开放平台](https://q.qq.com)
2. 进入你的机器人 → 开发配置 → 回调地址
3. 填写 Webhook URL：`https://你的域名/webhook.php?bot_id=bot1`
4. 平台会发送验证请求，框架自动处理 OpCode 13 验证

### 5. 配置 Web 服务器

**Nginx 示例：**

```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl;
    server_name your-domain.com;

    root /var/www/qq-bot-framework/public;
    index webhook.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \\.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

### 6. 启动框架

```bash
php test_boot.php
```

或直接访问 `webhook.php` 验证连通性。

### 7. 插件管理

访问 `https://你的域名/admin.php`，可：
- 查看所有已加载插件
- 启用 / 禁用插件
- 查看插件元信息（名称、版本、作者、描述）

---

## 插件开发教程

### 方式一：手动编写（推荐复杂插件）

#### 第 1 步：创建插件文件

在 `plugins/` 目录下创建 `MyPlugin.php`，文件名必须以 `Plugin.php` 结尾。

#### 第 2 步：实现 PluginInterface

```php
<?php
declare(strict_types=1);

namespace QQBot\Plugin;

use QQBot\Core\EventDispatcher;
use QQBot\Core\Logger;
use QQBot\Events\C2CMessageEvent;
use QQBot\Events\GroupAtMessageEvent;

class MyPlugin implements PluginInterface
{
    private Logger $logger;

    // ===== 插件元信息 =====
    public function getName(): string { return 'my_plugin'; }
    public function getDisplayName(): string { return '我的插件'; }
    public function getDescription(): string { return '这是一个示例插件'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getAuthor(): string { return '你的名字'; }
    public function getIcon(): ?string { return '🤖'; }
    public function getTags(): array { return ['示例', '测试']; }

    // ===== 注册事件监听 =====
    public function register(EventDispatcher $dispatcher, Logger $logger): void
    {
        $this->logger = $logger;

        // 监听单聊消息
        $dispatcher->on(C2CMessageEvent::class, function (C2CMessageEvent $event): void {
            $content = trim($event->getContent());

            if ($content === '你好') {
                $event->replyText('你好呀！我是机器人~');
            }

            if ($content === '帮助') {
                $event->replyMarkdown("**帮助菜单**\n- 你好：打招呼\n- md：Markdown 演示");
            }
        });

        // 监听群聊 @ 消息
        $dispatcher->on(GroupAtMessageEvent::class, function (GroupAtMessageEvent $event): void {
            $content = trim($event->getContent());

            if ($content === '图片') {
                $event->sendImage('https://example.com/image.jpg');
            }

            if ($content === '文件') {
                $event->sendFile('https://example.com/file.pdf', '文档.pdf');
            }
        });
    }

    public function enable(): void
    {
        $this->logger->info('MyPlugin enabled');
    }

    public function disable(): void
    {
        $this->logger->info('MyPlugin disabled');
    }
}
```

#### 第 3 步：自动加载

框架启动时会自动扫描 `plugins/` 目录，所有 `*Plugin.php` 文件都会被加载，无需手动注册。

---

### 方式二：可视化生成（推荐简单接口插件）

访问 `https://你的域名/plugin_generator.php`，通过 4 步向导生成代码：

1. **基础配置** — 填写类名、指令、请求 URL、Headers
2. **响应类型** — 选择：纯文本 / Markdown / 图片 / 音频 / 视频 / JSON 数据
3. **数据配置** — 测试接口、点击 JSON 树选择路径、配置字段映射和 Markdown 排版
4. **生成代码** — 点击生成，复制代码保存到 `plugins/YourPlugin.php`

**生成器特性：**
- 一键测试接口，可视化 JSON 树
- 点击选择数据路径，自动识别字段
- 拖拽排序字段映射
- 4 种 Markdown 排版：卡片、表格、列表、紧凑
- 支持自定义 Markdown 模板（`{字段名}` 占位符）

---

## 事件方法速查

### C2CMessageEvent / GroupAtMessageEvent

| 方法 | 说明 |
|------|------|
| `getContent(): string` | 获取消息文本内容 |
| `replyText($text, $msgSeq?)` | 回复文本 |
| `replyMarkdown($markdown, $msgSeq?)` | 回复 Markdown |
| `replyImage($fileInfo, $msgSeq?)` | 回复图片（需 file_info） |
| `replyVideo($fileInfo, $msgSeq?)` | 回复视频（需 file_info） |
| `replyAudio($fileInfo, $msgSeq?)` | 回复语音（需 file_info） |
| `replyFile($fileInfo, $msgSeq?)` | 回复文件（需 file_info） |
| `sendImage($url, $msgSeq?)` | 一键发送图片（自动上传+发送） |
| `sendVideo($url, $msgSeq?)` | 一键发送视频（自动上传+发送） |
| `sendAudio($url, $msgSeq?)` | 一键发送语音（自动上传+发送） |
| `sendFile($url, $filename?, $msgSeq?)` | 一键发送文件（自动上传+发送） |
| `getUserOpenid(): string` | 获取发送者 OpenID |
| `stopPropagation()` | 阻止后续监听器执行 |

### Markdown 模板构建

```php
use QQBot\Message\MarkdownTemplate;

// 使用预定义模板
$markdown = MarkdownTemplate::info('标题', '这是内容');
$markdown = MarkdownTemplate::warning('注意', '这是一条警告');
$markdown = MarkdownTemplate::success('成功', '操作已完成');
$markdown = MarkdownTemplate::error('错误', '操作失败');
$markdown = MarkdownTemplate::code('PHP', '<?php echo "Hello";');

$event->replyMarkdown($markdown);
```

---

## 多机器人配置

支持同时运行多个机器人实例：

```php
// config/bots.php
'bots' => [
    'bot1' => [
        'app_id'        => 'APPID_1',
        'client_secret' => 'SECRET_1',
        'nickname'      => '工作机器人',
    ],
    'bot2' => [
        'app_id'        => 'APPID_2',
        'client_secret' => 'SECRET_2',
        'nickname'      => '娱乐机器人',
    ],
],
```

Webhook URL 区分：
- 机器人一号：`https://域名/webhook.php?bot_id=bot1`
- 机器人二号：`https://域名/webhook.php?bot_id=bot2`

所有插件共享同一个事件分发器，同一份插件代码同时响应多个机器人的消息。

---

## 日志系统

日志文件位于 `logs/` 目录，按日期自动分割：

```
logs/
|-- 2025-04-30.log
|-- 2025-05-01.log
`-- ...
```

日志级别（在 `config/bots.php` 中配置）：
- `debug` — 调试信息（最详细）
- `info` — 一般信息
- `warning` — 警告
- `error` — 错误

---

## Ed25519 签名验证

框架默认开启 Ed25519 签名验证，确保 Webhook 请求来自 QQ 官方服务器。

验证流程：
1. QQ 平台推送请求时，在 Header 中携带 `X-Signature-Ed25519` 和 `X-Signature-Timestamp`
2. 框架使用 `sodium_crypto_sign_verify_detached()` 验证签名
3. 验证失败返回 401，验证通过进入事件处理

**回调验证（OpCode 13）特殊处理：** 验证请求不含签名，框架会自动跳过签名验证。

---

## 内置插件一览

| 插件 | 指令 | 功能 |
|------|------|------|
| ExamplePlugin | `帮助` | 示例：文本/Markdown/图片/视频/音频回复 |
| NeteaseMusicPlugin | `点歌` | 网易云音乐搜索、播放、歌词 |
| MarkdownDemoPlugin | `md` | Markdown 排版模板演示 |
| CustomApiPlugin | `api帮助` | 自定义 HTTP 接口（可视化配置） |

---

## 常见问题

**Q: Webhook 验证失败？**  
A: 确保 URL 是 HTTPS，检查 `config/bots.php` 中的 `app_id` 和 `client_secret` 是否正确。

**Q: 插件不被加载？**  
A: 确保文件名以 `Plugin.php` 结尾，类名与文件名一致，命名空间为 `QQBot\Plugin`，放在 `plugins/` 目录下。

**Q: 发送图片失败？**  
A: 图片 URL 必须公网可访问。如果图片较大，框架会自动缩放。对于文件发送，确保 `file_proxy.public_url` 配置正确。

**Q: msg_seq 重复导致消息被忽略？**  
A: 被动消息（Webhook 回复）中 `msg_seq` 需要 1-5 递增。框架已自动处理，插件无需关心。主动消息不受此限制。
