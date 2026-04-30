<?php
/**
 * QQ 官方机器人多用户配置文件
 * 支持配置多个机器人实例
 */
return [
    // 默认使用的机器人
    'default' => 'bot1',

    // 机器人实例列表
    'bots' => [
        'bot1' => [
            'app_id'        => '',
            'client_secret' => '',
            // Webhook 监听的事件类型（仅群聊和私聊）
            'intents'       => 1 << 25, // GROUP_AT_MESSAGE_CREATE + C2C_MESSAGE_CREATE
            // 是否沙箱环境
            'sandbox'       => false,
            // 可选：指定机器人昵称（用于日志区分）
            'nickname'      => '机器人一号',
        ],
        'bot2' => [
            'app_id'        => 'YOUR_APP_ID_2',
            'client_secret' => 'YOUR_CLIENT_SECRET_2',
            'intents'       => 1 << 25,
            'sandbox'       => false,
            'nickname'      => '机器人二号',
        ],
    ],

    // Webhook 服务配置
    'webhook' => [
        // 回调路径前缀，完整路径为 /webhook/{bot_id}
        'path_prefix'    => '/webhook',
        // 是否验证 Ed25519 签名（生产环境建议开启）
        'verify_sign'    => true,
        // 回复被动消息时，相同 msg_id 的 msg_seq 起始值
        'msg_seq_start'  => 1,
    ],

    // 日志配置
    'log' => [
        // 日志级别: debug, info, warning, error
        'level'      => 'debug',
        // 日志存储目录
        'path'       => __DIR__ . '/../logs',
        // 是否按日期分割日志文件
        'daily'      => true,
        // 是否输出到控制台（CLI 模式下）
        'console'    => true,
    ],

    // 文件代理配置（用于大文件下载后带正确文件名上传）
    'file_proxy' => [
        // 本地临时目录（需要 Web 可访问）
        'temp_dir'   => __DIR__ . '/../public/temp',
        // 临时目录对应的公网 URL 前缀
        'public_url' => 'https://你的域名/temp',
    ],

    // 插件配置
    'plugin' => [
        // 插件目录
        'path'       => __DIR__ . '/../plugins',
        // 插件数据目录（状态持久化）
        'data_path'  => __DIR__ . '/../data',
        // 自动加载的插件类名（完整类名或相对命名空间）
        'autoload'   => [
            // 'QQBot\Plugin\ExamplePlugin',
        ],
    ],
];