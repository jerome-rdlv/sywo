<?php


namespace Rdlv\WordPress\Sywo;


class Notices
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR = 'error';
    public const STATUS_WARNING = 'warning';
    public const STATUS_INFO = 'info';

    public $channel;

    private $userId;

    /** @var string */
    private $key;

    public function __construct(string $channel = 'default')
    {
        $this->channel = $channel;
    }

    private function getUserId()
    {
        if (!$this->userId) {
            $this->userId = get_current_user_id();
        }
        return $this->userId;
    }

    private function getKey()
    {
        if (!$this->key) {
            $this->key = sprintf(
                'notices_%s',
                strtolower(preg_replace('/[^a-z0-9]+/i', '_', $this->channel))
            );
        }
        return $this->key;
    }

    public function display(): string
    {
        $notices = get_user_meta($this->getUserId(), $this->getKey(), true);
        if (!$notices) {
            return '';
        }
        delete_user_meta($this->getUserId(), $this->getKey());

        return implode('', array_map(function ($notice) {
            return sprintf(
                '<div class="notice notice-%s%s"><p>%s</p></div>',
                $notice['status'],
                $notice['dismissible'] ? ' is-dismissible' : '',
                $notice['message']
            );
        }, $notices));
    }

    public function add(string $message, string $status = self::STATUS_INFO, $isDismissible = true): self
    {
        $notices = get_user_meta($this->getUserId(), $this->getKey(), true);
        if (!$notices) {
            $notices = [];
        }
        $notices[] = [
            'message'     => $message,
            'status'      => $status,
            'dismissible' => $isDismissible,
        ];
        update_user_meta($this->getUserId(), $this->getKey(), $notices);
        return $this;
    }
}