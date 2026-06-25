<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Constants;

final class OrganizationUnitPermission
{
    public const VIEW = 'organization-units.view';
    public const CREATE = 'organization-units.create';
    public const UPDATE = 'organization-units.update';
    public const ACTIVATE = 'organization-units.activate';
    public const DEACTIVATE = 'organization-units.deactivate';
    public const RETIRE = 'organization-units.retire';
    public const TYPES_VIEW = 'organization-unit-types.view';
    public const TYPES_MANAGE = 'organization-unit-types.manage';
    public const DOCUMENTS_VIEW = 'organization-unit-documents.view';
    public const DOCUMENTS_MANAGE = 'organization-unit-documents.manage';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::VIEW => 'View the organization hierarchy and accessible organization-unit details.',
            self::CREATE => 'Create organization units below an active parent.',
            self::UPDATE => 'Update organization-unit details and hierarchy placement.',
            self::ACTIVATE => 'Activate an eligible organization unit.',
            self::DEACTIVATE => 'Deactivate an organization unit after resolving lifecycle blockers.',
            self::RETIRE => 'Permanently retire an organization unit while retaining historical references.',
            self::TYPES_VIEW => 'View organization-unit type definitions.',
            self::TYPES_MANAGE => 'Create and maintain organization-unit type definitions.',
            self::DOCUMENTS_VIEW => 'View organization-unit document metadata and downloads.',
            self::DOCUMENTS_MANAGE => 'Upload, replace, and archive organization-unit documents.',
        ];
    }

    private function __construct() {}
}
