<?php

declare(strict_types=1);

namespace QQBot\Message;

/**
 * Markdown 模板库
 * 纯静态方法，零外部依赖
 */
class MarkdownTemplate
{
    /**
     * 音乐卡片模板
     */
    public static function musicCard(
        string $name,
        string $singer,
        string $album,
        string $duration,
        string $coverUrl,
        string $playUrl,
        int $index = 1,
    ): string {
        $md = "**{$index}. {$name}**\n\n";
        $md .= "![{$name}]({$coverUrl})\n\n";
        $md .= "- 歌手：{$singer}\n";
        $md .= "- 专辑：{$album}\n";
        $md .= "- 时长：{$duration}\n";
        $md .= "- [点击播放]({$playUrl})\n";

        return $md;
    }

    /**
     * 信息卡片模板
     */
    public static function infoCard(
        string $title,
        array $items,
        ?string $footer = null,
    ): string {
        $md = "## {$title}\n\n";
        $md .= "---\n\n";

        foreach ($items as $label => $value) {
            $md .= "**{$label}：** {$value}\n";
        }

        if ($footer !== null) {
            $md .= "\n---\n\n> {$footer}";
        }

        return $md;
    }

    /**
     * 表格模板
     */
    public static function table(array $headers, array $rows): string
    {
        $md = '| ' . implode(' | ', $headers) . " |\n";
        $md .= '|' . implode('|', array_fill(0, count($headers), ' --- ')) . "|\n";

        foreach ($rows as $row) {
            $md .= '| ' . implode(' | ', $row) . " |\n";
        }

        return $md;
    }

    /**
     * 代码块模板
     */
    public static function codeBlock(string $code, string $language = ''): string
    {
        return "```{$language}\n{$code}\n```";
    }

    /**
     * 引用块模板
     */
    public static function quote(string $content, ?string $source = null): string
    {
        $md = "> {$content}\n";
        if ($source !== null) {
            $md .= ">\n> —— {$source}";
        }
        return $md;
    }

    /**
     * 排行榜模板
     */
    public static function ranking(string $title, array $items, string $valueLabel = '分数'): string
    {
        $md = "## {$title}\n\n";
        $md .= "| 排名 | 名称 | {$valueLabel} |\n";
        $md .= "| --- | --- | --- |\n";

        foreach ($items as $i => $item) {
            $rank = $i + 1;
            $badge = match ($rank) {
                1 => '**1st**',
                2 => '**2nd**',
                3 => '**3rd**',
                default => (string) $rank,
            };
            $md .= "| {$badge} | {$item['name']} | {$item['value']} |\n";
        }

        return $md;
    }

    /**
     * 步骤模板
     */
    public static function steps(string $title, array $steps): string
    {
        $md = "## {$title}\n\n";
        foreach ($steps as $i => $step) {
            $md .= "**步骤 " . ($i + 1) . "：** {$step}\n\n";
        }
        return $md;
    }

    /**
     * 帮助菜单模板
     */
    public static function helpMenu(string $title, array $categories): string
    {
        $md = "# {$title}\n\n";
        foreach ($categories as $category => $commands) {
            $md .= "**{$category}**\n\n";
            foreach ($commands as $cmd => $desc) {
                $md .= "- `{$cmd}`：{$desc}\n";
            }
            $md .= "\n";
        }
        return $md;
    }

    /**
     * 状态面板模板
     */
    public static function statusPanel(string $title, array $metrics): string
    {
        $md = "## {$title}\n\n";
        foreach ($metrics as $label => $value) {
            $md .= "- {$label}：`{$value}`\n";
        }
        return $md;
    }

    /**
     * 列表模板
     */
    public static function list(string $title, array $items, bool $ordered = false): string
    {
        $md = "**{$title}**\n\n";
        foreach ($items as $i => $item) {
            if ($ordered) {
                $md .= ($i + 1) . ". {$item}\n";
            } else {
                $md .= "- {$item}\n";
            }
        }
        return $md;
    }

    /**
     * 通知模板
     */
    public static function notice(string $title, string $content, ?string $action = null): string
    {
        $md = "## {$title}\n\n";
        $md .= "> {$content}\n";
        if ($action !== null) {
            $md .= "\n{$action}";
        }
        return $md;
    }
}
