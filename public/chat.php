<?php
declare(strict_types=1);

/**
 * 网页客服聊天界面
 *
 * 访问方式：https://你的域名/chat.php?token=你的密钥
 */

$configFile = __DIR__ . '/../config/bots.php';
if (!file_exists($configFile)) {
    die('Config not found');
}
$config = require $configFile;

$token = $_GET['token'] ?? '';
$chatSecret = $config['chat_secret'] ?? ($config['push_secret'] ?? 'change_me');
if (!hash_equals($chatSecret, $token)) {
    http_response_code(403);
    die('Forbidden: invalid token');
}

$apiBase = './chat_api.php?token=' . urlencode($token);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QQ Bot 网页客服</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            height: 100vh;
            display: flex;
        }
        .sidebar {
            width: 320px;
            background: #fff;
            border-right: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
        }
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            background: #fafafa;
        }
        .sidebar-header h2 { font-size: 16px; color: #333; }
        .sidebar-header .status {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        .conversation-list {
            flex: 1;
            overflow-y: auto;
        }
        .conversation-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
        }
        .conversation-item:hover { background: #f5f5f5; }
        .conversation-item.active { background: #e3f2fd; }
        .conv-name {
            font-size: 14px;
            font-weight: 500;
            color: #333;
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .conv-preview {
            font-size: 12px;
            color: #999;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .conv-time {
            font-size: 11px;
            color: #bbb;
            margin-top: 2px;
        }
        .badge {
            background: #ff4d4f;
            color: #fff;
            font-size: 11px;
            padding: 1px 6px;
            border-radius: 10px;
        }
        .scene-tag {
            font-size: 10px;
            padding: 1px 5px;
            border-radius: 4px;
            margin-left: 6px;
        }
        .scene-tag.c2c { background: #e6f7ff; color: #1890ff; }
        .scene-tag.group { background: #f6ffed; color: #52c41a; }
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
        }
        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            background: #fafafa;
        }
        .chat-header h3 { font-size: 16px; color: #333; word-break: break-all; }
        .chat-header .sub {
            font-size: 12px;
            color: #999;
            margin-top: 4px;
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .message {
            max-width: 70%;
            margin-bottom: 15px;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.6;
            word-break: break-word;
        }
        .message.incoming {
            background: #fff;
            color: #333;
            margin-right: auto;
            border: 1px solid #e8e8e8;
        }
        .message.outgoing {
            background: #95ec69;
            color: #333;
            margin-left: auto;
        }
        .message-time {
            font-size: 11px;
            color: #999;
            margin-top: 4px;
        }
        .chat-input-area {
            padding: 15px 20px;
            border-top: 1px solid #e0e0e0;
            background: #fff;
            display: flex;
            gap: 10px;
        }
        .chat-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #d9d9d9;
            border-radius: 20px;
            outline: none;
            font-size: 14px;
        }
        .chat-input:focus { border-color: #40a9ff; }
        .btn-send {
            padding: 10px 24px;
            background: #1890ff;
            color: #fff;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-send:hover { background: #40a9ff; }
        .btn-send:disabled { background: #ccc; cursor: not-allowed; }
        .empty-state {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
            font-size: 14px;
        }
        .error-bar {
            background: #fff2f0;
            border-bottom: 1px solid #ffccc7;
            color: #ff4d4f;
            padding: 10px 20px;
            font-size: 13px;
            display: none;
        }
        .typing { font-size: 12px; color: #999; padding: 0 20px 10px; }
        .msg-scene { font-size: 10px; color: #bbb; margin-bottom: 2px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>🤖 客服对话中心</h2>
            <div class="status" id="status">连接中...</div>
        </div>
        <div class="conversation-list" id="conversationList">
            <div class="empty-state">暂无对话</div>
        </div>
    </div>
    <div class="main">
        <div class="error-bar" id="errorBar"></div>
        <div class="chat-header" id="chatHeader">
            <h3>请选择对话</h3>
        </div>
        <div class="chat-messages" id="chatMessages">
            <div class="empty-state">从左侧选择一个对话开始</div>
        </div>
        <div class="typing" id="typing"></div>
        <div class="chat-input-area">
            <input type="text" class="chat-input" id="chatInput" placeholder="输入回复..." disabled>
            <button class="btn-send" id="btnSend" disabled>发送</button>
        </div>
    </div>

    <script>
        const API_BASE = '<?php echo $apiBase; ?>';
        let currentOpenid = null;
        let currentScene = 'c2c';
        let pollTimer = null;
        let msgPollTimer = null;

        async function init() {
            await loadConversations();
            pollTimer = setInterval(loadConversations, 3000);
        }

        async function loadConversations() {
            try {
                const res = await fetch(`${API_BASE}&action=list`);
                const data = await res.json();
                if (data.error) {
                    showError(data.error);
                    return;
                }
                renderConversations(data.conversations || []);
                document.getElementById('status').textContent = `在线 · 最后更新 ${new Date().toLocaleTimeString()}`;
                hideError();
            } catch (e) {
                showError('获取对话列表失败: ' + e.message);
            }
        }

        function renderConversations(conversations) {
            const list = document.getElementById('conversationList');
            if (conversations.length === 0) {
                list.innerHTML = '<div class="empty-state">暂无对话</div>';
                return;
            }
            list.innerHTML = conversations.map(conv => {
                const sceneClass = conv.scene === 'group' ? 'group' : 'c2c';
                const sceneText = conv.scene === 'group' ? '群' : '私';
                return `
                <div class="conversation-item ${conv.openid === currentOpenid ? 'active' : ''}" 
                     onclick="selectConversation('${conv.openid}', '${conv.scene}')">
                    <div class="conv-name">
                        <span>${conv.openid.substring(0, 20)}...<span class="scene-tag ${sceneClass}">${sceneText}</span></span>
                        ${conv.unread ? '<span class="badge">新</span>' : ''}
                    </div>
                    <div class="conv-preview">${conv.last_message ? (conv.last_message.type === 'incoming' ? '👤' : '🤖') + ' ' + conv.last_message.content : '无消息'}</div>
                    <div class="conv-time">${conv.last_active}</div>
                </div>`;
            }).join('');
        }

        async function selectConversation(openid, scene) {
            currentOpenid = openid;
            currentScene = scene;
            document.getElementById('chatHeader').innerHTML = `
                <h3>${openid}</h3>
                <div class="sub">场景: ${scene === 'group' ? '群聊' : '单聊'} · msg_id 有效期: ${scene === 'group' ? '5分钟' : '60分钟'}</div>
            `;
            document.getElementById('chatInput').disabled = false;
            document.getElementById('btnSend').disabled = false;

            await loadMessages(openid);

            // 启动消息轮询
            if (msgPollTimer) clearInterval(msgPollTimer);
            msgPollTimer = setInterval(() => loadMessages(openid), 3000);
        }

        async function loadMessages(openid) {
            if (!openid) return;
            try {
                const res = await fetch(`${API_BASE}&action=messages&openid=${encodeURIComponent(openid)}`);
                const data = await res.json();
                if (data.error) {
                    showError(data.error);
                    return;
                }
                renderMessages(data.messages || []);
            } catch (e) {
                showError('获取消息失败: ' + e.message);
            }
        }

        function renderMessages(messages) {
            const container = document.getElementById('chatMessages');
            if (messages.length === 0) {
                container.innerHTML = '<div class="empty-state">暂无消息</div>';
                return;
            }
            container.innerHTML = messages.map(msg => `
                <div class="message ${msg.type}">
                    <div class="msg-scene">${msg.scene === 'group' ? '👥 群聊' : '👤 单聊'} · ${msg.time}</div>
                    <div>${escapeHtml(msg.content)}</div>
                    <div class="message-time">${msg.type === 'incoming' ? '用户' : '客服'}</div>
                </div>
            `).join('');
            container.scrollTop = container.scrollHeight;
        }

        async function sendReply() {
            const input = document.getElementById('chatInput');
            const content = input.value.trim();
            if (!content || !currentOpenid) return;

            const btn = document.getElementById('btnSend');
            btn.disabled = true;
            document.getElementById('typing').textContent = '发送中...';

            try {
                const res = await fetch(`${API_BASE}&action=reply`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `openid=${encodeURIComponent(currentOpenid)}&content=${encodeURIComponent(content)}&msg_type=0`
                });
                const data = await res.json();
                if (data.error) {
                    showError(data.error);
                    document.getElementById('typing').textContent = '';
                } else if (data.success) {
                    input.value = '';
                    hideError();
                    await loadMessages(currentOpenid);
                    document.getElementById('typing').textContent = '发送成功 · 被动回复';
                    setTimeout(() => document.getElementById('typing').textContent = '', 3000);
                }
            } catch (e) {
                showError('发送失败: ' + e.message);
            } finally {
                btn.disabled = false;
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        function showError(msg) {
            const bar = document.getElementById('errorBar');
            if (msg) { bar.textContent = msg; bar.style.display = 'block'; }
            else { bar.style.display = 'none'; }
        }
        function hideError() { showError(''); }

        document.getElementById('btnSend').addEventListener('click', sendReply);
        document.getElementById('chatInput').addEventListener('keypress', e => {
            if (e.key === 'Enter') sendReply();
        });

        init();
    </script>
</body>
</html>