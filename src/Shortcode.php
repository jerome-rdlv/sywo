<?php

namespace Rdlv\WordPress\Sywo;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use WP_Post;

class Shortcode extends App
{
    /** @var callable|Request */
    protected $request;

    private string $output = '';
    private bool $loaded = false;

    public function __construct(
        KernelInterface $kernel,
        private readonly string $shortcode,
        callable|Request $request = null,
        private readonly PostRouting|bool $routed = true
    ) {
        parent::__construct($kernel);
        $this->request = $request ?? function ($request) {
            return $request;
        };

        // enable post routing
        $routed && ($routed instanceof PostRouting ? $routed : new PostRouting())->filter(
            function (bool $routed, int $post_id) {
                return $this->is_registered_post($post_id);
            }
        );

        add_action('template_redirect', [$this, 'early_loading']);
        add_shortcode($shortcode, [$this, 'shortcode']);
        add_action('save_post', [$this, 'update_registered_post_ids'], 10, 2);

        // template use
        add_action($shortcode.'_load', [$this, 'load']);
        add_action($shortcode.'_display', function ($attributes = [], $content = null) {
            if (!$this->loaded) {
                /** @noinspection PhpUnhandledExceptionInspection */
                throw new Exception(
                    sprintf(
                        'You must call %1$s_load action before %1$s_display action.',
                        $this->shortcode
                    )
                );
            }
            echo $this->output((array)$attributes, $content);
        });
    }

    public function get_shortcode(): string
    {
        return $this->shortcode;
    }

    public function is_registered_post($post_id): bool
    {
        return in_array($post_id, $this->get_registered_post_ids());
    }

    public function early_loading(): void
    {
        // do not display in archive nor admin
        if (!is_singular() || is_admin()) {
            return;
        }

        global $post;
        if ($post && $this->is_registered_post($post->ID)) {
            if (($shortcodes = $this->get_shortcodes($post->post_content)) !== false) {
                foreach ($shortcodes ?? [] as $shortcode) {
                    $this->load($shortcode[0], $shortcode[1]);
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    public function load($attributes = [], $content = null): void
    {
        $this->loaded = true;

        $this->routed && Request::setFactory(new RoutedRequestFactory());

        $request = is_callable($this->request)
            ? call_user_func($this->request, Request::createFromGlobals(), $attributes, $content)
            : $this->request;

        $this->output = $this->_run($request)->getContent();
    }

    public function output($attributes = [], $content = null): string
    {
        return $this->output ?? '';
    }

    /**
     * @throws Exception
     */
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
        return $this->output((array)$attributes, $content);
    }

    /**
     * @return array|null The shortcode attributes and content if found, false otherwise
     */
    private function get_shortcodes($content): ?array
    {
        if (preg_match_all('/'.get_shortcode_regex([$this->shortcode]).'/s', $content, $matches, PREG_SET_ORDER)) {
            $shortcodes = [];
            foreach ($matches as $match) {
                $atts = shortcode_parse_atts($match[3] ?? '') ?: [];
                $shortcodes[sha1(serialize($atts))] = [$atts, $match[5]];
            }
            return $shortcodes;
        }
        return null;
    }

    public function update_registered_post_ids(int $id, WP_Post $post): void
    {
        $post_ids = $this->get_registered_post_ids();
        $index = array_search($id, $post_ids);
        $updated = false;
        if ($post->post_status === 'publish' && $this->get_shortcodes($post->post_content)) {
            if ($index === false) {
                $post_ids[] = $id;
                $updated = true;
            }
        } elseif ($index !== false) {
            $post_ids = array_splice($post_ids, $index, 1);
            $updated = true;
        }

        if ($updated) {
            update_option($this->get_registered_option_name(), $post_ids);
        }
    }

    private function get_registered_option_name(): string
    {
        return sprintf('sywo_has_shortcode_%s', $this->shortcode);
    }

    public function get_registered_post_ids(): array
    {
        return get_option($this->get_registered_option_name(), []);
    }
}