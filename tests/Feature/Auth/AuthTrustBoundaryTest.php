<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use PHPUnit\Framework\TestCase;

final class AuthTrustBoundaryTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = dirname(__DIR__, 3);
    }

    public function test_unsafe_legacy_auth_surfaces_are_not_routable(): void
    {
        $routes = $this->source('app/Modules/Auth/Routes/api.php');
        $userRoutes = $this->source('app/Modules/User/Routes/api.php');

        foreach ([
            "Route::post('token'",
            "Route::post('validate-token'",
            "Route::post('verification'",
            "Route::post('identity/link'",
            "Route::delete('identity'",
            "Route::post('sso'",
        ] as $legacySurface) {
            self::assertStringNotContainsString($legacySurface, $routes);
        }

        self::assertStringNotContainsString('operator-invitations', $userRoutes);
        self::assertStringNotContainsString('security-recovery', $userRoutes);
        self::assertStringNotContainsString('/invitation', $userRoutes);
    }

    public function test_requests_reject_client_owned_security_context(): void
    {
        $expectations = [
            'app/Modules/Auth/Http/Requests/LoginRequest.php' => [
                "'tenant_id' => ['prohibited']",
                "'tenant_code' => ['prohibited']",
                "'user_id' => ['prohibited']",
                "'ip_address' => ['prohibited']",
                "'user_agent' => ['prohibited']",
            ],
            'app/Modules/Auth/Http/Requests/RefreshTokenRequest.php' => [
                "'refresh_token' => ['prohibited']",
                "'scopes' => ['prohibited']",
                "'session_id' => ['prohibited']",
                "'user_id' => ['prohibited']",
            ],
            'app/Modules/Auth/Http/Requests/AuthorizeClientRequest.php' => [
                "'user_id' => ['prohibited']",
                "'session_id' => ['prohibited']",
                "'client_secret' => ['prohibited']",
                "'code_challenge_method' => ['required', 'in:S256']",
            ],
            'app/Modules/Auth/Http/Requests/ExchangeAuthorizationCodeRequest.php' => [
                "'scopes' => ['prohibited']",
                "'user_id' => ['prohibited']",
                "'session_id' => ['prohibited']",
            ],
        ];

        foreach ($expectations as $path => $requiredFragments) {
            $source = $this->source($path);
            foreach ($requiredFragments as $fragment) {
                self::assertStringContainsString($fragment, $source, $path);
            }
        }
    }


    public function test_public_tenant_auth_resolves_workspace_without_requiring_a_preexisting_session(): void
    {
        $routes = $this->source('app/Modules/Auth/Routes/api.php');

        self::assertStringContainsString("'core.current_tenant.resolver_middleware_alias'", $routes);
        self::assertStringContainsString("'core.current_tenant.access_middleware_alias'", $routes);
        self::assertStringContainsString('->middleware([\'api\', $resolveCurrentTenant])', $routes);
        self::assertStringContainsString('$requireCurrentTenantAccess,', $routes);
    }

    public function test_token_validation_router_is_small_and_token_lifecycle_is_realm_owned(): void
    {
        $routerPath = $this->root.'/app/Modules/Auth/Services/AccessTokenRouter.php';
        $router = (string) file_get_contents($routerPath);

        self::assertFileExists($this->root.'/app/Modules/Auth/Services/TenantTokenService.php');
        self::assertFileExists($this->root.'/app/Modules/Auth/Services/PlatformTokenService.php');
        self::assertStringContainsString('TenantTokenService', $router);
        self::assertStringContainsString('PlatformTokenService', $router);
        self::assertStringContainsString('function validate(', $router);
        self::assertStringNotContainsString('issueSessionTokens', $router);
        self::assertStringNotContainsString('function refresh(', $router);
        self::assertLessThanOrEqual(80, count(file($routerPath) ?: []));
    }

    public function test_tenant_and_platform_authentication_directories_are_separate(): void
    {
        self::assertFileExists($this->root.'/app/Modules/User/Services/Authentication/TenantUserAuthenticationDirectory.php');
        self::assertFileExists($this->root.'/app/Modules/User/Services/Authentication/PlatformOperatorAuthenticationDirectory.php');
        self::assertFileDoesNotExist($this->root.'/app/Modules/User/Services/Authentication/AuthenticationDirectory.php');

        $tenantDirectory = $this->source('app/Modules/User/Services/Authentication/TenantUserAuthenticationDirectory.php');
        $platformDirectory = $this->source('app/Modules/User/Services/Authentication/PlatformOperatorAuthenticationDirectory.php');
        self::assertStringNotContainsString('PlatformPermissionCheckerInterface', $tenantDirectory);
        self::assertStringNotContainsString('TenantUserAuthenticationDirectoryInterface', $platformDirectory);
    }

    public function test_auth_does_not_restore_the_generic_data_record_repository_stack(): void
    {
        $authSource = $this->directorySource('app/Modules/Auth');

        self::assertStringNotContainsString('DataRecord', $authSource);
        self::assertStringNotContainsString('AuthWorkflowService', $authSource);
        self::assertStringNotContainsString('DatabaseTokenProvider', $authSource);
        self::assertStringNotContainsString('VerificationChallenge', $authSource);
    }

    public function test_audit_ownership_validation_uses_owner_directories_not_ou_schema_guesses(): void
    {
        $validator = $this->source(
            'app/Modules/Audit/Services/AuditOwnershipValidator.php',
        );

        self::assertStringContainsString('OrganizationUnitDirectoryInterface', $validator);
        self::assertStringContainsString('TenantDirectoryInterface', $validator);
        self::assertStringNotContainsString("DB::table('organization_units')", $validator);
        self::assertStringNotContainsString('deleted_at', $validator);
    }

    private function source(string $relativePath): string
    {
        $path = $this->root.'/'.$relativePath;
        self::assertFileExists($path);

        return (string) file_get_contents($path);
    }

    private function directorySource(string $relativePath): string
    {
        $directory = $this->root.'/'.$relativePath;
        $source = '';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $source .= "\n".(string) file_get_contents($file->getPathname());
            }
        }

        return $source;
    }
}
