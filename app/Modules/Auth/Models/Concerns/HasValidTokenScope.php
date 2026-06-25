<?php

declare(strict_types=1);

namespace Modules\Auth\Models\Concerns;

use LogicException;
use Modules\Auth\Constants\AuthTokenScope;

trait HasValidTokenScope
{
    protected static function bootHasValidTokenScope(): void
    {
        static::saving(static function (self $model): void {
            $scope = AuthTokenScope::normalize((string) $model->getAttribute('token_scope'));
            $model->setAttribute('token_scope', $scope);

            if (! is_numeric($model->getAttribute('user_id')) || (int) $model->getAttribute('user_id') < 1) {
                throw new LogicException('Authentication tokens require a valid user identity.');
            }

            if ($scope === AuthTokenScope::TENANT) {
                if (! is_numeric($model->getAttribute('tenant_id')) || (int) $model->getAttribute('tenant_id') < 1) {
                    throw new LogicException('Tenant authentication tokens require tenant ownership.');
                }

                return;
            }

            foreach (['tenant_id', 'organization_unit_id', 'provider_id', 'client_id', 'identity_id', 'session_id'] as $attribute) {
                if ($model->getAttribute($attribute) !== null) {
                    throw new LogicException(sprintf(
                        'Platform authentication tokens cannot contain tenant-scoped attribute [%s].',
                        $attribute,
                    ));
                }
            }
        });

        static::updating(static function (self $model): void {
            foreach (['tenant_id', 'user_id', 'token_scope', 'token_key', 'refresh_key'] as $attribute) {
                if ($model->isDirty($attribute)) {
                    throw new LogicException(sprintf(
                        'Authentication token identity attribute [%s] is immutable.',
                        $attribute,
                    ));
                }
            }
        });
    }
}
