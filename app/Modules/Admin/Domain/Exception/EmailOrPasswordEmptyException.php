<?php

declare(strict_types=1);

namespace App\Modules\Admin\Domain\Exception;

use Exception;

class EmailOrPasswordEmptyException extends Exception {
    public function __construct(string $message = "Email or password is empty in .env file", int $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}