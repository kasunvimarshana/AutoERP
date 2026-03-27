<?php

namespace Tests\Unit;

use Modules\Core\Infrastructure\ApiDoc\Contracts\ApiDocServiceInterface;
use Modules\Core\Infrastructure\ApiDoc\OpenApiSpec;
use Modules\Core\Infrastructure\ApiDoc\Services\SwaggerApiDocService;
use Modules\Core\Infrastructure\Http\Controllers\HealthController;
use Modules\Auth\Infrastructure\Http\Controllers\AuthController;
use Modules\OrganizationUnit\Infrastructure\Http\Controllers\OrganizationUnitAttachmentController;
use Modules\OrganizationUnit\Infrastructure\Http\Controllers\OrganizationUnitController;
use Modules\Tenant\Infrastructure\Http\Controllers\TenantAttachmentController;
use Modules\Tenant\Infrastructure\Http\Controllers\TenantController;
use Modules\User\Infrastructure\Http\Controllers\PermissionController;
use Modules\User\Infrastructure\Http\Controllers\RoleController;
use Modules\User\Infrastructure\Http\Controllers\UserAttachmentController;
use Modules\User\Infrastructure\Http\Controllers\UserController;
use OpenApi\Attributes as OA;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SwaggerApiDocTest extends TestCase
{
    // ── Infrastructure ────────────────────────────────────────────────────────

    /**
     * Verify the ApiDocServiceInterface contract exists.
     */
    public function test_api_doc_service_interface_exists(): void
    {
        $this->assertTrue(interface_exists(ApiDocServiceInterface::class));
    }

    /**
     * Verify SwaggerApiDocService implements ApiDocServiceInterface.
     */
    public function test_swagger_api_doc_service_implements_interface(): void
    {
        $this->assertTrue(class_exists(SwaggerApiDocService::class));
        $this->assertTrue(
            in_array(ApiDocServiceInterface::class, class_implements(SwaggerApiDocService::class), true),
        );
    }

    /**
     * Verify SwaggerApiDocService exposes the expected public methods.
     */
    public function test_swagger_api_doc_service_has_required_methods(): void
    {
        $reflection = new \ReflectionClass(SwaggerApiDocService::class);

        $this->assertTrue($reflection->hasMethod('generate'));
        $this->assertTrue($reflection->hasMethod('uiUrl'));
        $this->assertTrue($reflection->hasMethod('specUrl'));

        $this->assertTrue($reflection->getMethod('generate')->isPublic());
        $this->assertTrue($reflection->getMethod('uiUrl')->isPublic());
        $this->assertTrue($reflection->getMethod('specUrl')->isPublic());
    }

    /**
     * Verify the global OpenApiSpec class exists and is not abstract.
     */
    public function test_open_api_spec_class_exists(): void
    {
        $this->assertTrue(class_exists(OpenApiSpec::class));

        $reflection = new \ReflectionClass(OpenApiSpec::class);
        $this->assertFalse($reflection->isAbstract());
    }

    // ── Global attributes on OpenApiSpec ──────────────────────────────────────

    /**
     * Verify OpenApiSpec carries the top-level OA\Info attribute.
     */
    public function test_open_api_spec_has_info_attribute(): void
    {
        $reflection = new \ReflectionClass(OpenApiSpec::class);
        $attributes = $reflection->getAttributes(OA\Info::class);

        $this->assertNotEmpty($attributes, 'OpenApiSpec must carry an #[OA\Info] attribute.');
    }

    /**
     * Verify OpenApiSpec carries at least one OA\Server attribute.
     */
    public function test_open_api_spec_has_server_attribute(): void
    {
        $reflection = new \ReflectionClass(OpenApiSpec::class);
        $attributes = $reflection->getAttributes(OA\Server::class);

        $this->assertNotEmpty($attributes, 'OpenApiSpec must carry at least one #[OA\Server] attribute.');
    }

    /**
     * Verify OpenApiSpec carries the bearerAuth security scheme.
     */
    public function test_open_api_spec_has_security_scheme_attribute(): void
    {
        $reflection = new \ReflectionClass(OpenApiSpec::class);
        $attributes = $reflection->getAttributes(OA\SecurityScheme::class);

        $this->assertNotEmpty($attributes, 'OpenApiSpec must carry an #[OA\SecurityScheme] attribute.');

        /** @var OA\SecurityScheme $scheme */
        $scheme = $attributes[0]->newInstance();
        $this->assertSame('bearerAuth', $scheme->securityScheme);
        $this->assertSame('http', $scheme->type);
        $this->assertSame('bearer', $scheme->scheme);
    }

    /**
     * Verify OpenApiSpec defines all expected tags.
     */
    public function test_open_api_spec_has_all_module_tags(): void
    {
        $reflection = new \ReflectionClass(OpenApiSpec::class);
        $tagAttributes = $reflection->getAttributes(OA\Tag::class);

        $tagNames = array_map(
            fn (\ReflectionAttribute $a) => $a->newInstance()->name,
            $tagAttributes,
        );

        $expectedTags = [
            'Health',
            'Auth',
            'Users',
            'Roles',
            'Permissions',
            'User Attachments',
            'Tenants',
            'Tenant Attachments',
            'Organization Units',
            'OrgUnit Attachments',
            'Products',
            'Product Images',
        ];

        foreach ($expectedTags as $tag) {
            $this->assertContains($tag, $tagNames, "Tag '{$tag}' is missing from OpenApiSpec.");
        }
    }

    /**
     * Verify OpenApiSpec defines all expected reusable schemas.
     */
    public function test_open_api_spec_has_all_reusable_schemas(): void
    {
        $reflection = new \ReflectionClass(OpenApiSpec::class);
        $schemaAttributes = $reflection->getAttributes(OA\Schema::class);

        $schemaNames = array_map(
            fn (\ReflectionAttribute $a) => $a->newInstance()->schema,
            $schemaAttributes,
        );

        $expectedSchemas = [
            'ErrorResponse',
            'ValidationErrorResponse',
            'MessageResponse',
            'PaginationMeta',
            'PaginationLinks',
            'AuthTokenResponse',
            'AddressObject',
            'UserPreferencesObject',
            'PermissionObject',
            'RoleObject',
            'UserObject',
            'AttachmentObject',
            'DatabaseConfigObject',
            'TenantObject',
            'TenantConfigObject',
            'OrganizationUnitObject',
            'MoneyObject',
            'ProductImageObject',
            'ProductObject',
        ];

        foreach ($expectedSchemas as $schema) {
            $this->assertContains($schema, $schemaNames, "Schema '{$schema}' is missing from OpenApiSpec.");
        }
    }

    // ── Per-controller OA annotation coverage ────────────────────────────────

    /**
     * Verify HealthController check method is annotated with an OA operation.
     */
    public function test_health_controller_check_method_has_oa_annotation(): void
    {
        $this->assertControllerMethodsHaveOaAnnotations(HealthController::class, ['check']);
    }

    /**
     * Verify AuthController methods are annotated with OA operations.
     */
    public function test_auth_controller_methods_have_oa_annotations(): void
    {
        $this->assertControllerMethodsHaveOaAnnotations(AuthController::class, [
            'register',
            'login',
            'logout',
            'me',
            'refresh',
            'forgotPassword',
            'resetPassword',
            'ssoExchange',
        ]);
    }

    /**
     * Verify UserController methods are annotated with OA operations.
     */
    public function test_user_controller_methods_have_oa_annotations(): void
    {
        $this->assertControllerMethodsHaveOaAnnotations(UserController::class, [
            'index',
            'store',
            'show',
            'update',
            'destroy',
            'assignRole',
            'updatePreferences',
        ]);
    }

    /**
     * Verify RoleController methods are annotated with OA operations.
     */
    public function test_role_controller_methods_have_oa_annotations(): void
    {
        $this->assertControllerMethodsHaveOaAnnotations(RoleController::class, [
            'index',
            'show',
            'store',
            'destroy',
            'syncPermissions',
        ]);
    }

    /**
     * Verify PermissionController methods are annotated with OA operations.
     */
    public function test_permission_controller_methods_have_oa_annotations(): void
    {
        $this->assertControllerMethodsHaveOaAnnotations(PermissionController::class, [
            'index',
            'show',
            'store',
            'destroy',
        ]);
    }

    /**
     * Verify UserAttachmentController methods are annotated with OA operations.
     */
    public function test_user_attachment_controller_methods_have_oa_annotations(): void
    {
        $this->assertControllerMethodsHaveOaAnnotations(UserAttachmentController::class, [
            'index',
            'store',
            'destroy',
            'serve',
        ]);
    }

    /**
     * Verify TenantController methods are annotated with OA operations.
     */
    public function test_tenant_controller_methods_have_oa_annotations(): void
    {
        $this->assertControllerMethodsHaveOaAnnotations(TenantController::class, [
            'index',
            'store',
            'show',
            'update',
            'destroy',
            'updateConfig',
            'configByDomain',
        ]);
    }

    /**
     * Verify TenantAttachmentController methods are annotated with OA operations.
     */
    public function test_tenant_attachment_controller_methods_have_oa_annotations(): void
    {
        $this->assertControllerMethodsHaveOaAnnotations(TenantAttachmentController::class, [
            'index',
            'store',
            'destroy',
            'serve',
        ]);
    }

    /**
     * Verify OrganizationUnitController methods are annotated with OA operations.
     */
    public function test_organization_unit_controller_methods_have_oa_annotations(): void
    {
        $this->assertControllerMethodsHaveOaAnnotations(OrganizationUnitController::class, [
            'index',
            'store',
            'show',
            'update',
            'destroy',
            'tree',
            'move',
        ]);
    }

    /**
     * Verify OrganizationUnitAttachmentController methods are annotated with OA operations.
     */
    public function test_organization_unit_attachment_controller_methods_have_oa_annotations(): void
    {
        $this->assertControllerMethodsHaveOaAnnotations(OrganizationUnitAttachmentController::class, [
            'index',
            'store',
            'destroy',
            'serve',
        ]);
    }

    /**
     * Verify ProductController methods are annotated with OA operations.
     */
    public function test_product_controller_methods_have_oa_annotations(): void
    {
        $this->assertControllerMethodsHaveOaAnnotations(\Modules\Product\Infrastructure\Http\Controllers\ProductController::class, [
            'index',
            'store',
            'show',
            'update',
            'destroy',
        ]);
    }

    /**
     * Verify ProductImageController methods are annotated with OA operations.
     */
    public function test_product_image_controller_methods_have_oa_annotations(): void
    {
        $this->assertControllerMethodsHaveOaAnnotations(\Modules\Product\Infrastructure\Http\Controllers\ProductImageController::class, [
            'index',
            'store',
            'destroy',
            'serve',
        ]);
    }

    // ── OA\Info attribute values ───────────────────────────────────────────────

    /**
     * Verify the OA\Info attribute on OpenApiSpec has a non-empty title and version.
     */
    public function test_open_api_info_has_title_and_version(): void
    {
        $reflection = new \ReflectionClass(OpenApiSpec::class);
        $attributes = $reflection->getAttributes(OA\Info::class);

        $this->assertNotEmpty($attributes);

        /** @var OA\Info $info */
        $info = $attributes[0]->newInstance();

        $this->assertNotEmpty($info->title, 'OA\Info title must not be empty.');
        $this->assertNotEmpty($info->version, 'OA\Info version must not be empty.');
    }

    // ── Schema accuracy ───────────────────────────────────────────────────────

    /**
     * Verify UserPreferencesObject schema uses the actual domain fields
     * (language, timezone, notifications) not legacy stubs.
     */
    public function test_user_preferences_schema_has_correct_fields(): void
    {
        $reflection = new \ReflectionClass(OpenApiSpec::class);
        $schemaAttributes = $reflection->getAttributes(OA\Schema::class);

        $prefSchema = null;
        foreach ($schemaAttributes as $attr) {
            $instance = $attr->newInstance();
            if ($instance->schema === 'UserPreferencesObject') {
                $prefSchema = $instance;
                break;
            }
        }

        $this->assertNotNull($prefSchema, 'UserPreferencesObject schema must exist.');

        $propertyNames = array_map(
            fn (OA\Property $p) => $p->property,
            $prefSchema->properties,
        );

        $this->assertContains('language',      $propertyNames, "UserPreferencesObject must have 'language' property.");
        $this->assertContains('timezone',      $propertyNames, "UserPreferencesObject must have 'timezone' property.");
        $this->assertContains('notifications', $propertyNames, "UserPreferencesObject must have 'notifications' property.");

        $this->assertNotContains('locale', $propertyNames, "UserPreferencesObject must not have legacy 'locale' property.");
        $this->assertNotContains('theme',  $propertyNames, "UserPreferencesObject must not have legacy 'theme' property.");
    }

    // ── Store endpoints return HTTP 201 ───────────────────────────────────────

    /**
     * Verify that every store/create operation documents a 201 Created response.
     */
    #[DataProvider('storeOperationProvider')]
    public function test_store_operations_document_201_response(string $controllerClass, string $method): void
    {
        $reflection = new \ReflectionClass($controllerClass);
        $this->assertTrue($reflection->hasMethod($method), "{$controllerClass}::{$method} must exist.");

        $methodRef = $reflection->getMethod($method);

        $oaPostAttrs = $methodRef->getAttributes(OA\Post::class);
        $this->assertNotEmpty($oaPostAttrs, "{$controllerClass}::{$method} must carry an #[OA\\Post] attribute.");

        /** @var OA\Post $post */
        $post = $oaPostAttrs[0]->newInstance();

        $responseCodes = array_map(
            fn (OA\Response $r) => (int) $r->response,
            $post->responses,
        );

        $this->assertContains(
            201,
            $responseCodes,
            "{$controllerClass}::{$method} must document a 201 Created response.",
        );
    }

    /**
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function storeOperationProvider(): array
    {
        return [
            'UserController::store'             => [UserController::class,             'store'],
            'TenantController::store'           => [TenantController::class,           'store'],
            'OrganizationUnitController::store' => [OrganizationUnitController::class, 'store'],
            'RoleController::store'             => [RoleController::class,             'store'],
            'PermissionController::store'       => [PermissionController::class,       'store'],
            'ProductController::store'          => [\Modules\Product\Infrastructure\Http\Controllers\ProductController::class, 'store'],
        ];
    }

    // ── Request body accuracy ─────────────────────────────────────────────────

    /**
     * Verify TenantController::store documents database_config as a required field.
     */
    public function test_tenant_store_documents_database_config(): void
    {
        $jsonContent = $this->getStoreRequestBodyContent(TenantController::class);

        $this->assertNotNull($jsonContent, 'TenantController::store must have a JSON request body.');

        $required = $jsonContent->required ?? [];
        $this->assertContains(
            'database_config',
            $required,
            "TenantController::store requestBody must list 'database_config' as required.",
        );
    }

    /**
     * Verify UserController::store does not document password/password_confirmation
     * (those fields are only for authentication registration, not admin user creation).
     */
    public function test_user_store_does_not_document_password_fields(): void
    {
        $jsonContent = $this->getStoreRequestBodyContent(UserController::class);

        $this->assertNotNull($jsonContent, 'UserController::store must have a JSON request body.');

        $propertyNames = array_map(
            fn (OA\Property $p) => $p->property,
            $jsonContent->properties ?? [],
        );

        $this->assertNotContains(
            'password',
            $propertyNames,
            "UserController::store must not document a 'password' field (not a validation rule for admin user creation).",
        );
        $this->assertNotContains(
            'password_confirmation',
            $propertyNames,
            "UserController::store must not document a 'password_confirmation' field.",
        );
    }

    // ── l5-swagger config ─────────────────────────────────────────────────────

    /**
     * Verify the l5-swagger config file exists and contains required keys.
     */
    public function test_l5_swagger_config_file_exists_and_is_valid(): void
    {
        $configPath = dirname(__DIR__, 2).'/config/l5-swagger.php';

        $this->assertFileExists($configPath, 'config/l5-swagger.php must exist.');

        $contents = file_get_contents($configPath);
        $this->assertIsString($contents);

        // Verify top-level config keys are present in the file source.
        $this->assertStringContainsString("'default'", $contents);
        $this->assertStringContainsString("'documentations'", $contents);
        $this->assertStringContainsString("'defaults'", $contents);
        $this->assertStringContainsString("'annotations'", $contents);
    }

    /**
     * Verify the annotations path in config points to the app directory.
     */
    public function test_l5_swagger_config_annotations_path_includes_app(): void
    {
        $configPath = dirname(__DIR__, 2).'/config/l5-swagger.php';
        $contents = (string) file_get_contents($configPath);

        // The annotations array must reference base_path('app') or base_path("app").
        $this->assertTrue(
            str_contains($contents, "base_path('app')") || str_contains($contents, 'base_path("app")'),
            "The 'annotations' key must include base_path('app').",
        );
    }

    /**
     * Verify l5-swagger config maps L5_SWAGGER_CONST_HOST to APP_URL so the
     * generated OpenAPI server URL is driven by an environment variable.
     */
    public function test_l5_swagger_config_maps_const_host_to_app_url(): void
    {
        $configPath = dirname(__DIR__, 2).'/config/l5-swagger.php';
        $contents = (string) file_get_contents($configPath);

        // The constants section must define L5_SWAGGER_CONST_HOST and derive
        // its value from the L5_SWAGGER_CONST_HOST / APP_URL env variables.
        $this->assertStringContainsString('L5_SWAGGER_CONST_HOST', $contents,
            "config/l5-swagger.php must define the L5_SWAGGER_CONST_HOST constant.");
        $this->assertTrue(
            str_contains($contents, "env('APP_URL'") || str_contains($contents, 'env("APP_URL"'),
            "The L5_SWAGGER_CONST_HOST constant must fall back to the APP_URL env variable.",
        );
    }

    /**
     * Verify OpenApiSpec server attribute references the configurable host constant
     * (L5_SWAGGER_CONST_HOST) rather than a hard-coded URL string.
     */
    public function test_open_api_spec_server_uses_configurable_host_constant(): void
    {
        $specPath = dirname(__DIR__, 2)
            .'/app/Modules/Core/Infrastructure/ApiDoc/OpenApiSpec.php';

        $this->assertFileExists($specPath, 'OpenApiSpec.php must exist.');

        $contents = (string) file_get_contents($specPath);

        // The #[OA\Server] must reference L5_SWAGGER_CONST_HOST so that
        // the generated spec URL is driven by the APP_URL / L5_SWAGGER_CONST_HOST
        // environment variable, not a hard-coded string.
        $this->assertStringContainsString(
            'L5_SWAGGER_CONST_HOST',
            $contents,
            "OpenApiSpec #[OA\Server] must use L5_SWAGGER_CONST_HOST for a configurable server URL.",
        );
    }

    // ── CORS configuration ────────────────────────────────────────────────────

    /**
     * Verify config/cors.php exists and contains the required top-level keys.
     */
    public function test_cors_config_file_exists_and_is_valid(): void
    {
        $configPath = dirname(__DIR__, 2).'/config/cors.php';

        $this->assertFileExists($configPath, 'config/cors.php must exist.');

        $contents = (string) file_get_contents($configPath);

        foreach (['paths', 'allowed_methods', 'allowed_origins', 'allowed_headers', 'supports_credentials'] as $key) {
            $this->assertStringContainsString("'{$key}'", $contents,
                "config/cors.php must contain the '{$key}' key.");
        }
    }

    /**
     * Verify the CORS config covers the API and documentation paths.
     */
    public function test_cors_config_covers_api_and_docs_paths(): void
    {
        $configPath = dirname(__DIR__, 2).'/config/cors.php';
        $contents = (string) file_get_contents($configPath);

        // The paths array must include the main API prefix and the Swagger docs route.
        $this->assertStringContainsString('api/*', $contents,
            "config/cors.php 'paths' must include 'api/*'.");
        $this->assertStringContainsString('docs', $contents,
            "config/cors.php 'paths' must include the docs route.");
    }

    /**
     * Verify that all CORS settings can be overridden via environment variables.
     */
    public function test_cors_config_uses_environment_variables(): void
    {
        $configPath = dirname(__DIR__, 2).'/config/cors.php';
        $contents = (string) file_get_contents($configPath);

        $envVars = [
            'CORS_ALLOWED_ORIGINS',
            'CORS_ALLOWED_METHODS',
            'CORS_ALLOWED_HEADERS',
            'CORS_SUPPORTS_CREDENTIALS',
            'CORS_MAX_AGE',
        ];

        foreach ($envVars as $var) {
            $this->assertStringContainsString($var, $contents,
                "config/cors.php must reference the {$var} environment variable.");
        }
    }

    /**
     * Verify the .env.example documents all CORS environment variables.
     */
    public function test_env_example_documents_cors_variables(): void
    {
        $envPath = dirname(__DIR__, 2).'/.env.example';

        $this->assertFileExists($envPath, '.env.example must exist.');

        $contents = (string) file_get_contents($envPath);

        foreach (['CORS_ALLOWED_ORIGINS', 'CORS_ALLOWED_METHODS', 'CORS_ALLOWED_HEADERS', 'CORS_SUPPORTS_CREDENTIALS'] as $var) {
            $this->assertStringContainsString($var, $contents,
                ".env.example must document the {$var} variable.");
        }
    }

    // ── Versioning strategy documentation ────────────────────────────────────

    /**
     * Verify the OA\Info description documents the versioning strategy.
     * The description must mention semantic versioning (semver) so that
     * API consumers know how future releases are communicated.
     */
    public function test_open_api_info_description_documents_versioning_strategy(): void
    {
        $reflection = new \ReflectionClass(OpenApiSpec::class);
        $attributes = $reflection->getAttributes(OA\Info::class);

        $this->assertNotEmpty($attributes, 'OpenApiSpec must carry an #[OA\Info] attribute.');

        /** @var OA\Info $info */
        $info = $attributes[0]->newInstance();

        $this->assertNotEmpty($info->description, 'OA\Info description must not be empty.');

        // Versioning strategy must be mentioned in the description.
        $lowerDesc = strtolower((string) $info->description);
        $this->assertTrue(
            str_contains($lowerDesc, 'versioning') || str_contains($lowerDesc, 'semver'),
            'OA\Info description must document the API versioning strategy.',
        );
    }

    /**
     * Verify the OA\Info description explains how breaking changes (major versions)
     * are handled (i.e. a new URL path prefix is introduced for backward compatibility).
     */
    public function test_open_api_info_description_explains_major_version_strategy(): void
    {
        $reflection = new \ReflectionClass(OpenApiSpec::class);
        $attributes = $reflection->getAttributes(OA\Info::class);

        $this->assertNotEmpty($attributes);

        /** @var OA\Info $info */
        $info = $attributes[0]->newInstance();

        $lowerDesc = strtolower((string) $info->description);

        // The description must indicate that breaking changes introduce a new URL
        // path prefix so that both old and new versions remain accessible.
        $this->assertTrue(
            (str_contains($lowerDesc, 'major') && (str_contains($lowerDesc, 'path') || str_contains($lowerDesc, 'url')))
            || str_contains($lowerDesc, 'breaking'),
            'OA\Info description must explain the major-version / breaking-change strategy.',
        );
    }

    // ── Additional l5-swagger configurability ────────────────────────────────

    /**
     * Verify that validator_url in l5-swagger config is driven by an environment
     * variable so operators can enable or disable Swagger UI request validation
     * without touching the config file.
     */
    public function test_l5_swagger_config_validator_url_is_env_driven(): void
    {
        $configPath = dirname(__DIR__, 2).'/config/l5-swagger.php';
        $contents = (string) file_get_contents($configPath);

        $this->assertStringContainsString(
            'L5_SWAGGER_VALIDATOR_URL',
            $contents,
            "config/l5-swagger.php must drive 'validator_url' from the L5_SWAGGER_VALIDATOR_URL env variable.",
        );
    }

    /**
     * Verify that additional_config_url in l5-swagger config is driven by an
     * environment variable so operators can point to an external Swagger UI
     * config without touching the config file.
     */
    public function test_l5_swagger_config_additional_config_url_is_env_driven(): void
    {
        $configPath = dirname(__DIR__, 2).'/config/l5-swagger.php';
        $contents = (string) file_get_contents($configPath);

        $this->assertStringContainsString(
            'L5_SWAGGER_ADDITIONAL_CONFIG_URL',
            $contents,
            "config/l5-swagger.php must drive 'additional_config_url' from the L5_SWAGGER_ADDITIONAL_CONFIG_URL env variable.",
        );
    }

    /**
     * Verify .env.example documents the L5_SWAGGER_VALIDATOR_URL and
     * L5_SWAGGER_ADDITIONAL_CONFIG_URL variables for operator awareness.
     */
    public function test_env_example_documents_swagger_configurability_variables(): void
    {
        $envPath = dirname(__DIR__, 2).'/.env.example';
        $this->assertFileExists($envPath, '.env.example must exist.');

        $contents = (string) file_get_contents($envPath);

        foreach (['L5_SWAGGER_VALIDATOR_URL', 'L5_SWAGGER_ADDITIONAL_CONFIG_URL'] as $var) {
            $this->assertStringContainsString($var, $contents,
                ".env.example must document the {$var} variable.");
        }
    }

    /**
     * Verify .env.example includes documentation about L5_SWAGGER_GENERATE_ALWAYS
     * so developers know to set it to true in local environments.
     */
    public function test_env_example_documents_generate_always_variable(): void
    {
        $envPath = dirname(__DIR__, 2).'/.env.example';
        $contents = (string) file_get_contents($envPath);

        $this->assertStringContainsString(
            'L5_SWAGGER_GENERATE_ALWAYS',
            $contents,
            ".env.example must document the L5_SWAGGER_GENERATE_ALWAYS variable.",
        );
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    /**
     * Assert that every listed method on a controller class carries at least one
     * OA HTTP-operation attribute (Get, Post, Put, Patch, Delete, Head, Options).
     */
    private function assertControllerMethodsHaveOaAnnotations(string $controllerClass, array $methods): void
    {
        $this->assertTrue(class_exists($controllerClass), "Controller class {$controllerClass} must exist.");

        $reflection = new \ReflectionClass($controllerClass);

        $oaOperationClasses = [
            OA\Get::class,
            OA\Post::class,
            OA\Put::class,
            OA\Patch::class,
            OA\Delete::class,
            OA\Head::class,
            OA\Options::class,
        ];

        foreach ($methods as $methodName) {
            $this->assertTrue(
                $reflection->hasMethod($methodName),
                "Method {$controllerClass}::{$methodName} must exist.",
            );

            $method = $reflection->getMethod($methodName);
            $hasOaAttr = false;

            foreach ($oaOperationClasses as $oaClass) {
                if (! empty($method->getAttributes($oaClass))) {
                    $hasOaAttr = true;
                    break;
                }
            }

            $this->assertTrue(
                $hasOaAttr,
                "Method {$controllerClass}::{$methodName} must have an OA HTTP-operation attribute.",
            );
        }
    }

    /**
     * Retrieve the OA\JsonContent from the store() method's OA\Post requestBody.
     *
     * The l5-swagger attribute system stores the JsonContent in the internal
     * `_unmerged` array of the RequestBody object rather than the `content` property.
     */
    private function getStoreRequestBodyContent(string $controllerClass): ?OA\JsonContent
    {
        $reflection = new \ReflectionClass($controllerClass);
        $methodRef  = $reflection->getMethod('store');

        $oaPostAttrs = $methodRef->getAttributes(OA\Post::class);
        if (empty($oaPostAttrs)) {
            return null;
        }

        /** @var OA\Post $post */
        $post = $oaPostAttrs[0]->newInstance();

        if (! $post->requestBody instanceof OA\RequestBody) {
            return null;
        }

        $rbRef       = new \ReflectionObject($post->requestBody);
        $unmergedProp = $rbRef->getProperty('_unmerged');
        $unmergedProp->setAccessible(true);
        $unmerged = $unmergedProp->getValue($post->requestBody);

        foreach ($unmerged as $item) {
            if ($item instanceof OA\JsonContent) {
                return $item;
            }
        }

        return null;
    }
}
