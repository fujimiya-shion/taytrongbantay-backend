<?php

declare(strict_types=1);

namespace App\Core\Repository;

use App\Core\Support\Criteria\QueryCriteria;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base cho decorator của repository (cache, log, đo thời gian...).
 *
 * Mặc định mọi method đọc/ghi chung đều chuyển thẳng xuống $inner. Decorator con
 * chỉ override method cần thêm logic, và tự cài các method riêng của module.
 *
 *   final class CachedAdminRepository extends RepositoryDecorator implements AdminRepositoryInterface
 *   {
 *       public function __construct(private readonly AdminRepositoryInterface $admins)
 *       {
 *           parent::__construct($admins);
 *       }
 *
 *       public function findByEmail(string $email): ?Admin
 *       {
 *           return Cache::remember("admin:email:{$email}", 300, fn () => $this->admins->findByEmail($email));
 *       }
 *   }
 *
 * Giữ thêm tham chiếu có kiểu cụ thể ($admins) để gọi method riêng của module mà IDE vẫn hiểu kiểu.
 */
abstract class RepositoryDecorator implements ReadRepositoryInterface, WriteRepositoryInterface
{
    public function __construct(
        protected readonly ReadRepositoryInterface&WriteRepositoryInterface $inner,
    ) {}

    /* =========================
     |  Read
     ========================= */

    public function findById(int $id, ?QueryCriteria $criteria = null): ?Model
    {
        return $this->inner->findById($id, $criteria);
    }

    public function first(?QueryCriteria $criteria = null): ?Model
    {
        return $this->inner->first($criteria);
    }

    public function get(?QueryCriteria $criteria = null): Collection
    {
        return $this->inner->get($criteria);
    }

    public function paginate(?QueryCriteria $criteria = null): LengthAwarePaginator
    {
        return $this->inner->paginate($criteria);
    }

    public function cursorPaginate(?QueryCriteria $criteria = null): CursorPaginator
    {
        return $this->inner->cursorPaginate($criteria);
    }

    public function count(?QueryCriteria $criteria = null): int
    {
        return $this->inner->count($criteria);
    }

    public function max(string $column, ?QueryCriteria $criteria = null): int|float|null
    {
        return $this->inner->max($column, $criteria);
    }

    public function autoComplete(
        string $term,
        string $column = 'name',
        ?QueryCriteria $criteria = null
    ): Collection {
        return $this->inner->autoComplete($term, $column, $criteria);
    }

    /* =========================
     |  Write
     ========================= */

    public function create(array $data): Model
    {
        return $this->inner->create($data);
    }

    public function createOrUpdate(?QueryCriteria $criteria, array $data): Collection|Model
    {
        return $this->inner->createOrUpdate($criteria, $data);
    }

    public function update(int $id, array $data): Model
    {
        return $this->inner->update($id, $data);
    }

    public function delete(int $id, bool $force = false): bool
    {
        return $this->inner->delete($id, $force);
    }
}
