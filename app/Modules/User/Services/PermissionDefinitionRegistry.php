<?php

declare(strict_types=1);

namespace Modules\User\Services;

use InvalidArgumentException;
use LogicException;
use Modules\Core\Contracts\PermissionDefinitionRegistryInterface;

final class PermissionDefinitionRegistry implements PermissionDefinitionRegistryInterface
{
    /** @var array<string, array{module:string,description:string}> */
    private array $definitions = [];

    private bool $frozen = false;

    public function register(string $module, array $descriptions): void
    {
        if ($this->frozen) {
            throw new LogicException('Permission definitions cannot be registered after the catalogue is frozen.');
        }

        $module = strtolower(trim($module));
        if ($module === '' || preg_match('/^[a-z][a-z0-9_-]*$/', $module) !== 1) {
            throw new InvalidArgumentException('Permission module names must be canonical lowercase identifiers.');
        }

        foreach ($descriptions as $name => $description) {
            $name = is_string($name) ? trim($name) : '';
            $description = is_string($description) ? trim($description) : '';
            if (
                $name === ''
                || preg_match('/^[a-z][a-z0-9_-]*(?:\.[a-z0-9_-]+)+$/', $name) !== 1
                || $description === ''
            ) {
                throw new InvalidArgumentException("Permission definition [{$name}] is invalid.");
            }

            $existing = $this->definitions[$name] ?? null;
            if ($existing !== null && $existing !== ['module' => $module, 'description' => $description]) {
                throw new LogicException("Permission [{$name}] is registered by more than one owner or with conflicting metadata.");
            }

            $this->definitions[$name] = [
                'module' => $module,
                'description' => $description,
            ];
        }
    }

    public function all(): array
    {
        $this->frozen = true;
        ksort($this->definitions);

        return $this->definitions;
    }
}
