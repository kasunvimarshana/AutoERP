<?php
namespace Modules\Configuration\Application\Contracts;

interface OrgUnitTreeServiceInterface
{
    public function insertNode(int $nodeId, ?int $parentId): void;
    public function deleteNode(int $nodeId): void;
    public function getDescendants(int $ancestorId, bool $includeSelf = false): array;
    public function getAncestors(int $descendantId, bool $includeSelf = false): array;
    public function getChildren(int $parentId): array;
    public function buildTree(int $tenantId): array;
}
