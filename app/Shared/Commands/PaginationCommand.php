<?php

declare(strict_types=1);

namespace App\Shared\Commands;

/**
 * Command cho các use case dạng list có phân trang.
 *
 *   final readonly class GetProjectListCommand extends PaginationCommand
 *   {
 *       public function __construct(int $page = 1, int $perPage = 15, public ?string $search = null)
 *       {
 *           parent::__construct($page, $perPage);
 *       }
 *   }
 */
abstract readonly class PaginationCommand extends BaseCommand
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 15,
    ) {}
}
