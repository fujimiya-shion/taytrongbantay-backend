<?php

declare(strict_types=1);

namespace App\Module\Admin\Inbound\Rest\V1\Controller;

use App\Core\Http\Controller\ApiController;

/**
 * Chỉ: validate (FormRequest) -> toCommand() -> gọi UseCase -> trả response.
 * Không chứa business logic, không query Eloquent trực tiếp.
 *
 *   public function store(CreateXxxRequest $request, CreateXxxUseCase $useCase): JsonResponse
 *   {
 *       return $this->success($useCase->execute($request->toCommand()), 201);
 *   }
 */
class AdminController extends ApiController
{
    //
}
