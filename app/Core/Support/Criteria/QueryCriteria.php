<?php

declare(strict_types=1);

namespace App\Core\Support\Criteria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class QueryCriteria
{
    /** @var array<int, array{0: string, 1: string, 2: mixed}> */
    protected array $wheres = [];

    /** @var string[] */
    protected array $with = [];

    /** @var string[] */
    protected array $columns = ['*'];

    /** @var array<int, array{0: string, 1: string}> */
    protected array $orders = [];

    protected ?int $page = null;
    protected int $perPage = 15;
    protected ?string $cursor = null;
    protected ?int $limit = null;

    public function __construct() {}

    public static function make(): static
    {
        return new static();
    }

    /* =========================
     |  Fluent Filtering (Copy-on-Write)
     ========================= */

    /**
     * Hỗ trợ 3 kiểu truyền:
     * 1. where('status', 'active') -> operator mặc định '='
     * 2. where('price', '>=', 1000) -> truyền rõ operator
     * 3. where(['status' => 'active', 'age' => ['>=', 18]]) -> truyền mảng điều kiện
     *
     * @param  string|array<string, mixed>  $column
     */
    public function where(string|array $column, mixed $operator = null, mixed $value = null): static
    {
        $clone = clone $this;

        if (is_array($column)) {
            foreach ($column as $col => $val) {
                $clone = $clone->parseArrayCondition((string) $col, $val);
            }

            return $clone;
        }

        // Nếu chỉ truyền 2 tham số: where('status', 'active')
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $clone->wheres[] = [$column, strtolower((string) $operator), $value];

        return $clone;
    }

    protected function parseArrayCondition(string $column, mixed $value): static
    {
        if (!is_array($value)) {
            $this->wheres[] = [$column, $value === null ? 'null' : '=', $value];

            return $this;
        }

        // Nếu là mảng thuần list giá trị: ['id' => [1, 2, 3]] -> tự hiểu là 'in'
        if (array_is_list($value) && !isset($value[0]) || (isset($value[0]) && !is_string($value[0]))) {
            $this->wheres[] = [$column, 'in', $value];

            return $this;
        }

        // Dạng tuple ['>=', 18] hoặc ['between', [1, 10]]
        $op = strtolower((string) ($value[0] ?? '='));
        $val = $value[1] ?? null;
        $this->wheres[] = [$column, $op, $val];

        return $this;
    }

    /** @param mixed[] $values */
    public function whereIn(string $column, array $values): static
    {
        return $this->where($column, 'in', $values);
    }

    /** @param array{0: mixed, 1: mixed} $range */
    public function whereBetween(string $column, array $range): static
    {
        return $this->where($column, 'between', $range);
    }

    public function whereNull(string $column): static
    {
        return $this->where($column, 'null', null);
    }

    public function whereNotNull(string $column): static
    {
        return $this->where($column, 'not null', null);
    }

    public function cursor(?string $cursor, int $perPage = 15): static {
        $clone = clone $this;
        $clone->cursor = $cursor;
        $clone->perPage = max(1, min(100, $perPage));

        return $clone;
    }

    public function getCursor(): ?string {
        return $this->cursor;
    }

    /* =========================
     |  Relations, Select, Pagination & Sort
     ========================= */

    /** @param string[] $relations */
    public function with(array $relations): static
    {
        $clone = clone $this;
        $clean = array_filter(array_map('trim', array_filter($relations, 'is_string')));
        $clone->with = array_values(array_unique(array_merge($this->with, $clean)));

        return $clone;
    }

    /** @param string[] $columns */
    public function select(array $columns): static
    {
        $clone = clone $this;
        $clone->columns = !empty($columns) ? $columns : ['*'];

        return $clone;
    }

    public function paginate(int $page = 1, int $perPage = 15): static
    {
        $clone = clone $this;
        $clone->page = max(1, $page);
        $clone->perPage = max(1, min(100, $perPage));

        return $clone;
    }

    public function perPage(int $perPage): static
    {
        $clone = clone $this;
        $clone->perPage = max(1, min(100, $perPage));

        return $clone;
    }

    public function limit(int $limit): static
    {
        $clone = clone $this;
        $clone->limit = max(1, $limit);

        return $clone;
    }

    /** @param string[] $allowedSorts */
    public function orderBy(string $column, string $direction = 'desc', array $allowedSorts = []): static
    {
        $column = trim($column);
        if ($column === '') {
            return $this;
        }

        if (!empty($allowedSorts) && !in_array($column, $allowedSorts, true)) {
            return $this;
        }

        $clone = clone $this;
        $dir = strtolower(trim($direction)) === 'asc' ? 'asc' : 'desc';
        $clone->orders[] = [$column, $dir];

        return $clone;
    }

    /* =========================
     |  Apply to Eloquent Builder
     ========================= */

    public function applyTo(Builder $query, ?Model $model = null): Builder
    {
        if (!empty($this->with)) {
            $query->with($this->with);
        }

        foreach ($this->wheres as [$column, $op, $val]) {
            $col = $model ? $model->qualifyColumn($column) : $column;

            match ($op) {
                'in' => $query->whereIn($col, is_array($val) ? $val : [$val]),
                'not in', 'nin' => $query->whereNotIn($col, is_array($val) ? $val : [$val]),
                'between' => $query->whereBetween($col, is_array($val) ? $val : []),
                'not between' => $query->whereNotBetween($col, is_array($val) ? $val : []),
                'null' => $query->whereNull($col),
                'not null' => $query->whereNotNull($col),
                'like', 'not like', '=', '!=', '<>', '>', '>=', '<', '<=' => $query->where($col, $op, $val),
                default => $query->where($col, '=', $val),
            };
        }

        foreach ($this->orders as [$column, $direction]) {
            $col = $model ? $model->qualifyColumn($column) : $column;
            $query->orderBy($col, $direction);
        }

        if ($this->limit !== null) {
            $query->limit($this->limit);
        }

        return $query->select($this->columns);
    }

    /* =========================
     |  Getters
     ========================= */

    public function hasOrders(): bool
    {
        return !empty($this->orders);
    }

    public function getPage(): int
    {
        return $this->page ?? 1;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /** @return string[] */
    public function getColumns(): array
    {
        return $this->columns;
    }
}