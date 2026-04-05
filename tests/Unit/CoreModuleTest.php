<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Core\Application\DTOs\BaseDto;

class TestDto extends BaseDto
{
    public string $name;
    public ?string $email = null;
    public int $count = 0;
}

class CoreModuleTest extends TestCase
{
    public function test_base_dto_from_array(): void
    {
        $dto = TestDto::fromArray(['name' => 'John', 'email' => 'john@example.com', 'count' => 5]);
        $this->assertEquals('John', $dto->name);
        $this->assertEquals('john@example.com', $dto->email);
        $this->assertEquals(5, $dto->count);
    }

    public function test_base_dto_to_array(): void
    {
        $dto = TestDto::fromArray(['name' => 'Alice', 'count' => 3]);
        $arr = $dto->toArray();
        $this->assertEquals('Alice', $arr['name']);
        $this->assertEquals(3, $arr['count']);
        $this->assertArrayHasKey('email', $arr);
    }

    public function test_base_dto_fill(): void
    {
        $dto = new TestDto();
        $dto->fill(['name' => 'Bob', 'email' => 'bob@test.com']);
        $this->assertEquals('Bob', $dto->name);
        $this->assertEquals('bob@test.com', $dto->email);
    }

    public function test_domain_exception(): void
    {
        $e = new DomainException('Something went wrong');
        $this->assertEquals('Something went wrong', $e->getMessage());
        $this->assertInstanceOf(\RuntimeException::class, $e);
    }

    public function test_not_found_exception_with_id(): void
    {
        $e = new NotFoundException('Product', 42);
        $this->assertStringContainsString('Product', $e->getMessage());
        $this->assertStringContainsString('42', $e->getMessage());
        $this->assertEquals(404, $e->getCode());
    }

    public function test_not_found_exception_without_id(): void
    {
        $e = new NotFoundException('Category');
        $this->assertStringContainsString('Category', $e->getMessage());
        $this->assertEquals(404, $e->getCode());
    }

    public function test_not_found_exception_with_string_id(): void
    {
        $e = new NotFoundException('Config', 'my-key');
        $this->assertStringContainsString('Config', $e->getMessage());
        $this->assertStringContainsString('my-key', $e->getMessage());
    }
}
