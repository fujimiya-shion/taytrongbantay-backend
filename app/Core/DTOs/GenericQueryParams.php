<?php
declare(strict_types=1);

namespace App\Core\DTOs;

use App\Core\Support\Criteria\QueryCriteria;

class GenericQueryParams extends BaseQueryParams {
    public ?string $search = null;
    public ?string $status = null;

    /** @var int[]|string[] */
    public array $ids = [];

    public ?string $fromDate = null;
    public ?string $toDate = null;

    /**
     * Map các field generic thành điều kiện truy vấn tương ứng
     */
    protected function applyCustomCriteria(QueryCriteria $criteria): QueryCriteria
    {
        $filters = [];

        if ($this->status !== null) {
            $filters['status'] = $this->status;
        }

        if (!empty($this->ids)) {
            $filters['id'] = $this->ids;
        }

        // Các filter đặc thù như search (LIKE) hay fromDate/toDate (BETWEEN)
        // có thể truyền qua callback/scope của QueryCriteria
        return $criteria->where($filters);
    }
}