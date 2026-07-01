<?php

declare(strict_types=1);

namespace Modules\Configuration\Tests;

use Illuminate\Validation\ValidationException;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Constants\ConfigurationValueType;
use Modules\Configuration\Data\ConfigurationDefinition;
use Modules\Configuration\Services\ConfigurationValueValidator;
use Modules\ReferenceData\Contracts\ReferenceValueLookupInterface;
use Tests\TestCase;

final class ConfigurationValueValidatorTest extends TestCase
{
    public function test_boolean_false_is_not_coerced_to_true(): void
    {
        $validator = new ConfigurationValueValidator($this->lookup());

        self::assertFalse($validator->validate($this->definition(ConfigurationValueType::BOOLEAN), 'false'));
        self::assertFalse($validator->validate($this->definition(ConfigurationValueType::BOOLEAN), 0));
        self::assertTrue($validator->validate($this->definition(ConfigurationValueType::BOOLEAN), 'true'));
    }

    public function test_option_values_are_restricted_to_the_owner_definition(): void
    {
        $validator = new ConfigurationValueValidator($this->lookup());
        $definition = $this->definition(ConfigurationValueType::STRING, options: ['fifo', 'weighted_average']);

        self::assertSame('fifo', $validator->validate($definition, 'fifo'));

        $this->expectException(ValidationException::class);
        $validator->validate($definition, 'invalid');
    }

    public function test_reference_values_must_be_active(): void
    {
        $lookup = $this->lookup(false);
        $validator = new ConfigurationValueValidator($lookup);

        $this->expectException(ValidationException::class);
        $validator->validate($this->definition(ConfigurationValueType::STRING, lookup: 'timezones'), 'Asia/Colombo');
    }

    public function test_decimal_values_remain_exact_strings(): void
    {
        $validator = new ConfigurationValueValidator($this->lookup());

        self::assertSame(
            '9007199254740993.125',
            $validator->validate($this->definition(ConfigurationValueType::DECIMAL), '9007199254740993.125000'),
        );
    }

    private function definition(string $type, array $options = [], ?string $lookup = null): ConfigurationDefinition
    {
        return new ConfigurationDefinition(
            key: 'test.setting',
            label: 'Test setting',
            description: 'A test setting.',
            owner: 'Tests',
            version: 1,
            valueType: $type,
            allowedScopes: [ConfigurationScope::TENANT],
            defaultValue: null,
            nullable: false,
            sensitive: false,
            runtimeMutable: true,
            inheritOrganizationHierarchy: false,
            options: $options,
            lookup: $lookup,
        );
    }

    private function lookup(bool $activeValueExists = true): ReferenceValueLookupInterface
    {
        $lookup = $this->createMock(ReferenceValueLookupInterface::class);
        $lookup->method('supports')->willReturn(true);
        $lookup->method('activeValueExists')->willReturn($activeValueExists);

        return $lookup;
    }
}
