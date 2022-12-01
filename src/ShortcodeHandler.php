<?php

namespace Rdlv\WordPress\Sywo;

use Exception;
use WP_Post;

class ShortcodeHandler
{
    /** @var array */
    private $listeners = [];

    /** @var string */
    private $shortcode;

    /** @var bool */
    private $loaded = false;

    public function __construct(string $shortcode)
    {
        $this->shortcode = $shortcode;

        add_action('template_redirect', [$this, 'early_loading']);
        add_shortcode($shortcode, [$this, 'shortcode']);
        add_action('save_post', [$this, 'update_registered_post_ids'], 10, 2);

        // template use
        add_action($shortcode . '_load', [$this, 'load']);
        add_action($shortcode . '_display', function ($attributes = [], $content = null) {
            if (!$this->loaded) {
                throw new Exception(
                    sprintf(
                        'You must call %1$s_load action before %1$s_display action.',
                        $this->shortcode
                    )
                );
            }
            echo $this->output($attributes, $content);
        });
    }

    public function get_shortcode(): string
    {
        return $this->shortcode;
    }

    public function add_listener(ShortcodeListenerInterface $listener)
    {
        $this->listeners[] = $listener;
    }

    public function early_loading()
    {
        // do not display in archive nor admin
        if (!is_singular() || is_admin()) {
            return;
        }

        global $post;
        if ($post && in_array($post->ID, $this->get_registered_post_ids())) {
            if (($s = $this->has_shortcode($post->post_content)) !== false) {
                $this->load($s[0], $s[1]);
            }
        }
    }

    public function load($attributes = [], $content = null): void
    {
        $this->loaded = true;
        array_map(function (ShortcodeListenerInterface $listener) use ($attributes, $content) {
            return $listener->load($this->shortcode, $attributes, $content);
        }, $this->listeners);
    }

    public function output($attributes = [], $content = null): string
    {
        return implode('', array_map(function (ShortcodeListenerInterface $listener) use ($attributes, $content) {
            return $listener->output($this->shortcode, $attributes, $content);
        }, $this->listeners));
    }

    public function shortcode($attributes = [], $content = null): string
    {
        // only display on full page on front
        if (!is_singular() || is_admin()) {
            return '';
        }
        if (!$this->loaded) {
            throw new Exception(
                sprintf(
                    'To call shortcode %1$s programmatically, please use %1$s_load and %1$s_display actions.',
                    $this->shortcode
                )
            );
        }
        return $this->output($attributes);
    }

    /**
     * @param $content
     * @return array|false The shortcode attributes and content if found, false otherwise
     */
    private function has_shortcode($content)
    {
        if (preg_match('/' . get_shortcode_regex([$this->shortcode]) . '/s', $content, $m)) {
            return [
                shortcode_parse_atts($m[3] ?? '') ?: [],
                $m[5],
            ];
        }
        return false;
    }

    public function update_registered_post_ids(int $id, WP_Post $post)
    {
        $post_ids = $this->get_registered_post_ids();
        $index = array_search($id, $post_ids);
        $updated = false;
        if ($post->post_status === 'publish' && $this->has_shortcode($post->post_content)) {
            if ($index === false) {
                $post_ids[] = $id;
                $updated = true;
            }
        } elseif ($index !== false) {
            $post_ids = array_splice($post_ids, $index, 1);
            $updated = true;
        }

        if ($updated) {
            $this->save_registered_post_ids($post_ids);
            array_map(function (ShortcodeListenerInterface $listener) use ($post_ids) {
                return $listener->updated($this->shortcode, $post_ids);
            }, $this->listeners);
        }
    }

    private function get_registered_option_name()
    {
        return sprintf('sywo_has_shortcode_%s', $this->shortcode);
    }

    public function get_registered_post_ids(): array
    {
        return get_option($this->get_registered_option_name(), []);
    }

    private function save_registered_post_ids($ids)
    {
        update_option($this->get_registered_option_name(), $ids);
    }
}