<?php

declare(strict_types=1);

namespace App\Core\Repositories;

use App\Core\Support\Criteria\QueryCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

abstract class GenericRepository implements BaseRepositoryInterface
{
    public function __construct(
        protected Model $model
    ) {}

    /* =========================
     |  Query APIs
     ========================= */

    public function query(?QueryCriteria $criteria = null): Builder
    {
        $query = $this->model->newQuery();

        if ($criteria !== null) {
            $query = $criteria->applyTo($query, $this->model);
        }

        return $query;
    }

    public function get(?QueryCriteria $criteria = null): Collection
    {
        return $this->query($criteria)->get();
    }

    public function paginate(?QueryCriteria $criteria = null): LengthAwarePaginator
    {
        $criteria ??= QueryCriteria::make();

        return $this->query($criteria)->paginate(
            perPage: $criteria->getPerPage(),
            columns: $criteria->getColumns(),
            page: $criteria->getPage(),
        );
    }

    public function findById(int $id, ?QueryCriteria $criteria = null): ?Model
    {
        return $this->query($criteria)->find($id);
    }

    public function first(?QueryCriteria $criteria = null): ?Model
    {
        return $this->query($criteria)->first();
    }

    public function count(?QueryCriteria $criteria = null): int
    {
        return $this->query($criteria)->count();
    }

    public function max(string $column, ?QueryCriteria $criteria = null): int|float|null
    {
        return $this->query($criteria)->max($this->model->qualifyColumn($column));
    }

    /* =========================
     |  Write APIs
     ========================= */

    public function create(array $data): Model
    {
        try {
            DB::beginTransaction();
            $created = $this->model->newQuery()->create($data);
            DB::commit();

            return $created;
        } catch (Throwable $e) {
            DB::rollBack();
            logger()->error('An error occurred when creating new ' . class_basename($this->model) . ' record', [
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    public function update(int $id, array $data): Model
    {
        try {
            DB::beginTransaction();

            /** @var Model $record */
            $record = $this->model->newQuery()->findOrFail($id);
            $record->fill($data);
            $record->save();

            DB::commit();

            return $record->refresh();
        } catch (Throwable $e) {
            DB::rollBack();
            logger()->error('An error occurred when updating ' . class_basename($this->model) . " record #{$id}", [
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    public function delete(int $id, bool $force = false): bool
    {
        $query = $this->model->newQuery()->where($this->model->getKeyName(), $id);

        try {
            $rowAffected = $force ? $query->forceDelete() : $query->delete();

            return is_int($rowAffected) && $rowAffected > 0;
        } catch (Throwable $e) {
            logger()->error('An error occurred when deleting model: ' . class_basename($this->model), [
                'id' => $id,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /* =========================
     |  AutoComplete (Index-friendly Prefix Match, NO LIKE)
     ========================= */

    protected function nextPrefix(string $prefix): ?string
    {
        $prefix = trim($prefix);
        if ($prefix === '') {
            return null;
        }

        $chars = str_split($prefix);
        for ($i = count($chars) - 1; $i >= 0; $i--) {
            $o = ord($chars[$i]);
            if ($o < 255) {
                $chars[$i] = chr($o + 1);

                return implode('', array_slice($chars, 0, $i + 1));
            }
        }

        return null;
    }

    protected function applyPrefixMatch(Builder $query, string $column, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $qualifiedCol = $this->model->qualifyColumn($column);
        $upper = $this->nextPrefix($term);

        $query->where($qualifiedCol, '>=', $term);

        if ($upper !== null) {
            $query->where($qualifiedCol, '<', $upper);
        }

        return $query;
    }

    public function autoComplete(
        string $term,
        string $column = 'name',
        ?QueryCriteria $criteria = null
    ): Collection {
        $term = trim($term);
        if ($term === '') {
            return new Collection();
        }

        $criteria ??= QueryCriteria::make();
        $query = $this->query($criteria);

        $this->applyPrefixMatch($query, $column, $term);

        return $query
            ->orderBy($this->model->qualifyColumn($column))
            ->limit($criteria->getPerPage())
            ->get();
    }
}