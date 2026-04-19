<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class AuthorizedController extends Controller
{
    use AuthorizesRequests {
        authorize as protected laravelAuthorize;
    }

    public function authorize(string $ability, mixed $arguments = []): mixed
    {
        // return $this->laravelAuthorize($ability, $arguments);
        $user = auth()->guard()->user();

        if (! $user instanceof Authenticatable) {
            throw new AuthenticationException;
        }

        $subject = $this->resolveAuthorizationSubject($arguments);
        $authorizationService = app()->bound('auth.authorization')
            ? app('auth.authorization')
            : null;

        if ($authorizationService === null) {
            return $this->laravelAuthorize($ability, $arguments);
        }

        foreach ($this->authorizationAbilities((string) $ability, $subject) as $candidateAbility) {
            if ($authorizationService->can((int) $user->getAuthIdentifier(), $candidateAbility, $subject)) {
                return true;
            }
        }

        try {
            return $this->laravelAuthorize($ability, $arguments);
        } catch (\Throwable) {
            throw new AuthorizationException('This action is unauthorized.');
        }
    }

    private function resolveAuthorizationSubject(mixed $arguments): mixed
    {
        if (! is_array($arguments)) {
            return $arguments;
        }

        return match (count($arguments)) {
            0 => null,
            1 => $arguments[0],
            default => $arguments,
        };
    }

    /**
     * Build RBAC candidate permissions from controller-style abilities.
     */
    private function authorizationAbilities(string $ability, mixed $subject): array
    {
        $candidates = [$ability];
        $resource = $this->resolveResourceName($subject);

        if ($resource === null) {
            return $this->uniqueAbilities($candidates);
        }

        $mappedAbility = $this->mapControllerAbility($ability);
        if ($mappedAbility !== null) {
            $candidates[] = $resource.'.'.$mappedAbility;
        }

        foreach ($this->specialAbilityCandidates($resource, $ability) as $candidate) {
            $candidates[] = $candidate;
        }

        return $this->uniqueAbilities($candidates);
    }

    private function resolveResourceName(mixed $subject): ?string
    {
        $className = match (true) {
            is_object($subject) => $subject::class,
            is_string($subject) && class_exists($subject) => $subject,
            default => null,
        };

        if ($className === null) {
            return null;
        }

        return Str::plural(Str::snake(class_basename($className)));
    }

    private function mapControllerAbility(string $ability): ?string
    {
        return match ($ability) {
            'viewAny', 'view' => 'view',
            'create' => 'create',
            'update' => 'update',
            'delete' => 'delete',
            'move' => 'move',
            default => null,
        };
    }

    private function specialAbilityCandidates(string $resource, string $ability): array
    {
        $attachmentResource = Str::replaceLast('s', '_attachments', Str::singular($resource));

        return match ($ability) {
            'viewAttachments' => [
                $resource.'.view_attachments',
            ],
            'uploadAttachment' => [
                $resource.'.upload_attachment',
            ],
            'deleteAttachment' => [
                $resource.'.delete_attachment',
            ],
            'updateConfig' => [
                $resource.'.update_config',
            ],
            'assignRole' => [
                $resource.'.assign_role',
            ],
            'updatePreferences' => [
                $resource.'.update_preferences',
            ],
            'syncPermissions' => [
                $resource.'.sync_permissions',
            ],
            default => [],
        };
    }

    private function uniqueAbilities(array $abilities): array
    {
        return array_values(array_unique(array_filter($abilities, static fn (mixed $ability): bool => is_string($ability) && $ability !== '')));
    }
}
