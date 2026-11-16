<?php

declare(strict_types=1);

namespace App\Shared\Commands;

use App\Core\Support\Criteria\QueryCriteria;

/**
 * Command cho use case dạng đọc (list, detail, search...).
 *
 *   final readonly class GetUserListCommand extends QueryCommand {}
 *
 *   // Cần thêm tham số ngoài điều kiện truy vấn:
 *   final readonly class GetMyOrdersCommand extends QueryCommand
 *   {
 *       public function __construct(QueryCriteria $criteria, public int $userId)
 *       {
 *           parent::__construct($criteria);
 *       }
 *   }
 */
abstract readonly class QueryCommand extends BaseCommand
{
    public function __construct(
        public QueryCriteria $criteria = new QueryCriteria,
    ) {}
}
