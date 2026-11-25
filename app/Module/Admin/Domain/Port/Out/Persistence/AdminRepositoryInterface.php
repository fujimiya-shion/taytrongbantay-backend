<?php

declare(strict_types=1);

namespace App\Module\Admin\Domain\Port\Out\Persistence;

use App\Core\Repository\ReadRepositoryInterface;
use App\Core\Repository\WriteRepositoryInterface;
use App\Module\Admin\Domain\Model\Admin;

/**
 * Method đọc/ghi chung lấy từ Read/WriteRepositoryInterface;
 * ở đây chỉ khai báo method riêng của module Admin.
 */
interface AdminRepositoryInterface extends ReadRepositoryInterface, WriteRepositoryInterface
{
    public function findByEmail(string $email): ?Admin;

    /**
     * Tạo mới nếu chưa có admin với email này, có rồi thì cập nhật.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function upsertByEmail(string $email, array $attributes): Admin;
}
