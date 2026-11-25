<?php

declare(strict_types=1);

namespace App\Core\DTO;

use App\Core\Support\Criteria\QueryCriteria;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Query params dùng chung cho màn hình danh sách:
 *   ?search=abc&status=active&ids=1,2,3&fromDate=2026-01-01&toDate=2026-01-31
 *
 * Cột tìm kiếm và cột ngày mặc định là `name` / `created_at`. Màn hình khác cột thì
 * tạo class con và override searchColumn() / dateColumn().
 */
class GenericQueryParams extends BaseQueryParams
{
    public ?string $search = null;

    public ?string $status = null;

    /** @var int[]|string[] */
    public array $ids = [];

    public ?string $fromDate = null;

    public ?string $toDate = null;

    protected function searchColumn(): string
    {
        return 'name';
    }

    protected function dateColumn(): string
    {
        return 'created_at';
    }

    /**
     * Map các field generic thành điều kiện truy vấn tương ứng
     */
    protected function applyCustomCriteria(QueryCriteria $criteria): QueryCriteria
    {
        if ($this->status !== null) {
            $criteria = $criteria->where('status', $this->status);
        }

        if ($this->ids !== []) {
            $criteria = $criteria->whereIn('id', $this->ids);
        }

        if ($this->search !== null && $this->search !== '') {
            // Không phân biệt hoa thường; escape % và _ để client không tự chèn wildcard.
            $criteria = $criteria->where($this->searchColumn(), 'ilike', '%'.self::escapeLike($this->search).'%');
        }

        if (($from = self::parseDate($this->fromDate, endOfDay: false)) !== null) {
            $criteria = $criteria->where($this->dateColumn(), '>=', $from);
        }

        if (($to = self::parseDate($this->toDate, endOfDay: true)) !== null) {
            $criteria = $criteria->where($this->dateColumn(), '<=', $to);
        }

        return $criteria;
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * "2026-01-31" (chỉ ngày) được hiểu là đầu ngày (fromDate) hoặc cuối ngày (toDate).
     * Ngày không hợp lệ thì bỏ qua filter thay vì lỗi 500.
     */
    private static function parseDate(?string $value, bool $endOfDay): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $date = CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            $date = $endOfDay ? $date->endOfDay() : $date->startOfDay();
        }

        return $date->format('Y-m-d H:i:s');
    }
}
