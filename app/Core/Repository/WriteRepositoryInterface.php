<?php

declare(strict_types=1);

namespace App\Core\Repository;

use App\Core\Support\Criteria\QueryCriteria;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Khả năng GHI dùng chung cho repository của mọi module.
 */
interface WriteRepositoryInterface
{
    public function create(array $data): Model;

    public function createOrUpdate(?QueryCriteria $criteria, array $data): Collection|Model;

    public function update(int $id, array $data): Model;

    public function delete(int $id, bool $force = false): bool;
}
