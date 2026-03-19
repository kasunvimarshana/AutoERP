<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\RevocationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies that the authenticated user's token_version claim matches the
 * current value stored in the database.  This enables instant global
 * revocation when `logoutAllDevices` is called and increments the version.
 */
final class VerifyTokenVersion
{
    public function __construct(
        private readonly RevocationService $revocationService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        // Check if there is a global user-level revocation timestamp in Redis
        $revocationTimestamp = $this->revocationService->getUserRevocationTimestamp($user->id);

        if ($revocationTimestamp !== null) {
            // If the token was issued before the revocation timestamp, deny it
            $tokenModel = $user->token();

            if ($tokenModel !== null) {
                $issuedAt = $tokenModel->created_at?->timestamp ?? 0;

                if ($issuedAt < $revocationTimestamp) {
                    return response()->json([
                        'success' => false,
                        'error'   => [
                            'code'    => 'TOKEN_REVOKED',
                            'message' => 'Token has been revoked. Please log in again.',
                        ],
                    ], Response::HTTP_UNAUTHORIZED);
                }
            }
        }

        return $next($request);
    }
}
