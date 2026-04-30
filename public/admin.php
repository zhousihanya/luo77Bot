<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QQBot 管理后台</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg-primary: #0f0f1a;
            --bg-secondary: #1a1a2e;
            --bg-card: #16162a;
            --bg-hover: #222240;
            --accent: #6366f1;
            --accent-hover: #818cf8;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text-primary: #e2e8f0;
            --text-secondary: #94a3b8;
            --border: #2d2d4a;
            --radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.3);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
        }

        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid var(--border);
        }

        .sidebar-header h1 {
            font-size: 20px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-header p { font-size: 12px; color: var(--text-secondary); margin-top: 4px; }

        .nav { padding: 12px; flex: 1; }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 4px;
        }

        .nav-item:hover { background: var(--bg-hover); color: var(--text-primary); }

        .nav-item.active { background: var(--accent); color: white; }

        .nav-item svg { width: 20px; height: 20px; }

        /* Main Content */
        .main {
            margin-left: 260px;
            flex: 1;
            padding: 32px;
            max-width: calc(100% - 260px);
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 24px;
        }

        /* Cards */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            transition: transform 0.2s;
        }

        .stat-card:hover { transform: translateY(-2px); }

        .stat-card .label { font-size: 13px; color: var(--text-secondary); margin-bottom: 8px; }

        .stat-card .value {
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Tables */
        .table-container {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; }

        th, td { padding: 14px 20px; text-align: left; font-size: 14px; }

        th {
            background: var(--bg-secondary);
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td { border-top: 1px solid var(--border); color: var(--text-primary); }

        tr:hover td { background: var(--bg-hover); }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary { background: var(--accent); color: white; }

        .btn-primary:hover { background: var(--accent-hover); }

        .btn-danger { background: var(--danger); color: white; }

        .btn-danger:hover { opacity: 0.85; }

        .btn-sm { padding: 6px 12px; font-size: 12px; }

        /* Toggle Switch */
        .toggle {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }

        .toggle input { opacity: 0; width: 0; height: 0; }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: var(--border);
            border-radius: 24px;
            transition: 0.3s;
        }

        .toggle-slider:before {
            content: '';
            position: absolute;
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: 0.3s;
        }

        .toggle input:checked + .toggle-slider { background: var(--success); }

        .toggle input:checked + .toggle-slider:before { transform: translateX(20px); }

        /* Plugin Cards */
        .plugin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 20px;
        }

        .plugin-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            transition: all 0.2s;
        }

        .plugin-card:hover { border-color: var(--accent); transform: translateY(-2px); }

        .plugin-card.disabled { opacity: 0.5; }

        .plugin-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .plugin-icon { font-size: 32px; margin-right: 12px; }

        .plugin-info h3 { font-size: 16px; font-weight: 600; margin-bottom: 4px; }

        .plugin-info .version {
            font-size: 12px;
            color: var(--accent);
            background: rgba(99,102,241,0.1);
            padding: 2px 8px;
            border-radius: 4px;
            display: inline-block;
        }

        .plugin-desc { font-size: 13px; color: var(--text-secondary); margin: 12px 0; line-height: 1.5; }

        .plugin-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }

        .plugin-author { font-size: 12px; color: var(--text-secondary); }

        .plugin-tags {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .tag {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 20px;
            background: var(--bg-hover);
            color: var(--text-secondary);
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active { display: flex; }

        .modal {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 32px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal h2 { font-size: 20px; margin-bottom: 24px; }

        .form-group { margin-bottom: 20px; }

        .form-group label {
            display: block;
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 14px;
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-group input:focus, .form-group select:focus { border-color: var(--accent); }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
        }

        .btn-secondary {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        .btn-secondary:hover { background: var(--border); }

        /* Status badge */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }

        .badge-success { background: rgba(34,197,94,0.15); color: var(--success); }

        .badge-danger { background: rgba(239,68,68,0.15); color: var(--danger); }

        .badge-warning { background: rgba(245,158,11,0.15); color: var(--warning); }

        /* Toast */
        .toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            padding: 14px 20px;
            border-radius: 8px;
            color: white;
            font-size: 14px;
            animation: slideIn 0.3s ease;
            max-width: 360px;
        }

        .toast.success { background: var(--success); }

        .toast.error { background: var(--danger); }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Section */
        .section { display: none; }

        .section.active { display: block; }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .webhook-url {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 13px;
            font-family: monospace;
            color: var(--accent);
            margin-bottom: 20px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-state svg { width: 64px; height: 64px; margin-bottom: 16px; opacity: 0.3; }

        @media (max-width: 768px) {
            .sidebar { width: 64px; }
            .sidebar-header h1, .nav-item span { display: none; }
            .main { margin-left: 64px; max-width: calc(100% - 64px); padding: 16px; }
            .plugin-grid { grid-template-columns: 1fr; }
            .card-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <h1>QQBot</h1>
            <p>机器人管理后台</p>
        </div>
        <div class="nav">
            <div class="nav-item active" onclick="showPage('dashboard')">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                <span>仪表盘</span>
            </div>
            <div class="nav-item" onclick="showPage('bots')">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <span>机器人管理</span>
            </div>
            <div class="nav-item" onclick="showPage('plugins')">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/></svg>
                <span>插件管理</span>
            </div>
            <div class="nav-item" onclick="showPage('settings')">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>系统设置</span>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main">
        <!-- Dashboard -->
        <div id="page-dashboard" class="section active">
            <h1 class="page-title">仪表盘</h1>
            <div class="card-grid">
                <div class="stat-card">
                    <div class="label">机器人数量</div>
                    <div class="value" id="stat-bots">-</div>
                </div>
                <div class="stat-card">
                    <div class="label">已安装插件</div>
                    <div class="value" id="stat-plugins">-</div>
                </div>
                <div class="stat-card">
                    <div class="label">已启用插件</div>
                    <div class="value" id="stat-enabled">-</div>
                </div>
                <div class="stat-card">
                    <div class="label">PHP 版本</div>
                    <div class="value" style="font-size:24px; margin-top:8px;" id="stat-php">-</div>
                </div>
            </div>
            <div class="table-container" style="padding:24px;">
                <h3 style="margin-bottom:16px; font-size:16px;">运行状态</h3>
                <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:16px;">
                    <div>
                        <span style="color:var(--text-secondary); font-size:13px;">Sodium 扩展</span>
                        <div id="stat-sodium" style="margin-top:4px;">-</div>
                    </div>
                    <div>
                        <span style="color:var(--text-secondary); font-size:13px;">服务器时间</span>
                        <div id="stat-time" style="margin-top:4px; color:var(--text-secondary);">-</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bot Management -->
        <div id="page-bots" class="section">
            <div class="section-header">
                <h1 class="page-title">机器人管理</h1>
                <button class="btn btn-primary" onclick="openBotModal()">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    添加机器人
                </button>
            </div>
            <div id="bots-list"></div>
        </div>

        <!-- Plugin Management -->
        <div id="page-plugins" class="section">
            <div class="section-header">
                <h1 class="page-title">插件管理</h1>
            </div>
            <div id="plugins-list" class="plugin-grid"></div>
        </div>

        <!-- Settings -->
        <div id="page-settings" class="section">
            <h1 class="page-title">系统设置</h1>
            <div class="table-container" style="padding:24px;">
                <h3 style="margin-bottom:16px; font-size:16px;">Webhook 回调地址</h3>
                <p style="font-size:13px; color:var(--text-secondary); margin-bottom:12px;">在 QQ 开放平台配置以下回调地址：</p>
                <div id="webhook-urls"></div>
            </div>
            <div class="table-container" style="padding:24px; margin-top:20px;">
                <h3 style="margin-bottom:16px; font-size:16px;">API Token</h3>
                <p style="font-size:13px; color:var(--text-secondary); margin-bottom:12px;">管理后台 API 鉴权 Token，请在服务器环境变量 <code>QQBOT_ADMIN_TOKEN</code> 中设置：</p>
                <div class="webhook-url">export QQBOT_ADMIN_TOKEN=your-secure-token</div>
            </div>
        </div>
    </main>

    <!-- Bot Modal -->
    <div class="modal-overlay" id="botModal">
        <div class="modal">
            <h2 id="botModalTitle">添加机器人</h2>
            <form id="botForm" onsubmit="saveBot(event)">
                <input type="hidden" id="bot-edit-id" value="">
                <div class="form-group">
                    <label>机器人 ID（英文标识）</label>
                    <input type="text" id="bot-id" placeholder="如：bot1" required>
                </div>
                <div class="form-group">
                    <label>App ID</label>
                    <input type="text" id="bot-appid" placeholder="QQ 开放平台获取的 AppID" required>
                </div>
                <div class="form-group">
                    <label>Client Secret</label>
                    <input type="text" id="bot-secret" placeholder="QQ 开放平台获取的 Secret" required>
                </div>
                <div class="form-group">
                    <label>显示名称</label>
                    <input type="text" id="bot-nickname" placeholder="如：小助手">
                </div>
                <div class="form-group">
                    <label>沙箱环境</label>
                    <select id="bot-sandbox">
                        <option value="false">否（正式环境）</option>
                        <option value="true">是（沙箱环境）</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="bot-default"> 设为默认机器人
                    </label>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeBotModal()">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        // API Token - 生产环境应使用环境变量或登录系统
        const API_TOKEN = localStorage.getItem('qqbot_api_token') || '';

        // Page navigation
        function showPage(page) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            document.getElementById('page-' + page).classList.add('active');
            event.currentTarget.classList.add('active');

            if (page === 'bots') loadBots();
            if (page === 'plugins') loadPlugins();
            if (page === 'dashboard') loadStatus();
        }

        // Toast
        function toast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const div = document.createElement('div');
            div.className = 'toast ' + type;
            div.textContent = message;
            container.appendChild(div);
            setTimeout(() => div.remove(), 3000);
        }

        // API helper
        async function api(action, params = {}) {
            const url = new URL('api.php', window.location.href);
            url.searchParams.set('action', action);
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'X-Api-Token': API_TOKEN, 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(params)
            });
            return response.json();
        }

        async function apiGet(action) {
            const url = new URL('api.php', window.location.href);
            url.searchParams.set('action', action);
            const response = await fetch(url, { headers: { 'X-Api-Token': API_TOKEN } });
            return response.json();
        }

        // Status
        async function loadStatus() {
            const data = await apiGet('status');
            if (data.success) {
                document.getElementById('stat-bots').textContent = data.bots;
                document.getElementById('stat-plugins').textContent = data.plugins.total;
                document.getElementById('stat-enabled').textContent = data.plugins.enabled;
                document.getElementById('stat-php').textContent = data.php_version;
                document.getElementById('stat-sodium').innerHTML = data.sodium
                    ? '<span class="badge badge-success">已启用</span>'
                    : '<span class="badge badge-danger">未启用</span>';
                document.getElementById('stat-time').textContent = data.time;
            }
        }

        // Bots
        async function loadBots() {
            const data = await apiGet('bots');
            const container = document.getElementById('bots-list');

            if (!data.success || data.bots.length === 0) {
                container.innerHTML = `
                    <div class="table-container empty-state">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <p>暂无机器人，点击右上角添加</p>
                    </div>`;
                return;
            }

            let html = '<div class="table-container"><table><thead><tr><th>ID</th><th>名称</th><th>App ID</th><th>Secret</th><th>环境</th><th>默认</th><th>操作</th></tr></thead><tbody>';
            data.bots.forEach(bot => {
                html += `<tr>
                    <td><strong>${esc(bot.id)}</strong></td>
                    <td>${esc(bot.nickname)}</td>
                    <td><code>${esc(bot.app_id)}</code></td>
                    <td><code>${esc(bot.client_secret)}</code></td>
                    <td>${bot.sandbox ? '<span class="badge badge-warning">沙箱</span>' : '<span class="badge badge-success">正式</span>'}</td>
                    <td>${bot.id === data.default ? '<span class="badge badge-success">是</span>' : '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-secondary" onclick="editBot('${esc(bot.id)}')">编辑</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteBot('${esc(bot.id)}')">删除</button>
                    </td>
                </tr>`;
            });
            html += '</tbody></table></div>';

            // Webhook URLs
            let whHtml = '<h3 style="margin:24px 0 16px; font-size:16px;">各机器人 Webhook 回调地址</h3>';
            data.bots.forEach(bot => {
                const url = data.webhookUrl + encodeURIComponent(bot.id);
                whHtml += `<div style="margin-bottom:12px;">
                    <div style="font-size:12px; color:var(--text-secondary); margin-bottom:4px;">${esc(bot.nickname || bot.id)}</div>
                    <div class="webhook-url" style="display:flex; justify-content:space-between; align-items:center;">
                        <span>${url}</span>
                        <button class="btn btn-sm btn-secondary" onclick="navigator.clipboard.writeText('${url}')">复制</button>
                    </div>
                </div>`;
            });
            document.getElementById('webhook-urls').innerHTML = whHtml;

            container.innerHTML = html;
        }

        function openBotModal() {
            document.getElementById('botModalTitle').textContent = '添加机器人';
            document.getElementById('botForm').reset();
            document.getElementById('bot-edit-id').value = '';
            document.getElementById('bot-id').disabled = false;
            document.getElementById('botModal').classList.add('active');
        }

        function closeBotModal() {
            document.getElementById('botModal').classList.remove('active');
        }

        function editBot(id) {
            // 从表格中获取数据填充
            const rows = document.querySelectorAll('#bots-list tbody tr');
            rows.forEach(row => {
                if (row.cells[0].textContent.trim() === id) {
                    document.getElementById('botModalTitle').textContent = '编辑机器人';
                    document.getElementById('bot-edit-id').value = id;
                    document.getElementById('bot-id').value = id;
                    document.getElementById('bot-id').disabled = true;
                    document.getElementById('bot-appid').value = row.cells[2].textContent.trim();
                    document.getElementById('bot-secret').value = '';
                    document.getElementById('bot-nickname').value = row.cells[1].textContent.trim();
                    document.getElementById('botModal').classList.add('active');
                }
            });
        }

        async function saveBot(e) {
            e.preventDefault();
            const id = document.getElementById('bot-edit-id').value || document.getElementById('bot-id').value;
            const result = await api('save_bot', {
                id: id,
                app_id: document.getElementById('bot-appid').value,
                client_secret: document.getElementById('bot-secret').value,
                nickname: document.getElementById('bot-nickname').value,
                sandbox: document.getElementById('bot-sandbox').value,
                is_default: document.getElementById('bot-default').checked
            });

            if (result.success) {
                toast('机器人保存成功');
                closeBotModal();
                loadBots();
            } else {
                toast(result.message || '保存失败', 'error');
            }
        }

        async function deleteBot(id) {
            if (!confirm(`确定要删除机器人 "${id}" 吗？`)) return;
            const result = await api('delete_bot', { id });
            if (result.success) {
                toast('机器人已删除');
                loadBots();
            } else {
                toast(result.message || '删除失败', 'error');
            }
        }

        // Plugins
        async function loadPlugins() {
            const data = await apiGet('plugins');
            const container = document.getElementById('plugins-list');

            if (!data.success || data.plugins.length === 0) {
                container.innerHTML = `
                    <div class="empty-state" style="grid-column:1/-1;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/></svg>
                        <p>暂无插件，将插件文件放入 plugins/ 目录后刷新</p>
                    </div>`;
                return;
            }

            container.innerHTML = data.plugins.map(p => `
                <div class="plugin-card ${p.enabled ? '' : 'disabled'}" id="plugin-${p.name}">
                    <div class="plugin-header">
                        <div style="display:flex; align-items:center;">
                            <span class="plugin-icon">${p.icon || '🔌'}</span>
                            <div class="plugin-info">
                                <h3>${esc(p.displayName)}</h3>
                                <span class="version">v${esc(p.version)}</span>
                            </div>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" ${p.enabled ? 'checked' : ''} onchange="togglePlugin('${esc(p.name)}', this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <p class="plugin-desc">${esc(p.description)}</p>
                    <div class="plugin-tags">
                        ${(p.tags || []).map(t => `<span class="tag">${esc(t)}</span>`).join('')}
                    </div>
                    <div class="plugin-meta">
                        <span class="plugin-author">👤 ${esc(p.author)}</span>
                        <span class="badge ${p.enabled ? 'badge-success' : 'badge-danger'}">${p.enabled ? '已启用' : '已禁用'}</span>
                    </div>
                    <div style="margin-top:12px; font-size:11px; color:var(--text-secondary);">
                        类名：${esc(p.className)}${p.installedAt ? ' · 安装于 ' + p.installedAt : ''}
                    </div>
                </div>
            `).join('');
        }

        async function togglePlugin(name, enabled) {
            const result = await api('toggle_plugin', { name, enabled: String(enabled) });
            if (result.success) {
                toast(enabled ? `插件 "${name}" 已启用` : `插件 "${name}" 已禁用`);
                const card = document.getElementById('plugin-' + name);
                card.classList.toggle('disabled', !enabled);
                const badge = card.querySelector('.badge');
                badge.className = 'badge ' + (enabled ? 'badge-success' : 'badge-danger');
                badge.textContent = enabled ? '已启用' : '已禁用';
                loadStatus();
            } else {
                toast(result.message || '操作失败', 'error');
            }
        }

        // Escape HTML
        function esc(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // Prompt for API token on first load
        if (!API_TOKEN) {
            const token = prompt('请输入管理后台 API Token（首次访问）：');
            if (token) {
                localStorage.setItem('qqbot_api_token', token);
                location.reload();
            }
        }

        // Init
        loadStatus();
    </script>
</body>
</html>
