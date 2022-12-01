<?php

namespace Rdlv\WordPress\Sywo;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class AppShortcode implements ShortcodeListenerInterface
{
    /** @var string */
    protected $protected;

    /** @var KernelInterface */
    protected $kernel;

    /** @var string */
    private $output;

    /** @var callable */
    private $configure;

    public function __construct(ShortcodeHandler $handler, KernelInterface $kernel)
    {
        $handler->add_listener($this);
        $this->kernel = $kernel;
    }

    public function load(string $shortcode, array $attributes, $content): void
    {
        $request = Request::createFromGlobals();

        if ($this->configure) {
            call_user_func($this->configure, $request, $attributes, $content);
        }

        $response = $this->kernel->handle($request);

        if ($this->kernel->isDebug() && ($response->isServerError())) {
            $response->send();
            exit;
        }

        $isHtml = explode(';', $response->headers->get('Content-Type'))[0] === 'text/html';
        if ($response->isRedirection() || !$isHtml) {
            $response->send();
            exit;
        }

        // send cookies
        foreach ($response->headers->getCookies() as $cookie) {
            header('Set-Cookie: ' . $cookie, false);
        }

        if (($code = $response->getStatusCode()) !== 200) {
            $version = $response->getProtocolVersion();
            header(sprintf('HTTP/%s %s %s', $version, $code, Response::$statusTexts[$code]), true, $code);
        }

        // todo Extract debug bar and insert in footer

        $this->output = $response->getContent();
    }

    public function output(string $shortcode, array $attributes, $content): string
    {
        return $this->output ?? '';
    }

    public function updated(string $shortcode, array $ids)
    {
    }

    public function configure(callable $configure): self
    {
        $this->configure = $configure;
        return $this;
    }
}