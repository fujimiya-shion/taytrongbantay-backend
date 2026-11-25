<?php

declare(strict_types=1);

namespace App\Shared\Command;

/**
 * Input của use case (Application layer). Immutable, không phụ thuộc HTTP Request.
 */
abstract readonly class BaseCommand {}
