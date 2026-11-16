<?php

declare(strict_types=1);

namespace App\Shared\UseCases;

/**
 * Marker interface cho mọi Port/In (tương đương shared/usecases/UseCase<I, O> bên Java).
 *
 * PHP không có generics và không cho phép thu hẹp kiểu tham số khi implement,
 * nên execute() được khai báo ở từng Port/In với kiểu cụ thể:
 *
 *   interface ICreateUserUseCase extends UseCase
 *   {
 *       public function execute(CreateUserCommand $command): CreateUserOutput;
 *   }
 */
interface UseCase {}
