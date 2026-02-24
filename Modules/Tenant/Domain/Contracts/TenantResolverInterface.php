<?php
namespace Modules\Tenant\Domain\Contracts;
use Illuminate\Http\Request;
interface TenantResolverInterface
{
    public function resolve(Request $request): ?object;
}
