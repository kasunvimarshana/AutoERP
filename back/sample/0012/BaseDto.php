<?php

declare(strict_types=1);

namespace Modules\Core\Application\DTOs;

use InvalidArgumentException;

/**
 * BaseDto
 *
 * Self-validating Data Transfer Object. Every module DTO extends this.
 * Inspired by KVAutoERP's confirmed pattern from PR #37.
 *
 * Pattern:
 *   - Extend and define public typed properties
 *   - Override rules() to declare Laravel validation rules
 *   - Use fromArray() as the primary factory — validates before hydrating
 *   - Use toArray() for serialization
 */
abstract class BaseDto
{
    public function __construct()
    {
        // Subclasses set defaults before calling parent::__construct()
    }

    /**
     * Laravel-compatible validation rules for all fields.
     * Subclasses MUST override this.
     */
    abstract public function rules(): array;

    /**
     * Factory — validate then hydrate. Throws on invalid data.
     */
    public static function fromArray(array $data): static
    {
        $dto = new static();
        $dto->validate($data);
        $dto->hydrate($data);
        return $dto;
    }

    public function toArray(): array
    {
        $props = [];
        foreach ((new \ReflectionClass($this))->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isInitialized($this)) {
                $props[$prop->getName()] = $prop->getValue($this);
            }
        }
        return $props;
    }

    protected function validate(array $data): void
    {
        $validator = \Illuminate\Support\Facades\Validator::make($data, $this->rules());
        if ($validator->fails()) {
            throw new InvalidArgumentException(
                'DTO validation failed: ' . implode(', ', $validator->errors()->all())
            );
        }
    }

    protected function hydrate(array $data): void
    {
        foreach ((new \ReflectionClass($this))->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $key = $prop->getName();
            if (array_key_exists($key, $data)) {
                $prop->setValue($this, $data[$key]);
            }
        }
    }
}
