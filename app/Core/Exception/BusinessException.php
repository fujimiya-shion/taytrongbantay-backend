<?php

declare(strict_types=1);

namespace App\Core\Exception;

use RuntimeException;
use Throwable;

/**
 * Base cho mọi lỗi vi phạm quy tắc nghiệp vụ (đặt trong Domain/Exception của module).
 *
 *   final class EmailAlreadyExistsException extends BusinessException
 *   {
 *       public function __construct(string $email)
 *       {
 *           parent::__construct("Email [{$email}] đã tồn tại.", errors: ['email' => ['Email đã tồn tại.']]);
 *       }
 *
 *       public function status(): int
 *       {
 *           return 409;
 *       }
 *   }
 *
 * Exception chỉ mang dữ liệu (thông điệp, mã trạng thái, chi tiết lỗi), không biết gì về HTTP.
 * Việc đổi sang JSON response nằm ở một chỗ duy nhất: withExceptions() trong bootstrap/app.php,
 * cùng format với ApiBehavior::error().
 */
abstract class BusinessException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $errors  Chi tiết lỗi theo field, giống format lỗi validate
     */
    public function __construct(
        string $message,
        private readonly array $errors = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Mã trạng thái trả cho client. Mặc định 422 (yêu cầu hợp lệ về cú pháp nhưng vi phạm nghiệp vụ).
     * Override khi cần: 409 xung đột dữ liệu, 403 không đủ quyền theo nghiệp vụ, 404...
     */
    public function status(): int
    {
        return 422;
    }

    /**
     * @return array<string, mixed>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
