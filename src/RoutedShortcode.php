<?php

namespace Rdlv\WordPress\Sywo;

class RoutedShortcode implements ShortcodeListenerInterface
{
    /** @var ShortcodeHandler */
    private $handler;

    public function __construct(ShortcodeHandler $handler)
    {
        $this->handler = $handler;
        $handler->add_listener($this);

        add_action('init', [$this, 'add_rewrite_rules']);

        // undo trailing slash manipulations on page routes
        add_filter('user_trailingslashit', function ($string) {
            global $post;
            if (!$post || !$this->handler->is_registered_post($post->ID)) {
                return $string;
            }
            $path = '/' . trim(get_page_uri($post->ID), '/');
            if (!preg_match('#^' . preg_quote($path) . '/.+#', $string)) {
                return $string;
            }
            
            return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        });
    }

    public function add_rewrite_rules()
    {
        foreach ($this->handler->get_registered_post_ids() as $id) {
            $uri = get_page_uri($id);
            add_rewrite_rule(
                '(' . trailingslashit($uri) . ')(.*)$',
                'index.php?pagename=$matches[1]&sywo_form_shortcode=true',
                'top'
            );
        }

        if (get_transient('sywo_flush_rewrite_rules')) {
            delete_transient('sywo_flush_rewrite_rules');
            add_action('shutdown', 'flush_rewrite_rules');
        }
    }

    public function load(string $shortcode, array $attributes, $content)
    {
    }

    public function output(string $shortcode, array $attributes, $content)
    {
    }

    public function updated(string $shortcode, array $ids)
    {
        set_transient('sywo_flush_rewrite_rules', true);
    }
}