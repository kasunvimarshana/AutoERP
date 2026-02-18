<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs;

use App\DTOs\ApiResponseDTO;
use Tests\TestCase;

/**
 * Test API Response DTO
 */
class ApiResponseDTOTest extends TestCase
{
    /** @test */
    public function it_creates_success_response(): void
    {
        $dto = ApiResponseDTO::success(['id' => 1, 'name' => 'Product']);

        $this->assertTrue($dto->success);
        $this->assertEquals('Operation successful', $dto->message);
        $this->assertEquals(['id' => 1, 'name' => 'Product'], $dto->data);
        $this->assertNull($dto->errors);
        $this->assertEquals(200, $dto->getStatusCode());
    }

    /** @test */
    public function it_creates_error_response(): void
    {
        $dto = ApiResponseDTO::error('Something went wrong', ['field' => ['error']]);

        $this->assertFalse($dto->success);
        $this->assertEquals('Something went wrong', $dto->message);
        $this->assertEquals(['field' => ['error']], $dto->errors);
        $this->assertEquals(400, $dto->getStatusCode());
    }

    /** @test */
    public function it_converts_to_array(): void
    {
        $dto = ApiResponseDTO::success(['id' => 1], 'Success message', ['total' => 100]);

        $array = $dto->toArray();

        $this->assertArrayHasKey('success', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('meta', $array);
        $this->assertTrue($array['success']);
        $this->assertEquals('Success message', $array['message']);
        $this->assertEquals(['total' => 100], $array['meta']);
    }
}
