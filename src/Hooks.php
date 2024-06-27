<?php


namespace Rdlv\WordPress\Sywo;


use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\FormView;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;

readonly class Hooks
{
    public string $formTheme;

    public function __construct(
        private string $namespace,
        private ?Environment $twig = null,
        private ?FilesystemLoader $loader = null
    ) {
        $this->twig && $this->add('form/view/finish', function (FormView $view) {
            if (!isset($this->formTheme)) {
                return;
            }
            // override form theme
            $formRenderer = $this->twig->getRuntime(FormRenderer::class);
            /** @var TwigRendererEngine $engine */
            $formRenderer->getEngine()->setTheme($view, $this->formTheme, false);
        });
    }

    public function add(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1)
    {
        return add_filter(sprintf('%s/%s', $this->namespace, $tag), $callback, $priority, $accepted_args);
    }

    public function filter(string $tag, $default, ...$args)
    {
        return apply_filters(sprintf('%s/%s', $this->namespace, $tag), $default, ...$args);
    }

    public function do(string $tag, ...$args)
    {
        do_action(sprintf('%s/%s', $this->namespace, $tag), ...$args);
    }

    /**
     * @throws LoaderError
     */
    public function addTemplatePath(string $path, string $namespace = FilesystemLoader::MAIN_NAMESPACE): void
    {
        $this->loader && $this->loader->addPath($path, $namespace);
    }
}