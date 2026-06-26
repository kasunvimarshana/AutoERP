<?php

declare(strict_types=1);

namespace Modules\User\Contracts;

interface PlatformOperatorAuthenticationDirectoryInterface
{
    /** @return array{id:int,first_name:string,last_name:?string,email:string,status:string,credentials_ready:bool}|null */
    public function findPlatformForLogin(string $email): ?array;

    /** @return array{id:int,first_name:string,last_name:?string,email:string,status:string,credentials_ready:bool}|null */
    public function findActivePlatformById(int $operatorId): ?array;

    /** @param list<int> $operatorIds @return array<int,array{id:int,first_name:string,last_name:?string,email:string,status:string,credentials_ready:bool}> */
    public function summariesByIds(array $operatorIds): array;

    /** @return list<string> */
    public function permissionNames(int $operatorId): array;
}
