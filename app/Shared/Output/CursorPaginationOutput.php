<?php

declare(strict_types=1);

namespace App\Shared\Output;

use Illuminate\Contracts\Pagination\CursorPaginator;

/**
 * Output cho use case dạng list phân trang theo cursor.
 * Bọc kết quả của <Model>RepositoryInterface::cursorPaginate().
 *
 *   final readonly class GetPostFeedOutput extends CursorPaginationOutput {}
 *
 *   return new GetPostFeedOutput($this->posts->cursorPaginate($command->criteria));
 */
abstract readonly class CursorPaginationOutput extends BaseOutput
{
    public function __construct(
        public CursorPaginator $paginator,
    ) {}
}
