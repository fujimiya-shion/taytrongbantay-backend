<?php

declare(strict_types=1);

namespace App\Modules\Admin\Domain\Model;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable('email', 'password', 'name')]
#[Hidden('password')]
class Admin extends Authenticatable implements FilamentUser {
    use HasRoles, SoftDeletes;

    protected string $guardName = "admin";

    public function casts(): array {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(\Filament\Panel $panel): bool {
        return true;
    }
}