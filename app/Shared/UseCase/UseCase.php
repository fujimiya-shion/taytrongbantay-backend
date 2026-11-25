<?php

declare(strict_types=1);

namespace App\Shared\UseCase;

/**
 * Marker interface cho mọi use case.
 *
 * PHP không có generics và không cho phép thu hẹp kiểu tham số khi implement,
 * nên execute() được khai báo trực tiếp trong từng use case với kiểu cụ thể:
 *
 *   final class CreateUserUseCase implements UseCase
 *   {
 *       public function execute(CreateUserCommand $command): CreateUserOutput { ... }
 *   }
 */
interface UseCase {}
