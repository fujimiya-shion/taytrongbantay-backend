<?php

declare(strict_types=1);

namespace App\Core\Repository;

use App\Core\Support\Criteria\QueryCriteria;
use Illuminate\Database\Eloquent\Builder;

/**
 * Hợp đồng của BasePersistenceRepository (cài đặt Eloquent).
 *
 * Chỉ dùng BÊN TRONG tầng Infrastructure. Interface Port/Out của module KHÔNG extends
 * interface này, mà extends ReadRepositoryInterface / WriteRepositoryInterface,
 * để query()/Builder không lộ ra ngoài và decorator (cache...) không phải cài query().
 */
interface BasePersistenceRepositoryInterface extends ReadRepositoryInterface, WriteRepositoryInterface
{
    public function query(?QueryCriteria $criteria = null): Builder;
}
