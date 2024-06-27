<?php

namespace Rdlv\WordPress\Sywo;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

class App
{
    /**
     * @throws Exception
     */
    public static function run(KernelInterface $kernel, Request $request): Response
    {
        return (new self($kernel))->_run($request);
    }

    public function __construct(protected KernelInterface $kernel)
    {
    }

    /**
     * @throws Exception
     */
    public function _run(Request $request): Response
    {
        $response = $this->kernel->handle($request);

        if ($this->kernel instanceof TerminableInterface) {
            add_action('shutdown', function () use ($request, $response) {
                $this->kernel->terminate($request, $response);
            });
        }

        if ($this->isWrappedResponse($request, $response)) {
            $this->wrapResponse($request, $response);
            return $response;
        } else {
            $response->send();
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
}