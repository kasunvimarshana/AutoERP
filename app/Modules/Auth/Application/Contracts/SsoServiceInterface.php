<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Contracts;

use Modules\Auth\Domain\Entities\AccessToken;
use Modules\Auth\Domain\Exceptions\AuthenticationException;
use Modules\Auth\Domain\Exceptions\TokenExpiredException;

/**
 * Contract for SSO (Single Sign-On) operations.
 * Passport's OAuth2 endpoints provide the OAuth2 flow;
 * this service handles the application-level SSO logic.
 */
interface SsoServiceInterface
{
    /**
     * Exchange a cross-application SSO token for a local access token.
     *
     * @param  string  $ssoToken  Token issued by the SSO provider
     * @param  string  $provider  SSO provider name (e.g. 'passport', 'google')
     *
     * @throws AuthenticationException
     */
    public function exchangeToken(string $ssoToken, string $provider): AccessToken;

    /**
     * Validate an SSO token and return the user ID it represents.
     *
     * @throws TokenExpiredException
     */
    public function validateSsoToken(string $ssoToken, string $provider): int;
}
