<?php

declare(strict_types=1);

namespace App\Module\Admin\Infrastructure\Persistence\Implementation;

use App\Core\Repository\BasePersistenceRepository;
use App\Core\Support\Criteria\QueryCriteria;
use App\Module\Admin\Domain\Model\Admin;
use App\Module\Admin\Domain\Port\Out\Persistence\AdminRepositoryInterface;
use UnexpectedValueException;

class AdminRepository extends BasePersistenceRepository implements AdminRepositoryInterface
{
    public function __construct(Admin $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?Admin
    {
        return $this->asAdmin($this->first(QueryCriteria::make()->where('email', $email)));
    }

    public function upsertByEmail(string $email, array $attributes): Admin
    {
        $attributes = ['email' => $email] + $attributes;
        $existing = $this->findByEmail($email);

        $admin = $existing === null
            ? $this->create($attributes)
            : $this->update((int) $existing->getKey(), $attributes);

        return $this->asAdmin($admin)
            ?? throw new UnexpectedValueException("Cannot upsert admin [{$email}]");
    }

    /**
     * Base trả ?Model; thu hẹp về ?Admin để nơi gọi có kiểu chính xác.
     */
    private function asAdmin(mixed $model): ?Admin
    {
        return $model instanceof Admin ? $model : null;
    }
}
