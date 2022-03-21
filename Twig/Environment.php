<?php

namespace Rdlv\WordPress\Sywo\Twig;

use Rdlv\WordPress\Sywo\Hooks;

class Environment extends \Twig\Environment
{
    /** @var Hooks */
    private $hooks;

    /**
     * @required
     * @param Hooks $hooks
     */
    public function setHooks(Hooks $hooks): void
    {
        $this->hooks = $hooks;
    }

    public function render($name, array $context = []): string
    {
        $name = $this->hooks->filter('twig/render/template', $name, $context);
        $context = $this->hooks->filter('twig/render/context', $context, $name);
        return parent::render($name, $context);
    }

    public function display($name, array $context = []): void
    {
        $name = $this->hooks->filter('twig/display/template', $name, $context);
        $context = $this->hooks->filter('twig/display/context', $context, $name);
        parent::display($name, $context);
    }
}