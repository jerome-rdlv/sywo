<?php

namespace Rdlv\WordPress\Sywo;

class ShortcodeForm
{
    /** @var string */
    protected $protected;

    /** @var callable */
    protected $callable;

    /** @var string */
    private $response = '';

    public function __construct(string $shortcode, callable $callable)
    {
        $this->callable = $callable;
        $this->shortcode = $shortcode;

        add_action('template_redirect', [$this, 'early_init']);
        add_action($shortcode . '_run', [$this, 'run']);

        add_shortcode($shortcode, [$this, 'shortcode']);
        add_action($shortcode, [$this, 'action']);
    }

    public function early_init()
    {
        // do not display in archive nor admin
        if (!is_singular() || is_admin()) {
            return;
        }

        global $post;
        if ($post && ($atts = $this->has_shortcode($post->post_content)) !== false) {
            do_action($this->shortcode . '_run', $atts);
        }
    }

    /**
     * @param $content
     * @return array|false The shortcode tag if found, false otherwise
     */
    private function has_shortcode($content)
    {
        if (preg_match('/(\[\s*' . preg_quote($this->shortcode) . '(\s.*)?\])/', $content, $m)) {
            return shortcode_parse_atts($m[2] ?? '') ?: [];
        }
        return false;
    }

    public function run($attributes): void
    {
        $this->response = call_user_func_array($this->callable, $attributes);
    }

    public function display($attributes = []): string
    {
        return apply_filters($this->shortcode . '_display', $this->response ?? '', $attributes);
    }

    public function action($attributes = []): void
    {
        echo $this->display($attributes);
    }

    public function shortcode($attributes = []): string
    {
        // only display on full page on front
        if (!is_singular() || is_admin()) {
            return '';
        }
        return $this->display($attributes);
    }

}