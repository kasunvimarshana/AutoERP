<?php

declare(strict_types=1);

namespace Modules\Core\Application\Pipes;

use Closure;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

final class ValidateCommandPipe
{
    /**
     * Validate a command-like object that exposes a `rules()` method.
     */
    public function handle(mixed $payload, Closure $next): mixed
    {
        if (method_exists($payload, 'rules') && method_exists($payload, 'toArray')) {
            $validator = Validator::make($payload->toArray(), $payload->rules());

            if ($validator->fails()) {
                throw new InvalidArgumentException(
                    'Validation failed: '.implode(', ', $validator->errors()->all())
                );
            }
        }

        return $next($payload);
    }
}
