<?php

declare(strict_types=1);

namespace App\Core\Repository;

use App\Core\Support\Criteria\QueryCriteria;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Khả năng ĐỌC dùng chung cho repository của mọi module.
 *
 * Không có query()/Builder: nơi gọi không được tự dựng query, để mọi lần đọc
 * đều đi qua repository (và qua decorator như cache nếu có).
 */
interface ReadRepositoryInterface
{
    public function findById(int $id, ?QueryCriteria $criteria = null): ?Model;

    public function first(?QueryCriteria $criteria = null): ?Model;

    /**
     * @return Collection<int, Model>
     */
    public function get(?QueryCriteria $criteria = null): Collection;

    public function paginate(?QueryCriteria $criteria = null): LengthAwarePaginator;

    public function count(?QueryCriteria $criteria = null): int;

    public function max(string $column, ?QueryCriteria $criteria = null): int|float|null;

    /**
     * @return Collection<int, Model>
     */
    public function autoComplete(
        string $term,
        string $column = 'name',
        ?QueryCriteria $criteria = null
    ): Collection;
}
