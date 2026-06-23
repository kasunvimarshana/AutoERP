<?php

declare(strict_types=1);

namespace Modules\Auth\Tests;

use Modules\Auth\Models\AuthAccessTokenModel;
use Modules\Auth\Models\AuthRefreshTokenModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AuthTokenModelCastTest extends TestCase
{
    /** @param class-string<AuthAccessTokenModel|AuthRefreshTokenModel> $modelClass */
    #[DataProvider('tokenModels')]
    public function test_token_metadata_is_encoded_as_json(string $modelClass): void
    {
        $model = new $modelClass();
        $model->fill([
            'metadata' => ['application_id' => 'platform'],
        ]);

        $storedMetadata = $model->getAttributes()['metadata'] ?? null;

        self::assertIsString($storedMetadata);
        self::assertSame(
            ['application_id' => 'platform'],
            json_decode($storedMetadata, true, flags: JSON_THROW_ON_ERROR),
        );
        self::assertSame(
            ['application_id' => 'platform'],
            $model->metadata,
        );
    }

    public function test_access_token_scopes_remain_json_casted(): void
    {
        $model = new AuthAccessTokenModel();
        $model->fill([
            'scopes' => ['platform'],
        ]);

        $storedScopes = $model->getAttributes()['scopes'] ?? null;

        self::assertIsString($storedScopes);
        self::assertSame(
            ['platform'],
            json_decode($storedScopes, true, flags: JSON_THROW_ON_ERROR),
        );
        self::assertSame(['platform'], $model->scopes);
    }

    /** @return iterable<string, array{class-string<AuthAccessTokenModel|AuthRefreshTokenModel>}> */
    public static function tokenModels(): iterable
    {
        yield 'access token' => [AuthAccessTokenModel::class];
        yield 'refresh token' => [AuthRefreshTokenModel::class];
    }
}
