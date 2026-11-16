<?php

declare(strict_types=1);

namespace App\Shared\Security;

use Illuminate\Support\Facades\Hash;
use SensitiveParameter;

/**
 * Wrapper cho hasher để Application layer không gọi thẳng facade Hash.
 */
final class PasswordHasher
{
    private function __construct() {}

    public static function hash(#[SensitiveParameter] string $rawPassword): string
    {
        return Hash::make($rawPassword);
    }

    public static function matches(#[SensitiveParameter] string $rawPassword, ?string $hashedPassword): bool
    {
        return $hashedPassword !== null && $hashedPassword !== '' && Hash::check($rawPassword, $hashedPassword);
    }

    public static function isHash(?string $value): bool
    {
        return $value !== null && Hash::isHashed($value);
    }
}
