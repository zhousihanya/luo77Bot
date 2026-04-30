<?php

declare(strict_types=1);

namespace QQBot\Plugin;

use QQBot\Core\EventDispatcher;
use QQBot\Core\Logger;
use QQBot\Events\C2CMessageEvent;
use QQBot\Events\GroupAtMessageEvent;
use QQBot\Message\MarkdownTemplate;

/**
 * Markdown 模板演示插件
 * 通过指令展示所有官方支持的 Markdown 样式模板
 */
class MarkdownDemoPlugin implements PluginInterface
{
    private Logger $logger;

    public function getName(): string { return 'markdown_demo'; }
    public function getDisplayName(): string { return 'Markdown 样式展示'; }
    public function getDescription(): string { return '展示 QQ 官方支持的所有 Markdown 模板样式'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getAuthor(): string { return 'QQBot Framework'; }
    public function getIcon(): ?string { return '**[MD]**'; }
    public function getTags(): array { return ['演示', 'Markdown', '模板']; }

    public function register(EventDispatcher $dispatcher, Logger $logger): void
    {
        $this->logger = $logger;

        $dispatcher->on(C2CMessageEvent::class, function (C2CMessageEvent $event): void {
            $this->process($event, $event->getContent());
        });

        $dispatcher->on(GroupAtMessageEvent::class, function (GroupAtMessageEvent $event): void {
            $this->process($event, $event->getContent());
        });
    }

    private function process(object $event, string $content): void
    {
        $content = trim($content);

        try {
            match (true) {
                $content === '模板帮助' || $content === 'md帮助' => $this->sendHelp($event),
                $content === 'md音乐'    => $this->demoMusicCard($event),
                $content === 'md信息'    => $this->demoInfoCard($event),
                $content === 'md表格'    => $this->demoTable($event),
                $content === 'md排行'    => $this->demoRanking($event),
                $content === 'md代码'    => $this->demoCodeBlock($event),
                $content === 'md引用'    => $this->demoQuote($event),
                $content === 'md步骤'    => $this->demoSteps($event),
                $content === 'md状态'    => $this->demoStatusPanel($event),
                $content === 'md列表'    => $this->demoList($event),
                $content === 'md通知'    => $this->demoNotice($event),
                $content === 'md帮助菜单' => $this->demoHelpMenu($event),
                $content === 'md按钮'    => $this->demoButtons($event),
                default => null,
            };
        } catch (\Throwable $e) {
            $this->logger->error('MarkdownDemoPlugin error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 帮助菜单
     */
    private function sendHelp(object $event): void
    {
        $md = MarkdownTemplate::helpMenu('Markdown 模板指令', [
            '基础样式' => [
                'md音乐'     => '音乐卡片（封面+信息+链接）',
                'md信息'     => '信息卡片（标题+键值对）',
                'md表格'     => '表格模板',
                'md排行'     => '排行榜（带1st/2nd/3rd徽章）',
            ],
            '文本样式' => [
                'md代码'     => '代码块（带语法高亮）',
                'md引用'     => '引用块（带出处）',
                'md步骤'     => '步骤说明（编号流程）',
                'md列表'     => '有序/无序列表',
            ],
            '面板样式' => [
                'md状态'     => '状态面板（指标展示）',
                'md通知'     => '通知消息（提示信息）',
                'md帮助菜单' => '帮助菜单（分类命令）',
            ],
            '交互样式' => [
                'md按钮'     => '带点击按钮的消息（可直接发送指令）',
            ],
        ]);

        $event->replyMarkdown($md);
    }

    /**
     * 音乐卡片演示
     */
    private function demoMusicCard(object $event): void
    {
        $md = "**模板：musicCard**\n\n";
        $md .= MarkdownTemplate::musicCard(
            name: '烟火里的尘埃',
            singer: '华晨宇',
            album: '卡西莫多的礼物',
            duration: '5:21',
            coverUrl: 'http://p4.music.126.net/_49Xz_x9kTTdEgmYYk6w2w==/6672936069046297.jpg',
            playUrl: 'https://music.163.com/#/song?id=29004400',
            index: 1,
        );

        $event->replyMarkdown($md);
    }

    /**
     * 信息卡片演示
     */
    private function demoInfoCard(object $event): void
    {
        $md = "**模板：infoCard**\n\n";
        $md .= MarkdownTemplate::infoCard(
            title: '歌曲详情',
            items: [
                '歌曲' => '烟火里的尘埃',
                '歌手' => '华晨宇',
                '专辑' => '卡西莫多的礼物',
                '时长' => '5:21',
                '音质' => 'Lossless (925kbps)',
            ],
            footer: '网易云音乐提供数据支持',
        );

        $event->replyMarkdown($md);
    }

    /**
     * 表格演示
     */
    private function demoTable(object $event): void
    {
        $md = "**模板：table**\n\n";
        $md .= MarkdownTemplate::table(
            headers: ['排名', '歌曲', '歌手', '时长'],
            rows: [
                ['1', '烟火里的尘埃', '华晨宇', '5:21'],
                ['2', '齐天', '华晨宇', '4:08'],
                ['3', '好想爱这个世界啊', '华晨宇', '4:21'],
                ['4', '国王与乞丐', '华晨宇/杨宗纬', '4:05'],
            ],
        );

        $event->replyMarkdown($md);
    }

    /**
     * 排行榜演示
     */
    private function demoRanking(object $event): void
    {
        $md = "**模板：ranking**\n\n";
        $md .= MarkdownTemplate::ranking(
            title: '本周热歌榜',
            items: [
                ['name' => '烟火里的尘埃', 'value' => '1.2亿播放'],
                ['name' => '齐天', 'value' => '9800万播放'],
                ['name' => '好想爱这个世界啊', 'value' => '8500万播放'],
                ['name' => '国王与乞丐', 'value' => '7200万播放'],
                ['name' => '寒鸦少年', 'value' => '6500万播放'],
            ],
            valueLabel: '播放量',
        );

        $event->replyMarkdown($md);
    }

    /**
     * 代码块演示
     */
    private function demoCodeBlock(object $event): void
    {
        $md = "**模板：codeBlock**\n\n";
        $md .= MarkdownTemplate::codeBlock(
            code: "function hello() {\n    console.log('Hello QQBot!');\n    return true;\n}",
            language: 'javascript',
        );

        $event->replyMarkdown($md);
    }

    /**
     * 引用块演示
     */
    private function demoQuote(object $event): void
    {
        $md = "**模板：quote**\n\n";
        $md .= MarkdownTemplate::quote(
            content: '我的心里住着一个苍老的小孩，如果世界听不明白，对影子表白。',
            source: '华晨宇《烟火里的尘埃》',
        );

        $event->replyMarkdown($md);
    }

    /**
     * 步骤演示
     */
    private function demoSteps(object $event): void
    {
        $md = "**模板：steps**\n\n";
        $md .= MarkdownTemplate::steps(
            title: '使用点歌插件',
            steps: [
                '发送「点歌 周杰伦」搜索歌曲',
                '查看搜索结果，找到喜欢的歌曲',
                '发送「选 歌曲ID」播放歌曲',
                '等待音频文件发送完成',
            ],
        );

        $event->replyMarkdown($md);
    }

    /**
     * 状态面板演示
     */
    private function demoStatusPanel(object $event): void
    {
        $md = "**模板：statusPanel**\n\n";
        $md .= MarkdownTemplate::statusPanel(
            title: '系统状态',
            metrics: [
                'PHP 版本'   => PHP_VERSION,
                '运行时间'   => '3天12小时',
                '内存占用'   => '24MB',
                '已加载插件' => '5个',
                '活跃机器人' => '2个',
            ],
        );

        $event->replyMarkdown($md);
    }

    /**
     * 列表演示
     */
    private function demoList(object $event): void
    {
        $md = "**模板：list（无序列表）**\n\n";
        $md .= MarkdownTemplate::list(
            title: '热门歌手',
            items: ['周杰伦', '林俊杰', '陈奕迅', '薛之谦', '邓紫棋'],
        );

        $md .= "\n\n**模板：list（有序列表）**\n\n";
        $md .= MarkdownTemplate::list(
            title: '本周 Top 5',
            items: ['烟火里的尘埃', '齐天', '好想爱这个世界啊', '国王与乞丐', '寒鸦少年'],
            ordered: true,
        );

        $event->replyMarkdown($md);
    }

    /**
     * 通知演示
     */
    private function demoNotice(object $event): void
    {
        $md = "**模板：notice**\n\n";
        $md .= MarkdownTemplate::notice(
            title: '系统通知',
            content: '机器人框架已更新到 v1.6.0 版本，新增 Markdown 模板库和点歌按钮功能。',
            action: '发送「模板帮助」查看所有新功能',
        );

        $event->replyMarkdown($md);
    }

    /**
     * 帮助菜单演示
     */
    private function demoHelpMenu(object $event): void
    {
        $md = "**模板：helpMenu**\n\n";
        $md .= MarkdownTemplate::helpMenu('机器人指令大全', [
            '音乐' => [
                '点歌 关键词' => '搜索网易云歌曲',
                '选 歌曲ID'   => '播放指定歌曲',
            ],
            '系统' => [
                '模板帮助'    => '查看 Markdown 模板',
                '点歌帮助'    => '查看点歌用法',
            ],
        ]);

        $event->replyMarkdown($md);
    }

    /**
     * 按钮交互演示
     * 使用 <qqbot-cmd-enter> 标签实现点击发送指令
     */
    private function demoButtons(object $event): void
    {
        $md = "**模板：带按钮的交互消息**\n\n";
        $md .= "以下是可以点击的指令按钮：\n\n";
        $md .= "<qqbot-cmd-enter text=\"点歌 华晨宇\" show=\"搜索华晨宇\" />\n\n";
        $md .= "<qqbot-cmd-enter text=\"模板帮助\" show=\"查看模板帮助\" />\n\n";
        $md .= "<qqbot-cmd-enter text=\"md音乐\" show=\"查看音乐卡片\" />\n\n";
        $md .= "<qqbot-cmd-enter text=\"md排行\" show=\"查看排行榜\" />\n\n";
        $md .= "> 点击按钮即可自动发送对应指令\n";

        $event->replyMarkdown($md);
    }

    public function enable(): void
    {
        $this->logger->info('MarkdownDemoPlugin enabled');
    }

    public function disable(): void
    {
        $this->logger->info('MarkdownDemoPlugin disabled');
    }
}
