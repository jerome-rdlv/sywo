<?php


namespace Rdlv\WordPress\Sywo;


use Psr\Container\ContainerInterface;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\FormView;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Hooks
{
    /** @var string */
    private $namespace;

    /** @var string */
    private $templatePaths = [];

    /** @var string */
    public $formTheme;

    /** @var Environment|null */
    private $twig;

    /** @var FilesystemLoader|null */
    private $loader;

    public function __construct(string $namespace, Environment $twig = null, FilesystemLoader $loader = null)
    {
        $this->namespace = $namespace;
        $this->twig = $twig;
        $this->loader = $loader;

        $this->twig && $this->add('form/form/view/finish', function (FormView $view) {
            if ($this->formTheme) {
                // override form theme
                $formRenderer = $this->twig->getRuntime(FormRenderer::class);
                /** @var TwigRendererEngine $engine */
                $formRenderer->getEngine()->setTheme($view, $this->formTheme, false);
            }
        });
    }

    /**
     * @param string $tag
     * @param callable $callback
     * @param int $priority
     * @param int $accepted_args
     * @return bool|true|void
     */
    public function add(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1)
    {
        return add_filter(sprintf('%s/%s', $this->namespace, $tag), $callback, $priority, $accepted_args);
    }

    /**
     * @param string $tag
     * @param mixed $default The value to filter
     * @param ...$args
     * @return mixed|void
     */
    public function filter(string $tag, $default, ...$args)
    {
        return apply_filters(sprintf('%s/%s', $this->namespace, $tag), $default, ...$args);
    }

    /**
     * @param string $tag
     * @param ...$args
     */
    public function do(string $tag, ...$args)
    {
        do_action(sprintf('%s/%s', $this->namespace, $tag), ...$args);
    }

    public function addTemplatePath(string $path, string $namespace = FilesystemLoader::MAIN_NAMESPACE): void
    {
        $this->loader && $this->loader->addPath($path, $namespace);
    }
}