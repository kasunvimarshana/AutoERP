<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Constants\AuthErrorCode;
use Modules\Auth\Exceptions\AuthFailure;
use Modules\Auth\Http\Responses\AuthResponseFactory;
use Modules\Core\Http\Middleware\EnsureApiErrorResponseMiddleware;
use Modules\Core\Http\Middleware\RequestCorrelationIdMiddleware;
use Tests\TestCase;

final class AuthErrorContractTest extends TestCase
{
    public function test_auth_failure_preserves_code_details_and_correlation_id(): void
    {
        $request = Request::create('/api/v1/platform/auth/login', 'POST');
        $request->headers->set('Accept', 'application/json');
        $request->attributes->set(RequestCorrelationIdMiddleware::ATTRIBUTE, '01AUTHERRORCONTRACT0000000000');
        $this->app->instance('request', $request);

        $response = $this->app->make(AuthResponseFactory::class)->failure(new AuthFailure(
            AuthErrorCode::MFA_ENROLLMENT_REQUIRED,
            'Multi-factor authentication enrollment is required.',
            409,
            [
                'stage' => 'mfa_enrollment',
                'enrollment_proof' => 'one-time-proof',
            ],
        ));

        self::assertSame(409, $response->getStatusCode());
        self::assertSame(AuthErrorCode::MFA_ENROLLMENT_REQUIRED, $response->getData(true)['error']['code']);
        self::assertSame('mfa_enrollment', $response->getData(true)['error']['details']['stage']);
        self::assertSame('one-time-proof', $response->getData(true)['error']['details']['enrollment_proof']);
        self::assertSame(
            '01AUTHERRORCONTRACT0000000000',
            $response->headers->get(RequestCorrelationIdMiddleware::HEADER),
        );
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_error_normalization_preserves_legacy_typed_auth_details(): void
    {
        $request = Request::create('/api/v1/platform/auth/login', 'POST');
        $request->headers->set('Accept', 'application/json');
        $request->attributes->set(RequestCorrelationIdMiddleware::ATTRIBUTE, '01AUTHNORMALIZE000000000000');
        $this->app->instance('request', $request);

        $response = $this->app->make(EnsureApiErrorResponseMiddleware::class)->handle(
            $request,
            static fn (): JsonResponse => response()->json([
                'message' => 'A multi-factor authentication code is required.',
                'code' => AuthErrorCode::MFA_REQUIRED,
                'details' => ['stage' => 'mfa_challenge'],
            ], 401),
        );

        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(AuthErrorCode::MFA_REQUIRED, $payload['error']['code']);
        self::assertSame('mfa_challenge', $payload['error']['details']['stage']);
        self::assertSame('01AUTHNORMALIZE000000000000', $payload['error']['details']['correlation_id']);
    }
}
