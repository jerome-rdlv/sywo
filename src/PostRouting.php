<?php

namespace Rdlv\WordPress\Sywo;

use WP_Post;

class PostRouting
{
    private const OPTION = 'sywo_routed_post_ids';
    private const TRANSIENT = 'sywo_flush_rewrite_rules';

    /** @var callable[] */
    private array $filters = [];

    public function __construct()
    {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_action('save_post', [$this, 'register_routed_post'], 10, 2);

        // undo trailing slash manipulations on page routes
        add_filter('user_trailingslashit', function ($string) {
            global $post;
            if (!$post || !in_array($post->ID, get_option(static::OPTION, []))) {
                return $string;
            }
            $path = '/'.trim(get_page_uri($post->ID), '/');
            if (!preg_match('#^'.preg_quote($path).'/.+#', $string)) {
                return $string;
            }

            return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        });
    }

    public function filter(callable $listener): void
    {
        $this->filters[] = $listener;
    }

    public function register_routed_post(int $post_id, WP_Post $post): void
    {
        $routed = false;
        foreach ($this->filters as $filter) {
            $routed = call_user_func($filter, $routed, $post_id, $post);
        }

        $updated = false;
        $post_ids = get_option(self::OPTION, []);
        $index = array_search($post_id, $post_ids);
        if ($routed && $post->post_status === 'publish') {
            if ($index === false) {
                $post_ids[] = $post_id;
            }
            // always update routed post on save, in case of slug update
            $updated = true;
        } elseif ($index !== false) {
            $post_ids = array_splice($post_ids, $index, 1);
            $updated = true;
        }

        if ($updated) {
            update_option(self::OPTION, $post_ids);
            $this->flush();
        }
    }

    public function flush(): void
    {
        set_transient(self::TRANSIENT, true);
    }

    public function add_rewrite_rules(): void
    {
        if (!get_transient(self::TRANSIENT)) {
            return;
        }

        foreach (get_option(self::OPTION, []) as $id) {
            $uri = get_page_uri($id);
            add_rewrite_rule(
                '('.trailingslashit($uri).')(.*)$',
                'index.php?pagename=$matches[1]&sywo_form_shortcode=true',
                'top'
            );
        }

        delete_transient(self::TRANSIENT);
        add_action('shutdown', 'flush_rewrite_rules');
    }
}