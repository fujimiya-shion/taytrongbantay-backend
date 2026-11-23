<?php

declare(strict_types=1);

namespace App\Core\Repositories;

use App\Core\Support\Criteria\QueryCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BasePersistenceRepositoryInterface
{
    public function query(?QueryCriteria $criteria = null): Builder;

    /**
     * @return Collection<int, Model>
     */
    public function get(?QueryCriteria $criteria = null): Collection;

    public function paginate(?QueryCriteria $criteria = null): LengthAwarePaginator;

    public function findById(int $id, ?QueryCriteria $criteria = null): ?Model;

    public function first(?QueryCriteria $criteria = null): ?Model;

    public function count(?QueryCriteria $criteria = null): int;

    /**
     * @return Collection<int, Model>
     */
    public function autoComplete(
        string $term,
        string $column = 'name',
        ?QueryCriteria $criteria = null
    ): Collection;

    public function create(array $data): Model;

    public function createOrUpdate(?QueryCriteria $criteria = null, array $data): Collection|Model;

    public function update(int $id, array $data): Model;

    public function delete(int $id, bool $force = false): bool;

    public function max(string $column, ?QueryCriteria $criteria = null): int|float|null;
}