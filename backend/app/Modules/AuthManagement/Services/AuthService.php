<?php

namespace App\Modules\AuthManagement\Services;

use App\Models\User;
use App\Modules\AuthManagement\Events\UserRegistered;
use App\Modules\AuthManagement\Events\UserLoggedIn;
use App\Modules\AuthManagement\Events\UserLoggedOut;
use App\Modules\AuthManagement\Events\PasswordChanged;
use App\Modules\AuthManagement\Events\LoginAttemptFailed;
use App\Modules\AuthManagement\Events\UserAccountLocked;
use App\Modules\AuthManagement\Repositories\SecurityAuditLogRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthService
{
    protected const MAX_LOGIN_ATTEMPTS = 5;
    protected const LOCKOUT_MINUTES = 30;

    public function __construct(
        protected ?MfaService $mfaService = null,
        protected ?SessionService $sessionService = null,
        protected ?SecurityAuditLogRepository $auditLogRepository = null
    ) {}

    public function register(array $data): array
    {
        try {
            DB::beginTransaction();

            $user = User::create([
                'tenant_id' => $data['tenant_id'] ?? null,
                'vendor_id' => $data['vendor_id'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'] ?? 'user',
                'status' => 'active',
                'password_changed_at' => now(),
            ]);

            // Assign default role if using Spatie Permission
            if (isset($data['role_name'])) {
                $user->assignRole($data['role_name']);
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            DB::commit();

            // Dispatch event after commit
            event(new UserRegistered($user, [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]));

            Log::info('User registered', ['user_id' => $user->id, 'email' => $user->email]);

            return [
                'user' => $user,
                'token' => $token,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User registration failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function login(array $credentials): array
    {
        $email = $credentials['email'];
        $password = $credentials['password'];
        $mfaCode = $credentials['mfa_code'] ?? null;

        $user = User::where('email', $email)->first();

        // Check if account is locked
        if ($user && $user->locked_until && $user->locked_until->isFuture()) {
            event(new LoginAttemptFailed($email, request()->ip() ?? '0.0.0.0', 'Account locked'));
            
            throw ValidationException::withMessages([
                'email' => ['Your account is locked. Please try again later or contact support.'],
            ]);
        }

        // Validate credentials
        if (!$user || !Hash::check($password, $user->password)) {
            if ($user) {
                $this->handleFailedLogin($user);
            }
            
            event(new LoginAttemptFailed($email, request()->ip() ?? '0.0.0.0', 'Invalid credentials'));
            
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check account status
        if ($user->status !== 'active') {
            event(new LoginAttemptFailed($email, request()->ip() ?? '0.0.0.0', 'Inactive account'));
            
            throw ValidationException::withMessages([
                'email' => ['Your account is inactive. Please contact support.'],
            ]);
        }

        try {
            DB::beginTransaction();

            // Check MFA if enabled
            if ($user->mfa_enabled) {
                if (!$mfaCode) {
                    DB::commit();
                    return [
                        'requires_mfa' => true,
                        'mfa_methods' => $this->mfaService ? 
                            $this->mfaService->getMfaStatus($user)['methods'] : 
                            [['type' => 'totp']],
                    ];
                }

                // Verify MFA code
                if ($this->mfaService && !$this->mfaService->verifyCode($user, $mfaCode)) {
                    DB::rollBack();
                    
                    event(new LoginAttemptFailed($email, request()->ip() ?? '0.0.0.0', 'Invalid MFA code'));
                    
                    throw ValidationException::withMessages([
                        'mfa_code' => ['The provided MFA code is incorrect.'],
                    ]);
                }
            }

            // Reset failed login attempts
            $user->update([
                'last_login_at' => now(),
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);

            // Create session if service is available
            if ($this->sessionService) {
                $this->sessionService->createSession($user);
            }

            // Generate token
            $token = $user->createToken('auth-token')->plainTextToken;

            DB::commit();

            // Dispatch event after commit
            event(new UserLoggedIn(
                $user,
                request()->ip() ?? '0.0.0.0',
                request()->userAgent() ?? 'Unknown'
            ));

            Log::info('User logged in', ['user_id' => $user->id, 'email' => $user->email]);

            return [
                'user' => $user->load('roles', 'permissions'),
                'token' => $token,
                'requires_mfa' => false,
            ];
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Login failed', ['email' => $email, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Handle failed login attempt
     */
    protected function handleFailedLogin(User $user): void
    {
        try {
            DB::beginTransaction();

            $attempts = $user->failed_login_attempts + 1;
            
            $updateData = ['failed_login_attempts' => $attempts];

            if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
                $updateData['locked_until'] = now()->addMinutes(self::LOCKOUT_MINUTES);
                
                event(new UserAccountLocked($user, 'Too many failed login attempts'));
                
                Log::warning('Account locked due to failed login attempts', [
                    'user_id' => $user->id,
                    'attempts' => $attempts
                ]);
            }

            $user->update($updateData);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to handle failed login', ['error' => $e->getMessage()]);
        }
    }

    public function logout(User $user): void
    {
        try {
            DB::beginTransaction();

            // Delete current token
            $user->currentAccessToken()->delete();
            
            DB::commit();

            // Dispatch event after commit
            event(new UserLoggedOut($user));

            Log::info('User logged out', ['user_id' => $user->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Logout failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function refreshToken(User $user): string
    {
        try {
            DB::beginTransaction();

            // Delete old tokens
            $user->tokens()->delete();

            // Create new token
            $token = $user->createToken('auth-token')->plainTextToken;

            DB::commit();

            Log::info('Token refreshed', ['user_id' => $user->id]);

            return $token;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Token refresh failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password is incorrect.'],
            ]);
        }

        try {
            DB::beginTransaction();

            $user->update([
                'password' => Hash::make($newPassword),
                'password_changed_at' => now(),
            ]);
            
            // Delete all tokens to force re-login
            $user->tokens()->delete();

            DB::commit();

            // Dispatch event after commit
            event(new PasswordChanged($user));

            Log::info('Password changed', ['user_id' => $user->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Password change failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Verify user email
     */
    public function verifyEmail(User $user): bool
    {
        try {
            DB::beginTransaction();

            $user->update(['email_verified_at' => now()]);

            DB::commit();

            Log::info('Email verified', ['user_id' => $user->id]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Email verification failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check if user account is locked
     */
    public function isAccountLocked(User $user): bool
    {
        return $user->locked_until && $user->locked_until->isFuture();
    }

    /**
     * Unlock user account
     */
    public function unlockAccount(User $user): bool
    {
        try {
            DB::beginTransaction();

            $user->update([
                'locked_until' => null,
                'failed_login_attempts' => 0,
            ]);

            DB::commit();

            Log::info('Account unlocked', ['user_id' => $user->id]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Account unlock failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
