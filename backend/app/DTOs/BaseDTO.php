<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Base Data Transfer Object
 *
 * Provides validation, type safety, and transformation for data transfer.
 * All DTOs should extend this class to ensure consistent behavior.
 */
abstract class BaseDTO implements Arrayable
{
    /**
     * Create DTO from array
     *
     * @throws ValidationException
     */
    public static function from(array $data): static
    {
        $instance = new static;
        $instance->validate($data);
        $instance->populate($data);

        return $instance;
    }

    /**
     * Create DTO from request
     */
    public static function fromRequest(\Illuminate\Http\Request $request): static
    {
        return static::from($request->all());
    }

    /**
     * Validate input data against rules
     *
     * @throws ValidationException
     */
    protected function validate(array $data): void
    {
        $rules = $this->rules();

        if (empty($rules)) {
            return;
        }

        $validator = Validator::make($data, $rules, $this->messages());

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Populate DTO properties from validated data
     */
    protected function populate(array $data): void
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $this->cast($key, $value);
            }
        }
    }

    /**
     * Cast value to property type
     */
    protected function cast(string $property, mixed $value): mixed
    {
        $casts = $this->casts();

        if (! isset($casts[$property])) {
            return $value;
        }

        $type = $casts[$property];

        return match ($type) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'string' => (string) $value,
            'array' => (array) $value,
            'object' => (object) $value,
            'datetime' => $value instanceof \DateTime ? $value : new \DateTime($value),
            default => $value,
        };
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        $data = [];

        foreach (get_object_vars($this) as $property => $value) {
            if ($value instanceof Arrayable) {
                $data[$property] = $value->toArray();
            } elseif ($value instanceof \DateTime) {
                $data[$property] = $value->format('Y-m-d H:i:s');
            } elseif (is_array($value)) {
                $data[$property] = array_map(function ($item) {
                    return $item instanceof Arrayable ? $item->toArray() : $item;
                }, $value);
            } else {
                $data[$property] = $value;
            }
        }

        return $data;
    }

    /**
     * Convert DTO to JSON
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Define validation rules
     * Override in child classes
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * Define validation messages
     * Override in child classes
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * Define property type casts
     * Override in child classes
     */
    protected function casts(): array
    {
        return [];
    }
}
