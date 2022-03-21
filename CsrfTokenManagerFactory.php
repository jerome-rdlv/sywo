<?php

namespace Rdlv\WordPress\Sywo;

use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManager as CsrfTokenManagerNative;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class CsrfTokenManagerFactory implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    private $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public function __invoke()
    {
        $manager = $this->locator->get(
            is_admin()
                ? 'security.csrf.token_manager.wp'
                : 'security.csrf.token_manager.native'
        );
        return $manager;
    }

    public static function getSubscribedServices()
    {
        return [
            'security.csrf.token_manager.native' => '?' . CsrfTokenManagerNative::class,
            'security.csrf.token_manager.wp'     => '?' . CsrfTokenManager::class,
        ];
    }
}