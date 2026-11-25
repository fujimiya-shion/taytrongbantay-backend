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
        return new static;
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

    /**
     * Operator mà applyTo() hiểu được. Dùng để phân biệt tuple ['>=', 18]
     * với danh sách giá trị ['1', '2', '3'] (cả hai đều là mảng list).
     */
    public const OPERATORS = [
        '=', '!=', '<>', '>', '>=', '<', '<=',
        'like', 'not like', 'ilike',
        'in', 'not in', 'nin',
        'between', 'not between',
        'null', 'not null',
    ];

    /**
     * - giá trị đơn:                          ['status' => 'active']        -> status = 'active'
     * - null:                                 ['deleted_at' => null]        -> deleted_at IS NULL
     * - tuple [operator, giá trị]:            ['age' => ['>=', 18]]         -> age >= 18
     * - mọi mảng khác là danh sách giá trị:   ['id' => ['1', '2', '3']]     -> id IN (...)
     *
     * Chỉ coi là tuple khi mảng có đúng 2 phần tử VÀ phần tử đầu là operator hợp lệ.
     * Nhờ vậy danh sách chuỗi (id tách từ query string "1,2,3", uuid, status...) luôn ra IN.
     */
    protected function parseArrayCondition(string $column, mixed $value): static
    {
        if (! is_array($value)) {
            $this->wheres[] = [$column, $value === null ? 'null' : '=', $value];

            return $this;
        }

        if ($this->isOperatorTuple($value)) {
            $this->wheres[] = [$column, strtolower(trim($value[0])), $value[1]];

            return $this;
        }

        $this->wheres[] = [$column, 'in', array_values($value)];

        return $this;
    }

    /**
     * @param  array<array-key, mixed>  $value
     */
    private function isOperatorTuple(array $value): bool
    {
        return array_is_list($value)
            && count($value) === 2
            && is_string($value[0])
            && in_array(strtolower(trim($value[0])), self::OPERATORS, true);
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

    public function cursor(?string $cursor, int $perPage = 15): static
    {
        $clone = clone $this;
        $clone->cursor = $cursor;
        $clone->perPage = max(1, min(100, $perPage));

        return $clone;
    }

    public function getCursor(): ?string
    {
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
        $clone->columns = ! empty($columns) ? $columns : ['*'];

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

        if (! empty($allowedSorts) && ! in_array($column, $allowedSorts, true)) {
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
        if (! empty($this->with)) {
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
                // Không phân biệt hoa thường, Laravel tự dịch đúng cho từng DB (ILIKE trên PostgreSQL).
                'ilike' => $query->whereLike($col, (string) $val, caseSensitive: false),
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
        return ! empty($this->orders);
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
