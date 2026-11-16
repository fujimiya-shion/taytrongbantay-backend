<?php

declare(strict_types=1);

namespace App\Shared\Outputs;

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Output cho các use case dạng list có phân trang.
 * Bọc kết quả của GenericRepository::paginate().
 *
 *   final readonly class GetProjectListOutput extends PaginationOutput {}
 *
 *   return new GetProjectListOutput($this->repository->paginate($params->toCriteria()));
 */
abstract readonly class PaginationOutput extends BaseOutput
{
    public function __construct(
        public LengthAwarePaginator $paginator,
    ) {}
}
