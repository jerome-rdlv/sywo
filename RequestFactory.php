<?php

namespace Rdlv\WordPress\Sywo;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class RequestFactory implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    private $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    /**
     * @param RequestStack $requestStack
     * @param SessionInterface $session
     * @return Request|null
     */
    public function __invoke(RequestStack $requestStack): ?Request
    {
        if ($request = $requestStack->getCurrentRequest()) {
            if (!is_admin() && ($this->locator->has('session'))) {
                $request->setSession($this->locator->get('session'));
            }
            return $request;
        }
        return null;
    }

    public static function getSubscribedServices()
    {
        return [
            'session' => '?' . SessionInterface::class,
        ];
    }
}