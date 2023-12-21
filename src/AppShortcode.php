<?php

namespace Rdlv\WordPress\Sywo;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

class AppShortcode implements ShortcodeListenerInterface
{
    protected KernelInterface $kernel;
    private string $output = '';

    /** @var callable */
    private $configure;

    public function __construct(ShortcodeHandler $handler, KernelInterface $kernel)
    {
        $handler->add_listener($this);
        $this->kernel = $kernel;
    }

    /**
     * @throws Exception
     */
    public function load(string $shortcode, array $attributes, $content): void
    {
        Request::setFactory(function ($query, $request, $attributes, $cookies, $files, $server, $content) {
            $server['SCRIPT_NAME'] = rtrim(parse_url(get_permalink(), PHP_URL_PATH), '/').$_SERVER['SCRIPT_NAME'];
            $parts = parse_url($server['REQUEST_URI']);
            if ($parts['path'] === dirname($server['SCRIPT_NAME'])) {
                $server['REQUEST_URI'] = $parts['path'].'/';
                if (!empty($parts['query'])) {
                    $server['REQUEST_URI'] .= '?'.$parts['query'];
                }
            }
            return RequestFactory::createRequest($query, $request, $attributes, $cookies, $files, $server, $content);
        });
        $request = Request::createFromGlobals();

        if ($this->configure) {
            call_user_func($this->configure, $request, $attributes, $content);
        }

        $response = $this->kernel->handle($request);

        if ($this->isWrappedResponse($request, $response)) {
            $this->wrapResponse($request, $response);
        } else {
            $response->send();
            $this->terminate($request, $response);
            exit;
        }
    }

    private function wrapResponse(Request $request, Response $response): void
    {
        // forward headers
        $headers = ['X-Debug-Token', 'Set-Cookie'];
        foreach ($headers as $header) {
            if ($response->headers->has($header)) {
                header(sprintf('%s: %s', $header, $response->headers->get($header)), false);
            }
        }

        // forward cookies
//        foreach ($response->headers->getCookies() as $cookie) {
//            header('Set-Cookie: ' . $cookie, false);
//        }

        if (($code = $response->getStatusCode()) !== 200) {
            $version = $response->getProtocolVersion();
            header(sprintf('HTTP/%s %s %s', $version, $code, Response::$statusTexts[$code]), true, $code);
        }

        $this->output = $response->getContent();

        add_action('shutdown', function () use ($request, $response) {
            $this->terminate($request, $response);
        });
    }

    private function isWrappedResponse(Request $request, Response $response): bool
    {
        if (!$request->get('_wrap')) {
            return false;
        }
        if ($this->kernel->isDebug() && ($response->isServerError())) {
            return false;
        }
        if ($response->isRedirection()) {
            return false;
        }
        if (explode(';', $response->headers->get('Content-Type'))[0] !== 'text/html') {
            return false;
        }
        return true;
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @return void
     */
    private function terminate(Request $request, Response $response): void
    {
        if ($this->kernel instanceof TerminableInterface) {
            $this->kernel->terminate($request, $response);
        }
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