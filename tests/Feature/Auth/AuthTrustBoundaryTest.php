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

    public function test_token_lifecycle_is_split_by_realm_and_the_facade_stays_small(): void
    {
        $facadePath = $this->root.'/app/Modules/Auth/Services/TokenService.php';
        $facade = (string) file_get_contents($facadePath);

        self::assertFileExists($this->root.'/app/Modules/Auth/Services/TenantTokenService.php');
        self::assertFileExists($this->root.'/app/Modules/Auth/Services/PlatformTokenService.php');
        self::assertStringContainsString('TenantTokenService', $facade);
        self::assertStringContainsString('PlatformTokenService', $facade);
        self::assertLessThanOrEqual(130, count(file($facadePath) ?: []));
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
