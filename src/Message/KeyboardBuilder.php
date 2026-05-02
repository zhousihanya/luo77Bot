<?php
declare(strict_types=1);

namespace QQBot\Message;

/**
 * QQ Bot Markdown 自定义键盘构建器
 *
 * 零侵入框架：纯新增文件，不修改任何原有类。
 */
class KeyboardBuilder
{
    private array $rows = [];
    private array $currentRow = [];

    /**
     * 在当前行添加一个按钮
     *
     * @param string      $label            按钮显示文字
     * @param string      $data             按钮携带的数据（URL、回调数据或 Bot 指令）
     * @param int         $actionType       0=跳转 1=回调 2=指令（默认）
     * @param int         $style            0=灰色线框 1=蓝色线框（默认）
     * @param string|null $visitedLabel     点击后按钮显示的文字，默认与 $label 相同
     * @param string|null $id               按钮唯一 ID，默认自动生成
     * @param int         $permissionType   0=指定用户 1=仅管理员 2=所有人（默认） 3=指定身份组
     * @param array|null  $specifyUserIds   有权限的用户 OpenID 列表
     * @param array|null  $specifyRoleIds   有权限的身份组 ID 列表（仅频道）
     * @param bool        $reply            指令按钮是否带引用回复本消息
     * @param bool        $enter            指令按钮是否点击后直接自动发送（仅单聊有效）
     */
    public function addButton(
        string $label,
        string $data,
        int $actionType = 2,
        int $style = 1,
        ?string $visitedLabel = null,
        ?string $id = null,
        int $permissionType = 2,
        ?array $specifyUserIds = null,
        ?array $specifyRoleIds = null,
        bool $reply = false,
        bool $enter = false
    ): self {
        $button = [
            'id' => $id ?? uniqid('kb_', true),
            'render_data' => [
                'label'         => $label,
                'visited_label' => $visitedLabel ?? $label,
                'style'         => $style,
            ],
            'action' => [
                'type'       => $actionType,
                'permission' => ['type' => $permissionType],
                'data'       => $data,
                'reply'      => $reply,
                'enter'      => $enter,
            ],
        ];

        if ($specifyUserIds !== null) {
            $button['action']['permission']['specify_user_ids'] = $specifyUserIds;
        }
        if ($specifyRoleIds !== null) {
            $button['action']['permission']['specify_role_ids'] = $specifyRoleIds;
        }

        $this->currentRow[] = $button;
        return $this;
    }

    /**
     * 结束当前行并开始新的一行
     */
    public function newRow(): self
    {
        if (!empty($this->currentRow)) {
            $this->rows[] = ['buttons' => $this->currentRow];
            $this->currentRow = [];
        }
        return $this;
    }

    /**
     * 构建自定义键盘数组
     *
     * @throws \RuntimeException 超出官方行/列限制时抛出
     */
    public function build(): array
    {
        if (!empty($this->currentRow)) {
            $this->rows[] = ['buttons' => $this->currentRow];
            $this->currentRow = [];
        }

        if (count($this->rows) > 5) {
            throw new \RuntimeException('Keyboard rows exceed the maximum limit of 5');
        }

        foreach ($this->rows as $row) {
            if (count($row['buttons']) > 5) {
                throw new \RuntimeException('Each keyboard row can have at most 5 buttons');
            }
        }

        return ['content' => ['rows' => $this->rows]];
    }

    /**
     * 使用通过管理端申请的模板键盘
     */
    public static function template(string $templateId): array
    {
        return ['id' => $templateId];
    }
}