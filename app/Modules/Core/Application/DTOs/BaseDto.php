<?php

declare(strict_types=1);

namespace Modules\Core\Application\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

// use Spatie\LaravelData\Data;

/**
 * Base Data Transfer Object (DTO)
 *
 * All DTOs should extend this class. It provides:
 * - Factory method fromArray()
 * - Fill from array
 * - Conversion to array / JSON
 * - Validation with Laravel Validator
 * - Support for nested DTOs (via casts array)
 */
abstract class BaseDto
{
    /**
     * Mapping of property names to DTO classes for nested DTOs.
     * Example: ['address' => AddressDto::class]
     *
     * @var array<string, string>
     */
    protected array $casts = [];

    /**
     * Create a new DTO instance from an array of data.
     */
    public static function fromArray(array $data): static
    {
        $dto = new static;
        $dto->fill($data);

        return $dto;
    }

    /**
     * Fill the DTO with data from an array.
     *
     * @return $this
     */
    public function fill(array $data): static
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                // If the value is an array and a cast exists, convert to DTO
                if (is_array($value) && isset($this->casts[$key])) {
                    $dtoClass = $this->casts[$key];
                    $this->$key = $dtoClass::fromArray($value);
                } else {
                    $this->$key = $value;
                }
            }
        }

        return $this;
    }

    /**
     * Convert the DTO to an array.
     */
    public function toArray(): array
    {
        $array = [];
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $name = $property->getName();
            $value = $this->$name;

            // Recursively convert nested DTOs
            if ($value instanceof self) {
                $value = $value->toArray();
            }

            $array[$name] = $value;
        }

        return $array;
    }

    /**
     * Convert the DTO to a JSON string.
     */
    public function toJson(int $options = JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Get the validation rules for the DTO.
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Get the validation messages for the DTO.
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Validate the DTO data.
     *
     * @throws ValidationException
     */
    public function validate(array $data): bool
    {
        $validator = Validator::make($data, $this->rules(), $this->messages());
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return true;
    }

    /**
     * Create a DTO from a validated request.
     *
     * @throws ValidationException
     */
    public static function fromRequest(Request $request): static
    {
        $dto = new static;
        $dto->validate($request->all());

        return $dto->fill($request->all());
    }

    /**
     * Magic method to allow array-like access to properties.
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        return null;
    }

    /**
     * Magic method to allow array-like setting of properties.
     *
     * @param  mixed  $value
     */
    public function __set(string $name, $value): void
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }
    }
}
