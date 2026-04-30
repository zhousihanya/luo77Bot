<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>自定义接口管理 v3.0</title>
<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<style>
/* ===== 基础 ===== */
*{margin:0;padding:0;box-sizing:border-box}
:root{
--bg:#0b0b14;--bg2:#13131f;--card:#1a1a2e;--card2:#222240;
--accent:#6366f1;--accent2:#818cf8;--accent3:#a5b4fc;
--border:#2d2d4a;--border2:#3d3d6a;
--text:#e2e8f0;--text2:#94a3b8;--text3:#64748b;
--danger:#ef4444;--success:#22c55e;--warning:#f59e0b;--info:#3b82f6;
--radius:10px;--radius-sm:6px;
}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:var(--text);line-height:1.6}
.container{max-width:1280px;margin:0 auto;padding:20px}

/* ===== 头部 ===== */
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px}
.header h1{font-size:22px;font-weight:700;display:flex;align-items:center;gap:8px}
.header .version{background:var(--accent);color:#fff;font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600}
.header .subtitle{color:var(--text2);font-size:12px;margin-top:2px}

/* ===== 工具栏 ===== */
.toolbar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:var(--radius-sm);border:none;cursor:pointer;font-size:13px;font-weight:500;transition:.2s;white-space:nowrap}
.btn-primary{background:var(--accent);color:#fff}.btn-primary:hover{background:var(--accent2)}
.btn-secondary{background:var(--card2);color:var(--text);border:1px solid var(--border)}.btn-secondary:hover{border-color:var(--accent2)}
.btn-success{background:rgba(34,197,94,0.15);color:var(--success);border:1px solid rgba(34,197,94,0.3)}
.btn-danger{background:rgba(239,68,68,0.15);color:var(--danger);border:1px solid rgba(239,68,68,0.3)}
.btn-sm{padding:5px 10px;font-size:12px}
.btn:disabled{opacity:.4;cursor:not-allowed}

/* ===== 卡片 ===== */
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:16px}
.card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
.card-title{font-size:16px;font-weight:600}

/* ===== 接口列表 ===== */
.api-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(360px,1fr));gap:12px}
.api-item{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:16px;transition:.2s;position:relative}
.api-item:hover{border-color:var(--accent2)}
.api-item.disabled{opacity:.45}
.api-item-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px}
.api-item-title{font-size:15px;font-weight:600;display:flex;align-items:center;gap:8px}
.api-item-cmd{font-size:12px;color:var(--accent);background:rgba(99,102,241,0.1);padding:2px 8px;border-radius:4px;font-family:monospace}
.api-item-desc{font-size:12px;color:var(--text2);margin-top:6px;line-height:1.5}
.api-item-meta{display:flex;gap:10px;margin-top:10px;font-size:11px;color:var(--text3);flex-wrap:wrap}
.api-item-actions{display:flex;gap:6px;margin-top:12px}

/* ===== 标签 ===== */
.badge{font-size:11px;padding:2px 8px;border-radius:4px;font-weight:500}
.badge-success{background:rgba(34,197,94,0.12);color:var(--success)}
.badge-danger{background:rgba(239,68,68,0.12);color:var(--danger)}
.badge-warn{background:rgba(245,158,11,0.12);color:var(--warning)}
.badge-info{background:rgba(59,130,246,0.12);color:var(--info)}
.badge-accent{background:rgba(99,102,241,0.12);color:var(--accent2)}

/* ===== 模态框 ===== */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:1000;display:flex;align-items:center;justify-content:center;padding:20px;overflow-y:auto;backdrop-filter:blur(4px)}
.modal{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);width:100%;max-width:900px;max-height:92vh;overflow-y:auto;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5)}
.modal-header{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;background:var(--card);z-index:10}
.modal-header h2{font-size:18px;font-weight:600}
.modal-close{width:32px;height:32px;border-radius:var(--radius-sm);border:none;background:var(--bg2);color:var(--text2);cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center}
.modal-close:hover{background:var(--border);color:var(--text)}
.modal-body{padding:24px}
.modal-footer{padding:16px 24px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;position:sticky;bottom:0;background:var(--card);z-index:10}

/* ===== 步骤导航 ===== */
.steps{display:flex;gap:0;margin-bottom:24px;position:relative}
.step{flex:1;text-align:center;padding:12px 8px;position:relative;cursor:default}
.step:not(:last-child)::after{content:'';position:absolute;top:20px;right:-50%;width:100%;height:2px;background:var(--border);z-index:0}
.step-num{width:32px;height:32px;border-radius:50%;background:var(--border);color:var(--text3);display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;margin-bottom:6px;position:relative;z-index:1;transition:.3s}
.step-label{font-size:12px;color:var(--text3);font-weight:500}
.step.active .step-num{background:var(--accent);color:#fff}
.step.active .step-label{color:var(--accent2)}
.step.active:not(:last-child)::after{background:linear-gradient(90deg,var(--accent),var(--border))}
.step.completed .step-num{background:var(--success);color:#fff}
.step.completed:not(:last-child)::after{background:var(--success)}
.step.completed .step-label{color:var(--success)}

/* ===== 表单 ===== */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:640px){.form-grid{grid-template-columns:1fr}}
.form-group{display:flex;flex-direction:column;gap:5px}
.form-group.full{grid-column:1/-1}
.form-group label{font-size:12px;color:var(--text2);font-weight:500;display:flex;align-items:center;gap:4px}
.form-group label .required{color:var(--danger)}
.form-group input,.form-group select,.form-group textarea{padding:10px 12px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:14px;outline:none;font-family:inherit;transition:.2s;width:100%}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(99,102,241,0.1)}
.form-group textarea{min-height:70px;resize:vertical;font-family:monospace;font-size:12px}
.form-hint{font-size:11px;color:var(--text3);line-height:1.4}
.form-error{font-size:11px;color:var(--danger);margin-top:2px}

/* ===== 响应模式选择卡片 ===== */
.mode-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:8px}
@media(max-width:640px){.mode-grid{grid-template-columns:repeat(2,1fr)}}
.mode-card{background:var(--bg2);border:2px solid var(--border);border-radius:var(--radius);padding:20px;text-align:center;cursor:pointer;transition:.2s;position:relative}
.mode-card:hover{border-color:var(--border2);transform:translateY(-1px)}
.mode-card.active{border-color:var(--accent);background:rgba(99,102,241,0.05)}
.mode-card .mode-icon{font-size:32px;margin-bottom:8px}
.mode-card .mode-name{font-size:14px;font-weight:600;margin-bottom:4px}
.mode-card .mode-desc{font-size:11px;color:var(--text3);line-height:1.4}
.mode-card .mode-check{position:absolute;top:8px;right:8px;width:20px;height:20px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;opacity:0;transition:.2s}
.mode-card.active .mode-check{opacity:1}

/* ===== JSON 树 ===== */
.json-tree{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:16px;max-height:400px;overflow-y:auto;font-family:monospace;font-size:12px;line-height:1.8}
.json-node{position:relative}
.json-node-key{color:var(--accent2);cursor:pointer;padding:1px 4px;border-radius:3px;transition:.15s;display:inline-flex;align-items:center;gap:4px}
.json-node-key:hover{background:rgba(99,102,241,0.1)}
.json-node-key.selected{background:var(--accent);color:#fff}
.json-node-string{color:var(--success)}
.json-node-number{color:var(--warning)}
.json-node-bool{color:var(--info)}
.json-node-null{color:var(--text3)}
.json-toggle{cursor:pointer;color:var(--text3);width:14px;display:inline-block;text-align:center;transition:.15s}
.json-toggle:hover{color:var(--accent2)}
.json-collapsible{display:none}
.json-collapsible.open{display:block}
.json-node-actions{position:absolute;right:0;top:0;display:none;gap:4px}
.json-node:hover > .json-node-actions{display:flex}
.json-node-action{font-size:10px;padding:1px 6px;border-radius:3px;border:none;cursor:pointer;background:var(--accent);color:#fff}

/* ===== 字段映射 ===== */
.field-list{display:flex;flex-direction:column;gap:8px}
.field-item{display:flex;align-items:center;gap:10px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px 14px;transition:.2s;cursor:move}
.field-item:hover{border-color:var(--border2)}
.field-item.dragging{opacity:.6;border-color:var(--accent)}
.field-drag-handle{color:var(--text3);cursor:grab;font-size:16px;padding:4px}
.field-drag-handle:active{cursor:grabbing}
.field-enable{width:18px;height:18px;accent-color:var(--accent);cursor:pointer;flex-shrink:0}
.field-key{font-family:monospace;font-size:12px;color:var(--accent2);background:rgba(99,102,241,0.08);padding:2px 8px;border-radius:3px;min-width:100px}
.field-label-input{flex:1;padding:6px 10px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:13px;outline:none}
.field-label-input:focus{border-color:var(--accent)}
.field-format-select{padding:6px 10px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:12px;outline:none;cursor:pointer}
.field-remove{color:var(--danger);background:none;border:none;cursor:pointer;font-size:16px;padding:4px;opacity:.6;transition:.15s}
.field-remove:hover{opacity:1}
.field-add{display:flex;gap:8px;align-items:center;margin-top:8px}

/* ===== 布局选择 ===== */
.layout-options{display:flex;gap:10px;flex-wrap:wrap}
.layout-option{padding:10px 18px;background:var(--bg2);border:2px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;transition:.2s;font-size:13px;font-weight:500}
.layout-option:hover{border-color:var(--border2)}
.layout-option.active{border-color:var(--accent);background:rgba(99,102,241,0.05)}

/* ===== 预览 ===== */
.preview-panel{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.preview-header{padding:12px 16px;background:var(--card2);border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
.preview-header h4{font-size:13px;font-weight:600}
.preview-body{padding:16px;font-size:13px;line-height:1.7;white-space:pre-wrap;max-height:400px;overflow-y:auto}
.preview-placeholder{color:var(--text3);text-align:center;padding:40px 20px}

/* ===== 测试区域 ===== */
.test-area{display:flex;gap:10px;margin-bottom:16px;align-items:flex-end}
.test-area .form-group{flex:1;margin:0}

/* ===== 建议标签 ===== */
.suggestion-list{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.suggestion-tag{padding:4px 10px;background:var(--bg2);border:1px solid var(--border);border-radius:20px;font-size:11px;cursor:pointer;transition:.15s;color:var(--text2)}
.suggestion-tag:hover{border-color:var(--accent);color:var(--accent2);background:rgba(99,102,241,0.05)}
.suggestion-tag .s-type{color:var(--text3);margin-left:4px}

/* ===== 详情接口 ===== */
.detail-toggle{display:flex;align-items:center;gap:10px;margin-bottom:16px;cursor:pointer;padding:10px 14px;background:var(--bg2);border-radius:var(--radius-sm);border:1px solid var(--border);transition:.2s}
.detail-toggle:hover{border-color:var(--border2)}
.detail-toggle input{width:18px;height:18px;accent-color:var(--accent)}

/* ===== Toast ===== */
.toast-container{position:fixed;top:20px;right:20px;z-index:2000;display:flex;flex-direction:column;gap:8px}
.toast{padding:12px 18px;border-radius:var(--radius-sm);color:#fff;font-size:13px;animation:slideIn .3s ease;max-width:360px;word-break:break-word}
.toast.success{background:var(--success)}.toast.error{background:var(--danger)}.toast.info{background:var(--info)}
@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}

/* ===== 空状态 ===== */
.empty{text-align:center;padding:60px 20px;color:var(--text3)}
.empty-icon{font-size:48px;margin-bottom:16px;opacity:.5}

/* ===== 滚动条 ===== */
::-webkit-scrollbar{width:6px;height:6px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:var(--border2)}

/* ===== 动画 ===== */
.fade-enter-active,.fade-leave-active{transition:opacity .25s}
.fade-enter-from,.fade-leave-to{opacity:0}
.slide-up-enter-active,.slide-up-leave-active{transition:all .3s ease}
.slide-up-enter-from{opacity:0;transform:translateY(20px)}
.slide-up-leave-to{opacity:0;transform:translateY(-20px)}

/* ===== 标签页 ===== */
.tabs{display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:16px}
.tab{padding:10px 18px;font-size:13px;font-weight:500;color:var(--text3);cursor:pointer;border-bottom:2px solid transparent;transition:.15s;margin-bottom:-1px;background:none;border:none}
.tab:hover{color:var(--text2)}
.tab.active{color:var(--accent2);border-bottom-color:var(--accent)}
</style>
</head>
<body>
<div id="app">
<div class="container">

<!-- Header -->
<div class="header">
<div>
<h1>自定义接口管理 <span class="version">v3.0</span></h1>
<div class="subtitle">可视化配置 · 零代码 · JSON 点击选择 · Markdown 实时预览</div>
</div>
</div>

<!-- Toolbar -->
<div class="toolbar">
<button class="btn btn-primary" @click="openWizard()">+ 添加接口</button>
<button class="btn btn-secondary" @click="loadApis()">刷新</button>
</div>

<!-- API List -->
<div class="card">
<div class="card-header">
<span class="card-title">接口列表</span>
<span style="font-size:12px;color:var(--text3)">{{ apis.length }} 个接口</span>
</div>
<div v-if="apis.length===0" class="empty">
<div class="empty-icon">📡</div>
<p>暂无接口配置</p>
<p style="font-size:12px;margin-top:8px">点击「添加接口」开始创建</p>
</div>
<div v-else class="api-grid">
<div v-for="api in apis" :key="api.id" class="api-item" :class="{disabled:!api.enabled}">
<div class="api-item-header">
<div class="api-item-title">
<span>{{ modeIcon(api.responseMode) }}</span>
<span>{{ api.name }}</span>
</div>
<span :class="api.enabled?'badge badge-success':'badge badge-danger'">{{ api.enabled?'启用':'禁用' }}</span>
</div>
<span class="api-item-cmd">{{ api.command }}</span>
<span class="badge badge-accent" style="margin-left:6px">{{ modeLabel(api.responseMode) }}</span>
<div class="api-item-desc">{{ api.description||'无描述' }}</div>
<div class="api-item-meta">
<span>{{ api.method||'GET' }}</span>
<span v-if="api.cacheSeconds">缓存 {{ api.cacheSeconds }}s</span>
<span v-if="api.isList">列表</span>
<span v-if="api.detailApiEnabled">详情接口</span>
</div>
<div class="api-item-actions">
<button class="btn btn-sm btn-secondary" @click="editApi(api)">编辑</button>
<button class="btn btn-sm" :class="api.enabled?'btn-secondary':'btn-success'" @click="toggleApi(api.id)">{{ api.enabled?'禁用':'启用' }}</button>
<button class="btn btn-sm btn-danger" @click="deleteApi(api.id)">删除</button>
</div>
</div>
</div>
</div>

</div>

<!-- ===== Wizard Modal ===== -->
<transition name="fade">
<div v-if="showWizard" class="modal-overlay" @click.self="closeWizard">
<div class="modal">
<div class="modal-header">
<h2>{{ isEdit?'编辑接口':'添加接口' }} - {{ wizard.api.name||'新接口' }}</h2>
<button class="modal-close" @click="closeWizard">&times;</button>
</div>

<!-- Steps -->
<div class="modal-body" style="padding-bottom:0">
<div class="steps">
<div v-for="(s,i) in steps" :key="i" class="step" :class="{active:wizard.step===i,completed:wizard.step>i}">
<div class="step-num"><template v-if="wizard.step>i">&#10003;</template><template v-else>{{ i+1 }}</template></div>
<div class="step-label">{{ s }}</div>
</div>
</div>
</div>

<div class="modal-body" style="padding-top:8px">

<!-- Step 1: 基础配置 -->
<transition name="slide-up" mode="out-in">
<div v-if="wizard.step===0" key="step0">
<div class="form-grid">
<div class="form-group">
<label>接口名称 <span class="required">*</span></label>
<input v-model="wizard.api.name" placeholder="如：每日笑话" maxlength="30">
</div>
<div class="form-group">
<label>触发指令 <span class="required">*</span></label>
<input v-model="wizard.api.command" placeholder="如：笑话（用户发送「笑话」触发）" maxlength="20">
</div>
<div class="form-group full">
<label>请求 URL <span class="required">*</span></label>
<input v-model="wizard.api.url" placeholder="https://api.example.com/joke?query={arg1}">
<span class="form-hint">使用 {arg1} {arg2} ... 作为参数占位符，用户用空格分隔参数</span>
</div>
<div class="form-group">
<label>请求方法</label>
<select v-model="wizard.api.method">
<option value="GET">GET</option>
<option value="POST">POST</option>
</select>
</div>
<div class="form-group">
<label>描述（可选）</label>
<input v-model="wizard.api.description" placeholder="接口功能说明">
</div>
<div class="form-group full">
<label>请求 Headers（JSON，可选）</label>
<textarea v-model="wizard.headersJson" placeholder='{"User-Agent": "Mozilla/5.0", "Authorization": "Bearer xxx"}'></textarea>
</div>
<div class="form-group full">
<label>请求 Body（POST 用，可选）</label>
<textarea v-model="wizard.api.body" placeholder='{"query": "{arg1}"}'></textarea>
<span class="form-hint">支持 {arg1} {arg2} ... 参数占位符</span>
</div>
</div>
</div>
</transition>

<!-- Step 2: 响应类型 -->
<transition name="slide-up" mode="out-in">
<div v-if="wizard.step===1" key="step1">
<p style="color:var(--text2);font-size:13px;margin-bottom:16px">选择接口返回的数据类型，将决定 QQ 中如何展示结果</p>
<div class="mode-grid">
<div v-for="m in responseModes" :key="m.value" class="mode-card" :class="{active:wizard.api.responseMode===m.value}" @click="wizard.api.responseMode=m.value">
<div class="mode-check">&#10003;</div>
<div class="mode-icon">{{ m.icon }}</div>
<div class="mode-name">{{ m.label }}</div>
<div class="mode-desc">{{ m.desc }}</div>
</div>
</div>

<div v-if="isJsonMode" style="margin-top:20px;padding:16px;background:var(--bg2);border-radius:var(--radius);border:1px solid var(--border)">
<p style="font-size:13px;font-weight:600;margin-bottom:8px">📊 JSON 数据模式说明</p>
<p style="font-size:12px;color:var(--text3);line-height:1.8">
接口返回 JSON，通过点击选择数据路径，可视化配置 Markdown 排版。<br>
适合：天气查询、新闻、翻译、搜索等结构化数据接口。
</p>
</div>
<div v-else-if="isDirectMediaMode" style="margin-top:20px;padding:16px;background:var(--bg2);border-radius:var(--radius);border:1px solid var(--border)">
<p style="font-size:13px;font-weight:600;margin-bottom:8px">{{ modeIcon(wizard.api.responseMode) }} 直接媒体模式说明</p>
<p style="font-size:12px;color:var(--text3);line-height:1.8">
接口直接返回媒体文件的 URL，QQ 自动发送对应类型的消息。<br>
如果接口返回的是 JSON 包裹的 URL，可在下一步配置 JSON 提取路径。
</p>
</div>
</div>
</transition>

<!-- Step 3: JSON / 媒体配置 -->
<transition name="slide-up" mode="out-in">
<div v-if="wizard.step===2" key="step2">

<!-- JSON 数据模式 -->
<template v-if="isJsonMode">
<!-- 测试区域 -->
<div style="margin-bottom:20px">
<h4 style="font-size:14px;font-weight:600;margin-bottom:10px">1. 测试接口获取 JSON</h4>
<div class="test-area">
<div class="form-group">
<label>测试参数（空格分隔，可选）</label>
<input v-model="wizard.testArgs" placeholder="输入测试参数，如：北京">
</div>
<button class="btn btn-primary" @click="testApi" :disabled="testing">
<template v-if="testing">测试中...</template>
<template v-else>测试接口</template>
</button>
</div>
</div>

<!-- JSON 树 -->
<div v-if="testResult" style="margin-bottom:20px">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
<h4 style="font-size:14px;font-weight:600">2. 点击选择数据路径</h4>
<div class="badge badge-info" v-if="selectedPath">已选：{{ selectedPath }}</div>
</div>

<!-- Tabs -->
<div class="tabs">
<button class="tab" :class="{active:jsonTab==='tree'}" @click="jsonTab='tree'">JSON 树</button>
<button class="tab" :class="{active:jsonTab==='raw'}" @click="jsonTab='raw'">原始数据</button>
</div>

<div v-if="jsonTab==='tree'" class="json-tree">
<json-tree-node :data="testResult.data" :path="''" :level="0" :selected-path="wizard.api.jsonPath" @select="onPathSelect"></json-tree-node>
</div>
<div v-else class="json-tree" style="white-space:pre-wrap">{{ JSON.stringify(testResult.data,null,2) }}</div>

<!-- 路径建议 -->
<div v-if="testResult.suggestions && testResult.suggestions.length" style="margin-top:10px">
<span style="font-size:12px;color:var(--text3)">快速选择：</span>
<div class="suggestion-list">
<span v-for="s in testResult.suggestions.slice(0,15)" :key="s.path" class="suggestion-tag" @click="applySuggestion(s)">
{{ s.path }}<span class="s-type">{{ s.type }}</span>
</span>
</div>
</div>
</div>

<!-- 字段映射 -->
<div v-if="wizard.api.jsonPath" style="margin-bottom:20px">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
<h4 style="font-size:14px;font-weight:600">3. 字段映射与 Markdown 排版</h4>
<button class="btn btn-sm btn-secondary" @click="autoDetectFields">自动识别字段</button>
</div>

<!-- 是否列表 -->
<div style="display:flex;gap:16px;margin-bottom:12px;align-items:center">
<label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px">
<input type="checkbox" v-model="wizard.api.isList" style="accent-color:var(--accent)">
<span>返回的是数组（列表数据）</span>
</label>
<div v-if="wizard.api.isList" class="form-group" style="flex:1;max-width:300px;margin:0">
<input v-model="wizard.api.listKey" placeholder="列表键路径，如：data.items（留空使用已选路径）">
</div>
</div>

<!-- 布局选择 -->
<div style="margin-bottom:12px">
<span style="font-size:12px;color:var(--text2);font-weight:500">排版布局：</span>
<div class="layout-options" style="margin-top:6px">
<div v-for="l in layouts" :key="l.value" class="layout-option" :class="{active:wizard.api.markdownLayout===l.value}" @click="wizard.api.markdownLayout=l.value">
{{ l.label }}
</div>
</div>
</div>

<!-- 字段列表 -->
<div class="field-list">
<div v-for="(field,index) in wizard.api.fieldMapping" :key="index" class="field-item" draggable="true"
@dragstart="dragStart(index)" @dragover.prevent="dragOver(index)" @drop="drop(index)" @dragend="dragEnd"
:class="{dragging:dragIndex===index}">
<span class="field-drag-handle">&#9776;</span>
<input type="checkbox" class="field-enable" v-model="field.enabled">
<span class="field-key">{{ field.key }}</span>
<input class="field-label-input" v-model="field.label" placeholder="显示名称">
<select class="field-format-select" v-model="field.format">
<option v-for="f in fieldFormats" :key="f.value" :value="f.value">{{ f.label }}</option>
</select>
<button class="field-remove" @click="removeField(index)">&times;</button>
</div>
</div>
<div class="field-add">
<input v-model="wizard.newFieldKey" placeholder="输入字段路径或选择" style="flex:1;padding:8px 12px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:13px" @keyup.enter="addField">
<button class="btn btn-sm btn-secondary" @click="addField">+ 添加字段</button>
</div>

<!-- 自定义模板 -->
<div class="form-group full" style="margin-top:12px">
<label>自定义 Markdown 模板（可选，留空使用自动排版）</label>
<textarea v-model="wizard.api.markdownTemplate" placeholder="用 {字段名} 作为占位符，如：标题：{title}
链接：[点击访问]({url})
---" style="min-height:80px"></textarea>
<span class="form-hint">用 {字段名} 引用字段值，支持 Markdown 语法</span>
</div>
</div>

<!-- 媒体 URL 路径 -->
<div v-if="wizard.api.jsonPath" style="margin-bottom:20px;padding:16px;background:var(--bg2);border-radius:var(--radius);border:1px solid var(--border)">
<h4 style="font-size:13px;font-weight:600;margin-bottom:8px">媒体 URL 提取（可选）</h4>
<div class="form-group" style="margin:0">
<input v-model="wizard.api.mediaUrlPath" placeholder="如：url 或 data.url（从 JSON 中提取媒体文件 URL）">
<span class="form-hint">如果数据中有图片/音频/视频 URL，填写路径可以自动发送媒体消息</span>
</div>
</div>

<!-- 预览 -->
<div v-if="wizard.api.fieldMapping.length" style="margin-bottom:20px">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
<h4 style="font-size:14px;font-weight:600">4. 实时预览</h4>
<button class="btn btn-sm btn-success" @click="previewMarkdown" :disabled="previewing">
<template v-if="previewing">生成中...</template>
<template v-else>刷新预览</template>
</button>
</div>
<div class="preview-panel">
<div class="preview-header">
<h4>Markdown 预览</h4>
<span class="badge badge-accent">{{ wizard.api.markdownLayout }}</span>
</div>
<div class="preview-body" v-if="previewResult">{{ previewResult.content }}</div>
<div class="preview-body preview-placeholder" v-else>点击「刷新预览」查看效果</div>
</div>
</div>
</template>

<!-- 直接媒体模式 -->
<template v-else-if="isDirectMediaMode">
<div class="form-grid">
<div class="form-group full">
<h4 style="font-size:14px;font-weight:600;margin-bottom:12px">{{ modeIcon(wizard.api.responseMode) }} 媒体 URL 配置</h4>
<p style="font-size:12px;color:var(--text3);margin-bottom:16px;line-height:1.8">
如果接口直接返回 URL 文本，无需额外配置。<br>
如果接口返回的是 JSON 包裹的 URL，请填写 JSON 路径来提取媒体 URL。
</p>
</div>
<div class="form-group full">
<label>媒体 URL 的 JSON 路径（可选）</label>
<input v-model="wizard.api.mediaUrlPath" placeholder="如：data.url 或 url（从 JSON 响应中提取媒体 URL）">
<span class="form-hint">接口直接返回 URL 时留空即可</span>
</div>
</div>

<!-- 媒体模式也支持测试 -->
<div style="margin-top:20px">
<h4 style="font-size:14px;font-weight:600;margin-bottom:10px">测试接口</h4>
<div class="test-area">
<div class="form-group">
<label>测试参数（可选）</label>
<input v-model="wizard.testArgs" placeholder="输入测试参数">
</div>
<button class="btn btn-primary" @click="testApi" :disabled="testing">测试</button>
</div>
<div v-if="testResult" class="preview-panel" style="margin-top:12px">
<div class="preview-header"><h4>响应结果</h4><span class="badge badge-info">{{ testResult.isJson?'JSON':'Text' }}</span></div>
<div class="preview-body" style="white-space:pre-wrap;font-size:12px">{{ JSON.stringify(testResult.data,null,2).slice(0,2000) }}</div>
</div>
</div>
</template>

<!-- 直接文本/Markdown 模式 -->
<template v-else>
<div style="padding:40px;text-align:center;color:var(--text3)">
<div style="font-size:48px;margin-bottom:16px">{{ modeIcon(wizard.api.responseMode) }}</div>
<p style="font-size:14px">{{ modeLabel(wizard.api.responseMode) }} 模式无需额外配置</p>
<p style="font-size:12px;margin-top:8px">接口返回的内容将直接作为{{ wizard.api.responseMode==='direct_markdown'?'Markdown':'文本' }}发送</p>
</div>
</template>
</div>
</transition>

<!-- Step 4: 高级设置 -->
<transition name="slide-up" mode="out-in">
<div v-if="wizard.step===3" key="step3">
<div class="form-grid">
<div class="form-group">
<label>缓存时间（秒，0=不缓存）</label>
<input type="number" v-model.number="wizard.api.cacheSeconds" min="0" max="3600" placeholder="0">
<span class="form-hint">相同参数的请求会缓存结果</span>
</div>
<div class="form-group">
<label>超时时间（秒）</label>
<input type="number" v-model.number="wizard.api.timeout" min="5" max="60" placeholder="20">
</div>
</div>

<!-- 详情接口 -->
<div style="margin-top:24px">
<div class="detail-toggle" @click="wizard.api.detailApiEnabled=!wizard.api.detailApiEnabled">
<input type="checkbox" v-model="wizard.api.detailApiEnabled" @click.stop>
<div>
<div style="font-size:13px;font-weight:600">详情接口</div>
<div style="font-size:11px;color:var(--text3)">列表模式下，为每项配置独立的详情查询接口</div>
</div>
</div>

<div v-if="wizard.api.detailApiEnabled" style="padding:16px;background:var(--bg2);border-radius:var(--radius);border:1px solid var(--border)">
<div class="form-grid">
<div class="form-group full">
<label>详情接口 URL</label>
<input v-model="wizard.api.detailApiUrl" placeholder="https://api.example.com/detail?id={arg1}">
</div>
<div class="form-group">
<label>请求方法</label>
<select v-model="wizard.api.detailApiMethod"><option value="GET">GET</option><option value="POST">POST</option></select>
</div>
<div class="form-group">
<label>数据路径</label>
<input v-model="wizard.api.detailApiJsonPath" placeholder="如：data">
</div>
<div class="form-group full">
<label>请求 Headers（JSON）</label>
<textarea v-model="wizard.detailApiHeadersJson" placeholder='{"Authorization": "Bearer xxx"}'></textarea>
</div>
<div class="form-group full">
<label>请求 Body</label>
<textarea v-model="wizard.api.detailApiBody" placeholder='{"id": "{arg1}"}'></textarea>
</div>
</div>
</div>
</div>
</div>
</transition>

</div>

<!-- Footer -->
<div class="modal-footer">
<button class="btn btn-secondary" @click="prevStep" v-if="wizard.step>0">上一步</button>
<div v-else></div>
<div style="display:flex;gap:10px">
<button class="btn btn-secondary" @click="closeWizard">取消</button>
<button class="btn btn-primary" @click="nextStep" v-if="wizard.step<steps.length-1">下一步</button>
<button class="btn btn-success" @click="saveApi" v-else :disabled="saving">
<template v-if="saving">保存中...</template>
<template v-else>{{ isEdit?'保存修改':'创建接口' }}</template>
</button>
</div>
</div>
</div>
</div>
</transition>

<!-- Toast -->
<div class="toast-container">
<transition-group name="fade">
<div v-for="t in toasts" :key="t.id" class="toast" :class="t.type">{{ t.message }}</div>
</transition-group>
</div>

</div>

<script>
const { createApp, ref, computed, reactive, nextTick } = Vue;

// ===== JSON Tree 组件 =====
const JsonTreeNode = {
props: ['data', 'path', 'level', 'selectedPath'],
emits: ['select'],
setup(props, { emit }) {
const collapsed = ref(props.level > 2);
const isObject = computed(() => typeof props.data === 'object' && props.data !== null);
const isArray = computed(() => Array.isArray(props.data));
const keys = computed(() => isObject.value ? Object.keys(props.data) : []);
const hasChildren = computed(() => isObject.value && keys.value.length > 0);
const displayValue = computed(() => {
if (props.data === null) return { text: 'null', cls: 'json-node-null' };
if (typeof props.data === 'boolean') return { text: props.data ? 'true' : 'false', cls: 'json-node-bool' };
if (typeof props.data === 'number') return { text: String(props.data), cls: 'json-node-number' };
if (typeof props.data === 'string') {
const s = props.data.length > 80 ? props.data.slice(0, 80) + '...' : props.data;
return { text: '"' + s + '"', cls: 'json-node-string' };
}
if (isArray.value) return { text: '[] ' + keys.value.length + ' items', cls: 'json-node-null' };
return { text: '{} ' + keys.value.length + ' keys', cls: 'json-node-null' };
});

function toggle() { if (hasChildren.value) collapsed.value = !collapsed.value; }
function select(key) {
const fullPath = props.path ? props.path + '.' + key : key;
emit('select', fullPath, props.data[key]);
}
function selectMe() {
if (!hasChildren.value) {
emit('select', props.path || '', props.data);
}
}

return { collapsed, isObject, isArray, keys, hasChildren, displayValue, toggle, select, selectMe };
},
template: `
<div class="json-node" :style="{paddingLeft: level*16+'px'}">
<template v-if="!isObject">
<span class="json-node-key" :class="{selected: path===selectedPath}" @click="selectMe">{{ path ? path.split('.').pop() : 'root' }}</span>
<span>:</span>
<span :class="displayValue.cls">{{ displayValue.text }}</span>
</template>
<template v-else>
<div style="position:relative">
<span class="json-toggle" @click="toggle">{{ collapsed ? '▶' : '▼' }}</span>
<span class="json-node-key" :class="{selected: path===selectedPath}" @click="selectMe">
{{ path ? path.split('.').pop() : 'root' }}
</span>
<span :class="displayValue.cls">{{ displayValue.text }}</span>
<div class="json-node-actions">
<button class="json-node-action" @click="select(path ? path.split('.').pop() : '')" v-if="path">选此路径</button>
</div>
</div>
<div class="json-collapsible" :class="{open:!collapsed}">
<div v-for="key in keys" :key="key">
<JsonTreeNode :data="data[key]" :path="path ? path+'.'+key : key" :level="level+1" :selected-path="selectedPath" @select="(p,v)=>$emit('select',p,v)"></JsonTreeNode>
</div>
</div>
</template>
</div>
`
};

// ===== 主应用 =====
createApp({
components: { JsonTreeNode },
setup() {
const apis = ref([]);
const showWizard = ref(false);
const isEdit = ref(false);
const saving = ref(false);
const testing = ref(false);
const previewing = ref(false);
const testResult = ref(null);
const previewResult = ref(null);
const jsonTab = ref('tree');
const selectedPath = ref('');
const dragIndex = ref(-1);
const toasts = ref([]);

const steps = ['基础配置', '响应类型', '数据配置', '高级设置'];
const layouts = [
{ value: 'card', label: '卡片' },
{ value: 'table', label: '表格' },
{ value: 'list', label: '列表' },
{ value: 'custom', label: '紧凑' },
];
const fieldFormats = [
{ value: 'text', label: '文本' },
{ value: 'bold', label: '粗体' },
{ value: 'link', label: '链接' },
{ value: 'image', label: '图片' },
{ value: 'code', label: '代码' },
];
const responseModes = [
{ value: 'direct_text', label: '纯文本', icon: '📝', desc: '接口直接返回文本内容' },
{ value: 'direct_markdown', label: 'Markdown', icon: '📄', desc: '接口直接返回 Markdown' },
{ value: 'direct_image', label: '图片', icon: '🖼️', desc: '接口返回图片 URL' },
{ value: 'direct_audio', label: '音频', icon: '🎵', desc: '接口返回音频 URL（mp3）' },
{ value: 'direct_video', label: '视频', icon: '🎬', desc: '接口返回视频 URL（mp4）' },
{ value: 'json_data', label: 'JSON 数据', icon: '📊', desc: '接口返回 JSON，可视化排版' },
];

const defaultApi = () => ({
name: '', command: '', description: '', enabled: true,
url: '', method: 'GET', headers: {}, body: '',
responseMode: 'json_data',
jsonPath: '', isList: false, listKey: '',
fieldMapping: [], markdownLayout: 'card', markdownTemplate: '',
mediaUrlPath: '',
detailApiEnabled: false, detailApiUrl: '', detailApiMethod: 'GET',
detailApiHeaders: {}, detailApiBody: '', detailApiJsonPath: '', detailApiFieldMapping: [],
cacheSeconds: 0, timeout: 20,
});

const wizard = reactive({
step: 0,
api: defaultApi(),
headersJson: '',
detailApiHeadersJson: '',
testArgs: '',
newFieldKey: '',
});

const isJsonMode = computed(() => wizard.api.responseMode === 'json_data');
const isDirectMediaMode = computed(() => ['direct_image', 'direct_audio', 'direct_video', 'direct_file'].includes(wizard.api.responseMode));

function toast(message, type = 'success') {
const id = Date.now();
toasts.value.push({ id, message, type });
setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id); }, 3000);
}

async function api(action, data = {}) {
const form = new URLSearchParams(data);
const res = await fetch('api_admin_api.php?action=' + action, { method: 'POST', body: form });
const text = await res.text();
try {
const json = JSON.parse(text);
return json;
} catch (e) {
const titleMatch = text.match(/<title>([^<]*)<\/title>/i);
const msgMatch = text.match(/<(b|strong)[^>]*>([^<]*(?:error|fatal|warning|parse)[^<]*)<\/\1>/i);
const title = titleMatch ? titleMatch[1] : '服务器返回非 JSON 数据';
const detail = msgMatch ? msgMatch[2] : '';
console.error('API ' + action + ' raw response:', text.slice(0, 800));
return { success: false, message: title + (detail ? ': ' + detail : '') + ' (HTTP ' + res.status + ')', _raw: text.slice(0, 800) };
}
}

async function loadApis() {
const res = await api('list');
if (res.success) apis.value = res.apis || [];
else toast('加载失败', 'error');
}

function modeIcon(mode) {
const m = responseModes.find(r => r.value === mode);
return m ? m.icon : '📡';
}
function modeLabel(mode) {
const m = responseModes.find(r => r.value === mode);
return m ? m.label : mode;
}

function openWizard() {
isEdit.value = false;
Object.assign(wizard.api, defaultApi());
wizard.step = 0;
wizard.headersJson = '';
wizard.detailApiHeadersJson = '';
wizard.testArgs = '';
wizard.newFieldKey = '';
testResult.value = null;
previewResult.value = null;
showWizard.value = true;
}

function closeWizard() {
showWizard.value = false;
}

async function editApi(api) {
isEdit.value = true;
Object.assign(wizard.api, JSON.parse(JSON.stringify(api)));
wizard.step = 0;
wizard.headersJson = typeof api.headers === 'object' ? JSON.stringify(api.headers, null, 2) : '';
wizard.detailApiHeadersJson = typeof api.detailApiHeaders === 'object' ? JSON.stringify(api.detailApiHeaders, null, 2) : '';
wizard.testArgs = '';
wizard.newFieldKey = '';
testResult.value = null;
previewResult.value = null;
showWizard.value = true;
}

function validateStep(step) {
if (step === 0) {
if (!wizard.api.name) { toast('请填写接口名称', 'error'); return false; }
if (!wizard.api.command) { toast('请填写触发指令', 'error'); return false; }
if (!wizard.api.url) { toast('请填写请求 URL', 'error'); return false; }
}
return true;
}

function nextStep() {
if (!validateStep(wizard.step)) return;
if (wizard.step < steps.length - 1) wizard.step++;
}

function prevStep() {
if (wizard.step > 0) wizard.step--;
}

async function saveApi() {
saving.value = true;
try {
const data = {
id: wizard.api.id || ('api_' + Date.now()),
name: wizard.api.name,
command: wizard.api.command,
description: wizard.api.description,
enabled: wizard.api.enabled ? '1' : '0',
url: wizard.api.url,
method: wizard.api.method,
headers: wizard.headersJson,
body: wizard.api.body,
responseMode: wizard.api.responseMode,
jsonPath: wizard.api.jsonPath,
isList: wizard.api.isList ? '1' : '0',
listKey: wizard.api.listKey,
fieldMapping: JSON.stringify(wizard.api.fieldMapping),
markdownLayout: wizard.api.markdownLayout,
markdownTemplate: wizard.api.markdownTemplate,
mediaUrlPath: wizard.api.mediaUrlPath,
detailApiEnabled: wizard.api.detailApiEnabled ? '1' : '0',
detailApiUrl: wizard.api.detailApiUrl,
detailApiMethod: wizard.api.detailApiMethod,
detailApiHeaders: wizard.detailApiHeadersJson,
detailApiBody: wizard.api.detailApiBody,
detailApiJsonPath: wizard.api.detailApiJsonPath,
detailApiFieldMapping: JSON.stringify(wizard.api.detailApiFieldMapping || []),
cacheSeconds: String(wizard.api.cacheSeconds || 0),
timeout: String(wizard.api.timeout || 20),
};
const res = await api('save', data);
if (res.success) {
toast(isEdit.value ? '修改成功' : '创建成功');
closeWizard();
loadApis();
} else {
toast(res.message || '保存失败', 'error');
}
} catch (e) {
toast('保存失败: ' + e.message, 'error');
} finally {
saving.value = false;
}
}

async function toggleApi(id) {
const res = await api('toggle', { id });
if (res.success) { toast('已切换'); loadApis(); }
else toast('操作失败', 'error');
}

async function deleteApi(id) {
if (!confirm('确定删除此接口？')) return;
const res = await api('delete', { id });
if (res.success) { toast('已删除'); loadApis(); }
else toast('删除失败', 'error');
}

// ===== 测试与预览 =====

async function testApi() {
testing.value = true;
testResult.value = null;
try {
const data = {
id: wizard.api.id || '_new',
args: wizard.testArgs,
// 对于新接口，把当前表单数据传过去测试
...(!wizard.api.id ? {
url: wizard.api.url,
method: wizard.api.method,
headers: wizard.headersJson,
body: wizard.api.body,
responseMode: wizard.api.responseMode,
jsonPath: wizard.api.jsonPath,
isList: wizard.api.isList ? '1' : '0',
listKey: wizard.api.listKey,
fieldMapping: JSON.stringify(wizard.api.fieldMapping),
markdownLayout: wizard.api.markdownLayout,
markdownTemplate: wizard.api.markdownTemplate,
mediaUrlPath: wizard.api.mediaUrlPath,
cacheSeconds: '0',
timeout: String(wizard.api.timeout || 20),
} : {})
};

// 新接口没有 id，用 preview 端点
const action = wizard.api.id ? 'test' : 'preview';
if (action === 'preview') {
data.api = JSON.stringify({
url: wizard.api.url,
method: wizard.api.method,
headers: parseJson(wizard.headersJson),
body: wizard.api.body,
responseMode: wizard.api.responseMode,
jsonPath: wizard.api.jsonPath,
isList: wizard.api.isList,
listKey: wizard.api.listKey,
fieldMapping: wizard.api.fieldMapping,
markdownLayout: wizard.api.markdownLayout,
markdownTemplate: wizard.api.markdownTemplate,
mediaUrlPath: wizard.api.mediaUrlPath,
timeout: wizard.api.timeout || 20,
});
}

const res = await api(action, data);
if (res.success) {
testResult.value = res;
// 如果是 JSON 且有建议路径，自动填充 jsonPath
if (res.isJson && res.suggestions && res.suggestions.length && !wizard.api.jsonPath) {
const best = res.suggestions.find(s => s.type.includes('array') || s.type === 'object') || res.suggestions[0];
if (best) wizard.api.jsonPath = best.path;
}
} else {
toast(res.message || '测试失败', 'error');
}
} catch (e) {
toast('测试失败: ' + e.message, 'error');
} finally {
testing.value = false;
}
}

async function previewMarkdown() {
if (!wizard.api.jsonPath) { toast('请先选择数据路径', 'error'); return; }
previewing.value = true;
try {
const apiConfig = {
url: wizard.api.url,
method: wizard.api.method,
headers: parseJson(wizard.headersJson),
body: wizard.api.body,
responseMode: 'json_data',
jsonPath: wizard.api.jsonPath,
isList: wizard.api.isList,
listKey: wizard.api.listKey,
fieldMapping: wizard.api.fieldMapping,
markdownLayout: wizard.api.markdownLayout,
markdownTemplate: wizard.api.markdownTemplate,
mediaUrlPath: wizard.api.mediaUrlPath,
timeout: wizard.api.timeout || 20,
};
const res = await api('preview', {
api: JSON.stringify(apiConfig),
args: wizard.testArgs
});
if (res.success) {
previewResult.value = res;
} else {
toast(res.message || '预览失败', 'error');
}
} catch (e) {
toast('预览失败: ' + e.message, 'error');
} finally {
previewing.value = false;
}
}

// ===== JSON 树交互 =====

function onPathSelect(path, value) {
wizard.api.jsonPath = path;
selectedPath.value = path;
// 自动识别是否为数组
if (Array.isArray(value) && value.length > 0) {
wizard.api.isList = true;
wizard.api.listKey = path;
autoDetectFieldsFromSample(value[0]);
} else if (typeof value === 'object' && value !== null) {
wizard.api.isList = false;
autoDetectFieldsFromSample(value);
}
toast('已选择路径: ' + path, 'info');
}

function applySuggestion(s) {
if (s.type.includes('array')) {
wizard.api.jsonPath = s.path;
wizard.api.isList = true;
wizard.api.listKey = s.path;
// 尝试获取数组样本
const sample = testResult.value?.data;
if (sample) {
const arr = extractPath(sample, s.path);
if (Array.isArray(arr) && arr[0]) autoDetectFieldsFromSample(arr[0]);
}
} else if (s.type === 'object') {
wizard.api.jsonPath = s.path;
wizard.api.isList = false;
const sample = testResult.value?.data;
if (sample) {
const obj = extractPath(sample, s.path);
if (obj && typeof obj === 'object') autoDetectFieldsFromSample(obj);
}
} else {
wizard.api.jsonPath = s.path;
}
selectedPath.value = wizard.api.jsonPath;
}

function autoDetectFields() {
if (!testResult.value || !testResult.value.isJson) { toast('请先测试接口获取 JSON', 'error'); return; }
const data = testResult.value.data;
let sample = extractPath(data, wizard.api.jsonPath);
if (Array.isArray(sample) && sample[0]) {
sample = sample[0];
wizard.api.isList = true;
} else if (Array.isArray(sample)) {
toast('数组为空，无法识别字段', 'error');
return;
}
if (sample && typeof sample === 'object') {
autoDetectFieldsFromSample(sample);
toast('已自动识别字段', 'success');
} else {
toast('选中路径不是对象', 'error');
}
}

function autoDetectFieldsFromSample(obj) {
if (!obj || typeof obj !== 'object') return;
const existingKeys = new Set(wizard.api.fieldMapping.map(f => f.key));
const newFields = [];
Object.keys(obj).forEach(key => {
if (existingKeys.has(key)) return;
const value = obj[key];
let format = 'text';
if (typeof value === 'string' && value.match(/^https?:\/\//)) {
format = obj[key].match(/\.(jpg|jpeg|png|gif|webp)/i) ? 'image' : 'link';
}
newFields.push({
key: key,
label: autoLabel(key),
enabled: true,
format: format
});
});
wizard.api.fieldMapping = [...wizard.api.fieldMapping, ...newFields];
}

function autoLabel(key) {
const map = {
name: '名称', title: '标题', content: '内容', text: '文本',
url: '链接', link: '链接', href: '链接', pic: '图片', image: '图片', img: '图片',
author: '作者', user: '用户', time: '时间', date: '日期', created: '创建时间',
description: '描述', desc: '描述', summary: '摘要', intro: '简介',
price: '价格', count: '数量', num: '数量', total: '总数',
status: '状态', type: '类型', category: '分类', tag: '标签',
address: '地址', location: '位置', city: '城市', area: '地区',
phone: '电话', email: '邮箱', qq: 'QQ', wechat: '微信',
};
return map[key.toLowerCase()] || key;
}

function extractPath(data, path) {
if (!path) return data;
const keys = path.split('.');
let current = data;
for (const key of keys) {
if (current && typeof current === 'object' && key in current) {
current = current[key];
} else {
return null;
}
}
return current;
}

// ===== 字段拖拽 =====

function dragStart(index) { dragIndex.value = index; }
function dragOver(index) {
if (dragIndex.value === -1 || dragIndex.value === index) return;
const item = wizard.api.fieldMapping.splice(dragIndex.value, 1)[0];
wizard.api.fieldMapping.splice(index, 0, item);
dragIndex.value = index;
}
function drop(index) { dragIndex.value = -1; }
function dragEnd() { dragIndex.value = -1; }

function addField() {
const key = wizard.newFieldKey.trim();
if (!key) return;
if (wizard.api.fieldMapping.some(f => f.key === key)) { toast('字段已存在', 'error'); return; }
wizard.api.fieldMapping.push({ key, label: autoLabel(key), enabled: true, format: 'text' });
wizard.newFieldKey = '';
}

function removeField(index) {
wizard.api.fieldMapping.splice(index, 1);
}

function parseJson(str) {
if (!str) return {};
try { return JSON.parse(str); } catch { return {}; }
}

// 初始化
loadApis();

return {
apis, showWizard, isEdit, saving, testing, previewing,
steps, layouts, fieldFormats, responseModes,
wizard, isJsonMode, isDirectMediaMode,
testResult, previewResult, jsonTab, selectedPath, dragIndex, toasts,
loadApis, openWizard, closeWizard, editApi, saveApi,
toggleApi, deleteApi, nextStep, prevStep,
testApi, previewMarkdown, onPathSelect, applySuggestion,
autoDetectFields, addField, removeField,
dragStart, dragOver, drop, dragEnd,
modeIcon, modeLabel, toast
};
}
}).mount('#app');
</script>
</body>
</html>
