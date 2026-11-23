<?php

declare(strict_types=1);

namespace App\Modules\Admin\Infrastructure\Persistence\Implementations;

use App\Core\Repositories\BasePersistenceRepository;
use App\Modules\Admin\Domain\Model\Admin;
use App\Modules\Admin\Domain\Port\Out\Persistence\AdminRepositoryInterface;

class AdminRepository extends BasePersistenceRepository implements AdminRepositoryInterface {
    public function __construct(Admin $model) {
        parent::__construct($model);
    }
}