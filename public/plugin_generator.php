<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>插件生成器 — 可视化代码生成</title>
<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<style>
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
.container{max-width:1200px;margin:0 auto;padding:20px}

.header{margin-bottom:20px}
.header h1{font-size:22px;font-weight:700}
.header .subtitle{color:var(--text2);font-size:13px;margin-top:4px}

.toolbar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:var(--radius-sm);border:none;cursor:pointer;font-size:13px;font-weight:500;transition:.2s;white-space:nowrap}
.btn-primary{background:var(--accent);color:#fff}.btn-primary:hover{background:var(--accent2)}
.btn-secondary{background:var(--card2);color:var(--text);border:1px solid var(--border)}.btn-secondary:hover{border-color:var(--accent2)}
.btn-success{background:rgba(34,197,94,0.15);color:var(--success);border:1px solid rgba(34,197,94,0.3)}
.btn-danger{background:rgba(239,68,68,0.15);color:var(--danger);border:1px solid rgba(239,68,68,0.3)}
.btn-sm{padding:5px 10px;font-size:12px}
.btn:disabled{opacity:.4;cursor:not-allowed}

.card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:16px}
.card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
.card-title{font-size:16px;font-weight:600}

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

.steps{display:flex;gap:0;margin-bottom:24px}
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

.json-tree{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);padding:16px;max-height:350px;overflow-y:auto;font-family:monospace;font-size:12px;line-height:1.8}
.json-node-key{color:var(--accent2);cursor:pointer;padding:1px 4px;border-radius:3px;transition:.15s}
.json-node-key:hover{background:rgba(99,102,241,0.1)}
.json-node-key.selected{background:var(--accent);color:#fff}
.json-node-string{color:var(--success)}
.json-node-number{color:var(--warning)}
.json-node-bool{color:var(--info)}
.json-node-null{color:var(--text3)}
.json-toggle{cursor:pointer;color:var(--text3);width:14px;display:inline-block;text-align:center}
.json-toggle:hover{color:var(--accent2)}
.json-collapsible{display:none}.json-collapsible.open{display:block}

.field-list{display:flex;flex-direction:column;gap:8px}
.field-item{display:flex;align-items:center;gap:10px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px 14px;cursor:move}
.field-item:hover{border-color:var(--border2)}
.field-drag-handle{color:var(--text3);cursor:grab;font-size:16px;padding:4px}
.field-enable{width:18px;height:18px;accent-color:var(--accent);flex-shrink:0;cursor:pointer}
.field-key{font-family:monospace;font-size:12px;color:var(--accent2);background:rgba(99,102,241,0.08);padding:2px 8px;border-radius:3px;min-width:100px}
.field-label-input{flex:1;padding:6px 10px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:13px;outline:none}
.field-label-input:focus{border-color:var(--accent)}
.field-format-select{padding:6px 10px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:12px;outline:none;cursor:pointer}
.field-remove{color:var(--danger);background:none;border:none;cursor:pointer;font-size:16px;padding:4px;opacity:.6;transition:.15s}
.field-remove:hover{opacity:1}

.layout-options{display:flex;gap:10px;flex-wrap:wrap}
.layout-option{padding:10px 18px;background:var(--bg2);border:2px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;transition:.2s;font-size:13px;font-weight:500}
.layout-option.active{border-color:var(--accent);background:rgba(99,102,241,0.05)}

.test-area{display:flex;gap:10px;margin-bottom:16px;align-items:flex-end}
.test-area .form-group{flex:1;margin:0}

.preview-panel{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.preview-header{padding:12px 16px;background:var(--card2);border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
.preview-header h4{font-size:13px;font-weight:600}
.preview-body{padding:16px;font-size:13px;line-height:1.7;white-space:pre-wrap;max-height:300px;overflow-y:auto}
.preview-placeholder{color:var(--text3);text-align:center;padding:40px 20px}

.suggestion-list{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.suggestion-tag{padding:4px 10px;background:var(--bg2);border:1px solid var(--border);border-radius:20px;font-size:11px;cursor:pointer;transition:.15s;color:var(--text2)}
.suggestion-tag:hover{border-color:var(--accent);color:var(--accent2);background:rgba(99,102,241,0.05)}

.tabs{display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:16px}
.tab{padding:10px 18px;font-size:13px;font-weight:500;color:var(--text3);cursor:pointer;border-bottom:2px solid transparent;transition:.15s;margin-bottom:-1px;background:none;border:none}
.tab.active{color:var(--accent2);border-bottom-color:var(--accent)}

.code-panel{background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-top:16px}
.code-header{padding:12px 16px;background:var(--card2);border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
.code-body{padding:16px;font-family:monospace;font-size:12px;line-height:1.6;white-space:pre-wrap;max-height:500px;overflow-y:auto;color:var(--text2)}

.toast-container{position:fixed;top:20px;right:20px;z-index:2000;display:flex;flex-direction:column;gap:8px}
.toast{padding:12px 18px;border-radius:var(--radius-sm);color:#fff;font-size:13px;animation:slideIn .3s ease;max-width:360px;word-break:break-word}
.toast.success{background:var(--success)}.toast.error{background:var(--danger)}.toast.info{background:var(--info)}
@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}

.empty{text-align:center;padding:60px 20px;color:var(--text3)}

.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:1000;display:flex;align-items:center;justify-content:center;padding:20px;overflow-y:auto;backdrop-filter:blur(4px)}
.modal{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);width:100%;max-width:900px;max-height:92vh;overflow-y:auto;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5)}
.modal-header{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;background:var(--card);z-index:10}
.modal-header h2{font-size:18px;font-weight:600}
.modal-close{width:32px;height:32px;border-radius:var(--radius-sm);border:none;background:var(--bg2);color:var(--text2);cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center}
.modal-close:hover{background:var(--border);color:var(--text)}
.modal-body{padding:24px}
.modal-footer{padding:16px 24px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;position:sticky;bottom:0;background:var(--card);z-index:10}

::-webkit-scrollbar{width:6px;height:6px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:var(--border2)}

.fade-enter-active,.fade-leave-active{transition:opacity .25s}
.fade-enter-from,.fade-leave-to{opacity:0}
</style>
</head>
<body>
<div id="app">
<div class="container">

<div class="header">
<h1>🔌 插件生成器</h1>
<div class="subtitle">可视化配置 → 生成完整 PHP 代码 → 手动保存到框架 plugins/ 目录</div>
</div>

<div class="toolbar">
<button class="btn btn-primary" @click="openWizard">+ 新建插件</button>
</div>

<!-- Wizard Modal -->
<transition name="fade">
<div v-if="showWizard" class="modal-overlay" @click.self="closeWizard">
<div class="modal">

<div class="modal-header">
<h2>配置插件</h2>
<button class="modal-close" @click="closeWizard">&times;</button>
</div>

<div class="modal-body" style="padding-bottom:0">
<div class="steps">
<div v-for="(s,i) in steps" :key="i" class="step" :class="{active:step===i,completed:step>i}">
<div class="step-num"><template v-if="step>i">&#10003;</template><template v-else>{{ i+1 }}</template></div>
<div class="step-label">{{ s }}</div>
</div>
</div>
</div>

<div class="modal-body" style="padding-top:8px">

<!-- ===== Step 1: 基础配置 ===== -->
<div v-if="step===0">
<div class="form-grid">
<div class="form-group">
<label>插件类名 <span class="required">*</span></label>
<input v-model="config.className" placeholder="如：WeatherPlugin">
<span class="form-hint">必须以 Plugin 结尾，框架自动加载</span>
</div>
<div class="form-group">
<label>显示名称 <span class="required">*</span></label>
<input v-model="config.name" placeholder="如：天气查询">
</div>
<div class="form-group">
<label>触发指令 <span class="required">*</span></label>
<input v-model="config.command" placeholder="如：天气（用户发送「天气 北京」触发）">
</div>
<div class="form-group">
<label>描述</label>
<input v-model="config.description" placeholder="插件功能描述">
</div>
<div class="form-group full">
<label>请求 URL <span class="required">*</span></label>
<input v-model="config.url" placeholder="https://api.example.com/weather?q={arg1}">
<span class="form-hint">使用 {arg1} {arg2} ... 作为参数占位符，用户用空格分隔参数</span>
</div>
<div class="form-group">
<label>请求方法</label>
<select v-model="config.method"><option value="GET">GET</option><option value="POST">POST</option></select>
</div>
<div class="form-group">
<label>超时时间（秒）</label>
<input type="number" v-model.number="config.timeout" min="5" max="60" placeholder="20">
</div>
<div class="form-group full">
<label>请求 Headers（JSON，可选）</label>
<textarea v-model="config.headersJson" placeholder='{"User-Agent": "Mozilla/5.0"}'></textarea>
</div>
<div class="form-group full">
<label>请求 Body（POST 用，可选）</label>
<textarea v-model="config.body" placeholder='{"city": "{arg1}"}'></textarea>
</div>
</div>
</div>

<!-- ===== Step 2: 响应类型 ===== -->
<div v-if="step===1">
<p style="color:var(--text2);font-size:13px;margin-bottom:16px">选择接口返回的数据类型</p>
<div class="mode-grid">
<div v-for="m in responseModes" :key="m.value" class="mode-card" :class="{active:config.responseMode===m.value}" @click="config.responseMode=m.value">
<div class="mode-check">&#10003;</div>
<div class="mode-icon">{{ m.icon }}</div>
<div class="mode-name">{{ m.label }}</div>
<div class="mode-desc">{{ m.desc }}</div>
</div>
</div>
</div>

<!-- ===== Step 3: 数据配置 ===== -->
<div v-if="step===2">

<!-- JSON 数据模式 -->
<template v-if="isJsonMode">
<div style="margin-bottom:20px">
<h4 style="font-size:14px;font-weight:600;margin-bottom:10px">1. 测试接口获取 JSON</h4>
<div class="test-area">
<div class="form-group">
<label>测试参数（空格分隔，可选）</label>
<input v-model="testArgs" placeholder="如：北京">
</div>
<button class="btn btn-primary" @click="testApi" :disabled="testing">
<template v-if="testing">测试中...</template>
<template v-else>测试接口</template>
</button>
</div>
</div>

<div v-if="testResult" style="margin-bottom:20px">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
<h4 style="font-size:14px;font-weight:600">2. 点击选择数据路径</h4>
<div v-if="selectedPath" style="font-size:12px;color:var(--accent2)">已选：{{ selectedPath }}</div>
</div>

<div class="tabs">
<button class="tab" :class="{active:jsonTab==='tree'}" @click="jsonTab='tree'">JSON 树</button>
<button class="tab" :class="{active:jsonTab==='raw'}" @click="jsonTab='raw'">原始数据</button>
</div>

<div v-if="jsonTab==='tree'" class="json-tree">
<json-tree-node :data="testResult.data" :path="''" :level="0" :selected-path="config.jsonPath" @select="onPathSelect"></json-tree-node>
</div>
<div v-else class="json-tree" style="white-space:pre-wrap">{{ JSON.stringify(testResult.data,null,2) }}</div>

<div v-if="testResult.suggestions && testResult.suggestions.length" style="margin-top:10px">
<span style="font-size:12px;color:var(--text3)">快速选择：</span>
<div class="suggestion-list">
<span v-for="s in testResult.suggestions.slice(0,15)" :key="s.path" class="suggestion-tag" @click="applySuggestion(s)">
{{ s.path }}<span style="color:var(--text3);margin-left:4px">{{ s.type }}</span>
</span>
</div>
</div>
</div>

<div v-if="config.jsonPath" style="margin-bottom:20px">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
<h4 style="font-size:14px;font-weight:600">3. 字段映射与 Markdown 排版</h4>
<button class="btn btn-sm btn-secondary" @click="autoDetectFields">自动识别字段</button>
</div>

<div style="display:flex;gap:16px;margin-bottom:12px;align-items:center">
<label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px">
<input type="checkbox" v-model="config.isList" style="accent-color:var(--accent)"> 返回的是数组（列表数据）
</label>
<div v-if="config.isList" class="form-group" style="flex:1;max-width:300px;margin:0">
<input v-model="config.listKey" placeholder="列表键路径（如：data.items）">
</div>
</div>

<div style="margin-bottom:12px">
<span style="font-size:12px;color:var(--text2);font-weight:500">排版布局：</span>
<div class="layout-options" style="margin-top:6px">
<div v-for="l in layouts" :key="l.value" class="layout-option" :class="{active:config.markdownLayout===l.value}" @click="config.markdownLayout=l.value">{{ l.label }}</div>
</div>
</div>

<div class="field-list">
<div v-for="(field,index) in config.fieldMapping" :key="index" class="field-item" draggable="true" @dragstart="dragStart(index)" @dragover.prevent="dragOver(index)" @drop="drop(index)">
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
<div style="display:flex;gap:8px;align-items:center;margin-top:8px">
<input v-model="newFieldKey" placeholder="输入字段路径" style="flex:1;padding:8px 12px;background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text);font-size:13px" @keyup.enter="addField">
<button class="btn btn-sm btn-secondary" @click="addField">+ 添加字段</button>
</div>

<div class="form-group full" style="margin-top:12px">
<label>自定义 Markdown 模板（可选，留空使用自动排版）</label>
<textarea v-model="config.markdownTemplate" placeholder="用 {字段名} 作为占位符，如：&#10;标题：{title}&#10;链接：[点击访问]({url})" style="min-height:60px"></textarea>
</div>
</div>

<div v-if="config.jsonPath" style="margin-bottom:20px;padding:16px;background:var(--bg2);border-radius:var(--radius);border:1px solid var(--border)">
<h4 style="font-size:13px;font-weight:600;margin-bottom:8px">媒体 URL 提取（可选）</h4>
<input v-model="config.mediaUrlPath" placeholder="如：url 或 data.url（从 JSON 中提取媒体文件 URL）">
<span class="form-hint">如果 JSON 中有图片/音频/视频 URL，填写路径可以自动发送媒体消息</span>
</div>
</template>

<!-- 直接媒体模式 -->
<template v-else-if="isDirectMediaMode">
<div style="padding:20px;background:var(--bg2);border-radius:var(--radius);border:1px solid var(--border)">
<h4 style="font-size:14px;font-weight:600;margin-bottom:12px">{{ modeIcon(config.responseMode) }} 媒体 URL 配置</h4>
<p style="font-size:12px;color:var(--text3);margin-bottom:16px">如果接口直接返回 URL 文本，无需额外配置。如果是 JSON 包裹的 URL，填写提取路径。</p>
<div class="form-group" style="margin:0">
<input v-model="config.mediaUrlPath" placeholder="JSON 路径（如：data.url）">
<span class="form-hint">接口直接返回 URL 时留空即可</span>
</div>
</div>
<div style="margin-top:20px">
<h4 style="font-size:14px;font-weight:600;margin-bottom:10px">测试接口</h4>
<div class="test-area">
<div class="form-group"><label>测试参数</label><input v-model="testArgs" placeholder="输入测试参数"></div>
<button class="btn btn-primary" @click="testApi" :disabled="testing">测试</button>
</div>
<div v-if="testResult" class="preview-panel" style="margin-top:12px">
<div class="preview-header"><h4>响应结果</h4><span style="font-size:11px;padding:2px 8px;border-radius:4px;background:rgba(59,130,246,0.12);color:var(--info)">{{ testResult.isJson?'JSON':'Text' }}</span></div>
<div class="preview-body" style="white-space:pre-wrap;font-size:12px">{{ JSON.stringify(testResult.data,null,2).slice(0,2000) }}</div>
</div>
</div>
</template>

<!-- 直接文本/Markdown 模式 -->
<template v-else>
<div style="padding:40px;text-align:center;color:var(--text3)">
<div style="font-size:48px;margin-bottom:16px">{{ modeIcon(config.responseMode) }}</div>
<p style="font-size:14px">{{ modeLabel(config.responseMode) }} 模式无需额外配置</p>
<p style="font-size:12px;margin-top:8px">接口返回内容将直接作为{{ config.responseMode==='direct_markdown'?'Markdown':'文本' }}发送</p>
</div>
</template>

</div>

<!-- ===== Step 4: 生成代码 ===== -->
<div v-if="step===3">
<div class="form-grid">
<div class="form-group">
<label>缓存时间（秒，0=不缓存）</label>
<input type="number" v-model.number="config.cacheSeconds" min="0" max="3600" placeholder="0">
<span class="form-hint">相同参数的请求会缓存结果</span>
</div>
</div>

<div style="margin-top:24px;text-align:center">
<h3 style="font-size:18px;margin-bottom:16px">生成插件代码</h3>
<p style="color:var(--text3);font-size:13px;margin-bottom:20px">点击下方按钮生成完整 PHP 代码，复制后手动保存到框架的 plugins/ 目录</p>

<div style="display:flex;gap:12px;justify-content:center">
<button class="btn btn-success" @click="generateCode" :disabled="generating">
<template v-if="generating">生成中...</template>
<template v-else>生成代码</template>
</button>
<button class="btn btn-secondary" @click="downloadCode" :disabled="!generatedCode">下载文件</button>
<button class="btn btn-primary" @click="copyCode" :disabled="!generatedCode">复制代码</button>
</div>

<p v-if="generatedCode" style="margin-top:16px;font-size:13px;color:var(--text3)">
文件名：<code style="background:var(--bg2);padding:4px 8px;border-radius:4px">{{ config.className || 'CustomApi' }}Plugin.php</code>
</p>
</div>

<div v-if="generatedCode" class="code-panel">
<div class="code-header">
<h4>{{ config.className || 'CustomApi' }}Plugin.php</h4>
<span class="badge" style="font-size:11px;padding:2px 8px;border-radius:4px;background:rgba(34,197,94,0.12);color:var(--success)">可手动复制保存</span>
</div>
<div class="code-body">{{ generatedCode }}</div>
</div>
</div>

</div>

<div class="modal-footer">
<button class="btn btn-secondary" @click="prevStep" v-if="step>0">上一步</button>
<div v-else></div>
<div style="display:flex;gap:10px">
<button class="btn btn-secondary" @click="closeWizard">取消</button>
<button class="btn btn-primary" @click="nextStep" v-if="step<3">下一步</button>
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
</div>

<script>
const { createApp, ref, computed, reactive } = Vue;

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
function select(key) { emit('select', props.path ? props.path + '.' + key : key, props.data[key]); }
function selectMe() { if (!hasChildren.value) emit('select', props.path || '', props.data); }
return { collapsed, isObject, isArray, keys, hasChildren, displayValue, toggle, select, selectMe };
},
template: `
<div :style="{paddingLeft: level*16+'px'}">
<template v-if="!isObject">
<span class="json-node-key" :class="{selected: path===selectedPath}" @click="selectMe">{{ path ? path.split('.').pop() : 'root' }}</span>
<span>:</span>
<span :class="displayValue.cls">{{ displayValue.text }}</span>
</template>
<template v-else>
<div style="position:relative">
<span class="json-toggle" @click="toggle">{{ collapsed ? '▶' : '▼' }}</span>
<span class="json-node-key" :class="{selected: path===selectedPath}" @click="selectMe">{{ path ? path.split('.').pop() : 'root' }}</span>
<span :class="displayValue.cls">{{ displayValue.text }}</span>
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

createApp({
components: { JsonTreeNode },
setup() {
const showWizard = ref(false);
const step = ref(0);
const steps = ['基础配置', '响应类型', '数据配置', '生成代码'];
const testing = ref(false);
const generating = ref(false);
const testResult = ref(null);
const jsonTab = ref('tree');
const selectedPath = ref('');
const testArgs = ref('');
const newFieldKey = ref('');
const generatedCode = ref('');
const toasts = ref([]);

const config = reactive({
className: '', name: '', command: '', description: '',
url: '', method: 'GET', headersJson: '', body: '',
responseMode: 'json_data',
jsonPath: '', isList: false, listKey: '',
fieldMapping: [], markdownLayout: 'card', markdownTemplate: '',
mediaUrlPath: '',
cacheSeconds: 0, timeout: 20,
});

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
{ value: 'direct_text', label: '纯文本', icon: '📝', desc: '接口直接返回文本' },
{ value: 'direct_markdown', label: 'Markdown', icon: '📄', desc: '接口直接返回 Markdown' },
{ value: 'direct_image', label: '图片', icon: '🖼️', desc: '接口返回图片 URL' },
{ value: 'direct_audio', label: '音频', icon: '🎵', desc: '接口返回音频 URL' },
{ value: 'direct_video', label: '视频', icon: '🎬', desc: '接口返回视频 URL' },
{ value: 'json_data', label: 'JSON 数据', icon: '📊', desc: '接口返回 JSON，可视化排版' },
];

const isJsonMode = computed(() => config.responseMode === 'json_data');
const isDirectMediaMode = computed(() => ['direct_image','direct_audio','direct_video','direct_file'].includes(config.responseMode));

function toast(message, type='success') {
const id = Date.now();
toasts.value.push({ id, message, type });
setTimeout(() => { toasts.value = toasts.value.filter(t => t.id !== id); }, 3000);
}

async function api(action, data={}) {
const form = new URLSearchParams(data);
const res = await fetch('plugin_generator_api.php?action=' + action, { method: 'POST', body: form });
const text = await res.text();
try {
return JSON.parse(text);
} catch (e) {
console.error('API raw:', text.slice(0, 500));
return { success: false, message: '服务器返回非 JSON 数据 (HTTP ' + res.status + ')' };
}
}

function openWizard() {
Object.assign(config, {
className: '', name: '', command: '', description: '',
url: '', method: 'GET', headersJson: '', body: '',
responseMode: 'json_data',
jsonPath: '', isList: false, listKey: '',
fieldMapping: [], markdownLayout: 'card', markdownTemplate: '',
mediaUrlPath: '',
cacheSeconds: 0, timeout: 20,
});
step.value = 0;
testArgs.value = '';
testResult.value = null;
generatedCode.value = '';
selectedPath.value = '';
showWizard.value = true;
}

function closeWizard() { showWizard.value = false; }

function validateStep(s) {
if (s === 0) {
if (!config.className) { toast('请填写插件类名', 'error'); return false; }
if (!config.name) { toast('请填写显示名称', 'error'); return false; }
if (!config.command) { toast('请填写触发指令', 'error'); return false; }
if (!config.url) { toast('请填写请求 URL', 'error'); return false; }
}
if (s === 2 && isJsonMode.value) {
if (!config.jsonPath) { toast('请先测试接口并选择数据路径', 'error'); return false; }
}
return true;
}

function nextStep() {
if (!validateStep(step.value)) return;
if (step.value < 3) step.value++;
}

function prevStep() { if (step.value > 0) step.value--; }

function modeIcon(mode) {
const m = responseModes.find(r => r.value === mode);
return m ? m.icon : '📡';
}
function modeLabel(mode) {
const m = responseModes.find(r => r.value === mode);
return m ? m.label : mode;
}

// ===== Test API =====

async function testApi() {
testing.value = true;
testResult.value = null;
try {
const testConfig = {
url: config.url,
method: config.method,
headers: config.headersJson,
body: config.body,
responseMode: config.responseMode,
args: testArgs.value.split(' ').filter(Boolean),
timeout: config.timeout,
};
const res = await api('test', { config: JSON.stringify(testConfig) });
if (res.success) {
testResult.value = res;
if (res.isJson && res.suggestions && res.suggestions.length && !config.jsonPath) {
const best = res.suggestions.find(s => s.type.includes('array') || s.type === 'object') || res.suggestions[0];
if (best) {
config.jsonPath = best.path;
selectedPath.value = best.path;
}
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

// ===== Path Selection =====

function onPathSelect(path, value) {
config.jsonPath = path;
selectedPath.value = path;
if (Array.isArray(value) && value.length > 0) {
config.isList = true;
config.listKey = path;
autoDetectFieldsFromSample(value[0]);
} else if (typeof value === 'object' && value !== null) {
config.isList = false;
autoDetectFieldsFromSample(value);
}
toast('已选择路径: ' + path, 'info');
}

function applySuggestion(s) {
if (s.type.includes('array')) {
config.jsonPath = s.path;
config.isList = true;
config.listKey = s.path;
const sample = testResult.value?.data;
if (sample) {
const arr = extractPath(sample, s.path);
if (Array.isArray(arr) && arr[0]) autoDetectFieldsFromSample(arr[0]);
}
} else if (s.type === 'object') {
config.jsonPath = s.path;
config.isList = false;
const sample = testResult.value?.data;
if (sample) {
const obj = extractPath(sample, s.path);
if (obj && typeof obj === 'object') autoDetectFieldsFromSample(obj);
}
} else {
config.jsonPath = s.path;
}
selectedPath.value = config.jsonPath;
}

function autoDetectFields() {
if (!testResult.value || !testResult.value.isJson) { toast('请先测试接口', 'error'); return; }
const data = testResult.value.data;
let sample = extractPath(data, config.jsonPath);
if (Array.isArray(sample) && sample[0]) {
sample = sample[0];
config.isList = true;
} else if (Array.isArray(sample)) {
toast('数组为空', 'error'); return;
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
const existing = new Set(config.fieldMapping.map(f => f.key));
Object.keys(obj).forEach(key => {
if (existing.has(key)) return;
const val = obj[key];
let fmt = 'text';
if (typeof val === 'string' && val.match(/^https?:\/\//)) {
fmt = val.match(/\.(jpg|jpeg|png|gif|webp)/i) ? 'image' : 'link';
}
config.fieldMapping.push({ key, label: autoLabel(key), enabled: true, format: fmt });
});
}

function autoLabel(key) {
const map = { name: '名称', title: '标题', content: '内容', text: '文本', url: '链接', link: '链接', author: '作者', time: '时间', date: '日期', description: '描述', price: '价格', status: '状态', type: '类型', city: '城市', phone: '电话' };
return map[key.toLowerCase()] || key;
}

function extractPath(data, path) {
if (!path) return data;
const keys = path.split('.');
let current = data;
for (const k of keys) {
if (current && typeof current === 'object' && k in current) current = current[k];
else return null;
}
return current;
}

// ===== Field Management =====

let dragIdx = -1;
function dragStart(idx) { dragIdx = idx; }
function dragOver(idx) {
if (dragIdx === -1 || dragIdx === idx) return;
const item = config.fieldMapping.splice(dragIdx, 1)[0];
config.fieldMapping.splice(idx, 0, item);
dragIdx = idx;
}
function drop(idx) { dragIdx = -1; }

function addField() {
const key = newFieldKey.value.trim();
if (!key) return;
if (config.fieldMapping.some(f => f.key === key)) { toast('字段已存在', 'error'); return; }
config.fieldMapping.push({ key, label: autoLabel(key), enabled: true, format: 'text' });
newFieldKey.value = '';
}

function removeField(idx) { config.fieldMapping.splice(idx, 1); }

// ===== Generate Code =====

async function generateCode() {
generating.value = true;
try {
const genConfig = {
className: config.className,
name: config.name,
command: config.command,
description: config.description,
url: config.url,
method: config.method,
headers: parseJson(config.headersJson),
body: config.body,
responseMode: config.responseMode,
jsonPath: config.jsonPath,
isList: config.isList,
listKey: config.listKey,
fieldMapping: config.fieldMapping,
markdownLayout: config.markdownLayout,
markdownTemplate: config.markdownTemplate,
mediaUrlPath: config.mediaUrlPath,
cacheSeconds: config.cacheSeconds,
timeout: config.timeout,
};
const res = await api('generate', { config: JSON.stringify(genConfig) });
if (res.success) {
generatedCode.value = res.code;
toast('代码生成成功！请复制保存到 plugins/ 目录', 'success');
} else {
toast(res.message || '生成失败', 'error');
}
} catch (e) {
toast('生成失败: ' + e.message, 'error');
} finally {
generating.value = false;
}
}

async function copyCode() {
if (!generatedCode.value) return;
try {
await navigator.clipboard.writeText(generatedCode.value);
toast('已复制到剪贴板', 'success');
} catch (e) {
toast('复制失败，请手动选择复制', 'error');
}
}

function downloadCode() {
if (!generatedCode.value) return;
const filename = (config.className || 'CustomApi') + 'Plugin.php';
const blob = new Blob([generatedCode.value], { type: 'text/plain' });
const url = URL.createObjectURL(blob);
const a = document.createElement('a');
a.href = url;
a.download = filename;
document.body.appendChild(a);
a.click();
document.body.removeChild(a);
URL.revokeObjectURL(url);
toast('已下载 ' + filename, 'success');
}

function parseJson(str) {
if (!str) return {};
try { return JSON.parse(str); } catch { return {}; }
}

return {
showWizard, step, steps, config,
responseModes, layouts, fieldFormats,
isJsonMode, isDirectMediaMode,
testing, generating, testResult, jsonTab, selectedPath,
testArgs, newFieldKey, generatedCode, toasts,
openWizard, closeWizard, nextStep, prevStep,
testApi, onPathSelect, applySuggestion,
autoDetectFields, addField, removeField,
dragStart, dragOver, drop,
generateCode, copyCode, downloadCode,
modeIcon, modeLabel, toast
};
}
}).mount('#app');
</script>
</body>
</html>
