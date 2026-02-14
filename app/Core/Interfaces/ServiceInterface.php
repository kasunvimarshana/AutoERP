<?php

namespace App\Core\Interfaces;

interface ServiceInterface
{
    public function create(array $data);

    public function update($id, array $data);

    public function delete($id): bool;

    public function find($id);

    public function all();

    public function paginate(int $perPage = 15);
}
