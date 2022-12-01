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