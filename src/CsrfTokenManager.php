<?php

namespace Rdlv\WordPress\Sywo;

use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfTokenManager implements CsrfTokenManagerInterface
{
    /**
     * @inheritDoc
     */
    public function getToken(string $tokenId): CsrfToken
    {
        return new CsrfToken($tokenId, wp_create_nonce($tokenId));
    }

    /**
     * @inheritDoc
     */
    public function refreshToken(string $tokenId): CsrfToken
    {
        return new CsrfToken($tokenId, wp_create_nonce($tokenId));
    }

    /**
     * @inheritDoc
     */
    public function removeToken(string $tokenId): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function isTokenValid(CsrfToken $token): bool
    {
        return wp_verify_nonce($token->getValue(), $token->getId());
    }
}