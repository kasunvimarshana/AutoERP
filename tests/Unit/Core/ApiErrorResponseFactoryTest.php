<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use InvalidArgumentException;
use Modules\Core\Http\Responses\ApiErrorResponseFactory;
use PHPUnit\Framework\TestCase;

final class ApiErrorResponseFactoryTest extends TestCase
{
    public function test_validation_errors_follow_the_shared_contract(): void
    {
        $response = (new ApiErrorResponseFactory())->forStatus(422, 'Validation failed.', [
            'fields' => ['amount' => ['The amount field is required.']],
        ]);
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(422, $response->getStatusCode());
        self::assertFalse($payload['success']);
        self::assertSame('VALIDATION_FAILED', $payload['error']['code']);
        self::assertSame($payload['errors'], $payload['error']['details']['fields']);
    }
    public function test_it_rejects_non_error_http_statuses(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ApiErrorResponseFactory())->forStatus(200, 'Not an error.');
    }
}
