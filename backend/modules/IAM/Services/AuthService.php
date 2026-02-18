<?php

namespace Modules\IAM\Services;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Modules\Core\Services\BaseService;
use Modules\Core\Services\TenantContext;
use Modules\IAM\DTOs\LoginDTO;
use Modules\IAM\DTOs\RegisterDTO;
use Modules\IAM\Events\PasswordReset;
use Modules\IAM\Events\UserLoggedIn;
use Modules\IAM\Events\UserLoggedOut;
use Modules\IAM\Exceptions\InvalidCredentialsException;
use Modules\IAM\Exceptions\RateLimitExceededException;
use Modules\IAM\Models\User;
use Modules\IAM\Repositories\LoginAttemptRepository;
use Modules\IAM\Repositories\UserRepository;

class AuthService extends BaseService
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    private const LOGIN_ATTEMPT_WINDOW = 15; // minutes

    public function __construct(
        TenantContext $tenantContext,
        protected UserRepository $userRepository,
        protected LoginAttemptRepository $loginAttemptRepository,
        protected MfaService $mfaService
    ) {
        parent::__construct($tenantContext);
    }

    public function login(LoginDTO $dto, string $ipAddress, string $userAgent): array
    {
        $this->checkRateLimit($dto->email, $ipAddress);

        $user = $this->userRepository->findByEmail($dto->email);

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            $this->recordFailedAttempt($user?->id, $dto->email, $ipAddress, $userAgent, 'Invalid credentials');
            throw InvalidCredentialsException::default();
        }

        if (! $user->is_active) {
            $this->recordFailedAttempt($user->id, $dto->email, $ipAddress, $userAgent, 'Account inactive');
            throw InvalidCredentialsException::accountInactive();
        }

        // Check MFA if enabled
        if ($user->mfa_enabled) {
            if (!$dto->mfa_code) {
                // MFA code is required but not provided
                throw InvalidCredentialsException::invalidMfaCode();
            }

            $secret = $user->getMfaSecret();
            if (!$secret) {
                throw InvalidCredentialsException::invalidMfaCode();
            }

            // Try to verify MFA code or backup code
            $isValidMfa = $this->mfaService->verifyCode($secret, $dto->mfa_code);
            $isValidBackup = !$isValidMfa && $this->mfaService->verifyBackupCode($user, $dto->mfa_code);

            if (!$isValidMfa && !$isValidBackup) {
                $this->recordFailedAttempt($user->id, $dto->email, $ipAddress, $userAgent, 'Invalid MFA code');
                throw InvalidCredentialsException::invalidMfaCode();
            }
        }

        // Define token abilities based on user roles and permissions
        $abilities = $this->getUserAbilities($user);

        $token = $user->createToken(
            'auth-token',
            $abilities,
            $dto->remember ? now()->addDays(30) : now()->addDay()
        )->plainTextToken;

        $user->updateLastLogin($ipAddress);

        $this->recordSuccessfulAttempt($user->id, $dto->email, $ipAddress, $userAgent);
        $this->dispatchEvent(new UserLoggedIn($user, $ipAddress, $userAgent));

        return [
            'user' => $user->load('roles', 'permissions'),
            'token' => $token,
            'expires_at' => $dto->remember ? now()->addDays(30) : now()->addDay(),
        ];
    }

    public function register(RegisterDTO $dto): User
    {
        return $this->transaction(function () use ($dto) {
            $user = $this->userRepository->create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => Hash::make($dto->password),
                'tenant_id' => $dto->tenant_id ?? $this->getTenantId(),
                'phone' => $dto->phone,
                'timezone' => $dto->timezone ?? 'UTC',
                'locale' => $dto->locale ?? 'en',
                'is_active' => true,
            ]);

            event(new Registered($user));

            return $user;
        });
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
        $this->dispatchEvent(new UserLoggedOut($user));
    }

    public function logoutAllDevices(User $user): void
    {
        $user->tokens()->delete();
        $this->dispatchEvent(new UserLoggedOut($user));
    }

    public function refreshToken(User $user, bool $remember = false): string
    {
        $user->currentAccessToken()->delete();

        $abilities = $this->getUserAbilities($user);

        return $user->createToken(
            'auth-token',
            $abilities,
            $remember ? now()->addDays(30) : now()->addDay()
        )->plainTextToken;
    }

    public function sendPasswordResetLink(string $email): string
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }

    public function resetPassword(string $email, string $password, string $token): string
    {
        $status = Password::reset(
            ['email' => $email, 'password' => $password, 'password_confirmation' => $password, 'token' => $token],
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        $this->dispatchEvent(new PasswordReset($email));

        return __($status);
    }

    private function checkRateLimit(string $email, string $ipAddress): void
    {
        $emailAttempts = $this->loginAttemptRepository->countRecentFailedAttempts(
            $email,
            self::LOGIN_ATTEMPT_WINDOW
        );

        $ipAttempts = $this->loginAttemptRepository->countRecentFailedAttemptsForIp(
            $ipAddress,
            self::LOGIN_ATTEMPT_WINDOW
        );

        if ($emailAttempts >= self::MAX_LOGIN_ATTEMPTS || $ipAttempts >= self::MAX_LOGIN_ATTEMPTS) {
            throw RateLimitExceededException::forLogin(
                max($emailAttempts, $ipAttempts),
                self::LOGIN_ATTEMPT_WINDOW
            );
        }
    }

    private function recordSuccessfulAttempt(int $userId, string $email, string $ipAddress, string $userAgent): void
    {
        $this->loginAttemptRepository->recordAttempt(
            $userId,
            $email,
            $ipAddress,
            $userAgent,
            true,
            null,
            $this->getTenantId()
        );
    }

    private function recordFailedAttempt(?int $userId, string $email, string $ipAddress, string $userAgent, string $reason): void
    {
        $this->loginAttemptRepository->recordAttempt(
            $userId,
            $email,
            $ipAddress,
            $userAgent,
            false,
            $reason,
            $this->getTenantId()
        );
    }

    /**
     * Get user abilities for Sanctum token scoping
     * Returns specific abilities based on user permissions
     */
    private function getUserAbilities(User $user): array
    {
        // Load user permissions
        $user->loadMissing('roles.permissions', 'permissions');
        
        $abilities = [];
        
        // Add abilities from direct permissions
        foreach ($user->permissions as $permission) {
            $abilities[] = $permission->name;
        }
        
        // Add abilities from role permissions
        foreach ($user->roles as $role) {
            foreach ($role->permissions as $permission) {
                $abilities[] = $permission->name;
            }
        }
        
        // Remove duplicates
        $abilities = array_unique($abilities);
        
        // If user is super admin, grant all abilities
        if ($user->hasRole('super-admin')) {
            $abilities[] = '*';
        }
        
        // Ensure at least basic abilities
        if (empty($abilities)) {
            $abilities = ['read:own-profile', 'update:own-profile'];
        }
        
        return $abilities;
    }
}
