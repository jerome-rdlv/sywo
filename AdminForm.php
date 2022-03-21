<?php

namespace Rdlv\WordPress\Sywo;

class AdminForm
{
    /** @var string */
    protected $slug;

    /** @var callable */
    protected $callable;

    /** @var string */
    protected $response;

    public function __construct(string $slug, callable $callable)
    {
        $this->slug = $slug;
        $this->callable = $callable;

        add_action('admin_init', [$this, 'admin_init']);
    }

    public function admin_init()
    {
        global $plugin_page;
        if ($plugin_page !== $this->slug) {
            return;
        }

        $this->response = call_user_func($this->callable);
    }

    public function __invoke()
    {
        echo $this->response;
    }
}