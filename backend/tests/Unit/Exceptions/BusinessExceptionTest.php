<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\BusinessException;
use App\Exceptions\EntityNotFoundException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\ValidationException;
use Tests\TestCase;

class BusinessExceptionTest extends TestCase
{
    public function test_entity_not_found_exception_has_correct_status_code(): void
    {
        $exception = new EntityNotFoundException('Product', '123');
        
        $this->assertEquals(404, $exception->getStatusCode());
        $this->assertEquals('ENTITY_NOT_FOUND', $exception->getErrorCode());
    }

    public function test_entity_not_found_exception_has_context(): void
    {
        $exception = new EntityNotFoundException('Product', '123');
        
        $context = $exception->getContext();
        
        $this->assertEquals('Product', $context['entity_type']);
        $this->assertEquals('123', $context['identifier']);
    }

    public function test_validation_exception_has_errors(): void
    {
        $errors = ['email' => 'Invalid email format'];
        $exception = new ValidationException('Validation failed', $errors);
        
        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertEquals('VALIDATION_ERROR', $exception->getErrorCode());
        $this->assertEquals($errors, $exception->getContext()['errors']);
    }

    public function test_forbidden_exception_formats_message(): void
    {
        $exception = new ForbiddenException('products', 'delete');
        
        $this->assertEquals(403, $exception->getStatusCode());
        $this->assertStringContainsString('delete products', $exception->getMessage());
    }

    public function test_exception_can_render_json_response(): void
    {
        $exception = new EntityNotFoundException('Product', '123');
        
        $response = $exception->render();
        
        $this->assertEquals(404, $response->getStatusCode());
        
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
        $this->assertEquals('ENTITY_NOT_FOUND', $content['error']['code']);
    }
}
