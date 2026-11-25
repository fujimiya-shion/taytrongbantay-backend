<?php

declare(strict_types=1);

namespace App\Core\DTO;

use App\Core\Support\Criteria\QueryCriteria;
use BackedEnum;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

abstract class BaseQueryParams
{
    public ?int $page = null;
    public int $perPage = 15;
    public ?string $cursor = null;
    public ?string $orderBy = null;
    public string $orderDirection = 'desc';

    /** @var string[] */
    public array $with = [];

    /**
     * Khởi tạo từ HTTP Request và tự động map vào các typed properties
     */
    public static function fromRequest(Request $request): static
    {
        return static::fromArray($request->query());
    }

    /**
     * Khởi tạo từ mảng dữ liệu bất kỳ (hữu ích khi gọi nội bộ giữa các Service)
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static
    {
        $instance = new static();
        $reflection = new ReflectionClass($instance);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();

            if (!array_key_exists($name, $data) || $data[$name] === null || $data[$name] === '') {
                continue;
            }

            $instance->{$name} = static::castValue($property, $data[$name]);
        }

        return $instance;
    }

    /**
     * Tự động ép kiểu dựa trên khai báo type của property (hỗ trợ cả PHP 8.1+ BackedEnum)
     */
    protected static function castValue(ReflectionProperty $property, mixed $value): mixed
    {
        $type = $property->getType();

        if (!$type instanceof ReflectionNamedType) {
            return $value;
        }

        $typeName = $type->getName();

        // Hỗ trợ tự động parse BackedEnum (VD: public ?OrderStatus $status = null)
        if (enum_exists($typeName) && is_subclass_of($typeName, BackedEnum::class)) {
            return $typeName::tryFrom($value);
        }

        return match ($typeName) {
            'int' => is_numeric($value) ? (int) $value : null,
            'float' => is_numeric($value) ? (float) $value : null,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'string' => trim((string) $value),
            'array' => is_string($value)
                ? array_values(array_filter(array_map('trim', explode(',', $value))))
                : (array) $value,
            default => $value,
        };
    }

    /**
     * Lấy danh sách các property filter của class con (loại trừ các field phân trang/sort gốc)
     *
     * @return array<string, mixed>
     */
    public function getFilterValues(): array
    {
        $baseProps = ['page', 'perPage', 'orderBy', 'orderDirection', 'with'];
        $filters = [];

        foreach ((new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $name = $prop->getName();

            if (in_array($name, $baseProps, true) || !$prop->isInitialized($this)) {
                continue;
            }

            $val = $this->{$name};
            if ($val === null || $val === [] || $val === '') {
                continue;
            }

            $filters[$name] = $val instanceof BackedEnum ? $val->value : $val;
        }

        return $filters;
    }

    /**
     * Chuyển đổi trực tiếp sang QueryCriteria cho tầng Repository
     *
     * @param  string[]  $allowedSorts
     */
    public function toCriteria(array $allowedSorts = []): QueryCriteria
    {
        $criteria = QueryCriteria::make();

        $criteria = $this->page !== null
            ? $criteria->paginate($this->page, $this->perPage)
            : $criteria->perPage($this->perPage);

        if (!empty($this->with)) {
            $criteria = $criteria->with($this->with);
        }

        if ($this->orderBy !== null) {
            $criteria = $criteria->orderBy($this->orderBy, $this->orderDirection, $allowedSorts);
        }

        if ($this->cursor != null) {
            $criteria = $criteria->cursor($this->cursor);
        }

        return $this->applyCustomCriteria($criteria);
    }

    /**
     * Hook method: Class con có thể override để xử lý các filter đặc thù (LIKE, date range...)
     */
    protected function applyCustomCriteria(QueryCriteria $criteria): QueryCriteria
    {
        return $criteria->where($this->getFilterValues());
    }
}